jQuery(document).ready(function ($) {

    var calendar_image = '';

    if (typeof woocommerce_writepanel_params != 'undefined') {
        calendar_image = woocommerce_writepanel_params.calendar_image;
    } else if (typeof woocommerce_admin_meta_boxes != 'undefined') {
        calendar_image = woocommerce_admin_meta_boxes.calendar_image;
    }

    jQuery('.datetimepicker').datetimepicker({
        defaultDate: "",
        dateFormat: "yy-mm-dd",
        numberOfMonths: 1,
        showButtonPanel: true,
        showOn: "button",
        buttonImage: calendar_image,
        buttonImageOnly: true
    });

    var productType = jQuery('#product-type').val();
    if (productType == 'lottery') {
        jQuery('.show_if_simple').show();
        jQuery('.inventory_options').hide();
        jQuery('#lottery_tab .required').each(function (index, el) {
            jQuery(this).attr("required", true);
        });
    } else {
        jQuery('#lottery_tab .required').each(function (index, el) {
            jQuery(this).attr("required", false);
        });
    }

    jQuery('#product-type').on('change', function () {
        if (jQuery(this).val() == 'lottery') {
            jQuery('.show_if_simple').show();
            jQuery('.inventory_options').hide();
            jQuery('#lottery_tab .required').each(function (index, el) {
                jQuery(this).attr("required", true);
            });
        } else {
            jQuery('#lottery_tab .required').each(function (index, el) {
                jQuery(this).attr("required", false);
            });
        }
    });

    jQuery('label[for="_virtual"]').addClass('show_if_lottery');

    jQuery('label[for="_downloadable"]').addClass('show_if_lottery');

    jQuery('.lottery-table .action a').on('click', function (event) {
        var logid = $(this).data('id');
        var postid = $(this).data('postid');
        var curent = $(this);
        jQuery.ajax({
            type: "post",
            url: ajaxurl,
            data: {action: "delete_lottery_participate_entry", logid: logid, postid: postid},
            success: function (response) {
                if (response === 'deleted') {
                    curent.parent().parent().addClass('deleted').fadeOut('slow');
                }
            }
        });
        event.preventDefault();
    });

    jQuery('#lottery-refund').on('click', function (event) {
        if (window.confirm(woocommerce_admin_meta_boxes.i18n_do_refund)) {
            var product_id = $(this).data('product_id');
            var curent = $(this);
            var $wrapper = $('#Lottery');

            $("#refund-status").empty();

            $wrapper.block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });

            jQuery.ajax({
                type: "post",
                url: ajaxurl,
                data: {action: "lottery_refund", product_id: product_id, security: woocommerce_lottery.lottery_refund_nonce},
                success: function (response) {


                    if (response.error) {

                        $("#refund-status").append('<div class="error notice"></div>');

                        $.each(response.error, function (index, value) {

                            $("#refund-status .error").append('<p class"error">' + index + ': ' + value + '</p>');

                        });


                    }

                    if (response.succes) {

                        $("#refund-status").append('<div class="updated  notice"></div>');
                        $.each(response.succes, function (index, value) {

                            $("#refund-status .updated ").append('<li class"ok">' + index + ': ' + value + '</li>');

                        });
                    }
                    $wrapper.unblock();
                }
            });
        }
        event.preventDefault();
    });

    jQuery('#general_product_data #_regular_price').on('keyup', function () {
        jQuery('#auction_tab #_regular_price').val(jQuery(this).val());
    });

    var lotterymaxwinners = jQuery('#_lottery_num_winners').val();

    if (lotterymaxwinners > 1) {
        $('._lottery_multiple_winner_per_user_field').show();
    } else {
        $('._lottery_multiple_winner_per_user_field').hide();
    }
    jQuery('#relistlottery').on('click', function (event) {
        event.preventDefault();
        jQuery('.relist_lottery_dates_fields').toggle();
    });
    jQuery('#extendlottery').on('click', function (event) {
        event.preventDefault();
        jQuery('.extend_lottery_dates_fields').toggle();
    });

    /**
     * @Author Igor
     * --------------START change_pick_number_alphabet
     */
    jQuery('#_lottery_pick_number_alphabet').on('change', function (event) {
        const pick_line = event.target.value;
        let lottery_number_range_from = jQuery('#_lottery_number_range_from');
        let lottery_number_range_to = jQuery('#_lottery_number_range_to');
        let lottery_number_user_pick = jQuery('#_lottery_number_user_pick');

        if (pick_line == 1) {
            lottery_number_range_from.val(1);
            lottery_number_range_from.prop("readonly", true);
            lottery_number_range_to.val(48);
            lottery_number_range_to.prop("readonly", false);
            lottery_number_user_pick.val(6);
            lottery_number_user_pick.attr("disabled", false);
        } else {
            lottery_number_range_from.val(0);
            lottery_number_range_from.prop("readonly", true);
            lottery_number_range_to.val(9);
            lottery_number_range_to.prop("readonly", true);
            lottery_number_user_pick.val(1);
            lottery_number_user_pick.attr("disabled", true);
        }

        // Change select options when select this
        add_options_lottery_match_alphabet(pick_line);

    });

    jQuery("#_lottery_bonus_exist").change(function () {
        if (jQuery("#_lottery_bonus_exist").prop("checked")) {
            if (jQuery("#_lottery_number_allow_pick").val() == "2") {
                jQuery(".lottery_match_plus_symbol_1").show();
                jQuery(".lottery_match_bonus_1").show();
                jQuery(".lottery_match_plus_symbol_2").show();
                jQuery(".lottery_match_bonus_2").show();
            } else if (jQuery("#_lottery_number_allow_pick").val() == "1") {
                jQuery(".lottery_match_plus_symbol_1").show();
                jQuery(".lottery_match_bonus_1").show();
                jQuery(".lottery_match_plus_symbol_2").hide();
                jQuery(".lottery_match_bonus_2").hide();
            }
        } else {
            jQuery(".lottery_match_plus_symbol_1").hide();
            jQuery(".lottery_match_bonus_1").hide();
            jQuery(".lottery_match_plus_symbol_2").hide();
            jQuery(".lottery_match_bonus_2").hide();
        }
    });

    function add_options_lottery_match_alphabet(pick_line) {
        var lottery_match_alphabet = $("#_lottery_match_alphabet");
        lottery_match_alphabet.empty();
        lottery_match_alphabet.append(`<option value="" disabled selected>${$("#_lottery_pick_number_alphabet option:selected").text()}</option>`);

        var lottery_match_alphabet_options = {
            1: {
                6: '7 Digit Match (A)',
                5: '6 Digit Match (A)',
                4: '5 Digit Match (A)',
                3: '4 Digit Match (A)',
                2: '3 Digit Match (A)',
                1: '2 Digit Match (A)'
            },
            3: {
                1: '3 Digit (ABC)',
            },
            4: {
                1: '4 Digit (ABCD)',
            },
            5: {
                1: '5 Digit (ABCDE)',
                2: '4D Prefix (ABCD)',
                3: '4D Suffix (CDEF)',
                4: '3D Prefix (ABCD)',
                5: '3D Suffix (CDEF)',
                6: '2D Suffix (EF)',
            },
            6: {
                6: '6 Digit (ABCDEF)',
                5: '4D Suffix (CDEF)',
                4: '4D Prefix (ABCD)',
                3: '3D Suffix (DEF)',
                2: '3D Prefix (ABC)',
                1: '2D Suffix (EF)',
            }
        };
        if (lottery_match_alphabet_options.hasOwnProperty(pick_line)) {

            const lottery_match_alphabet_options_pick_line = lottery_match_alphabet_options[pick_line];
            for (const lv in lottery_match_alphabet_options_pick_line) {
                if (lottery_match_alphabet_options_pick_line.hasOwnProperty(lv)) {
                    lottery_match_alphabet.append(`<option value=${lv}>${lottery_match_alphabet_options_pick_line[lv]}</option>`)
                }
            }
        }
    }

    function add_lottery_prize(id, order, prize_name, prize_point, prize_point_type, prize_match_line, prize_match_bonus, alphabet_line) {
        let html = "";

        const gami_point_types = JSON.parse(jQuery('#gami_point_types').val());
        let gp_html = "<select name='lottery_prize_gp_point_type[]' class='select short'>" +
            "<option value='0'>Point type</option>";
        for (const gp in gami_point_types) {
            gp_html += `<option value="${gami_point_types[gp].ID}" ${gami_point_types[gp].ID === prize_point_type ? 'selected' : ''}>${gami_point_types[gp].singular_name}</option>`;
        }
        gp_html += '</select>';

        let match_html = "<select name='lottery_prize_match_alphabet[]' class='select short' style='width:100%'>";
        var lottery_match_alphabet_options = {
            1: {
                6: '7 Digit Match (A)',
                5: '6 Digit Match (A)',
                4: '5 Digit Match (A)',
                3: '4 Digit Match (A)',
                2: '3 Digit Match (A)',
                1: '2 Digit Match (A)'
            },
            3: {
                1: '3 Digit (ABC)',
            },
            4: {
                1: '4 Digit (ABCD)',
            },
            5: {
                1: '5 Digit (ABCDE)',
                2: '4D Prefix (ABCD)',
                3: '4D Suffix (CDEF)',
                4: '3D Prefix (ABCD)',
                5: '3D Suffix (CDEF)',
                6: '2D Suffix (EF)',
            },
            6: {
                6: '6 Digit (ABCDEF)',
                5: '4D Suffix (CDEF)',
                4: '4D Prefix (ABCD)',
                3: '3D Suffix (DEF)',
                2: '3D Prefix (ABC)',
                1: '2D Suffix (EF)',
            }
        };
        if (lottery_match_alphabet_options.hasOwnProperty(alphabet_line)) {
            const lottery_match_alphabet_options_pick_line = lottery_match_alphabet_options[alphabet_line];
            for (const lv in lottery_match_alphabet_options_pick_line) {
                if (lottery_match_alphabet_options_pick_line.hasOwnProperty(lv)) {
                    match_html += `<option value=${lv} ${lv == prize_match_line ? "selected" : ""}>${lottery_match_alphabet_options_pick_line[lv]}</option>`;
                }
            }
        }
        match_html += "</select>";

        html +=
            `<div class="wc-metabox closed lottery_prize_item lottery_prize_${id}" id="lottery_prize_handle_${id}">
                <h3 class="lottery_gray_wrapper lottery_prize_handler lottery_prize_wrapper_controller_reorder">
                    <a href="#" class="remove_row lottery_prize_wrapper_controller_remove delete" id="lottery_prize_remove_${id}">Remove</a>
                    <div class="handlediv" title="Click to toggle"></div>
                    <div class="tips sort lottery_prize_wrapper_controller_reorder" id="lottery_prize_reorder_${id}" data-tip="Drag and drop to set prize order"></div>
                    <label class="lottery_prize_wrapper_label lottery_prize_wrapper_label_${id}">#${order}</label>
                    <input type="text" class="short required lottery_prize_name_default" name="lottery_prize[]" value="${prize_name}" placeholder="First Prize" />
                </h3>
                <!--p class="form-field lottery_gray_wrapper">
                  <span class="lottery_prize_wrapper">
                     <label class="lottery_prize_wrapper_label lottery_prize_wrapper_label_${id}">#${order}</label>
                     <span class="lottery_prize_wrapper_content">
                         <span class="lottery_prize_wrapper_content_name">
                            <input type="text" class="short required lottery_prize_name_default" name="lottery_prize[]" value="${prize_name}" placeholder="First Prize" />
                         </span>
                         <span class="lottery_prize_wrapper_content_controller">
                             <a href="#" class="lottery_prize_wrapper_controller_remove" id="lottery_prize_remove_${id}">Remove</a>
                             <a href="#" class="lottery_prize_wrapper_controller_reorder" id="lottery_prize_reorder_${id}">&#9776;</a>
                             <a href="#" class="lottery_prize_wrapper_controller_collapse" id="lottery_prize_collapse_${id}">&#9650;</a>
                         </span>
                     </span>
                   </span>
                </p-->
                <div class="wc-metabox-content hidden lottery_prize_content_${id}">
                    <p class="form-field">
                        <span class="lottery_prize">
                            <span class="lottery_prize_point">
                                <span>
                                    <label for="_lottery_prize">Prize</label>
                                    <input type="number" class="short required lottery_prize_input" name="lottery_prize_point[]" value="${prize_point}" placeholder=""/>
                                    <span>&nbsp;points</span>
                                </span>
                                <span class="description">
                                    <br/><br/>Total point for winner of this prize
                                </span>
                            </span>
                            <span class="lottery_prize_gami_points">
                                ${gp_html}
                                <span class="description">
                                    <br/><br/>The point type use when payout for winners
                                </span>
                            </span>
                        </span>
                    </p>
                    <p class="form-field">
                        <span class="lottery_prize_match_wrapper">
                            <label class="lottery_prize_match_label">Match</label>
                            <span class="lottery_match_content">
                                <span class="lottery_match_alphabet">
                                    <span class="top_desc">Lucky Draw</span>
                                    <span>
                                        ${match_html}
                                    </span>
                                </span>`;
        var allow_pick_style = "";
        if (jQuery("#_lottery_bonus_enabled").val() == 0)
            allow_pick_style = ' style="display : none;"';
        html += `<span class="lottery_match_plus_symbol"` + allow_pick_style + `><br/>+</span>
                                <span class="lottery_match_bonus"` + allow_pick_style + `>
                                    <span class="top_desc">Bonus Number</span>
                                    <span>
                                        <select class="select short lottery_prize_match_bonus_select" name="lottery_prize_match_bonus[]" style="width:100%">`;
        if (jQuery("#_lottery_bonus_enabled").val() == 0) {
            html += '<option value=0 selected>None</option>';
            html += '<option value=1>1 Digit(L)</option>';
            html += '<option value=2>2 Digit(L)</option>';
        } else {
            if (jQuery("#_lottery_number_allow_pick").val() == 1) {
                html += `<option value=0 ${prize_match_bonus == 0 ? 'selected' : ''}>None</option>`;
                html += `<option value=1 ${prize_match_bonus == 1 ? 'selected' : ''}>1 Digit(L)</option>`;
            } else if (jQuery("#_lottery_number_allow_pick").val() == 2) {
                html += `<option value=0 ${prize_match_bonus == 0 ? 'selected' : ''}>None</option>`;
                html += `<option value=2 ${prize_match_bonus == 2 ? 'selected' : ''}>2 Digit(L)</option>`;
            }
        }

        html += `</select>
                                    </span>
                                </span>`;
        html += `</span>
                        </span>
                    </p>
                </div>
             </div>`;

        return html;
    }

    jQuery('.lottery_prizes').sortable({
        placeholder: "ui-state-highlight",
        handle: ".lottery_prize_wrapper_controller_reorder",
        stop: function (event, ui) {
            let items = [];
            jQuery('.lottery_prizes .lottery_prize_item').each(function (index, element) {
                let id = element.id.split('_')[3];
                items.push(id);
            });
            jQuery.ajax({
                type: 'post',
                url: ajaxurl,
                data: {
                    action: 'lottery_sort_prize',
                    prize_ids: items
                },
                success: function (response) {
                    let order = 1;
                    for (item of items) {
                        jQuery('.lottery_prize_wrapper_label_' + item).html('#' + order);
                        order++;
                    }
                }
            })
        }
    }).disableSelection();

    jQuery('.lottery_prizes').on('click', '.lottery_prize_wrapper_controller_remove', function (e) {
        e.preventDefault();
        console.log('test');
        let id = e.target.id.split('_')[3];
        console.log(id);
        jQuery.ajax({
            type: 'post',
            url: ajaxurl,
            data: {
                action: 'lottery_remove_prize',
                prize_id: id
            },
            beforeSend: function () {
                $(".lottery_prize_" + id).remove();
            },
            success: function (response) {
                if (response[0]) {
                    jQuery('.lottery_prize_' + id).remove();
                    for (const lp of response[1]) {
                        $(`.lottery_prize_wrapper_label_${lp.id}`).html(`#${lp.order}`);
                    }
                }
            }
        })
    });

    jQuery('.lottery_prizes').on('click', '.lottery_prize_wrapper_controller_reorder', function (e) {
        e.preventDefault();
        let id = e.target.id.split('_')[3];
        console.log(id);
    });

    jQuery('.lottery_prizes').on('click', '.lottery_prize_wrapper_controller_collapse', function (e) {
        e.preventDefault();
        let id = e.target.id.split('_')[3];
        // jQuery(".lottery_prize_content_" + id).toggle('highlight', 'swing', 0);
        if (!$(".lottery_prize_content_" + id).hasClass('collapsed')) {
            jQuery(".lottery_prize_content_" + id).hide(0);
            $(".lottery_prize_content_" + id).addClass('collapsed');
        } else {
            jQuery(".lottery_prize_content_" + id).show(10);
            $(".lottery_prize_content_" + id).removeClass('collapsed');
        }
        if (jQuery("#lottery_prize_collapse_" + id).html() == '▲')
            jQuery("#lottery_prize_collapse_" + id).html('&#9660;');
        else
            jQuery("#lottery_prize_collapse_" + id).html('&#9650;');

    });

    jQuery('#_lottery_add_prize').on('click', function (e) {
        e.preventDefault();
        var post_id = jQuery('#post_ID').val();
        var prize_name = jQuery('#_lottery_prize_name').val();
        var prize_point = jQuery('#_lottery_prize').val();
        var prize_point_type = jQuery('#_lottery_prize_gami_points').val();
        var prize_match_line = jQuery('#_lottery_match_alphabet').val();
        var prize_match_bonus = jQuery('#_lottery_match_bonus').val();
        var alphabet_line = jQuery('#_lottery_pick_number_alphabet').val();
        var bonus_allow_pick = jQuery("#_lottery_number_allow_pick").val();

        if (jQuery("#_lottery_bonus_enabled").val() == 0) {
            prize_match_bonus = 0;
        }

        jQuery.ajax({
            type: 'post',
            url: ajaxurl,
            data: {
                action: 'lottery_add_prize',
                post_id: post_id,
                prize_name: prize_name,
                prize_point: prize_point,
                prize_point_type: prize_point_type,
                prize_match_line: prize_match_line,
                prize_match_bonus: prize_match_bonus,
                alphabet_line: alphabet_line
            },
            success: function (response) {
                if (response) {
                    console.log('success');
                    console.log(response[0], response[1]);
                    let lottery_prize_content = jQuery(".lottery_prizes");
                    const add_html = add_lottery_prize(response[0], response[1], prize_name, prize_point, prize_point_type, prize_match_line, prize_match_bonus, alphabet_line)
                    lottery_prize_content.html(lottery_prize_content.html() + add_html);
                } else {
                    console.log('failed');
                }
            }
        });
    });

    jQuery('.lottery_add_winner_prize').on('click', function (e) {
        e.preventDefault();
        var prize_index = e.target.id.replace("add_prize_", "");
        var prize = jQuery('.prize_' + prize_index).html();
        var alphabets = jQuery('.alphabet_' + prize_index).html();
        var alphabet_line = jQuery('.alphabet_line_' + prize_index).html();
        var six_digit = jQuery('.six_digit_' + prize_index).html();
        var p_class_name = jQuery('.winner_prize_' + prize_index + ' p:last-child').attr('class');
        var max_p_count = 10000;
        jQuery('.winner_prize_' + prize_index + ' p:last-child').attr('class').split(' ').map(function (clssName) {
            if (clssName.startsWith("small_prize_")) {
                max_p_count = clssName.replace("small_prize_", "");
            }
        });
        jQuery('.winner_prize_' + prize_index).append('<p class="small_prize small_prize_' + (parseInt(max_p_count) + 1) + '">' +
            '<span class="lottery_winner_span_wrapper">' +
            '<span></span>' +
            '<span class="lottery_six_digit_panel">' +
            alphabets +
            '</span>' +
            '<span>' +
            '</span>' +
            '<span>' +
            '</span>' +
            '</span>' +
            '<span class="lottery_winner_span_wrapper">' +
            '<span></span>' +
            '<span class="lottery_six_digit_panel">' +
            six_digit +
            '</span>' +
            '<span>' +
            prize +
            '</span>' +
            '<button class="button button-primary lottery_remove_winner_prize" id="remove_prize_' + (parseInt(max_p_count) + 1) + '" style="width:50%;background-color: #dc3545;border-color: #dc3545;padding:0;">Remove</button>' +
            '</span>' +
            '<span class="lottery_winner_span_wrapper">' +
            '<span></span>' +
            '<span class="border_bottom_wrapper">' +
            alphabet_line +
            '</span>' +
            '<span class="border_bottom_wrapper"></span>' +
            '<span class="border_bottom_wrapper"></span>' +
            '</span></p>');
    });

    jQuery('#_lottery_prize_group_collapse').on('click', function (e) {
        e.preventDefault();
        if (!$(this).hasClass('collapsed')) {
            jQuery(".lottery_prize_content").hide(0);
            $(this).addClass('collapsed');
        } else {
            jQuery(".lottery_prize_content").show(10);
            $(this).removeClass('collapsed');
        }
    });

    jQuery('#_lottery_number_allow_pick').on('change', function (event) {
        if (jQuery("#_lottery_number_allow_pick").val() == 1) {
            $("#_lottery_match_bonus option[value='1']").remove();
            $("#_lottery_match_bonus option[value='2']").remove();
            $("#_lottery_match_bonus").append('<option value="1">1 Digit(L)</option>');
        } else {
            $("#_lottery_match_bonus option[value='1']").remove();
            $("#_lottery_match_bonus option[value='2']").remove();
            $("#_lottery_match_bonus").append('<option value="2">2 Digit(L)</option>');
        }
    });

    jQuery('#_lottery_bonus_enabled').on('change', function (event) {
        if (jQuery("#_lottery_bonus_enabled").val() == 1) {
            jQuery(".lottery_match_plus_symbol").show();
            jQuery(".lottery_match_bonus").show();
        } else {
            jQuery(".lottery_match_plus_symbol").hide();
            jQuery(".lottery_match_bonus").hide();
        }
        $('#_lottery_number_allow_pick').trigger('change');
    });

    $('input[type=radio][name=_lottery_bonus_number_range_type]').change(function () {
        if (this.value == '1') {
            jQuery("#_lottery_bonus_number_range_from").val(1);
            jQuery("#_lottery_bonus_number_range_from").prop("readonly", false);
            jQuery("#_lottery_bonus_number_range_to").val(42);
            jQuery("#_lottery_bonus_number_range_to").prop("readonly", false);
            jQuery("#_lottery_bonus_number_range_from, #_lottery_bonus_number_range_to").attr({
                "max": 42,
                "min": 1
            });
            jQuery("#_lottery_number_allow_pick").attr("disabled", false);
        } else if (this.value == '2') {
            jQuery("#_lottery_bonus_number_range_from").val(0);
            jQuery("#_lottery_bonus_number_range_from").prop("readonly", true);
            jQuery("#_lottery_bonus_number_range_to").val(99);
            jQuery("#_lottery_bonus_number_range_to").prop("readonly", true);
            jQuery("#_lottery_bonus_number_range_from, #_lottery_bonus_number_range_to").attr({
                "max": 99,
                "min": 0
            });
            jQuery("#_lottery_number_allow_pick").val(1);
            jQuery("#_lottery_number_allow_pick").attr("disabled", true);
        }
        $('#_lottery_number_allow_pick').trigger('change');
    });

    $(".payout").on('click', function (e) {
        e.preventDefault();
    });

    $(".return-payout").on('click', function (e) {
        e.preventDefault();
    });

    /**
     * -------------END
     */
});

