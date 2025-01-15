jQuery(document).ready(function($) {
    $.datepicker.regional['hy'] = {
        dayNamesShort: ['Բշ.', 'Գշ.','Դշ.','Եշ.','Ուրբ.','Շբ.','Կիր.'],
        dayNamesMin: ['Բշ.', 'Գշ.','Դշ.','Եշ.','Ուրբ.','Շբ.','Կիր.'],    
    };
    // set default language to current language
    // get current page language
    var lang = $('html').attr('lang');
    // set the language to the current language
    $.datepicker.setDefaults($.datepicker.regional[lang]);
    //  $.datepicker.setDefaults($.datepicker.regional['hy']);
    // change the datepicker language
    $('#quote_date_picker').datepicker({
        dateFormat: 'yy-mm-dd',
        maxDate: null
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
