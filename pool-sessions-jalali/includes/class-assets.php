<?php
namespace PoolSessionsJalali;

/**
 * Assets Management
 * 
 * Handles enqueuing of CSS, JavaScript, and external libraries
 */
class Assets {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Only enqueue if shortcode is used
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'pool_calendar')) {
            $this->enqueue_calendar_assets();
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'pool-sessions-jalali') === false) {
            return;
        }
        
        wp_enqueue_script('pool-sessions-jalali-admin', POOL_SESSIONS_JALALI_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), POOL_SESSIONS_JALALI_VERSION, true);
        wp_enqueue_style('pool-sessions-jalali-admin', POOL_SESSIONS_JALALI_PLUGIN_URL . 'assets/css/admin.css', array(), POOL_SESSIONS_JALALI_VERSION);
        
        wp_localize_script('pool-sessions-jalali-admin', 'poolSessionsAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pool_sessions_nonce'),
        ));
    }
    
    /**
     * Enqueue calendar assets (FullCalendar, Day.js, Jalali)
     */
    private function enqueue_calendar_assets() {
        // Try to load from CDN first, fallback to local files
        $this->enqueue_fullcalendar_cdn();
        $this->enqueue_dayjs_cdn();
        $this->enqueue_jalali_cdn();
        
        // Enqueue plugin-specific assets
        wp_enqueue_script('pool-sessions-jalali-frontend', POOL_SESSIONS_JALALI_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), POOL_SESSIONS_JALALI_VERSION, true);
        wp_enqueue_style('pool-sessions-jalali-frontend', POOL_SESSIONS_JALALI_PLUGIN_URL . 'assets/css/frontend.css', array(), POOL_SESSIONS_JALALI_VERSION);
        
        // Localize script with plugin options and translations
        $options = get_option('pool_sessions_jalali_options', array());
        wp_localize_script('pool-sessions-jalali-frontend', 'poolSessionsData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('pool/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'options' => $options,
            'translations' => array(
                'loading' => __('Loading...', 'pool-sessions-jalali'),
                'noSessions' => __('No sessions found for this month.', 'pool-sessions-jalali'),
                'error' => __('Error loading sessions.', 'pool-sessions-jalali'),
                'all' => __('All', 'pool-sessions-jalali'),
                'male' => __('Male', 'pool-sessions-jalali'),
                'female' => __('Female', 'pool-sessions-jalali'),
                'allServices' => __('All Services', 'pool-sessions-jalali'),
                'prev' => __('Previous', 'pool-sessions-jalali'),
                'next' => __('Next', 'pool-sessions-jalali'),
                'today' => __('Today', 'pool-sessions-jalali'),
                'month' => __('Month', 'pool-sessions-jalali'),
                'week' => __('Week', 'pool-sessions-jalali'),
                'day' => __('Day', 'pool-sessions-jalali'),
                'list' => __('List', 'pool-sessions-jalali'),
            )
        ));
    }
    
    /**
     * Try to enqueue FullCalendar from CDN
     */
    private function enqueue_fullcalendar_cdn() {
        $cdn_urls = array(
            'https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js',
            'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.10/index.global.min.js',
            'https://unpkg.com/fullcalendar@6.1.10/index.global.min.js'
        );
        
        $enqueued = false;
        foreach ($cdn_urls as $cdn_url) {
            if ($this->test_cdn_availability($cdn_url)) {
                wp_enqueue_script('fullcalendar', $cdn_url, array(), '6.1.10', false);
                $enqueued = true;
                break;
            }
        }
        
        if (!$enqueued) {
            $this->enqueue_fullcalendar_local();
        }
    }
    
    /**
     * Try to enqueue Day.js from CDN
     */
    private function enqueue_dayjs_cdn() {
        $cdn_urls = array(
            'https://cdn.jsdelivr.net/npm/dayjs@1.11.10/dayjs.min.js',
            'https://cdnjs.cloudflare.com/ajax/libs/dayjs/1.11.10/dayjs.min.js',
            'https://unpkg.com/dayjs@1.11.10/dayjs.min.js'
        );
        
        $enqueued = false;
        foreach ($cdn_urls as $cdn_url) {
            if ($this->test_cdn_availability($cdn_url)) {
                wp_enqueue_script('dayjs', $cdn_url, array(), '1.11.10', false);
                $enqueued = true;
                break;
            }
        }
        
        if (!$enqueued) {
            $this->enqueue_dayjs_local();
        }
    }
    
    /**
     * Try to enqueue Jalali plugin from CDN
     */
    private function enqueue_jalali_cdn() {
        $cdn_urls = array(
            'https://cdn.jsdelivr.net/npm/dayjs@1.11.10/plugin/calendar.min.js',
            'https://cdnjs.cloudflare.com/ajax/libs/dayjs/1.11.10/plugin/calendar.min.js',
            'https://unpkg.com/dayjs@1.11.10/plugin/calendar.min.js'
        );
        
        $enqueued = false;
        foreach ($cdn_urls as $cdn_urls) {
            if ($this->test_cdn_availability($cdn_urls)) {
                wp_enqueue_script('dayjs-calendar', $cdn_urls, array('dayjs'), '1.11.10', false);
                $enqueued = true;
                break;
            }
        }
        
        if (!$enqueued) {
            $this->enqueue_jalali_local();
        }
    }
    
    /**
     * Test CDN availability
     * 
     * @param string $url CDN URL
     * @return bool True if available
     */
    private function test_cdn_availability($url) {
        $response = wp_remote_head($url, array('timeout' => 5));
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
    
    /**
     * Enqueue local FullCalendar files
     */
    private function enqueue_fullcalendar_local() {
        $local_path = POOL_SESSIONS_JALALI_PLUGIN_DIR . 'assets/lib/fullcalendar/';
        $local_url = POOL_SESSIONS_JALALI_PLUGIN_URL . 'assets/lib/fullcalendar/';
        
        if (file_exists($local_path . 'index.global.min.js')) {
            wp_enqueue_script('fullcalendar', $local_url . 'index.global.min.js', array(), '6.1.10', false);
        } else {
            // Fallback to basic calendar functionality
            wp_enqueue_script('pool-sessions-jalali-basic-calendar', POOL_SESSIONS_JALALI_PLUGIN_URL . 'assets/js/basic-calendar.js', array('jquery'), POOL_SESSIONS_JALALI_VERSION, true);
        }
    }
    
    /**
     * Enqueue local Day.js files
     */
    private function enqueue_dayjs_local() {
        $local_path = POOL_SESSIONS_JALALI_PLUGIN_DIR . 'assets/lib/dayjs/';
        $local_url = POOL_SESSIONS_JALALI_PLUGIN_URL . 'assets/lib/dayjs/';
        
        if (file_exists($local_path . 'dayjs.min.js')) {
            wp_enqueue_script('dayjs', $local_url . 'dayjs.min.js', array(), '1.11.10', false);
        }
    }
    
    /**
     * Enqueue local Jalali plugin files
     */
    private function enqueue_jalali_local() {
        $local_path = POOL_SESSIONS_JALALI_PLUGIN_DIR . 'assets/lib/dayjs/';
        $local_url = POOL_SESSIONS_JALALI_PLUGIN_URL . 'assets/lib/dayjs/';
        
        if (file_exists($local_path . 'plugin/calendar.min.js')) {
            wp_enqueue_script('dayjs-calendar', $local_url . 'plugin/calendar.min.js', array('dayjs'), '1.11.10', false);
        }
    }
    
    /**
     * Get dynamic CSS variables based on plugin options
     * 
     * @return string CSS variables
     */
    public function get_dynamic_css() {
        $options = get_option('pool_sessions_jalali_options', array());
        
        $css_vars = array(
            '--male-bg: ' . ($options['male_bg'] ?? '#e6f3ff'),
            '--male-bd: ' . ($options['male_bd'] ?? '#b3ddff'),
            '--male-tx: ' . ($options['male_tx'] ?? '#174766'),
            '--female-bg: ' . ($options['female_bg'] ?? '#ffe6f0'),
            '--female-bd: ' . ($options['female_bd'] ?? '#ffc2d6'),
            '--female-tx: ' . ($options['female_tx'] ?? '#6b1e3a'),
            '--font-size-title: ' . ($options['font_size_title'] ?? '16px'),
            '--font-size-event: ' . ($options['font_size_event'] ?? '14px'),
            '--border-radius: ' . ($options['border_radius'] ?? '8px'),
            '--spacing: ' . ($options['spacing'] ?? '12px'),
        );
        
        // Dark mode variables
        if (isset($options['dark_mode_male_bg'])) {
            $css_vars[] = '--dark-mode-male-bg: ' . $options['dark_mode_male_bg'];
            $css_vars[] = '--dark-mode-male-bd: ' . $options['dark_mode_male_bd'];
            $css_vars[] = '--dark-mode-male-tx: ' . $options['dark_mode_male_tx'];
            $css_vars[] = '--dark-mode-female-bg: ' . $options['dark_mode_female_bg'];
            $css_vars[] = '--dark-mode-female-bd: ' . $options['dark_mode_female_bd'];
            $css_vars[] = '--dark-mode-female-tx: ' . $options['dark_mode_female_tx'];
        }
        
        $css = ':root {' . "\n";
        $css .= '    ' . implode(";\n    ", $css_vars) . ";\n";
        $css .= '}' . "\n\n";
        
        // Add custom CSS if provided
        if (!empty($options['custom_css'])) {
            $css .= $options['custom_css'] . "\n";
        }
        
        return $css;
    }
    
    /**
     * Output dynamic CSS in head
     */
    public function output_dynamic_css() {
        if (has_shortcode(get_the_content(), 'pool_calendar')) {
            echo '<style id="pool-sessions-jalali-dynamic-css">' . "\n";
            echo $this->get_dynamic_css();
            echo '</style>' . "\n";
        }
    }
}
