<?php
// This file handles both displaying and processing the dynamic form
$current_lang = apply_filters( 'wpml_current_language', null );
global $wpdb;
$table = $wpdb->prefix . 'tsck_form_fields';
$submission_table = $wpdb->prefix . 'tsck_form_submissions';

// Set language (can be dynamic via query param in future)
$selected_lang = 'en';


      
// Handle form submission
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tsck_form_nonce']) && wp_verify_nonce($_POST['tsck_form_nonce'], 'tsck_form_submit')) {
    $form_data = [];

    $fields = $wpdb->get_results("SELECT * FROM $table ORDER BY position ASC;");
    foreach ($fields as $field) {
        $name = $field->name;
        $type = $field->type;

        if ($type === 'checkbox') {
            $form_data[$name] = isset($_POST[$name]) ? implode(", ", array_map('sanitize_text_field', $_POST[$name])) : '';
        } else {
            $form_data[$name] = isset($_POST[$name]) ? sanitize_text_field($_POST[$name]) : '';
        }
    }

    // Get email from form
    $email = isset($form_data['email']) ? sanitize_email($form_data['email']) : '';

    // Messages based on language
    $msg_duplicate = ($selected_lang === 'ar')
        ? 'لقد قمت بالفعل بإرسال هذا النموذج من قبل.'
        : 'You have already submitted this form.';
    $msg_success = ($selected_lang === 'ar')
        ? 'تم إرسال النموذج بنجاح!'
        : 'Form submitted successfully!';
    $msg_email_required = ($selected_lang === 'ar')
        ? 'البريد الإلكتروني مطلوب.'
        : 'Email is required.';

    if (!empty($email)) {
        // Check if already submitted (limit check to last 3 months if needed)
        $already = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $submission_table 
                 WHERE JSON_EXTRACT(form_data, '$.email') = %s",
                $email
            )
        );

        if ($already > 0) {
            echo '<p style="color:red;">' . esc_html($msg_duplicate) . '</p>';
        } else {
            
          $data =   $wpdb->insert($submission_table, [
                'form_data'      => wp_json_encode($form_data),
                'twiel_language' => $selected_lang,
                'submitted_at'   => current_time('mysql'),
            ]);

           
                $inserted_id = $wpdb->insert_id;
                if($inserted_id) {
                    //tsck_send_booking_email($form_data, $selected_lang,$inserted_id);
                    
                
                }


            
            echo '<p style="color: green;">' . esc_html($msg_success) . '</p>';
        }
    } else {
        echo '<p style="color:red;">' . esc_html($msg_email_required) . '</p>';
    }
}

// Fetch fields again for display
$fields = $wpdb->get_results("SELECT * FROM $table ORDER BY position ASC");
?>

