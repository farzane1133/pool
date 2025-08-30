<?php
namespace PoolSessionsJalali;

/**
 * ICS Importer for Pool Sessions
 * 
 * Handles ICS file import with VEVENT parsing and timezone conversion
 */
class ICS_Importer {
    
    private $jalali;
    
    public function __construct() {
        $this->jalali = new Jalali();
    }
    
    /**
     * Import sessions from ICS file
     * 
     * @param string $file_path Path to ICS file
     * @return array|WP_Error Import result or error
     */
    public function import($file_path) {
        if (!file_exists($file_path)) {
            return new \WP_Error('file_not_found', __('ICS file not found.', 'pool-sessions-jalali'));
        }
        
        // Read ICS file content
        $content = file_get_contents($file_path);
        if (!$content) {
            return new \WP_Error('file_read_error', __('Could not read ICS file.', 'pool-sessions-jalali'));
        }
        
        // Parse ICS content
        $events = $this->parse_ics_content($content);
        if (is_wp_error($events)) {
            return $events;
        }
        
        $imported = 0;
        $skipped = 0;
        $errors = array();
        
        // Process each event
        foreach ($events as $event) {
            $result = $this->process_event($event);
            
            if (is_wp_error($result)) {
                $errors[] = $result->get_error_message();
                $skipped++;
            } else {
                $imported++;
            }
        }
        
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
     * Parse ICS content and extract events
     * 
     * @param string $content ICS file content
     * @return array|WP_Error Array of events or error
     */
    private function parse_ics_content($content) {
        $events = array();
        $lines = explode("\n", $content);
        
        $current_event = null;
        $in_event = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines and comments
            if (empty($line) || $line[0] === '#') {
                continue;
            }
            
            // Check for event start
            if ($line === 'BEGIN:VEVENT') {
                $in_event = true;
                $current_event = array();
                continue;
            }
            
            // Check for event end
            if ($line === 'END:VEVENT') {
                if ($in_event && $current_event) {
                    $events[] = $current_event;
                }
                $in_event = false;
                $current_event = null;
                continue;
            }
            
            // Parse event properties
            if ($in_event && $current_event !== null) {
                $this->parse_event_property($line, $current_event);
            }
        }
        
        if (empty($events)) {
            return new \WP_Error('no_events', __('No events found in ICS file.', 'pool-sessions-jalali'));
        }
        
        return $events;
    }
    
    /**
     * Parse a single event property line
     * 
     * @param string $line Property line
     * @param array &$event Event array to populate
     */
    private function parse_event_property($line, &$event) {
        // Handle multi-line values
        if (preg_match('/^([A-Z-]+):(.+)$/', $line, $matches)) {
            $property = $matches[1];
            $value = $matches[2];
            
            // Handle common properties
            switch ($property) {
                case 'DTSTART':
                    $event['start'] = $this->parse_datetime($value);
                    break;
                    
                case 'DTEND':
                    $event['end'] = $this->parse_datetime($value);
                    break;
                    
                case 'SUMMARY':
                    $event['summary'] = $this->unescape_ics_text($value);
                    break;
                    
                case 'DESCRIPTION':
                    $event['description'] = $this->unescape_ics_text($value);
                    break;
                    
                case 'UID':
                    $event['uid'] = $value;
                    break;
                    
                case 'LOCATION':
                    $event['location'] = $this->unescape_ics_text($value);
                    break;
            }
        }
    }
    
