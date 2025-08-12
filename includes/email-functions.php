<?php
if (!defined('ABSPATH')) exit;

function tsck_send_booking_email($form_data, $selected_lang, $inserted_id) {
    $admin_email = "er.dimplekumat@gmail.com";

    // Validate customer email
    if (empty($form_data['email']) || !is_email($form_data['email'])) {
        error_log("Booking Email Error: Invalid or missing customer email.");
        return false;
    }
    $customer_email = sanitize_email($form_data['email']);

    // Prepare booking details rows
    $details_html = "";
    foreach ($form_data as $value) {
        $details_html .= "<tr>
            <td style='padding:8px;border:1px solid #ddd;'>" . esc_html($value) . "</td>
        </tr>";
    }

    // Arabic template
    $subject_ar = "حجز جديد";
    $body_ar = "
    <html dir='rtl' lang='ar'>
    <head><meta charset='UTF-8'></head>
    <body style='font-family:Tahoma, sans-serif; background-color:#f7f7f7; padding:20px;'>
        <div style='max-width:600px;margin:auto;background:#fff;padding:20px;border-radius:8px;'>
            <h2 style='text-align:center;color:#333;'>تفاصيل الحجز</h2>
            <table style='width:100%;border-collapse:collapse;'>
                $details_html
            </table>
            <p style='margin-top:20px;text-align:center;color:#555;'>شكراً لاستخدامك نظام الحجز لدينا</p>
        </div>
    </body>
    </html>";

    // English template
    $subject_en = "New Booking";
    $body_en = "
    <html lang='en'>
    <head><meta charset='UTF-8'></head>
    <body style='font-family:Arial, sans-serif; background-color:#f7f7f7; padding:20px;'>
        <div style='max-width:600px;margin:auto;background:#fff;padding:20px;border-radius:8px;'>
            <h2 style='text-align:center;color:#333;'>Booking Details</h2>
            <table style='width:100%;border-collapse:collapse;'>
                $details_html
            </table>
            <p style='margin-top:20px;text-align:center;color:#555;'>Thank you for using our booking system</p>
        </div>
    </body>
    </html>";

    // Headers
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: My Booking System <no-reply@' . $_SERVER['SERVER_NAME'] . '>'
    ];

    // Send to customer in their language
    if ($selected_lang === 'ar') {
        wp_mail($customer_email, $subject_ar, $body_ar, $headers);
    } else {
        wp_mail($customer_email, $subject_en, $body_en, $headers);
    }

    // Send to admin in English (change if you want Arabic)
    wp_mail($admin_email, $subject_en, $body_en, $headers);

    if ($customer_sent && $admin_sent) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tsck_form_submission';
        $wpdb->update(
            $table_name,
            [ 'email_status' => 1 ],
            [ 'id' => $inserted_id ],
            [ '%d' ],
            [ '%d' ]
        );
    }

    return true;
}
