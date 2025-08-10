<?php


function tsck_render_fields_admin() {
    global $wpdb;
    $table = $wpdb->prefix . 'tsck_form_fields';

    $editing_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
    $editing_field = $editing_id ? $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $editing_id)) : null;

    // Add Field
    if (isset($_POST['tsck_add_field'])) {
        $wpdb->insert($table, [
            'label'       => sanitize_text_field($_POST['label']),
            'nameAR'      => sanitize_text_field($_POST['nameAR']),
            'name'        => sanitize_text_field($_POST['name']),
            'type'        => sanitize_text_field($_POST['type']),
            'value'       => sanitize_text_field($_POST['value']),
            'valueAR'     => sanitize_text_field($_POST['valueAR']),
            'placeholder' => sanitize_text_field($_POST['placeholder']),
            'required'    => isset($_POST['required']) ? 1 : 0,
            'position'    => intval($_POST['position']),
        ]);
    }

    // Update Field
    if (isset($_POST['tsck_update_field'])) {
        $wpdb->update($table, [
            'label'       => sanitize_text_field($_POST['label']),
            'nameAR'      => sanitize_text_field($_POST['nameAR']),
            'name'        => sanitize_text_field($_POST['name']),
            'type'        => sanitize_text_field($_POST['type']),
            'value'       => sanitize_text_field($_POST['value']),
            'valueAR'     => sanitize_text_field($_POST['valueAR']),
            'placeholder' => sanitize_text_field($_POST['placeholder']),
            'required'    => isset($_POST['required']) ? 1 : 0,
            'position'    => intval($_POST['position']),
        ], ['id' => intval($_POST['field_id'])]);
        $editing_id = 0; // Reset edit mode
        $editing_field = null;
        echo '<div class="updated"><p>Field updated successfully.</p></div>';
    }

    // Delete Field
    if (isset($_POST['tsck_delete_field'])) {
        $wpdb->delete($table, ['id' => intval($_POST['tsck_delete_field'])]);
    }

    $fields = $wpdb->get_results("SELECT * FROM $table ORDER BY position ASC");
    ?>
    <div class="wrap">
        <h1><?php esc_html_e($editing_field ? 'Edit Field' : 'Manage Form Fields', 'tsck'); ?></h1>

        <form method="post">
            <h2><?php esc_html_e($editing_field ? 'Edit Field' : 'Add New Field', 'tsck'); ?></h2>
            <?php if ($editing_field): ?>
                <input type="hidden" name="field_id" value="<?php echo esc_attr($editing_field->id); ?>">
            <?php endif; ?>

            <input name="label" placeholder="Label (English)" required value="<?php echo esc_attr($editing_field->label ?? ''); ?>">
            <input name="nameAR" placeholder="Label (Arabic)" required value="<?php echo esc_attr($editing_field->nameAR ?? ''); ?>">
            <input name="name" placeholder="Name (HTML name attr)" required value="<?php echo esc_attr($editing_field->name ?? ''); ?>">

            <select name="type" required>
                <?php
                $types = ['text', 'email', 'checkbox', 'radio', 'file', 'date', 'number', 'range', 'color', 'url', 'tel', 'select', 'textarea'];
                foreach ($types as $type) {
                    $selected = (isset($editing_field->type) && $editing_field->type === $type) ? 'selected' : '';
                    echo "<option value='" . esc_attr($type) . "' $selected>" . esc_html(ucfirst($type)) . "</option>";
                }
                ?>
            </select>

            <input name="value" placeholder="Value(s) EN" value="<?php echo esc_attr($editing_field->value ?? ''); ?>">
            <input name="valueAR" placeholder="Value(s) AR" value="<?php echo esc_attr($editing_field->valueAR ?? ''); ?>">
            <input name="placeholder" placeholder="Placeholder" value="<?php echo esc_attr($editing_field->placeholder ?? ''); ?>">
            <input name="position" type="number" placeholder="Position" value="<?php echo esc_attr($editing_field->position ?? 0); ?>" min="0">

            <label>
                <input type="checkbox" name="required" <?php checked($editing_field && $editing_field->required); ?>>
                <?php esc_html_e('Required', 'tsck'); ?>
            </label>

            <button type="submit" name="<?php echo $editing_field ? 'tsck_update_field' : 'tsck_add_field'; ?>" class="button button-primary">
                <?php echo $editing_field ? esc_html__('Update Field', 'tsck') : esc_html__('Add Field', 'tsck'); ?>
            </button>

            <?php if ($editing_field): ?>
                <a href="<?php echo admin_url('admin.php?page=tsck_form_fields'); ?>" class="button">Cancel</a>
            <?php endif; ?>
        </form>

        <hr>

        <h2><?php esc_html_e('Current Fields', 'tsck'); ?></h2>
        <table class="widefat fixed">
            <thead>
                <tr>
                    <th><?php esc_html_e('Label', 'tsck'); ?></th>
                    <th><?php esc_html_e('Label (AR)', 'tsck'); ?></th>
                    <th><?php esc_html_e('Type', 'tsck'); ?></th>
                    <th><?php esc_html_e('Name', 'tsck'); ?></th>
                    <th><?php esc_html_e('Required', 'tsck'); ?></th>
                    <th><?php esc_html_e('Actions', 'tsck'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fields as $field): ?>
                    <tr>
                        <td><?php echo esc_html($field->label); ?></td>
                        <td><?php echo esc_html($field->nameAR); ?></td>
                        <td><?php echo esc_html($field->type); ?></td>
                        <td><?php echo esc_html($field->name); ?></td>
                        <td><?php echo $field->required ? 'Yes' : 'No'; ?></td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=tsck_form_fields&edit=' . $field->id); ?>" class="button">Edit</a>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="tsck_delete_field" value="<?php echo intval($field->id); ?>">
                                <button class="button button-secondary" onclick="return confirm('Delete this field?')">
                                    <?php esc_html_e('Delete', 'tsck'); ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <?php
}

