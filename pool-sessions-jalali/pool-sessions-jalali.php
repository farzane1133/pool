<?php
/**
 * Plugin Name: Pool Sessions (Jalali)
 * Plugin URI: https://github.com/hoseinmos/pool-sessions-jalali
 * Description: افزونه حرفه‌ای مدیریت سانس‌های استخر با تقویم شمسی، فیلتر جنسیت و سرویس، و ایمپورت CSV/ICS
 * Version: 1.0.0
 * Author: hoseinmos
 * Author URI: https://github.com/hoseinmos
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: pool-sessions-jalali
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 8.0
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('POOL_SESSIONS_JALALI_VERSION', '1.0.0');
define('POOL_SESSIONS_JALALI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('POOL_SESSIONS_JALALI_PLUGIN_URL', plugin_dir_url(__FILE__));
define('POOL_SESSIONS_JALALI_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader for plugin classes
spl_autoload_register(function ($class) {
    $prefix = 'PoolSessionsJalali\\';
    $base_dir = POOL_SESSIONS_JALALI_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . 'class-' . str_replace('\\', '/', strtolower(str_replace('_', '-', $relative_class))) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Initialize plugin
function pool_sessions_jalali_init() {
    // Load text domain
    load_plugin_textdomain('pool-sessions-jalali', false, dirname(POOL_SESSIONS_JALALI_PLUGIN_BASENAME) . '/languages');
    
    // Initialize core classes
    new PoolSessionsJalali\Post_Type();
    new PoolSessionsJalali\REST_API();
    new PoolSessionsJalali\Admin_Page();
    new PoolSessionsJalali\Assets();
}
add_action('plugins_loaded', 'pool_sessions_jalali_init');

// Activation hook
register_activation_hook(__FILE__, 'pool_sessions_jalali_activate');
function pool_sessions_jalali_activate() {
    // Create custom post type
    $post_type = new PoolSessionsJalali\Post_Type();
    $post_type->register_post_type();
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Set default options
    $default_options = array(
        'timezone' => 'Asia/Tehran',
        'week_start' => 'saturday',
        'time_format' => 'HH:mm',
        'show_tooltip' => true,
        'show_calendar_header' => true,
        'enable_mobile_gestures' => true,
        'male_bg' => '#e6f3ff',
        'male_bd' => '#b3ddff',
        'male_tx' => '#174766',
        'female_bg' => '#ffe6f0',
        'female_bd' => '#ffc2d6',
        'female_tx' => '#6b1e3a',
        'dark_mode_male_bg' => '#1a3a4a',
        'dark_mode_male_bd' => '#2d5a6a',
        'dark_mode_male_tx' => '#e6f3ff',
        'dark_mode_female_bg' => '#4a1a3a',
        'dark_mode_female_bd' => '#6a2d5a',
        'dark_mode_female_tx' => '#ffe6f0',
        'font_size_title' => '16px',
        'font_size_event' => '14px',
        'border_radius' => '8px',
        'spacing' => '12px',
        'custom_css' => '',
        'capability' => 'manage_options'
    );
    
    add_option('pool_sessions_jalali_options', $default_options);
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'pool_sessions_jalali_deactivate');
function pool_sessions_jalali_deactivate() {
    flush_rewrite_rules();
}

// Shortcode for frontend calendar
add_shortcode('pool_calendar', 'pool_sessions_jalali_calendar_shortcode');
function pool_sessions_jalali_calendar_shortcode($atts) {
    $atts = shortcode_atts(array(
        'gender' => 'all',
        'service' => '*',
        'initial_year' => '',
        'initial_month' => ''
    ), $atts, 'pool_calendar');
    
    // Enqueue required assets
    wp_enqueue_script('pool-sessions-jalali-frontend');
    wp_enqueue_style('pool-sessions-jalali-frontend');
    
    // Get current Jalali date if not specified
    if (empty($atts['initial_year']) || empty($atts['initial_month'])) {
        $jalali = new PoolSessionsJalali\Jalali();
        $current_jalali = $jalali->gregorian_to_jalali(date('Y'), date('n'), date('j'));
        $atts['initial_year'] = $current_jalali['year'];
        $atts['initial_month'] = $current_jalali['month'];
    }
    
    ob_start();
    ?>
    <div id="pool-calendar-container" 
         data-gender="<?php echo esc_attr($atts['gender']); ?>"
         data-service="<?php echo esc_attr($atts['service']); ?>"
         data-year="<?php echo esc_attr($atts['initial_year']); ?>"
         data-month="<?php echo esc_attr($atts['initial_month']); ?>">
        <div class="pool-calendar-controls">
            <div class="gender-toggle">
                <button type="button" class="gender-btn active" data-gender="all">هردو</button>
                <button type="button" class="gender-btn" data-gender="male">آقا</button>
                <button type="button" class="gender-btn" data-gender="female">خانم</button>
                </div>
            <div class="service-selector">
                <select id="service-filter">
                    <option value="*">همه سرویس‌ها</option>
                </select>
            </div>
            <div class="date-selector">
                <select id="month-selector">
                    <option value="1">فروردین</option>
                    <option value="2">اردیبهشت</option>
                    <option value="3">خرداد</option>
                    <option value="4">تیر</option>
                    <option value="5">مرداد</option>
                    <option value="6">شهریور</option>
                    <option value="7">مهر</option>
                    <option value="8">آبان</option>
                    <option value="9">آذر</option>
                    <option value="10">دی</option>
                    <option value="11">بهمن</option>
                    <option value="12">اسفند</option>
                </select>
                <select id="year-selector">
                    <?php for ($year = 1400; $year <= 1410; $year++): ?>
                        <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
        <div id="pool-calendar"></div>
    </div>
    <?php
    return ob_get_clean();
}
