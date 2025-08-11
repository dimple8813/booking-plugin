jQuery(document).ready(function($) {
    $('#tsck-booking-form').on('submit', function(e) {
     
        e.preventDefault();
        let formData = $(this).serialize();
        $.post(tsck_ajax_obj.ajaxurl, {
            action: 'tsck_submit_booking',
            ...$.deparam(formData)
        }, function(response) {
            console.log("fff",response);
            $('#tsck-response').html('<p>' + response.data.message + '</p>');
            $('#tsck-booking-form')[0].reset();
        });
    });
});





