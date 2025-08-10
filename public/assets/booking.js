jQuery(function ($) {
    const allowedDates = tsckBookingData.event_dates || [];

    $('#event_date').datepicker({
        dateFormat: 'yy-mm-dd',
        beforeShowDay: function (date) {
            const formatted = $.datepicker.formatDate('mm-dd-yy', date);
            return [allowedDates.includes(formatted), '', ''];
        }
    });
});