jQuery(function ($) {
    $(document.body)
        .on('wc_add_error_tip_lottery', function (e, element, error_type) {
            var offset = element.position();

            if (element.parent().find('.wc_error_tip').size() === 0) {
                element.after('<div class="wc_error_tip ' + error_type + '">' + woocommerce_lottery[error_type] + '</div>');
                element.parent().find('.wc_error_tip')
                    .css('left', offset.left + element.width() - (element.width() / 2) - ($('.wc_error_tip').width() / 2))
                    .css('top', offset.top + element.height())
                    .fadeIn('100');
            }
        })
        .on('wc_remove_error_tip_lottery', function (e, element, error_type) {
            element.parent().find('.wc_error_tip.' + error_type).remove();
        })

        .on('keyup change', '#_max_tickets.input_text[type=number]', function () {
            var max_ticket_field = $(this), min_ticket_field;

            min_ticket_field = $('#_min_tickets');

            var max_ticket = parseInt(max_ticket_field.val());
            var min_ticket = parseInt(min_ticket_field.val());

            if (max_ticket <= min_ticket) {
                $(document.body).triggerHandler('wc_add_error_tip_lottery', [$(this), 'i18_max_ticket_less_than_min_ticket_error']);
            } else {
                $(document.body).triggerHandler('wc_remove_error_tip_lottery', [$(this), 'i18_max_ticket_less_than_min_ticket_error']);
            }
        })

        .on('keyup change focusout ', '#_lottery_num_winners.input_text[type=number]', function () {
            var lottery_num_winners_field = $(this);
            var lottery_winers = parseInt(lottery_num_winners_field.val());

            if (lottery_winers <= 0 || !lottery_winers) {
                $(document.body).triggerHandler('wc_add_error_tip_lottery', [$(this), 'i18_minimum_winers_error']);
            } else {
                $(document.body).triggerHandler('wc_remove_error_tip_lottery', [$(this), 'i18_minimum_winers_error']);
            }


            if (lottery_winers > 1) {
                $('._lottery_multiple_winner_per_user_field').show();
            } else {
                $('._lottery_multiple_winner_per_user_field').hide();
            }
        })
        .on('mouseenter', '.lottery_prize_handler', function () {
            jQuery(this).find('.sort').css("visibility", 'visible');
        })
        .on('mouseleave', '.lottery_prize_handler', function () {
            jQuery(this).find('.sort').css("visibility", 'hidden');
        })
    ;

});