<?php if ($fields): ?>
    <form id="tsck-dynamic-booking-form" class="booking-form" method="post" novalidate>
        <?php wp_nonce_field('tsck_form_submit', 'tsck_form_nonce'); ?>
     

        <?php foreach ($fields as $field):
            $is_required = $field->required ? 'required' : '';
            $label       = esc_html($selected_lang === 'ar' ? $field->nameAR : $field->label);
            $name        = esc_attr($field->name);
            $placeholder = esc_attr($field->placeholder);
            $type        = esc_attr($field->type);
            $value       = $field->value;
            $valueAR     = $field->valueAR;
        ?>
            <div class="group-form">
                <label for="<?php echo $name; ?>"><?php echo $label; ?></label>

                <?php
                switch ($type) {
                 case 'select':
                        $options = explode(',', $value);       // English values
                        $options_ar = explode(',', $valueAR);  // Arabic values

                        echo "<select name='$name' id='$name' $is_required class='form-control'>";

                        // Static first option with blank value
                        $placeholder = $selected_lang === 'ar' ? 'اختر خيار' : 'Select option';
                        echo "<option value=''>" . esc_html($placeholder) . "</option>";

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
                            echo "<div class='radio-group'><input type='radio' name='$name' value='" . esc_attr($option) . "' $is_required> " . esc_html($option_label) . "</div>";
                        }
                        break;

                    case 'checkbox':
                        $options = explode(',', $value);
                        $options_ar = explode(',', $valueAR);
                        foreach ($options as $i => $option) {
                            $option = trim($option);
                            $option_ar = isset($options_ar[$i]) ? trim($options_ar[$i]) : '';
                            $option_label = $selected_lang === 'ar' ? $option_ar : $option;
                            echo "<div class='radio-group'><input type='checkbox' name='{$name}[]' value='" . esc_attr($option) . "' $is_required> " . esc_html($option_label) . "</div>";
                        }
                        break;

                    case 'textarea':
                        echo "<textarea name='$name' id='$name' placeholder='$placeholder' class='form-control' $is_required></textarea>";
                        break;

                    default:
                    if($name === 'booking_date') {
                        echo "<input type='$type' id='event_date' name='$name' id='$name' placeholder='$placeholder'  class='form-control' $is_required>";
                    }else if($name === 'num_students') {
                        echo "<input type='$type' name='$name' id='$name'  class='form-control' $is_required min='10' max='30'>";
                    }
                    
                    else{
                        echo "<input type='$type' name='$name' id='$name' class='form-control' $is_required>";
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
        <div class="btn-group">
            <button id="submit-btn" class="btn btn-md btn-primary" type="submit"><?php echo esc_html__('Submit', 'tsck'); ?></button>
        </div>
    </form>
<?php else: ?>
    <p><?php echo esc_html__('No form fields configured.', 'tsck'); ?></p>
<?php endif; ?>

<script>
jQuery(function ($) {

 var selectedLang = "<?php echo $selected_lang; ?>";
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
  const phoneInput = document.getElementById('phone');
    const prefix = '+971';
    const selectedLang = <?php echo json_encode($selected_lang); ?>;

     // Add event listener for keydown
    phoneInput.addEventListener('keydown', function(e) {
        // Check if the cursor is within the prefix region and prevent Backspace/Delete
        if (e.key === 'Backspace' || e.key === 'Delete') {
            // Alert message based on the selected language
             if (phoneInput.selectionStart <= prefix.length)  {
            alert(selectedLang === 'ar'
                ? 'لا يمكنك حذف رمز الدولة.'
                : 'You cannot delete the country code prefix.'
            );
            e.preventDefault(); 
        }
             // Prevent default behavior of the keypress
        }
    });

    // Ensure cursor stays after the prefix when user focuses on the input field
    phoneInput.addEventListener('focus', function() {
        // If the value is exactly +971, move the cursor after it
        if (phoneInput.value === prefix) {
            setTimeout(function() {
                phoneInput.setSelectionRange(prefix.length, prefix.length);  // Place cursor after +971
            }, 0);
        }
    });

    // Set error message based on language
    const msgInvalid = selectedLang === 'ar'
        ? "رقم الهاتف يجب أن يتكون من 9 أرقام بعد كود الدولة"
        : "Phone number must be 9 digits after the country code";

    if (phoneInput) {
        // Always ensure prefix is present
        phoneInput.addEventListener('focus', function () {
            if (!phoneInput.value.startsWith(prefix)) {
                phoneInput.value = prefix;
            }
        });

        // Validate on blur
        phoneInput.addEventListener('blur', function () {
            let val = phoneInput.value.trim();

            // Ensure prefix is present
            if (!val.startsWith(prefix)) {
                val = prefix + val.replace(/\D+/g, '');
            }

            let num = val.replace(/\D+/g, ''); // only digits
            if (num.startsWith('971')) {
                num = num.slice(3); // remove prefix for checking
            }

            removePhoneError();
     
            if (num.length != 9) {
                  
                phoneInput.classList.add('error-field');
                showPhoneError(msgInvalid);
            } else {
                
                phoneInput.classList.remove('error-field');
                phoneInput.value = prefix + num; // store full number
            }
        });
    }

    function showPhoneError(msg) {
        const error = document.createElement('div');
        error.classList.add('field-error-message', 'phone-error');
        error.style.color = 'red';
        error.style.fontSize = '13px';
        error.style.marginTop = '4px';
        error.textContent = msg;
        phoneInput.parentElement.appendChild(error);
    }

    function removePhoneError() {
        const old = document.querySelector('.phone-error');
        if (old) old.remove();
    }
});



document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('tsck-dynamic-booking-form');
    const phoneField = document.getElementById('phone');
    const studentField = document.getElementById('num_students'); // change ID to match your input

    form.addEventListener('submit', function (e) {
        let valid = true;
        let processedRadioGroups = new Set();

        // Remove old error borders & messages
        form.querySelectorAll('.error-field').forEach(el => el.classList.remove('error-field'));
        form.querySelectorAll('.field-error-message').forEach(msg => msg.remove());

        // Loop through required fields
        form.querySelectorAll('[required]').forEach(function (field) {
            let fieldValid = true;

            if (field.type === 'radio') {
                if (!processedRadioGroups.has(field.name)) {
                    processedRadioGroups.add(field.name);

                    const group = form.querySelectorAll(`[name="${field.name}"]`);
                    if (![...group].some(r => r.checked)) {
                        fieldValid = false;
                        group.forEach(r => r.classList.add('error-field'));

                        const groupWrapper = group[0].closest('div') || group[0].parentElement;
                        addErrorMessage(groupWrapper, getErrorMessage(field), 'beforebegin');
                    }
                }
            } else if (!field.value.trim()) {
                fieldValid = false;
                field.classList.add('error-field');
                addErrorMessage(field, getErrorMessage(field), 'afterend');
            }

            if (!fieldValid) {
                valid = false;
            }
        });

        // Extra validation for number of students 10–30
        if (studentField) {
            const numStudents = parseInt(studentField.value.trim(), 10);
            const selectedLang = "<?php echo esc_js($selected_lang); ?>";
            if (isNaN(numStudents) || numStudents < 10 || numStudents > 30) {
                valid = false;
                studentField.classList.add('error-field');
                addErrorMessage(
                    studentField,
                    selectedLang === 'ar'
                        ? 'عدد الطلاب يجب أن يكون بين 10 و 30'
                        : 'Number of students must be between 10 and 30',
                    'afterend'
                );
            }
        }

        if (!valid) {
            e.preventDefault();
        }
    });

    function addErrorMessage(element, message, position) {
        const error = document.createElement('div');
        error.classList.add('field-error-message');
        error.textContent = message;
        error.style.color = 'red';
        error.style.fontSize = '13px';
        error.style.marginTop = '4px';
        element.insertAdjacentElement(position, error);
    }

    function getErrorMessage(field) {
        const selectedLang = "<?php echo esc_js($selected_lang); ?>";
        return field.dataset.error || (selectedLang === 'ar' ? 'هذا الحقل مطلوب' : 'This field is required');
    }
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
}gi
</style>
