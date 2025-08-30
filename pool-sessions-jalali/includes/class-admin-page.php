<?php
namespace PoolSessionsJalali;

/**
 * Admin Page Management
 * 
 * Handles the admin menu, settings pages, and form processing
 */
class Admin_Page {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_pool_sessions_save_service', array($this, 'ajax_save_service'));
        add_action('wp_ajax_pool_sessions_delete_service', array($this, 'ajax_delete_service'));
        add_action('wp_ajax_pool_sessions_export_settings', array($this, 'ajax_export_settings'));
        add_action('wp_ajax_pool_sessions_import_settings', array($this, 'ajax_import_settings'));
    }
    
    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Pool Sessions', 'pool-sessions-jalali'),
            __('Pool Sessions', 'pool-sessions-jalali'),
            'manage_options',
            'pool-sessions-jalali',
            array($this, 'render_main_page'),
            'dashicons-calendar-alt',
            20
        );
        
        add_submenu_page(
            'pool-sessions-jalali',
            __('Settings', 'pool-sessions-jalali'),
            __('Settings', 'pool-sessions-jalali'),
            'manage_options',
            'pool-sessions-jalali-settings',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'pool-sessions-jalali',
            __('Services', 'pool-sessions-jalali'),
            __('Services', 'pool-sessions-jalali'),
            'manage_options',
            'pool-sessions-jalali-services',
            array($this, 'render_services_page')
        );
        
        add_submenu_page(
            'pool-sessions-jalali',
            __('Import/Export', 'pool-sessions-jalali'),
            __('Import/Export', 'pool-sessions-jalali'),
            'manage_options',
            'pool-sessions-jalali-import',
            array($this, 'render_import_page')
        );
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting('pool_sessions_jalali_options', 'pool_sessions_jalali_options', array(
            'sanitize_callback' => array($this, 'sanitize_options')
        ));
        
        // General settings section
        add_settings_section(
            'pool_sessions_general',
            __('General Settings', 'pool-sessions-jalali'),
            array($this, 'render_general_section'),
            'pool_sessions_jalali_options'
        );
        
        add_settings_field(
            'timezone',
            __('Display Timezone', 'pool-sessions-jalali'),
            array($this, 'render_timezone_field'),
            'pool_sessions_jalali_options',
            'pool_sessions_general'
        );
        
        add_settings_field(
            'week_start',
            __('Week Start Day', 'pool-sessions-jalali'),
            array($this, 'render_week_start_field'),
            'pool_sessions_jalali_options',
            'pool_sessions_general'
        );
        
        add_settings_field(
            'time_format',
            __('Time Format', 'pool-sessions-jalali'),
            array($this, 'render_time_format_field'),
            'pool_sessions_jalali_options',
            'pool_sessions_general'
        );
        
        add_settings_field(
            'show_tooltip',
            __('Show Tooltips', 'pool-sessions-jalali'),
            array($this, 'render_checkbox_field'),
            'pool_sessions_jalali_options',
            'pool_sessions_general',
            array('field' => 'show_tooltip')
        );
        
        add_settings_field(
            'show_calendar_header',
            __('Show Calendar Header', 'pool-sessions-jalali'),
            array($this, 'render_checkbox_field'),
            'pool_sessions_jalali_options',
            'pool_sessions_jalali_options',
            'pool_sessions_general',
            array('field' => 'show_calendar_header')
        );
        
        add_settings_field(
            'enable_mobile_gestures',
            __('Enable Mobile Gestures', 'pool-sessions-jalali'),
            array($this, 'render_checkbox_field'),
            'pool_sessions_jalali_options',
            'pool_sessions_general',
            array('field' => 'enable_mobile_gestures')
        );
        
        // Colors and appearance section
        add_settings_section(
            'pool_sessions_colors',
            __('Colors and Appearance', 'pool-sessions-jalali'),
            array($this, 'render_colors_section'),
            'pool_sessions_jalali_options'
        );
        
        $this->add_color_fields();
        
        // Services section
        add_settings_section(
            'pool_sessions_services',
            __('Services Management', 'pool-sessions-jalali'),
            array($this, 'render_services_section'),
            'pool_sessions_jalali_options'
        );
        
        // Import/Export section
        add_settings_section(
            'pool_sessions_import',
            __('Import/Export Settings', 'pool-sessions-jalali'),
            array($this, 'render_import_section'),
            'pool_sessions_jalali_options'
        );
        
        add_settings_field(
            'capability',
            __('Required Capability', 'pool-sessions-jalali'),
            array($this, 'render_capability_field'),
            'pool_sessions_jalali_options',
            'pool_sessions_import'
        );
    }
    
    /**
     * Add color fields dynamically
     */
    private function add_color_fields() {
        $color_fields = array(
            'male_bg' => __('Male Background', 'pool-sessions-jalali'),
            'male_bd' => __('Male Border', 'pool-sessions-jalali'),
            'male_tx' => __('Male Text', 'pool-sessions-jalali'),
            'female_bg' => __('Female Background', 'pool-sessions-jalali'),
            'female_bd' => __('Female Border', 'pool-sessions-jalali'),
            'female_tx' => __('Female Text', 'pool-sessions-jalali'),
        );
        
        foreach ($color_fields as $field => $label) {
            add_settings_field(
                $field,
                $label,
                array($this, 'render_color_field'),
                'pool_sessions_jalali_options',
                'pool_sessions_colors',
                array('field' => $field)
            );
        }
        
        // Dark mode colors
        $dark_color_fields = array(
            'dark_mode_male_bg' => __('Dark Mode - Male Background', 'pool-sessions-jalali'),
            'dark_mode_male_bd' => __('Dark Mode - Male Border', 'pool-sessions-jalali'),
            'dark_mode_male_tx' => __('Dark Mode - Male Text', 'pool-sessions-jalali'),
            'dark_mode_female_bg' => __('Dark Mode - Female Background', 'pool-sessions-jalali'),
            'dark_mode_female_bd' => __('Dark Mode - Female Border', 'pool-sessions-jalali'),
            'dark_mode_female_tx' => __('Dark Mode - Female Text', 'pool-sessions-jalali'),
        );
        
        foreach ($dark_color_fields as $field => $label) {
            add_settings_field(
                $field,
                $label,
                array($this, 'render_color_field'),
                'pool_sessions_jalali_options',
                'pool_sessions_colors',
                array('field' => $field)
            );
        }
    }
    
    /**
     * Render main admin page
     */
    public function render_main_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Pool Sessions (Jalali)', 'pool-sessions-jalali'); ?></h1>
            <p><?php _e('Manage your pool sessions with Jalali calendar support.', 'pool-sessions-jalali'); ?></p>
            
            <div class="pool-sessions-dashboard">
                <div class="pool-sessions-stats">
                    <h2><?php _e('Quick Statistics', 'pool-sessions-jalali'); ?></h2>
                    <?php $this->render_stats(); ?>
                </div>
                
                <div class="pool-sessions-shortcode">
                    <h2><?php _e('Shortcode Usage', 'pool-sessions-jalali'); ?></h2>
                    <p><?php _e('Use this shortcode to display the calendar on any page or post:', 'pool-sessions-jalali'); ?></p>
                    <code>[pool_calendar]</code>
                    
                    <h3><?php _e('Parameters:', 'pool-sessions-jalali'); ?></h3>
                    <ul>
                        <li><strong>gender</strong>: male, female, or all (default: all)</li>
                        <li><strong>service</strong>: specific service or * for all (default: *)</li>
                        <li><strong>initial_year</strong>: Jalali year to start with</li>
                        <li><strong>initial_month</strong>: Jalali month to start with</li>
                    </ul>
                    
                    <h3><?php _e('Examples:', 'pool-sessions-jalali'); ?></h3>
                    <ul>
                        <li><code>[pool_calendar gender="male"]</code> - Show only male sessions</li>
                        <li><code>[pool_calendar service="pool" gender="female"]</code> - Show female pool sessions</li>
                        <li><code>[pool_calendar initial_year="1404" initial_month="6"]</code> - Start with Shahrivar 1404</li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'pool-sessions-jalali'));
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Pool Sessions Settings', 'pool-sessions-jalali'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('pool_sessions_jalali_options');
                do_settings_sections('pool_sessions_jalali_options');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render services page
     */
    public function render_services_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'pool-sessions-jalali'));
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Services Management', 'pool-sessions-jalali'); ?></h1>
            
            <div class="pool-services-container">
                <div class="pool-services-form">
                    <h2><?php _e('Add/Edit Service', 'pool-sessions-jalali'); ?></h2>
                    <form id="pool-service-form">
                        <?php wp_nonce_field('pool_service_nonce', 'pool_service_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="service_name"><?php _e('Service Name', 'pool-sessions-jalali'); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="service_name" id="service_name" class="regular-text" required />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="service_slug"><?php _e('Service Slug', 'pool-sessions-jalali'); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="service_slug" id="service_slug" class="regular-text" required />
                                    <p class="description"><?php _e('URL-friendly version of the service name', 'pool-sessions-jalali'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="service_color"><?php _e('Service Color', 'pool-sessions-jalali'); ?></label>
                                </th>
                                <td>
                                    <input type="color" name="service_color" id="service_color" value="#0073aa" />
                                    <p class="description"><?php _e('Optional: Custom color for this service', 'pool-sessions-jalali'); ?></p>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php _e('Save Service', 'pool-sessions-jalali'); ?></button>
                        </p>
                    </form>
                </div>
                
                <div class="pool-services-list">
                    <h2><?php _e('Existing Services', 'pool-sessions-jalali'); ?></h2>
                    <div id="pool-services-list">
                        <?php $this->render_services_list(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render import page
     */
    public function render_import_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'pool-sessions-jalali'));
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Import/Export Data', 'pool-sessions-jalali'); ?></h1>
            
            <div class="pool-import-export-container">
                <div class="pool-import-section">
                    <h2><?php _e('Import Data', 'pool-sessions-jalali'); ?></h2>
                    
                    <h3><?php _e('Import CSV', 'pool-sessions-jalali'); ?></h3>
                    <form id="pool-csv-import-form" enctype="multipart/form-data">
                        <?php wp_nonce_field('pool_csv_import_nonce', 'pool_csv_import_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="csv_file"><?php _e('CSV File', 'pool-sessions-jalali'); ?></label>
                                </th>
                                <td>
                                    <input type="file" name="csv_file" id="csv_file" accept=".csv" required />
                                    <p class="description"><?php _e('Upload a CSV file with session data', 'pool-sessions-jalali'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="csv_mapping"><?php _e('Column Mapping', 'pool-sessions-jalali'); ?></label>
                                </th>
                                <td>
                                    <div id="csv-mapping-container">
                                        <p><?php _e('Column mapping will be detected automatically from the first row', 'pool-sessions-jalali'); ?></p>
                                    </div>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php _e('Import CSV', 'pool-sessions-jalali'); ?></button>
                        </p>
                    </form>
                    
                    <h3><?php _e('Import ICS', 'pool-sessions-jalali'); ?></h3>
                    <form id="pool-ics-import-form" enctype="multipart/form-data">
                        <?php wp_nonce_field('pool_ics_import_nonce', 'pool_ics_import_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="ics_file"><?php _e('ICS File', 'pool-sessions-jalali'); ?></label>
                                </th>
                                <td>
                                    <input type="file" name="ics_file" id="ics_file" accept=".ics" required />
                                    <p class="description"><?php _e('Upload an ICS file with calendar events', 'pool-sessions-jalali'); ?></p>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php _e('Import ICS', 'pool-sessions-jalali'); ?></button>
                        </p>
                    </form>
                </div>
                
                <div class="pool-export-section">
                    <h2><?php _e('Export Settings', 'pool-sessions-jalali'); ?></h2>
                    <p><?php _e('Export your current settings to a JSON file for backup or migration purposes.', 'pool-sessions-jalali'); ?></p>
                    <p class="submit">
                        <button type="button" id="export-settings" class="button button-secondary"><?php _e('Export Settings', 'pool-sessions-jalali'); ?></button>
                    </p>
                    
                    <h3><?php _e('Import Settings', 'pool-sessions-jalali'); ?></h3>
                    <form id="pool-settings-import-form" enctype="multipart/form-data">
                        <?php wp_nonce_field('pool_settings_import_nonce', 'pool_settings_import_nonce'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="settings_file"><?php _e('Settings File', 'pool-sessions-jalali'); ?></label>
                                </th>
                                <td>
                                    <input type="file" name="settings_file" id="settings_file" accept=".json" required />
                                    <p class="description"><?php _e('Upload a previously exported settings file', 'pool-sessions-jalali'); ?></p>
                                </td>
                            </tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-secondary"><?php _e('Import Settings', 'pool-sessions-jalali'); ?></button>
                        </p>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render general settings section
     */
    public function render_general_section() {
        echo '<p>' . __('Configure general calendar settings and display options.', 'pool-sessions-jalali') . '</p>';
    }
    
    /**
     * Render colors section
     */
    public function render_colors_section() {
        echo '<p>' . __('Customize the appearance of your calendar events.', 'pool-sessions-jalali') . '</p>';
    }
    
    /**
     * Render services section
     */
    public function render_services_section() {
        echo '<p>' . __('Manage your services and their custom colors.', 'pool-sessions-jalali') . '</p>';
    }
    
    /**
     * Render import section
     */
    public function render_import_section() {
        echo '<p>' . __('Configure import/export settings and permissions.', 'pool-sessions-jalali') . '</p>';
    }
    
    /**
     * Render timezone field
     */
    public function render_timezone_field() {
        $options = get_option('pool_sessions_jalali_options', array());
        $timezone = isset($options['timezone']) ? $options['timezone'] : 'Asia/Tehran';
        
        $timezones = timezone_identifiers_list();
        ?>
        <select name="pool_sessions_jalali_options[timezone]" id="timezone">
            <?php foreach ($timezones as $tz): ?>
                <option value="<?php echo esc_attr($tz); ?>" <?php selected($timezone, $tz); ?>>
                    <?php echo esc_html($tz); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    /**
     * Render week start field
     */
    public function render_week_start_field() {
        $options = get_option('pool_sessions_jalali_options', array());
        $week_start = isset($options['week_start']) ? $options['week_start'] : 'saturday';
        ?>
        <select name="pool_sessions_jalali_options[week_start]" id="week_start">
            <option value="saturday" <?php selected($week_start, 'saturday'); ?>><?php _e('Saturday', 'pool-sessions-jalali'); ?></option>
            <option value="sunday" <?php selected($week_start, 'sunday'); ?>><?php _e('Sunday', 'pool-sessions-jalali'); ?></option>
        </select>
        <?php
    }
    
    /**
     * Render time format field
     */
    public function render_time_format_field() {
        $options = get_option('pool_sessions_jalali_options', array());
        $time_format = isset($options['time_format']) ? $options['time_format'] : 'HH:mm';
        ?>
        <select name="pool_sessions_jalali_options[time_format]" id="time_format">
            <option value="HH:mm" <?php selected($time_format, 'HH:mm'); ?>><?php _e('24-hour (HH:mm)', 'pool-sessions-jalali'); ?></option>
            <option value="hh:mm A" <?php selected($time_format, 'hh:mm A'); ?>><?php _e('12-hour (hh:mm AM/PM)', 'pool-sessions-jalali'); ?></option>
        </select>
        <?php
    }
    
    /**
     * Render checkbox field
     */
    public function render_checkbox_field($args) {
        $options = get_option('pool_sessions_jalali_options', array());
        $field = $args['field'];
        $value = isset($options[$field]) ? $options[$field] : false;
        ?>
        <input type="checkbox" name="pool_sessions_jalali_options[<?php echo esc_attr($field); ?>]" id="<?php echo esc_attr($field); ?>" value="1" <?php checked($value, true); ?> />
        <label for="<?php echo esc_attr($field); ?>"><?php _e('Enable this option', 'pool-sessions-jalali'); ?></label>
        <?php
    }
    
    /**
     * Render color field
     */
    public function render_color_field($args) {
        $options = get_option('pool_sessions_jalali_options', array());
        $field = $args['field'];
        $value = isset($options[$field]) ? $options[$field] : '#000000';
        ?>
        <input type="color" name="pool_sessions_jalali_options[<?php echo esc_attr($field); ?>]" id="<?php echo esc_attr($field); ?>" value="<?php echo esc_attr($value); ?>" />
        <?php
    }
    
    /**
     * Render capability field
     */
    public function render_capability_field() {
        $options = get_option('pool_sessions_jalali_options', array());
        $capability = isset($options['capability']) ? $options['capability'] : 'manage_options';
        
        $capabilities = array(
            'manage_options' => __('Administrator', 'pool-sessions-jalali'),
            'edit_posts' => __('Editor', 'pool-sessions-jalali'),
            'publish_posts' => __('Author', 'pool-sessions-jalali'),
        );
        ?>
        <select name="pool_sessions_jalali_options[capability]" id="capability">
            <?php foreach ($capabilities as $cap => $label): ?>
                <option value="<?php echo esc_attr($cap); ?>" <?php selected($capability, $cap); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php _e('Minimum user capability required to import data', 'pool-sessions-jalali'); ?></p>
        <?php
    }
    
    /**
     * Sanitize options
     */
    public function sanitize_options($input) {
        $sanitized = array();
        
        // General settings
        $sanitized['timezone'] = sanitize_text_field($input['timezone']);
        $sanitized['week_start'] = in_array($input['week_start'], array('saturday', 'sunday')) ? $input['week_start'] : 'saturday';
        $sanitized['time_format'] = in_array($input['time_format'], array('HH:mm', 'hh:mm A')) ? $input['time_format'] : 'HH:mm';
        $sanitized['show_tooltip'] = isset($input['show_tooltip']);
        $sanitized['show_calendar_header'] = isset($input['show_calendar_header']);
        $sanitized['enable_mobile_gestures'] = isset($input['enable_mobile_gestures']);
        
        // Colors
        $color_fields = array('male_bg', 'male_bd', 'male_tx', 'female_bg', 'female_bd', 'female_tx');
        foreach ($color_fields as $field) {
            $sanitized[$field] = sanitize_hex_color($input[$field]);
        }
        
        // Dark mode colors
        $dark_color_fields = array('dark_mode_male_bg', 'dark_mode_male_bd', 'dark_mode_male_tx', 'dark_mode_female_bg', 'dark_mode_female_bd', 'dark_mode_female_tx');
        foreach ($dark_color_fields as $field) {
            $sanitized[$field] = sanitize_hex_color($input[$field]);
        }
        
        // Other fields
        $sanitized['font_size_title'] = sanitize_text_field($input['font_size_title']);
        $sanitized['font_size_event'] = sanitize_text_field($input['font_size_event']);
        $sanitized['border_radius'] = sanitize_text_field($input['border_radius']);
        $sanitized['spacing'] = sanitize_text_field($input['spacing']);
        $sanitized['custom_css'] = wp_kses_post($input['custom_css']);
        $sanitized['capability'] = sanitize_text_field($input['capability']);
        
        return $sanitized;
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
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
     * Render statistics
     */
    private function render_stats() {
        $total_sessions = wp_count_posts('pool_session')->publish;
        $male_sessions = get_posts(array(
            'post_type' => 'pool_session',
            'post_status' => 'publish',
            'meta_query' => array(
                array('key' => 'gender', 'value' => 'male')
            ),
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        $female_sessions = get_posts(array(
            'post_type' => 'pool_session',
            'post_status' => 'publish',
            'meta_query' => array(
                array('key' => 'gender', 'value' => 'female')
            ),
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        ?>
        <div class="pool-stats-grid">
            <div class="pool-stat-item">
                <span class="pool-stat-number"><?php echo $total_sessions; ?></span>
                <span class="pool-stat-label"><?php _e('Total Sessions', 'pool-sessions-jalali'); ?></span>
            </div>
            <div class="pool-stat-item">
                <span class="pool-stat-number"><?php echo count($male_sessions); ?></span>
                <span class="pool-stat-label"><?php _e('Male Sessions', 'pool-sessions-jalali'); ?></span>
            </div>
            <div class="pool-stat-item">
                <span class="pool-stat-number"><?php echo count($female_sessions); ?></span>
                <span class="pool-stat-label"><?php _e('Female Sessions', 'pool-sessions-jalali'); ?></span>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render services list
     */
    private function render_services_list() {
        global $wpdb;
        
        $services = $wpdb->get_col(
            "SELECT DISTINCT meta_value FROM {$wpdb->postmeta} 
             WHERE meta_key = 'service' AND meta_value != '' 
             ORDER BY meta_value ASC"
        );
        
        if (empty($services)) {
            echo '<p>' . __('No services found.', 'pool-sessions-jalali') . '</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . __('Service Name', 'pool-sessions-jalali') . '</th>';
        echo '<th>' . __('Actions', 'pool-sessions-jalali') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($services as $service) {
            echo '<tr>';
            echo '<td>' . esc_html($service) . '</td>';
            echo '<td>';
            echo '<button type="button" class="button button-small edit-service" data-service="' . esc_attr($service) . '">' . __('Edit', 'pool-sessions-jalali') . '</button> ';
            echo '<button type="button" class="button button-small button-link-delete delete-service" data-service="' . esc_attr($service) . '">' . __('Delete', 'pool-sessions-jalali') . '</button>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    /**
     * AJAX: Save service
     */
    public function ajax_save_service() {
        check_ajax_referer('pool_sessions_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'pool-sessions-jalali'));
        }
        
        $service_name = sanitize_text_field($_POST['service_name']);
        $service_slug = sanitize_title($_POST['service_slug']);
        $service_color = sanitize_hex_color($_POST['service_color']);
        
        if (empty($service_name) || empty($service_slug)) {
            wp_send_json_error(__('Service name and slug are required.', 'pool-sessions-jalali'));
        }
        
        // Save service color to options
        $options = get_option('pool_sessions_jalali_options', array());
        if (!isset($options['service_colors'])) {
            $options['service_colors'] = array();
        }
        $options['service_colors'][$service_name] = $service_color;
        update_option('pool_sessions_jalali_options', $options);
        
        wp_send_json_success(__('Service saved successfully.', 'pool-sessions-jalali'));
    }
    
    /**
     * AJAX: Delete service
     */
    public function ajax_delete_service() {
        check_ajax_referer('pool_sessions_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'pool-sessions-jalali'));
        }
        
        $service_name = sanitize_text_field($_POST['service_name']);
        
        if (empty($service_name)) {
            wp_send_json_error(__('Service name is required.', 'pool-sessions-jalali'));
        }
        
        // Remove service color from options
        $options = get_option('pool_sessions_jalali_options', array());
        if (isset($options['service_colors'][$service_name])) {
            unset($options['service_colors'][$service_name]);
            update_option('pool_sessions_jalali_options', $options);
        }
        
        wp_send_json_success(__('Service deleted successfully.', 'pool-sessions-jalali'));
    }
    
    /**
     * AJAX: Export settings
     */
    public function ajax_export_settings() {
        check_ajax_referer('pool_sessions_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'pool-sessions-jalali'));
        }
        
        $options = get_option('pool_sessions_jalali_options', array());
        $export_data = array(
            'version' => POOL_SESSIONS_JALALI_VERSION,
            'exported_at' => current_time('mysql'),
            'options' => $options
        );
        
        $filename = 'pool-sessions-jalali-settings-' . date('Y-m-d-H-i-s') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen(json_encode($export_data)));
        
        echo json_encode($export_data, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * AJAX: Import settings
     */
    public function ajax_import_settings() {
        check_ajax_referer('pool_sessions_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'pool-sessions-jalali'));
        }
        
        $files = $_FILES;
        
        if (empty($files['settings_file'])) {
            wp_send_json_error(__('No settings file provided.', 'pool-sessions-jalali'));
        }
        
        $file = $files['settings_file'];
        
        if ($file['type'] !== 'application/json') {
            wp_send_json_error(__('Invalid file type. Please upload a JSON file.', 'pool-sessions-jalali'));
        }
        
        $content = file_get_contents($file['tmp_name']);
        $import_data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(__('Invalid JSON file.', 'pool-sessions-jalali'));
        }
        
        if (!isset($import_data['options']) || !is_array($import_data['options'])) {
            wp_send_json_error(__('Invalid settings file format.', 'pool-sessions-jalali'));
        }
        
        update_option('pool_sessions_jalali_options', $import_data['options']);
        
        wp_send_json_success(__('Settings imported successfully.', 'pool-sessions-jalali'));
    }
}
