<?php
if (!defined('ABSPATH')) exit;

add_action('wp_ajax_tsck_submit_booking', 'tsck_submit_booking');
add_action('wp_ajax_nopriv_tsck_submit_booking', 'tsck_submit_booking');

function tsck_submit_booking() {
    global $wpdb;
    $table = $wpdb->prefix . 'tsck_bookings';

    $data = [
        'organization_name' => sanitize_text_field($_POST['organization_name']),
        'booking_type' => sanitize_text_field($_POST['booking_type']),
        'grade_levels' => sanitize_text_field($_POST['grade_levels']),
        'school_category' => sanitize_text_field($_POST['school_category']),
        'subject' => sanitize_text_field($_POST['subject']),
        'contact_name' => sanitize_text_field($_POST['contact_name']),
        'num_students' => intval($_POST['num_students']),
        'phone' => sanitize_text_field($_POST['phone']),
        'email' => sanitize_email($_POST['email']),
        'address' => sanitize_textarea_field($_POST['address']),
        'governorate' => sanitize_text_field($_POST['governorate']),
        'location_link' => esc_url($_POST['location_link']),
        'notes' => sanitize_textarea_field($_POST['notes']),
        'booking_date' => sanitize_text_field($_POST['booking_date']),
    ];

    $wpdb->insert($table, $data);
    tsck_send_booking_email($data);

    wp_send_json_success(['message' => 'Booking submitted successfully.']);
}
