<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function () {
    // Main menu (Top Level)
    add_menu_page(
        'TSCK Dashboard',              // Page title
        'TSCK Booking',                 // Menu title
        'manage_options',               // Capability
        'tsck-dashboard',               // Menu slug
        'tsck_submissions_list_page',   // Default page function
        'dashicons-calendar-alt',       // Icon
        2                               // Position
    );

    // Submenu: Submissions
    add_submenu_page(
        'tsck-dashboard',
        'TSCK Submissions',
        'Submissions',
        'manage_options',
        'tsck-submissions',
        'tsck_submissions_list_page'
    );

    // Hidden pages
    add_submenu_page(
        null,
        'View Submission',
        'View Submission',
        'manage_options',
        'tsck-submissions-view',
        'tsck_submissions_view_page'
    );
    add_submenu_page(
        null,
        'Edit Submission',
        'Edit Submission',
        'manage_options',
        'tsck-submissions-edit',
        'tsck_submissions_edit_page'
    );

    // Submenu: Events
    add_submenu_page(
        'tsck-dashboard',
        'Event Management',
        'Events',
        'manage_options',
        'tsck-events',
        'tsck_render_event_dashboard'
    );

    // Submenu: Form Fields
    add_submenu_page(
        'tsck-dashboard',
        'Form Fields',
        'Form Fields',
        'manage_options',
        'tsck_form_fields',
        'tsck_render_fields_admin'
    );
});

// Enqueue scripts for ALL tsck-dashboard subpages automatically
