<?php if (!defined('ABSPATH')) exit(); ?>

<style>
.tsck-booking-container {
    max-width: 600px;
    margin: 30px auto;
    border: 1px solid #ccc;
    padding: 20px;
    font-family: sans-serif;
    background: #fff;
}
.tsck-booking-container h2 {
    text-align: center;
    margin-bottom: 20px;
}
.tsck-booking-container label {
    display: block;
    margin-bottom: 15px;
}
.tsck-booking-container input[type="text"],
.tsck-booking-container input[type="number"],
.tsck-booking-container input[type="email"],
.tsck-booking-container input[type="url"],
.tsck-booking-container textarea,
.tsck-booking-container select {
    width: 100%;
    padding: 8px;
    box-sizing: border-box;
}
.tsck-booking-container .radio-group {
    margin-bottom: 15px;
}
.tsck-booking-container .radio-group label {
    display: inline-block;
    margin-right: 15px;
}
.tsck-booking-container .submit-btn {
    text-align: center;
    margin-top: 20px;
}
.tsck-booking-container button {
    padding: 10px 25px;
    background: #0073aa;
    color: #fff;
    border: none;
    cursor: pointer;
}
.tsck-booking-container button:hover {
    background: #005177;
}
</style>

<div class="tsck-booking-container">
    <h2>Steam Bus Booking</h2>
    <form id="tsck-booking-form">
        <label>Organization Name
            <input type="text" name="organization_name" required>
        </label>

        <div class="radio-group">
            <label><input type="radio" name="booking_type" value="Private" checked> Private</label>
            <label><input type="radio" name="booking_type" value="Government"> Government</label>
            <label><input type="radio" name="booking_type" value="Company"> Company</label>
        </div>

        <label>Booking Date
            <input type="text" id="event_date" name="booking_date" required>
        </label>

        <label>Grade Levels
            <input type="text" name="grade_levels" placeholder="e.g., Primary, Secondary">
        </label>

        <label>School Category
            <select name="school_category">
                <option value="">Select Category</option>
                <option value="Mixed">Mixed</option>
                <option value="Boys">Boys</option>
                <option value="Girls">Girls</option>
            </select>
        </label>

        <label>Subject (Optional)
            <input type="text" name="subject">
        </label>

        <label>Contact Person Name
            <input type="text" name="contact_name">
        </label>

        <label>Number of Students
            <input type="number" name="num_students" min="1" max="30" value="5">
        </label>

        <label>Phone
            <input type="text" name="phone">
        </label>

        <label>Email
            <input type="email" name="email">
        </label>

        <label>Address
            <textarea name="address"></textarea>
        </label>

        <label>Governorate
            <select name="governorate">
                <option value="">Select Governorate</option>
                <option value="Ahmadi">Ahmadi</option>
                <option value="Hawalli">Hawalli</option>
                <option value="Farwaniya">Farwaniya</option>
                <option value="Capital">Capital</option>
            </select>
        </label>

        <label>Location Link (Google Maps)
            <input type="url" name="location_link">
        </label>

        <label>Note
            <textarea name="notes"></textarea>
        </label>

        <div class="submit-btn">
            <button type="submit">Submit</button>
        </div>
    </form>

    <div id="tsck-response"></div>
</div>

<script>
jQuery(function ($) {
    const allowedDates = tsckBookingData.event_dates || [];
    console.log("Allowed Dates:", allowedDates);

    $('#event_date').datepicker({
        dateFormat: 'yy-mm-dd',
        beforeShowDay: function (date) {
            const formatted = $.datepicker.formatDate('yy-mm-dd', date);
            return [allowedDates.includes(formatted), '', ''];
        }
    });

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



</script>
