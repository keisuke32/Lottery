jQuery(document).ready(function($) {

    $(".lottery-time-countdown").each(function(index) {

        var time = $(this).data('time');
        var format = $(this).data('format');

        if (format == '') {
            format = 'yowdHMS';
        }
        var etext = '';
        if ($(this).hasClass('future')) {
           etext = '<div class="started">' + wc_lottery_data.started + '</div>';
        } else {
           etext = '<div class="over">' + wc_lottery_data.finished + '</div>';

        }
        if (wc_lottery_data.compact_counter == 'yes') {
            compact = true;
        } else {
            compact = false;
        }

        $(this).wc_lotery_countdown({
            until: $.wc_lotery_countdown.UTCDate(-(new Date().getTimezoneOffset()), new Date(time * 1000)),
            format: format,
            expiryText: etext,
            compact: compact
        });

    });

    $('form.cart input[name ="quantity"]:not(#qty_dip)').on('change', function() {
        qty = $(this).val();
        priceelement = $(this).closest('form').find('.atct-price');
        price = priceelement.data('price');
        id = priceelement.data('id');
        newprice = qty * price;
        newprice = number_format(newprice, wc_lottery_data.price_decimals, wc_lottery_data.price_decimal_separator, wc_lottery_data.price_thousand_separator);
        oldtext = $(priceelement).children('.woocommerce-Price-amount').clone().children().remove().end().text();
        if( oldtext ){
        	newtext = $(priceelement).children('.woocommerce-Price-amount').html().replace(oldtext, newprice);
        	$(priceelement).children('.woocommerce-Price-amount').html(newtext);
        }


    });

    $('.cart.pick-number').on('submit',function(e){
        var pass = true;
        if ( $('#selected_ticket_number').val() == "" ){
            pass = false;
        }
        if ( pass == false ){
            e.preventDefault();
        }

    });

    $(".qty").on('change', function(e){
        $(this).val(1);
    })
});

number_format = function(number, decimals, dec_point, thousands_sep) {
    number = number.toFixed(decimals);

    var nstr = number.toString();
    nstr += '';
    x = nstr.split('.');
    x1 = x[0];
    x2 = x.length > 1 ? dec_point + x[1] : '';
    var rgx = /(\d+)(\d{3})/;

    while (rgx.test(x1))
        x1 = x1.replace(rgx, '$1' + thousands_sep + '$2');

    return x1 + x2;
}

