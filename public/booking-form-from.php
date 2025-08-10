<?php
// This file handles both displaying and processing the dynamic form

global $wpdb;
$table = $wpdb->prefix . 'tsck_form_fields';
$submission_table = $wpdb->prefix . 'tsck_form_submissions';

// Set language (can be dynamic via query param in future)
$selected_lang = 'en';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tsck_form_nonce']) && wp_verify_nonce($_POST['tsck_form_nonce'], 'tsck_form_submit')) {
    $form_data = [];

    $fields = $wpdb->get_results("SELECT * FROM $table ORDER BY position ASC");
    foreach ($fields as $field) {
        $name = $field->name;
        $type = $field->type;

        if ($type === 'checkbox') {
            $form_data[$name] = isset($_POST[$name]) ? implode(", ", array_map('sanitize_text_field', $_POST[$name])) : '';
        } else {
            $form_data[$name] = isset($_POST[$name]) ? sanitize_text_field($_POST[$name]) : '';
        }
    }

    // Save to database
    $wpdb->insert($submission_table, [
        'form_data'    => wp_json_encode($form_data),
        'language'    => $selected_lang,
        'submitted_at' => current_time('mysql'),
    ]);

    //tsck_send_booking_email($form_data,$selected_lang);

    echo '<p style="color: green;">Form submitted successfully!</p>';
}

// Fetch fields again for display
$fields = $wpdb->get_results("SELECT * FROM $table ORDER BY position ASC");
?>

<?php if ($fields): ?>
    <form id="tsck-dynamic-booking-form" method="post" style="max-width: 600px;" novalidate>
        <?php wp_nonce_field('tsck_form_submit', 'tsck_form_nonce'); ?>
        <input type="hidden" name="lang" value="<?php echo esc_attr($selected_lang); ?>">

        <?php foreach ($fields as $field):
            $is_required = $field->required ? 'required' : '';
            $label       = esc_html($selected_lang === 'ar' ? $field->nameAR : $field->label);
            $name        = esc_attr($field->name);
            $placeholder = esc_attr($field->placeholder);
            $type        = esc_attr($field->type);
            $value       = $field->value;
            $valueAR     = $field->valueAR;
        ?>
            <div class="tsck-form-group" style="margin-bottom: 20px;">
                <label for="<?php echo $name; ?>"><?php echo $label; ?></label><br>

                <?php
                switch ($type) {
                    case 'select':
                        $options = explode(',', $value);
                        $options_ar = explode(',', $valueAR);
                        echo "<select name='$name' id='$name' $is_required>";
                        foreach ($options as $i => $option) {
                            $option = trim($option);
                            $option_ar = isset($options_ar[$i]) ? trim($options_ar[$i]) : '';
                            $option_label = $selected_lang === 'ar' ? $option_ar : $option;
                            echo "<option value='" . esc_attr($option) . "'>" . esc_html($option_label) . "</option>";
                        }
                        echo "</select>";
                        break;

                    case 'radio':
                        $options = explode(',', $value);
                        $options_ar = explode(',', $valueAR);
                        foreach ($options as $i => $option) {
                            $option = trim($option);
                            $option_ar = isset($options_ar[$i]) ? trim($options_ar[$i]) : '';
                            $option_label = $selected_lang === 'ar' ? $option_ar : $option;
                            echo "<div><label><input type='radio' name='$name' value='" . esc_attr($option) . "' $is_required> " . esc_html($option_label) . "</label></div>";
                        }
                        break;

                    case 'checkbox':
                        $options = explode(',', $value);
                        $options_ar = explode(',', $valueAR);
                        foreach ($options as $i => $option) {
                            $option = trim($option);
                            $option_ar = isset($options_ar[$i]) ? trim($options_ar[$i]) : '';
                            $option_label = $selected_lang === 'ar' ? $option_ar : $option;
                            echo "<div><label><input type='checkbox' name='{$name}[]' value='" . esc_attr($option) . "' $is_required> " . esc_html($option_label) . "</label></div>";
                        }
                        break;

                    case 'textarea':
                        echo "<textarea name='$name' id='$name' placeholder='$placeholder' style='width: 100%;' $is_required></textarea>";
                        break;

                    default:
                    if($name === 'booking_date') {
                        echo "<input  type='$type' id='event_date' name='$name' id='$name' placeholder='$placeholder' style='width: 100%;' $is_required>";
                    }else{
                        echo "<input type='$type' name='$name' id='$name' placeholder='$placeholder' style='width: 100%;' $is_required>";
                }
                        break;
                }
                ?>
            </div>
        <?php endforeach; ?>

        <div id="custom-recaptcha" style="margin-bottom: 15px;">
    <label id="captcha-question"></label><br>
    <input type="text" id="captcha-input" placeholder="Enter answer" required style="width:100%;">
