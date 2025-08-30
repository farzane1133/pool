<?php
namespace PoolSessionsJalali;

/**
 * Helper Functions
 * 
 * Utility functions for the Pool Sessions Jalali plugin
 */

/**
 * Get plugin option with default fallback
 * 
 * @param string $key Option key
 * @param mixed $default Default value
 * @return mixed Option value
 */
function get_plugin_option($key, $default = null) {
    $options = get_option('pool_sessions_jalali_options', array());
    return isset($options[$key]) ? $options[$key] : $default;
}

/**
 * Format time according to plugin settings
 * 
 * @param string $time Time string (HH:MM:SS)
 * @return string Formatted time
 */
function format_time($time) {
    $time_format = get_plugin_option('time_format', 'HH:mm');
    
    if ($time_format === 'hh:mm A') {
        return date('g:i A', strtotime($time));
    }
    
    return date('H:i', strtotime($time));
}

/**
 * Convert UTC datetime to local timezone
 * 
 * @param string $utc_datetime UTC datetime string
 * @param string $timezone Target timezone
 * @return string Local datetime string
 */
function utc_to_local($utc_datetime, $timezone = null) {
    if (!$timezone) {
        $timezone = get_plugin_option('timezone', 'Asia/Tehran');
    }
    
    $utc = new \DateTime($utc_datetime, new \DateTimeZone('UTC'));
    $local = $utc->setTimezone(new \DateTimeZone($timezone));
    
    return $local->format('Y-m-d H:i:s');
}

/**
 * Convert local datetime to UTC
 * 
 * @param string $local_datetime Local datetime string
 * @param string $timezone Source timezone
 * @return string UTC datetime string
 */
function local_to_utc($local_datetime, $timezone = null) {
    if (!$timezone) {
        $timezone = get_plugin_option('timezone', 'Asia/Tehran');
    }
    
    $local = new \DateTime($local_datetime, new \DateTimeZone($timezone));
    $utc = $local->setTimezone(new \DateTimeZone('UTC'));
    
    return $utc->format('Y-m-d H:i:s');
}

/**
 * Get Jalali month name
 * 
 * @param int $month Month number (1-12)
 * @return string Month name
 */
function get_jalali_month_name($month) {
    $jalali = new Jalali();
    $month_names = $jalali->get_month_names();
    
    return isset($month_names[$month]) ? $month_names[$month] : '';
}

/**
 * Get Jalali weekday name
 * 
 * @param int $weekday Weekday number (0-6, 0 = Saturday)
 * @return string Weekday name
 */
function get_jalali_weekday_name($weekday) {
    $jalali = new Jalali();
    $weekday_names = $jalali->get_weekday_names();
    
    return isset($weekday_names[$weekday]) ? $weekday_names[$weekday] : '';
}

/**
 * Check if current user can manage sessions
 * 
 * @return bool True if user has permission
 */
function can_manage_sessions() {
    $capability = get_plugin_option('capability', 'manage_options');
    return current_user_can($capability);
}

/**
 * Get service color
 * 
 * @param string $service Service name
 * @return string Color hex code
 */
function get_service_color($service) {
    $options = get_option('pool_sessions_jalali_options', array());
    
    if (isset($options['service_colors'][$service])) {
        return $options['service_colors'][$service];
    }
    
    // Default colors based on service type
    $default_colors = array(
        'pool' => '#0073aa',
        'swimming' => '#0073aa',
        'massage' => '#d63638',
        'sauna' => '#8c8f94',
        'steam' => '#8c8f94',
        'gym' => '#00a32a',
        'fitness' => '#00a32a',
        'yoga' => '#7c3aed',
        'pilates' => '#7c3aed',
        'spa' => '#d97706',
        'wellness' => '#d97706',
        'therapy' => '#dc2626'
    );
    
    $service_lower = strtolower($service);
    foreach ($default_colors as $key => $color) {
        if (strpos($service_lower, $key) !== false) {
            return $color;
        }
    }
    
    return '#0073aa'; // Default blue
}

/**
 * Sanitize Jalali date
 * 
 * @param string $date Jalali date string (YYYY-MM-DD)
 * @return string|false Sanitized date or false if invalid
 */
function sanitize_jalali_date($date) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }
    
    $parts = explode('-', $date);
    $year = (int)$parts[0];
    $month = (int)$parts[1];
    $day = (int)$parts[2];
    
    if ($month < 1 || $month > 12) {
        return false;
    }
    
    $jalali = new Jalali();
    if ($day < 1 || $day > $jalali->jalali_month_length($year, $month)) {
        return false;
    }
    
    return sprintf('%04d-%02d-%02d', $year, $month, $day);
}

/**
 * Validate Jalali date range
 * 
 * @param string $start_date Start date (YYYY-MM-DD)
 * @param string $end_date End date (YYYY-MM-DD)
 * @return bool True if valid range
 */
function validate_jalali_date_range($start_date, $end_date) {
    $start = sanitize_jalali_date($start_date);
    $end = sanitize_jalali_date($end_date);
    
    if (!$start || !$end) {
        return false;
    }
    
    return $start <= $end;
}

/**
 * Get current Jalali date
 * 
 * @return array Array with year, month, day
 */
function get_current_jalali_date() {
    $jalali = new Jalali();
    return $jalali->gregorian_to_jalali(date('Y'), date('n'), date('j'));
}

/**
 * Format Jalali date for display
 * 
 * @param string $date Jalali date string (YYYY-MM-DD)
 * @param string $format Display format
 * @return string Formatted date
 */
function format_jalali_date($date, $format = 'Y/m/d') {
    if (!$date) {
        return '';
    }
    
    $parts = explode('-', $date);
    if (count($parts) !== 3) {
        return $date;
    }
    
    $year = $parts[0];
    $month = $parts[1];
    $day = $parts[2];
    
    $formatted = str_replace(
        array('Y', 'm', 'd'),
        array($year, $month, $day),
        $format
    );
    
    return $formatted;
}

/**
 * Check if dark mode is enabled
 * 
 * @return bool True if dark mode
 */
function is_dark_mode() {
    // Check for system preference
    if (isset($_COOKIE['dark_mode'])) {
        return $_COOKIE['dark_mode'] === 'true';
    }
    
    // Check for user preference
    $user_id = get_current_user_id();
    if ($user_id) {
        $dark_mode = get_user_meta($user_id, 'pool_sessions_dark_mode', true);
        return $dark_mode === 'true';
    }
    
    return false;
}

/**
 * Get CSS class for gender
 * 
 * @param string $gender Gender value
 * @return string CSS class
 */
function get_gender_css_class($gender) {
    return 'fc-event ' . sanitize_html_class($gender);
}

/**
 * Get event title with service and time
 * 
 * @param string $service Service name
 * @param string $start_time Start time
 * @param string $end_time End time
 * @return string Event title
 */
function get_event_title($service, $start_time, $end_time) {
    $start = format_time($start_time);
    $end = format_time($end_time);
    
    return sprintf('%s %s–%s', $service, $start, $end);
}

/**
 * Log debug information
 * 
 * @param mixed $data Data to log
 * @param string $context Log context
 */
function debug_log($data, $context = 'pool-sessions-jalali') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        if (is_array($data) || is_object($data)) {
            error_log(sprintf('[%s] %s', $context, print_r($data, true)));
        } else {
            error_log(sprintf('[%s] %s', $context, $data));
        }
    }
}
