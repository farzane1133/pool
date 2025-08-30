<?php
namespace PoolSessionsJalali;

/**
 * REST API endpoints for Pool Sessions
 * 
 * Provides API endpoints for sessions, services, and import functionality
 */
class REST_API {
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route('pool/v1', '/sessions', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_sessions'),
                'permission_callback' => '__return_true',
                'args' => array(
                    'year' => array(
                        'required' => true,
                        'type' => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                    'month' => array(
                        'required' => true,
                        'type' => 'integer',
                        'sanitize_callback' => 'absint',
                        'validate_callback' => function($param) {
                            return $param >= 1 && $param <= 12;
                        },
                    ),
                    'gender' => array(
                        'required' => false,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => function($param) {
                            return in_array($param, array('male', 'female', 'all'));
                        },
                        'default' => 'all',
                    ),
                    'service' => array(
                        'required' => false,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'default' => '*',
                    ),
                ),
            ),
        ));
        
        register_rest_route('pool/v1', '/services', array(
            array(
                'methods' => 'GET',
                'callback' => array($this, 'get_services'),
                'permission_callback' => '__return_true',
            ),
        ));
        
        register_rest_route('pool/v1', '/import/csv', array(
            array(
                'methods' => 'POST',
                'callback' => array($this, 'import_csv'),
                'permission_callback' => array($this, 'check_import_permissions'),
            ),
        ));
        
        register_rest_route('pool/v1', '/import/ics', array(
            array(
                'methods' => 'POST',
                'callback' => array($this, 'import_ics'),
                'permission_callback' => array($this, 'check_import_permissions'),
            ),
        ));
    }
    
    /**
     * Get sessions for a specific month and filters
     */
    public function get_sessions($request) {
        $year = $request->get_param('year');
        $month = $request->get_param('month');
        $gender = $request->get_param('gender');
        $service = $request->get_param('service');
        
        // Check cache first
        $cache_key = "pool_sessions_{$year}_{$month}_{$gender}_{$service}";
        $cached_sessions = get_transient($cache_key);
        
        if ($cached_sessions !== false) {
            return rest_ensure_response($cached_sessions);
        }
        
        // Convert Jalali month to Gregorian date range
        $jalali = new Jalali();
        $start_date = $jalali->jalali_to_gregorian($year, $month, 1);
        $end_date = $jalali->jalali_to_gregorian($year, $month, $jalali->jalali_month_length($year, $month));
        
        // Build query args
        $args = array(
            'post_type' => 'pool_session',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => 'start_datetime',
                    'value' => array(
                        date('Y-m-d H:i:s', mktime(0, 0, 0, $start_date['month'], $start_date['day'], $start_date['year'])),
                        date('Y-m-d H:i:s', mktime(23, 59, 59, $end_date['month'], $end_date['day'], $end_date['year']))
                    ),
                    'compare' => 'BETWEEN',
                    'type' => 'DATETIME'
                )
            )
        );
        
        // Add gender filter
        if ($gender !== 'all') {
            $args['meta_query'][] = array(
                'key' => 'gender',
                'value' => $gender,
                'compare' => '='
            );
        }
        
        // Add service filter
        if ($service !== '*') {
            $args['meta_query'][] = array(
                'key' => 'service',
                'value' => $service,
                'compare' => '='
            );
        }
        
        $query = new \WP_Query($args);
        $sessions = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $sessions[] = array(
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'service' => get_post_meta($post_id, 'service', true),
                    'gender' => get_post_meta($post_id, 'gender', true),
                    'start' => get_post_meta($post_id, 'start_datetime', true),
                    'end' => get_post_meta($post_id, 'end_datetime', true),
                    'note' => get_post_meta($post_id, 'note', true),
                    'capacity' => get_post_meta($post_id, 'capacity', true),
                );
            }
        }
        
        wp_reset_postdata();
        
        // Cache the results for 1 hour
        set_transient($cache_key, $sessions, HOUR_IN_SECONDS);
        
        return rest_ensure_response($sessions);
    }
    
    /**
     * Get all available services
     */
    public function get_services($request) {
        global $wpdb;
        
        $services = $wpdb->get_col(
            "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} 
             WHERE meta_key = 'service' AND meta_value != '' 
             ORDER BY meta_value ASC"
        );
        
        $services_with_colors = array();
        $options = get_option('pool_sessions_jalali_options', array());
        
        foreach ($services as $service) {
            $service_data = array(
                'name' => $service,
                'slug' => sanitize_title($service),
            );
            
            // Check if service has custom color
            if (isset($options['service_colors'][$service])) {
                $service_data['color'] = $options['service_colors'][$service];
            }
            
            $services_with_colors[] = $service_data;
        }
        
        return rest_ensure_response($services_with_colors);
    }
    
    /**
     * Import CSV data
     */
    public function import_csv($request) {
        $files = $request->get_file_params();
        
        if (empty($files['csv_file'])) {
            return new \WP_Error('no_file', __('No CSV file provided', 'pool-sessions-jalali'), array('status' => 400));
        }
        
        $file = $files['csv_file'];
        
        // Validate file type
        if ($file['type'] !== 'text/csv' && $file['type'] !== 'application/csv') {
            return new \WP_Error('invalid_file_type', __('Invalid file type. Please upload a CSV file.', 'pool-sessions-jalali'), array('status' => 400));
        }
        
        // Process CSV
        $importer = new CSV_Importer();
        $result = $importer->import($file['tmp_name']);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => sprintf(__('Successfully imported %d sessions', 'pool-sessions-jalali'), $result['imported']),
            'imported' => $result['imported'],
            'skipped' => $result['skipped'],
        ));
    }
    
    /**
     * Import ICS data
     */
    public function import_ics($request) {
        $files = $request->get_file_params();
        
        if (empty($files['ics_file'])) {
            return new \WP_Error('no_file', __('No ICS file provided', 'pool-sessions-jalali'), array('status' => 400));
        }
        
        $file = $files['ics_file'];
        
        // Validate file type
        if ($file['type'] !== 'text/calendar' && $file['type'] !== 'application/ics') {
            return new \WP_Error('invalid_file_type', __('Invalid file type. Please upload an ICS file.', 'pool-sessions-jalali'), array('status' => 400));
        }
        
        // Process ICS
        $importer = new ICS_Importer();
        $result = $importer->import($file['tmp_name']);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => sprintf(__('Successfully imported %d sessions', 'pool-sessions-jalali'), $result['imported']),
            'imported' => $result['imported'],
            'skipped' => $result['skipped'],
        ));
    }
    
    /**
     * Check if user has permission to import
     */
    public function check_import_permissions() {
        $options = get_option('pool_sessions_jalali_options', array());
        $capability = isset($options['capability']) ? $options['capability'] : 'manage_options';
        
        return current_user_can($capability);
    }
}