</div>
        
        <button id="submit-btn" type="submit"><?php echo esc_html__('Submit', 'tsck'); ?></button>
    </form>
<?php else: ?>
    <p><?php echo esc_html__('No form fields configured.', 'tsck'); ?></p>
<?php endif; ?>

<script>
jQuery(function ($) {
 console.log("TSCK Booking Data:", tsckBookingData);

const allowedDates = tsckBookingData.event_dates || [];
const bookedDates = tsckBookingData.booked_dates || [];
const ppDates = tsckBookingData.ppevent_date || [];

$('#event_date').datepicker({
    dateFormat: 'yy-mm-dd',
    beforeShowDay: function (date) {
        const formatted = $.datepicker.formatDate('yy-mm-dd', date);

        const isAllowed = allowedDates.includes(formatted);
        const isBooked = bookedDates.includes(formatted);
        const PPisBooked = ppDates.includes(formatted);

        if (isBooked) {
            // Booked date → disabled (red)
            return [false, 'booked-date', 'Already Booked'];
        }
         if (PPisBooked) {
            // Booked date → disabled (red)
            return [false, 'ppevent_date', 'Already Booked'];
        }

        if (isAllowed) {
            // Allowed date → green and selectable
            return [true, 'allowed-date', 'Available'];
        }

        // Anything else → disabled
        return [false, '', 'Not Available'];
    }
})



    $('#tsck-booking-form').on('submit', function (e) {
        e.preventDefault();

        const formData = $(this).serialize();
        $.ajax({
            url: tsckBookingData.ajax_url,
            type: 'POST',
            data: {
                action: 'tsck_submit_booking',
                data: formData
            },
            success: function (response) {
                $('#tsck-response').html('<p style="color:green;">Booking submitted successfully!</p>');
                $('#tsck-booking-form')[0].reset();
            },
            error: function () {
                $('#tsck-response').html('<p style="color:red;">Error submitting booking.</p>');
            }
        });
    });
});


 const input = document.getElementById('phone');
    const countryCode = '+966';

    // Convert Arabic to English digits
    function convertArabicToEnglish(input) {
        return input.replace(/[\u0660-\u0669]/g, d => d.charCodeAt(0) - 0x0660);
    }

    // Ensure country code prefix
    function enforceCountryCode() {
        let val = convertArabicToEnglish(input.value);

        // Strip all non-digit except +
        val = val.replace(/[^\d+]/g, '');

        // Ensure it starts with +966
        if (!val.startsWith(countryCode)) {
            val = countryCode + val.replace(/^(\+)?(966)?/, '');
        }

        input.value = val;
    }

    // Initial setup
    input.addEventListener('focus', () => {
        if (!input.value.startsWith(countryCode)) {
            input.value = countryCode;
        }
    });

    // Prevent deleting country code
    input.addEventListener('keydown', (e) => {
        const caretPos = input.selectionStart;
        if (
            (e.key === 'Backspace' || e.key === 'Delete') &&
            caretPos <= countryCode.length
        ) {
            e.preventDefault();
        }
    });


    (function () {
    const input = document.getElementById('phone');
    const countryCode = '+966';

    // Convert Arabic numerals to Western digits
    function convertArabicToEnglish(str) {
        return str.replace(/[\u0660-\u0669]/g, d => d.charCodeAt(0) - 0x0660);
    }

    // Filter out everything except digits after +966
    function enforcePhoneFormat() {
        let val = convertArabicToEnglish(input.value);
        if (!val.startsWith(countryCode)) {
            val = countryCode + val.replace(/^\+?966/, '');
        }

        const numbersOnly = val.slice(countryCode.length).replace(/\D/g, '');
        input.value = countryCode + numbersOnly;
    }

    // Prevent removing the country code
    input.addEventListener('keydown', function (e) {
        const caret = input.selectionStart;
        if ((e.key === 'Backspace' || e.key === 'Delete') && caret <= countryCode.length) {
            e.preventDefault();
        }

        // Disallow typing letters/symbols
        if (
            caret > countryCode.length &&
            !/[0-9\u0660-\u0669]/.test(e.key) &&
            e.key.length === 1 // skip keys like Arrow, Delete etc.
        ) {
            e.preventDefault();
        }
    });

    // Ensure valid format after every change
    input.addEventListener('input', enforcePhoneFormat);

    // Initialize value correctly
    input.addEventListener('focus', () => {
        if (!input.value.startsWith(countryCode)) {
            input.value = countryCode;
        }
    });

    // Run once on load
    enforcePhoneFormat();
})();