    /**
     * Parse ICS datetime string
     * 
     * @param string $datetime_string ICS datetime string
     * @return string|false Parsed datetime or false on error
     */
    private function parse_datetime($datetime_string) {
        // Remove timezone info if present
        $datetime_string = preg_replace('/[A-Z]{3}$/', '', $datetime_string);
        
        // Handle different datetime formats
        if (preg_match('/^(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})Z?$/', $datetime_string, $matches)) {
            // Format: 20241201T120000Z or 20241201T120000
            $year = $matches[1];
            $month = $matches[2];
            $day = $matches[3];
            $hour = $matches[4];
            $minute = $matches[5];
            $second = $matches[6];
            
            return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);
        } elseif (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $datetime_string, $matches)) {
            // Format: 20241201 (date only)
            $year = $matches[1];
            $month = $matches[2];
            $day = $matches[3];
            
            return sprintf('%04d-%02d-%02d 00:00:00', $year, $month, $day);
        }
        
        return false;
    }
    
    /**
     * Unescape ICS text values
     * 
     * @param string $text ICS text value
     * @return string Unescaped text
     */
    private function unescape_ics_text($text) {
        // Handle common ICS escaping
        $text = str_replace('\\n', "\n", $text);
        $text = str_replace('\\N', "\n", $text);
        $text = str_replace('\\t', "\t", $text);
        $text = str_replace('\\T', "\t", $text);
        $text = str_replace('\\,', ',', $text);
        $text = str_replace('\\;', ';', $text);
        $text = str_replace('\\\\', '\\', $text);
        
        return $text;
    }
    
    /**
     * Process a single event
     * 
     * @param array $event Event data
     * @return true|WP_Error Success or error
     */
    private function process_event($event) {
        // Validate required fields
        if (empty($event['start']) || empty($event['end'])) {
            return new \WP_Error('missing_datetime', __('Event missing start or end time.', 'pool-sessions-jalali'));
        }
        
        if (empty($event['summary'])) {
            return new \WP_Error('missing_summary', __('Event missing summary/title.', 'pool-sessions-jalali'));
        }
        
        // Parse event data
        $session_data = $this->parse_event_data($event);
        if (is_wp_error($session_data)) {
            return $session_data;
        }
        
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
                return true;
            } else {
                return new \WP_Error('update_failed', __('Failed to update existing session.', 'pool-sessions-jalali'));
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
                return true;
            } else {
                return new \WP_Error('creation_failed', __('Failed to create new session.', 'pool-sessions-jalali'));
            }
        }
    }
    
    /**
     * Parse event data into session format
     * 
     * @param array $event Event data
     * @return array|WP_Error Session data or error
     */
    private function parse_event_data($event) {
        // Extract service and gender from summary
        $summary = $event['summary'];
        $service = $this->extract_service($summary);
        $gender = $this->extract_gender($summary);
        
        if (empty($service)) {
            return new \WP_Error('no_service', __('Could not determine service from event summary.', 'pool-sessions-jalali'));
        }
        
        if (empty($gender)) {
            return new \WP_Error('no_gender', __('Could not determine gender from event summary.', 'pool-sessions-jalali'));
        }
        
        // Parse start and end times
        $start_datetime = $event['start'];
        $end_datetime = $event['end'];
        
        // Convert to UTC if needed
        if (strpos($start_datetime, 'Z') !== false) {
            $start_datetime = str_replace('Z', '', $start_datetime);
        }
        if (strpos($end_datetime, 'Z') !== false) {
            $end_datetime = str_replace('Z', '', $end_datetime);
        }
        
        // Get optional fields
        $note = isset($event['description']) ? $event['description'] : '';
        $capacity = 0; // Default capacity
        
        // Generate title
        $title = $this->generate_title($service, $start_datetime, $end_datetime);
        
        return array(
            'service' => $service,
            'gender' => $gender,
            'start_datetime' => $start_datetime,
            'end_datetime' => $end_datetime,
            'note' => $note,
            'capacity' => $capacity,
            'title' => $title
        );
    }
    
    /**
     * Extract service from event summary
     * 
     * @param string $summary Event summary
     * @return string Service name
     */
    private function extract_service($summary) {
        // Common service keywords
        $service_keywords = array(
            'pool', 'swimming', 'massage', 'sauna', 'steam', 'gym', 'fitness',
            'yoga', 'pilates', 'spa', 'wellness', 'therapy'
        );
        
        $summary_lower = strtolower($summary);
        
        foreach ($service_keywords as $keyword) {
            if (strpos($summary_lower, $keyword) !== false) {
                return ucfirst($keyword);
            }
        }
        
        // If no specific service found, use first word of summary
        $words = explode(' ', trim($summary));
        return !empty($words[0]) ? $words[0] : 'Session';
    }
    
    /**
     * Extract gender from event summary
     * 
     * @param string $summary Event summary
     * @return string Gender
     */
    private function extract_gender($summary) {
        $summary_lower = strtolower($summary);
        
        // Check for gender indicators
        if (strpos($summary_lower, 'male') !== false || strpos($summary_lower, 'm') !== false) {
            return 'male';
        }
        
        if (strpos($summary_lower, 'female') !== false || strpos($summary_lower, 'f') !== false) {
            return 'female';
        }
        
        // Default to male if no gender specified
        return 'male';
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
     * @param string $start_datetime Start datetime
     * @param string $end_datetime End datetime
     * @return string Generated title
     */
    private function generate_title($service, $start_datetime, $end_datetime) {
        $start_time = date('H:i', strtotime($start_datetime));
        $end_time = date('H:i', strtotime($end_datetime));
        
        return sprintf('%s %s-%s', $service, $start_time, $end_time);
    }
}
