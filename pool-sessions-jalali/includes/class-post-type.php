<?php
namespace PoolSessionsJalali;

/**
 * Custom Post Type for Pool Sessions
 * 
 * Registers the pool_session post type and handles meta fields
 */
class Post_Type {
    
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        add_action('init', array($this, 'register_meta_fields'));
    }
    
    /**
     * Register the pool_session custom post type
     */
    public function register_post_type() {
        $labels = array(
            'name' => __('Sessions', 'pool-sessions-jalali'),
            'singular_name' => __('Session', 'pool-sessions-jalali'),
            'menu_name' => __('Pool Sessions', 'pool-sessions-jalali'),
            'add_new' => __('Add New Session', 'pool-sessions-jalali'),
            'add_new_item' => __('Add New Session', 'pool-sessions-jalali'),
            'edit_item' => __('Edit Session', 'pool-sessions-jalali'),
            'new_item' => __('New Session', 'pool-sessions-jalali'),
            'view_item' => __('View Session', 'pool-sessions-jalali'),
            'search_items' => __('Search Sessions', 'pool-sessions-jalali'),
            'not_found' => __('No sessions found', 'pool-sessions-jalali'),
            'not_found_in_trash' => __('No sessions found in trash', 'pool-sessions-jalali'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'pool-sessions-jalali',
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => false,
            'supports' => array('title'),
            'menu_position' => 20,
            'show_in_rest' => true,
        );
        
        register_post_type('pool_session', $args);
    }
    
    /**
     * Register meta fields for the post type
     */
    public function register_meta_fields() {
        register_post_meta('pool_session', 'gender', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => array($this, 'sanitize_gender'),
        ));
        
        register_post_meta('pool_session', 'service', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_text_field',
        ));
        
        register_post_meta('pool_session', 'start_datetime', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => array($this, 'sanitize_datetime'),
        ));
        
        register_post_meta('pool_session', 'end_datetime', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => array($this, 'sanitize_datetime'),
        ));
        
        register_post_meta('pool_session', 'note', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_textarea_field',
        ));
        
        register_post_meta('pool_session', 'capacity', array(
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => true,
            'sanitize_callback' => 'absint',
        ));
    }
    
    /**
     * Add meta boxes to the post edit screen
     */
    public function add_meta_boxes() {
        add_meta_box(
            'pool_session_details',
            __('Session Details', 'pool-sessions-jalali'),
            array($this, 'render_meta_box'),
            'pool_session',
            'normal',
            'high'
        );
    }
    
    /**
     * Render the meta box content
     */
    public function render_meta_box($post) {
        wp_nonce_field('pool_session_meta_box', 'pool_session_meta_box_nonce');
        
        $gender = get_post_meta($post->ID, 'gender', true);
        $service = get_post_meta($post->ID, 'service', true);
        $start_datetime = get_post_meta($post->ID, 'start_datetime', true);
        $end_datetime = get_post_meta($post->ID, 'end_datetime', true);
        $note = get_post_meta($post->ID, 'note', true);
        $capacity = get_post_meta($post->ID, 'capacity', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="gender"><?php _e('Gender', 'pool-sessions-jalali'); ?></label>
                </th>
                <td>
                    <select name="gender" id="gender" required>
                        <option value=""><?php _e('Select Gender', 'pool-sessions-jalali'); ?></option>
                        <option value="male" <?php selected($gender, 'male'); ?>><?php _e('Male', 'pool-sessions-jalali'); ?></option>
                        <option value="female" <?php selected($gender, 'female'); ?>><?php _e('Female', 'pool-sessions-jalali'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="service"><?php _e('Service', 'pool-sessions-jalali'); ?></label>
                </th>
                <td>
                    <input type="text" name="service" id="service" value="<?php echo esc_attr($service); ?>" class="regular-text" required />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="start_datetime"><?php _e('Start Date & Time', 'pool-sessions-jalali'); ?></label>
                </th>
                <td>
                    <input type="datetime-local" name="start_datetime" id="start_datetime" value="<?php echo esc_attr($start_datetime); ?>" required />
                    <p class="description"><?php _e('Date and time in local timezone (will be converted to UTC)', 'pool-sessions-jalali'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="end_datetime"><?php _e('End Date & Time', 'pool-sessions-jalali'); ?></label>
                </th>
                <td>
                    <input type="datetime-local" name="end_datetime" id="end_datetime" value="<?php echo esc_attr($end_datetime); ?>" required />
                    <p class="description"><?php _e('Date and time in local timezone (will be converted to UTC)', 'pool-sessions-jalali'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="note"><?php _e('Note', 'pool-sessions-jalali'); ?></label>
                </th>
                <td>
                    <textarea name="note" id="note" rows="3" class="large-text"><?php echo esc_textarea($note); ?></textarea>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="capacity"><?php _e('Capacity', 'pool-sessions-jalali'); ?></label>
                </th>
                <td>
                    <input type="number" name="capacity" id="capacity" value="<?php echo esc_attr($capacity); ?>" min="1" />
                    <p class="description"><?php _e('Optional: Maximum number of participants', 'pool-sessions-jalali'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save meta box data
     */
    public function save_meta_box($post_id) {
        // Check nonce
        if (!isset($_POST['pool_session_meta_box_nonce']) || !wp_verify_nonce($_POST['pool_session_meta_box_nonce'], 'pool_session_meta_box')) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save meta fields
        if (isset($_POST['gender'])) {
            update_post_meta($post_id, 'gender', sanitize_text_field($_POST['gender']));
        }
        
        if (isset($_POST['service'])) {
            update_post_meta($post_id, 'service', sanitize_text_field($_POST['service']));
        }
        
        if (isset($_POST['start_datetime'])) {
            update_post_meta($post_id, 'start_datetime', sanitize_text_field($_POST['start_datetime']));
        }
        
        if (isset($_POST['end_datetime'])) {
            update_post_meta($post_id, 'end_datetime', sanitize_text_field($_POST['end_datetime']));
        }
        
        if (isset($_POST['note'])) {
            update_post_meta($post_id, 'note', sanitize_textarea_field($_POST['note']));
        }
        
        if (isset($_POST['capacity'])) {
            update_post_meta($post_id, 'capacity', absint($_POST['capacity']));
        }
    }
    
    /**
     * Sanitize gender field
     */
    public function sanitize_gender($value) {
        $allowed_values = array('male', 'female');
        return in_array($value, $allowed_values) ? $value : '';
    }
    
    /**
     * Sanitize datetime field
     */
    public function sanitize_datetime($value) {
        if (empty($value)) {
            return '';
        }
        
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return '';
        }
        
        return date('Y-m-d H:i:s', $timestamp);
    }
}
