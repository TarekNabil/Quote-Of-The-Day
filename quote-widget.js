jQuery(document).ready(function($) {
    $('#quote_date_picker').datepicker({
        dateFormat: 'yy-mm-dd',
        maxDate: 0
    });
    // at date change, update quote-content WITH  the new quote
    $('#quote_date_picker').change(function() {
        var date = $('#quote_date_picker').val();
        $.ajax({
            url: qotd.ajax_url,
            type: 'POST',
            data: {
                action: 'qotd_get_quote_by_date',
                date: date
            },
            success: function(response) {
                $('.quote-content').fadeOut('slow', function() {
                    $(this).html(response).fadeIn('slow');
            });
        }

        });
        
    });

});
