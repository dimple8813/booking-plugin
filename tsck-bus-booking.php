<?php
/*
Plugin Name: TSCK Bus Booking
Description: Booking system for schools and companies.
Version: 10.14.1
Author: Your Name
*/

if (!defined('ABSPATH')) exit;

// Include necessary plugin files
add_action('plugins_loaded', function () {
    require_once plugin_dir_path(__FILE__) . 'includes/booking-functions.php';
    require_once plugin_dir_path(__FILE__) . 'includes/email-functions.php';
    require_once plugin_dir_path(__FILE__) . 'admin/admin-dashboard.php';
    require_once plugin_dir_path(__FILE__) . 'admin/event-dashboard.php';
    require_once plugin_dir_path(__FILE__) . 'admin/form-fields-dashboard.php';
});

// Enqueue admin styles/scripts (optional – only if needed)

// Enqueue frontend scripts
add_action('wp_enqueue_scripts', function () {
    global $wpdb;

    $event_table = $wpdb->prefix . 'tsck_events';
    $results = $wpdb->get_col("SELECT event_date FROM $event_table WHERE status = 'active'");
    $pendingResults = $wpdb->get_col("SELECT event_date FROM $event_table WHERE status = 'inactive'");

    $submissionstable = $wpdb->prefix . 'tsck_form_submissions';

    $submissions = $wpdb->get_results("SELECT * FROM $submissionstable ORDER BY submitted_at DESC");
   // echo "SELECT * FROM submissionstable ORDER BY submitted_at DESC";
 
     $booked_dates = [];
    foreach ($submissions as $s) {
        $data = json_decode($s->form_data, true);
        if (!empty($data['booking_date'])) {
            $date = date('Y-m-d', strtotime($data['booking_date']));
            $booked_dates[] = $date;
        }
    }

   $ppevent_date = array_map(function ($date) {
        return date('Y-m-d', strtotime($date));
    }, $pendingResults);

    $event_dates = array_map(function ($date) {
        return date('Y-m-d', strtotime($date));
    }, $results);

    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui-style', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');

    wp_enqueue_script('tsck-booking-datepicker', plugins_url('/assets/booking-datepicker.js', __FILE__), ['jquery', 'jquery-ui-datepicker'], null, true);
    wp_localize_script('tsck-booking-datepicker', 'tsckBookingData', [
        'event_dates' => $event_dates,
        'booked_dates' => $booked_dates,
        "ppevent_date" => $ppevent_date,
    ]);

    wp_enqueue_style('tsck-style', plugin_dir_url(__FILE__) . 'public/assets/style.css');
    wp_enqueue_script('tsck-script', plugin_dir_url(__FILE__) . 'public/assets/script.js', ['jquery'], null, true);
    wp_localize_script('tsck-script', 'tsck_ajax_obj', ['ajaxurl' => admin_url('admin-ajax.php')]);
    wp_enqueue_script('jquery-deparam', plugin_dir_url(__FILE__) . 'public/assets/jquery-deparam.js', ['jquery'], null, true);
});

// Frontend shortcode
add_shortcode('tsck_booking_form', function () {
    if (is_admin()) return '';
    ob_start();
    include plugin_dir_path(__FILE__) . 'public/booking-form.php';
    return ob_get_clean();
});
     
add_shortcode('tsck_new_formm', function () {
    if (is_admin()) return '';
    ob_start();
    include plugin_dir_path(__FILE__) . 'public/booking-form-from.php';
    return ob_get_clean();
});

add_action('wp_ajax_tsck_check_recent_submission', 'tsck_check_recent_submission');
add_action('wp_ajax_nopriv_tsck_check_recent_submission', 'tsck_check_recent_submission');

function tsck_check_recent_submission() {
    global $wpdb;

    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

    if (empty($email)) {
        wp_send_json_error(['message' => 'Missing email']);
    }

    $table = $wpdb->prefix . 'tsck_form_submissions';

    // Make sure your table and column names are correct
    $three_months_ago = date('Y-m-d H:i:s', strtotime('-3 months'));

    // Adjust if your email is stored inside JSON
    $count = $wpdb->get_var(
        $wpdb->prepare("
            SELECT COUNT(*) FROM $table
            WHERE submitted_at >= %s
            AND form_data LIKE %s
        ", $three_months_ago, '%' . $wpdb->esc_like($email) . '%')
    );

    wp_send_json(['exists' => $count > 0]);
}
// Disable update notice for TSCK Bus Booking plugin
// Disable update checks for TSCK Bus Booking
add_filter('site_transient_update_plugins', function($value) {
    if (isset($value->response)) {
        foreach ($value->response as $plugin_file => $update_data) {
            if (strpos($plugin_file, 'tsck-bus-booking') !== false) {
                unset($value->response[$plugin_file]);
            }
        }
    }
    return $value;
});

// Also block API calls for plugin updates
add_filter('pre_set_site_transient_update_plugins', function($value) {
    if (isset($value->response)) {
        foreach ($value->response as $plugin_file => $update_data) {
            if (strpos($plugin_file, 'tsck-bus-booking') !== false) {
                unset($value->response[$plugin_file]);
            }
        }
    }
    return $value;
});
// Plugin activation: create DB tables
register_activation_hook(__FILE__, function () {
    global $wpdb;
    $charset = $wpdb->get_charset_collate();

    $booking_table = $wpdb->prefix . 'tsck_bookings';
    $event_table   = $wpdb->prefix . 'tsck_events';
    $form_table    = $wpdb->prefix . 'tsck_form_fields';
     $table_sub = $wpdb->prefix . 'tsck_form_submissions';

   

    $sql2 = "CREATE TABLE $event_table (
        id INT NOT NULL AUTO_INCREMENT,
        event_name VARCHAR(255),
        event_date DATE,
        status VARCHAR(50) DEFAULT 'Scheduled',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY(id)
    ) $charset;";

    $sql3 = "CREATE TABLE $form_table (
        id INT NOT NULL AUTO_INCREMENT, 
        label VARCHAR(255),
        name VARCHAR(255),
        nameAR VARCHAR(255),
        type VARCHAR(50),
        value TEXT,
        valueAR TEXT,
        placeholder VARCHAR(255),
        required BOOLEAN DEFAULT FALSE,
        field_show_customer TINYINT(1) NOT NULL DEFAULT 0,
        position INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(id)
    ) $charset;";

   

    $sql4 = "CREATE TABLE $table_sub (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    form_data LONGTEXT NOT NULL,
    twiel_language TEXT,
    email_status TINYINT(1) NOT NULL DEFAULT 0,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP
) $charset_collate;";

  

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    
    dbDelta($sql2);
    dbDelta($sql3);
    dbDelta($sql4);
});
