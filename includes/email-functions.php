<?php
if (!defined('ABSPATH')) exit;

function tsck_send_booking_email($data) {
 
    $admin_email = get_option('admin_email');
    $subject = "New Booking from " . $data['organization_name'];
    $body = "<h2>New Bus Booking</h2>";
    foreach ($data as $key => $value) {
        $body .= "<p><strong>" . ucfirst(str_replace('_', ' ', $key)) . ":</strong> " . esc_html($value) . "</p>";
    }
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    wp_mail($admin_email, $subject, $body, $headers);
}