jQuery(document).on('click', '.lottery_remove_winner_prize', function (e) {
    e.preventDefault();
    var prize_index = e.target.id.replace("remove_prize_", "");
    jQuery(this).closest('p.small_prize').remove();
});

function onPayout(id, user, pt, pp) {
    jQuery.ajax({
        type: "post",
        url: ajaxurl,
        data: {action: "payout_lottery_winner", id: id, user: user, pt: pt, pp: pp},
        success: function (response) {
            if (response == 1) {
                alert('Successfully paid');
                jQuery("#payout-" + id).hide();
                jQuery("#paid-" + id).show();
                jQuery("#return-" + id).show();
                // curent.parent().parent().addClass('deleted').fadeOut('slow');
            } else {
                alert('Error occured');
            }
        }
    });
}

function onReturnPayout(id, user, pt, pp) {
    jQuery.ajax({
        type: "post",
        url: ajaxurl,
        data: {action: "return_payout_lottery_winner", id: id, user: user, pt: pt, pp: pp},
        success: function (response) {
            if (response == 1) {
                alert('Successfully returned');
                jQuery("#payout-" + id).show();
                jQuery("#paid-" + id).hide();
                jQuery("#return-" + id).hide();
                // curent.parent().parent().addClass('deleted').fadeOut('slow');
            } else {
                alert('Error occured');
            }
        }
    });
}
