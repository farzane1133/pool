<?php
namespace PoolSessionsJalali;

/**
 * CSV Importer for Pool Sessions
 * 
 * Handles CSV file import with batch processing and validation
 */
class CSV_Importer {
    
    private $batch_size = 100;
    private $jalali;
    
    public function __construct() {
        $this->jalali = new Jalali();
    }
    
    /**
     * Import sessions from CSV file
     * 
     * @param string $file_path Path to CSV file
     * @return array|WP_Error Import result or error
     */
    public function import($file_path) {
        if (!file_exists($file_path)) {
            return new \WP_Error('file_not_found', __('CSV file not found.', 'pool-sessions-jalali'));
        }
        
        // Read CSV file
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return new \WP_Error('file_read_error', __('Could not read CSV file.', 'pool-sessions-jalali'));
        }
        
        // Read header row
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return new \WP_Error('invalid_csv', __('Invalid CSV format or empty file.', 'pool-sessions-jalali'));
        }
        
        // Validate required columns
        $required_columns = array('service', 'gender', 'date', 'start', 'end');
        $missing_columns = array_diff($required_columns, $headers);
        
        if (!empty($missing_columns)) {
            fclose($handle);
            return new \WP_Error('missing_columns', sprintf(__('Missing required columns: %s', 'pool-sessions-jalali'), implode(', ', $missing_columns)));
        }
        
        // Get column indices
        $column_map = array_flip($headers);
        
        $imported = 0;
        $skipped = 0;
        $errors = array();
        $batch = array();
        
        // Process rows
        $row_number = 1; // Start from 1 (header is row 0)
        while (($row = fgetcsv($handle)) !== false) {
            $row_number++;
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            // Validate row data
            $validation_result = $this->validate_row($row, $column_map, $row_number);
            if (is_wp_error($validation_result)) {
                $errors[] = sprintf(__('Row %d: %s', 'pool-sessions-jalali'), $row_number, $validation_result->get_error_message());
                $skipped++;
                continue;
            }
            
            // Process row
            $session_data = $this->process_row($row, $column_map);
            if (is_wp_error($session_data)) {
                $errors[] = sprintf(__('Row %d: %s', 'pool-sessions-jalali'), $row_number, $session_data->get_error_message());
                $skipped++;
                continue;
            }
            
            $batch[] = $session_data;
            
            // Process batch when it reaches the batch size
            if (count($batch) >= $this->batch_size) {
                $batch_result = $this->process_batch($batch);
                $imported += $batch_result['imported'];
                $skipped += $batch_result['skipped'];
                $batch = array();
            }
        }
        
        // Process remaining batch
        if (!empty($batch)) {
            $batch_result = $this->process_batch($batch);
            $imported += $batch_result['imported'];
            $skipped += $batch_result['skipped'];
        }
        
        fclose($handle);
        
        // Return result
        $result = array(
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        );
        
        if (!empty($errors)) {
            $result['error_message'] = sprintf(__('Import completed with %d errors. %d sessions imported, %d skipped.', 'pool-sessions-jalali'), count($errors), $imported, $skipped);
        }
        
        return $result;
    }
    
    /**
     * Validate a single row
     * 
     * @param array $row Row data
     * @param array $column_map Column mapping
     * @param int $row_number Row number for error reporting
     * @return true|WP_Error Validation result
     */
    private function validate_row($row, $column_map, $row_number) {
        // Check if required columns have values
        $required_columns = array('service', 'gender', 'date', 'start', 'end');
        foreach ($required_columns as $column) {
            if (!isset($column_map[$column]) || empty(trim($row[$column_map[$column]]))) {
                return new \WP_Error('missing_value', sprintf(__('Missing value for required column: %s', 'pool-sessions-jalali'), $column));
            }
        }
        
        // Validate gender
        $gender = trim($row[$column_map['gender']]);
        if (!in_array(strtolower($gender), array('male', 'female', 'm', 'f'))) {
            return new \WP_Error('invalid_gender', sprintf(__('Invalid gender value: %s. Must be male/female or m/f', 'pool-sessions-jalali'), $gender));
        }
        
        // Validate date format (Jalali: YYYY-MM-DD)
        $date = trim($row[$column_map['date']]);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return new \WP_Error('invalid_date_format', sprintf(__('Invalid date format: %s. Expected format: YYYY-MM-DD (Jalali)', 'pool-sessions-jalali'), $date));
        }
        
        // Validate Jalali date
        $date_parts = explode('-', $date);
        $year = (int)$date_parts[0];
        $month = (int)$date_parts[1];
        $day = (int)$date_parts[2];
        
        if ($month < 1 || $month > 12) {
            return new \WP_Error('invalid_month', sprintf(__('Invalid Jalali month: %d. Must be between 1 and 12', 'pool-sessions-jalali'), $month));
        }
        
        if ($day < 1 || $day > $this->jalali->jalali_month_length($year, $month)) {
            return new \WP_Error('invalid_day', sprintf(__('Invalid Jalali day: %d for month %d', 'pool-sessions-jalali'), $day, $month));
        }
        
        // Validate time format (HH:MM)
        $start_time = trim($row[$column_map['start']]);
        $end_time = trim($row[$column_map['end']]);
        
        if (!preg_match('/^\d{2}:\d{2}$/', $start_time) || !preg_match('/^\d{2}:\d{2}$/', $end_time)) {
            return new \WP_Error('invalid_time_format', __('Invalid time format. Expected format: HH:MM (24-hour)', 'pool-sessions-jalali'));
        }
        
        // Validate time values
        $start_parts = explode(':', $start_time);
        $end_parts = explode(':', $end_time);
        
        if ($start_parts[0] < 0 || $start_parts[0] > 23 || $start_parts[1] < 0 || $start_parts[1] > 59) {
            return new \WP_Error('invalid_start_time', sprintf(__('Invalid start time: %s', 'pool-sessions-jalali'), $start_time));
        }
        
        if ($end_parts[0] < 0 || $end_parts[0] > 23 || $end_parts[1] > 59) {
            return new \WP_Error('invalid_end_time', sprintf(__('Invalid end time: %s', 'pool-sessions-jalali'), $end_time));
        }
        
        // Validate that end time is after start time
        $start_minutes = $start_parts[0] * 60 + $start_parts[1];
        $end_minutes = $end_parts[0] * 60 + $end_parts[1];
        
        if ($end_minutes <= $start_minutes) {
            return new \WP_Error('invalid_time_range', __('End time must be after start time', 'pool-sessions-jalali'));
        }
        
        return true;
    }
    
    /**
     * Process a single row into session data
     * 
     * @param array $row Row data
     * @param array $column_map Column mapping
     * @return array|WP_Error Session data or error
     */
    private function process_row($row, $column_map) {
        // Normalize gender
        $gender = strtolower(trim($row[$column_map['gender']]));
        if ($gender === 'm') $gender = 'male';
        if ($gender === 'f') $gender = 'female';
        
        // Parse Jalali date and time
        $jalali_date = trim($row[$column_map['date']]);
        $start_time = trim($row[$column_map['start']]);
        $end_time = trim($row[$column_map['end']]);
        
        $date_parts = explode('-', $jalali_date);
        $jalali_year = (int)$date_parts[0];
        $jalali_month = (int)$date_parts[1];
        $jalali_day = (int)$date_parts[2];
        
        // Convert Jalali to Gregorian
        $gregorian = $this->jalali->jalali_to_gregorian($jalali_year, $jalali_month, $jalali_day);
        
        // Create datetime strings
        $start_datetime = sprintf('%04d-%02d-%02d %s:00', $gregorian['year'], $gregorian['month'], $gregorian['day'], $start_time);
        $end_datetime = sprintf('%04d-%02d-%02d %s:00', $gregorian['year'], $gregorian['month'], $gregorian['day'], $end_time);
        
        // Convert to UTC (assuming local timezone is Asia/Tehran)
        $timezone = new \DateTimeZone('Asia/Tehran');
        $start_local = new \DateTime($start_datetime, $timezone);
        $end_local = new \DateTime($end_datetime, $timezone);
        
        $start_utc = $start_local->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $end_utc = $end_local->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        
        // Get optional fields
        $note = isset($column_map['note']) ? trim($row[$column_map['note']]) : '';
        $capacity = isset($column_map['capacity']) ? (int)trim($row[$column_map['capacity']]) : 0;
        
        return array(
            'service' => trim($row[$column_map['service']]),
            'gender' => $gender,
            'start_datetime' => $start_utc,
            'end_datetime' => $end_utc,
            'note' => $note,
            'capacity' => $capacity > 0 ? $capacity : 0,
            'title' => $this->generate_title(trim($row[$column_map['service']]), $start_time, $end_time)
        );
    }
    
    /**
     * Process a batch of sessions
     * 
     * @param array $batch Batch of session data
     * @return array Result with imported and skipped counts
     */
    private function process_batch($batch) {
        $imported = 0;
        $skipped = 0;
        
        foreach ($batch as $session_data) {
            // Check for existing session (idempotency)
            $existing = $this->find_existing_session($session_data);
            
            if ($existing) {
                // Update existing session
                $post_id = $existing;
                $updated = wp_update_post(array(
                    'ID' => $post_id,
                    'post_title' => $session_data['title'],
                    'post_type' => 'pool_session',
                    'post_status' => 'publish'
                ));
                
                if ($updated) {
                    update_post_meta($post_id, 'service', $session_data['service']);
                    update_post_meta($post_id, 'gender', $session_data['gender']);
                    update_post_meta($post_id, 'start_datetime', $session_data['start_datetime']);
                    update_post_meta($post_id, 'end_datetime', $session_data['end_datetime']);
                    update_post_meta($post_id, 'note', $session_data['note']);
                    update_post_meta($post_id, 'capacity', $session_data['capacity']);
                    $imported++;
                } else {
                    $skipped++;
                }
            } else {
                // Create new session
                $post_id = wp_insert_post(array(
                    'post_title' => $session_data['title'],
                    'post_type' => 'pool_session',
                    'post_status' => 'publish'
                ));
                
                if ($post_id && !is_wp_error($post_id)) {
                    update_post_meta($post_id, 'service', $session_data['service']);
                    update_post_meta($post_id, 'gender', $session_data['gender']);
                    update_post_meta($post_id, 'start_datetime', $session_data['start_datetime']);
                    update_post_meta($post_id, 'end_datetime', $session_data['end_datetime']);
                    update_post_meta($post_id, 'note', $session_data['note']);
                    update_post_meta($post_id, 'capacity', $session_data['capacity']);
                    $imported++;
                } else {
                    $skipped++;
                }
            }
        }
        
        return array(
            'imported' => $imported,
            'skipped' => $skipped
        );
    }
    
    /**
     * Find existing session for idempotency
     * 
     * @param array $session_data Session data
     * @return int|false Post ID if found, false otherwise
     */
    private function find_existing_session($session_data) {
        $args = array(
            'post_type' => 'pool_session',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_query' => array(
                array('key' => 'service', 'value' => $session_data['service']),
                array('key' => 'gender', 'value' => $session_data['gender']),
                array('key' => 'start_datetime', 'value' => $session_data['start_datetime']),
                array('key' => 'end_datetime', 'value' => $session_data['end_datetime'])
            )
        );
        
        $query = new \WP_Query($args);
        
        if ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            wp_reset_postdata();
            return $post_id;
        }
        
        return false;
    }
    
    /**
     * Generate session title
     * 
     * @param string $service Service name
     * @param string $start_time Start time
     * @param string $end_time End time
     * @return string Generated title
     */
    private function generate_title($service, $start_time, $end_time) {
        return sprintf('%s %s-%s', $service, $start_time, $end_time);
    }
    
    /**
     * Set batch size for processing
     * 
     * @param int $size Batch size
     */
    public function set_batch_size($size) {
        $this->batch_size = max(1, (int)$size);
    }
}
