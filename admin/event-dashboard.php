<?php
if (!defined('ABSPATH')) exit;



function tsck_render_event_dashboard()
{
    global $wpdb;
    $table = $wpdb->prefix . 'tsck_events';

    $edit_mode = false;
    $edit_event = null;

    // Check if editing
    if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
        $edit_id = intval($_GET['id']);
        $edit_event = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $edit_id));
        if ($edit_event) {
            $edit_mode = true;
        }
    }

    // Handle form submission
    if (isset($_POST['tsck_event_nonce']) && wp_verify_nonce($_POST['tsck_event_nonce'], 'save_event')) {
        $name = sanitize_text_field($_POST['name']);
        $date = sanitize_text_field($_POST['date']);
        $status = sanitize_text_field($_POST['status']);

        if (isset($_POST['event_id']) && !empty($_POST['event_id'])) {
            // Update existing event
            $event_id = intval($_POST['event_id']);
            $wpdb->update($table, [
                'event_name' => $name,
                'event_date' => $date,
                'status' => $status
            ], ['id' => $event_id]);

            echo '<div class="updated"><p>Event updated successfully.</p></div>';
            $edit_mode = false;
        } else {
            // Insert new event
            $wpdb->insert($table, [
                'event_name' => $name,
                'event_date' => $date,
                'status' => $status
            ]);

            echo '<div class="updated"><p>Event added successfully.</p></div>';
        }
    }

    // Handle delete
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $wpdb->delete($table, ['id' => $id]);
        echo '<div class="updated"><p>Event deleted.</p></div>';
    }

    // Fetch all events
    $events = $wpdb->get_results("SELECT * FROM $table ORDER BY event_date ASC");
    ?>

    <div class="wrap">
        <h1><?php echo $edit_mode ? 'Edit Event' : 'Event Management'; ?></h1>

        <h2><?php echo $edit_mode ? 'Edit Event' : 'Add New Event'; ?></h2>
        <form method="POST">
            <?php wp_nonce_field('save_event', 'tsck_event_nonce'); ?>
            <input type="hidden" name="event_id" value="<?php echo esc_attr($edit_event->id ?? ''); ?>">
            <table class="form-table">
                <tr>
                    <th><label for="name">Event Name</label></th>
                    <td><input type="text" name="name" required class="regular-text" value="<?php echo esc_attr($edit_event->event_name ?? ''); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="date">Date</label></th>
                    <td><input type="date" name="date" required value="<?php echo esc_attr($edit_event->event_date ?? ''); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="status">Status</label></th>
                    <td>
                        <select name="status">
                            <option value="active" <?php selected($edit_event->status ?? '', 'active'); ?>>Active</option>
                            <option value="inactive" <?php selected($edit_event->status ?? '', 'inactive'); ?>>Inactive</option>
                        </select>
                    </td>
                </tr>
            </table>
            <?php submit_button($edit_mode ? 'Update Event' : 'Add Event'); ?>
        </form>

        <hr>

        <h2>All Events</h2>
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($events) :
                    foreach ($events as $event) : ?>
                        <tr>
                            <td><?= esc_html($event->id) ?></td>
                            <td><?= esc_html($event->event_name) ?></td>
                            <td><?= esc_html($event->event_date) ?></td>
                            <td><?= esc_html($event->status) ?></td>
                            <td>
                                <a href="<?= esc_url(admin_url('admin.php?page=tsck-events&action=edit&id=' . $event->id)) ?>">Edit</a> |
                                <a href="<?= esc_url(admin_url('admin.php?page=tsck-events&action=delete&id=' . $event->id)) ?>"
                                   onclick="return confirm('Delete this event?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach;
                else : ?>
                    <tr><td colspan="5">No events found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
}