document.addEventListener('DOMContentLoaded', function () {
    const emailInput = document.getElementById('email');
    const submitBtn = document.getElementById('submit-btn'); // Replace with your button's ID

    let lastCheckedEmail = '';

    emailInput.addEventListener('input', function () {
        const email = emailInput.value.trim();

        if (validateEmail(email) && email !== lastCheckedEmail) {
            lastCheckedEmail = email;

            fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'tsck_check_recent_submission',
                    email: email
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.exists) {
                    alert('This email has already submitted a form within the last 3 months.');
                    submitBtn.disabled = true;
                } else {
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    });

    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const questionEl = document.getElementById('captcha-question');
    const inputEl = document.getElementById('captcha-input');
    const submitBtn = document.getElementById('submit-btn');
     const selected_lang = "<?php echo $selected_lang; ?>";

    // Generate random numbers for the challenge
    let num1 = Math.floor(Math.random() * 10) + 1;
    let num2 = Math.floor(Math.random() * 10) + 1;
    let answer = num1 + num2;

    // Show question

    if (selected_lang === 'ar') {
        questionEl.textContent = `ما هو ${num1} + ${num2}؟`;
    } else {
        questionEl.textContent = `What is ${num1} + ${num2}?`;
    }
    //questionEl.textContent = `What is ${num1} + ${num2}?`;

    // Check input on every change
    inputEl.addEventListener('input', function () {
        if (parseInt(inputEl.value, 10) === answer) {
            submitBtn.disabled = false; // Enable if correct
        } else {
            submitBtn.disabled = true;  // Disable if wrong
        }
    });
});


 document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('tsck-dynamic-booking-form');

    form.addEventListener('submit', function (e) {
        let valid = true;

        // Remove old error borders
        form.querySelectorAll('.error-field').forEach(el => el.classList.remove('error-field'));

        form.querySelectorAll('[required]').forEach(function (field) {
            if (field.type === 'radio') {
                // Check radio group
                const group = form.querySelectorAll(`[name="${field.name}"]`);
                if (![...group].some(r => r.checked)) {
                    valid = false;
                    group.forEach(r => r.classList.add('error-field'));
                }
            } else if (!field.value.trim()) {
                valid = false;
                field.classList.add('error-field');
            }
        });

        if (!valid) {
            e.preventDefault(); // Stop submission
        }
    });
});

</script>
<style>
a.ui-state-default {
    color: #fbfbfb !important;
    background: #051c44 !important;
}
/* Allowed date style */
.allowed-date a {
    background-color: #d4f4d4 !important; /* light green */
    color: #0a0 !important;
 
}

 .error-field {
        border: 2px solid red !important;
    }

/* Booked date style */
.booked-date a {
    background-color: #ffd4d4 !important; /* light red */
    color: #a00 !important;
    
}
td.ui-datepicker-unselectable.ui-state-disabled.booked-date {
    background: red;
}
td.ui-datepicker-unselectable.ui-state-disabled.ppevent_date {
    border: 1px solid yellow !important;
    background: yellow;
}
</style>
