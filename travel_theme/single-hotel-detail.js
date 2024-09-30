// File path: public_html/wp-content/themes/traveler/v3/js
;
(function ($) {
    // Format Date To YYYY-MM-DD

    function setCookie(name, value, days) {
        let expires = "";
        if (days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    }

    function formatDate(date) {

        if (date === null || date === '' || date === undefined) return -1;

        let date_years = date.split('/')[2];
        let date_months = date.split('/')[1];
        let date_days = date.split('/')[0];

        let _date = date_years + '-' + date_months + '-' + date_days;

        return _date;
    }

    function loadPage(page, hotel_id, data) {
        if ($(".pagination-container").length < 1) {
            const paginationContainer = $('<div class="pagination-container"></div>');
            $('.st-list-rooms').append(paginationContainer);
        }

        $('.fixed-on-mobile .loader-wrapper').show();
        $('.st-list-rooms .loader-wrapper').show();

        $.ajax({
            url: 'https://staging.balkanea.com/wp-plugin/APIs/search_hotel.php',
            type: 'GET',
            data: {
                "checkin": formatDate(data[5].value),
                "checkout": formatDate(data[6].value),
                "guests": JSON.stringify([{
                    "adults": data[9].value,
                    "children": [data[10].value]
                }]),
                "hotel_id": hotel_id,
                "page": page,
                "currency": document.querySelector('#dropdown-currency').innerText.trim()
            }
        }).done((response) => {

            $('.fixed-on-mobile .loader-wrapper').hide();
            $('.st-list-rooms .loader-wrapper').hide();

            if (response === 'No rooms') {
                $('.st-list-rooms .fetch div').hide();
                $('<div class="alert alert-danger" role="alert">Sorry! No available rooms found</div>').appendTo('.st-list-rooms .fetch');
                $('.st-list-rooms .loader-wrapper').hide();
                $('.fixed-on-mobile .loader-wrapper').hide();
                return;
            }

            let parsedResponse = JSON.parse(response);

            $('.st-list-rooms .fetch div').show();
            $('.st-list-rooms .fetch .alert').hide();

            $('.st-list-rooms .fetch').html(parsedResponse.html);
            $('.pagination-container').html(parsedResponse.pagination);
            $('[data-toggle="tooltip"]').tooltip();
            $('.st-list-rooms .loader-wrapper').hide();
            $('.fixed-on-mobile .loader-wrapper').hide();
            // loader.hide();
        }).fail((err) => {
            $('.st-list-rooms .fetch').html('<div class="alert alert-danger" role="alert">Oops! Something went wrong please try again later</div>');
            $('.st-list-rooms .loader-wrapper').hide();
            $('.fixed-on-mobile .loader-wrapper').hide();
            console.log(err);
        });
    }

    function getQueryParams(url) {
        const queryString = url.split('?')[1] || '';
        return new URLSearchParams(queryString);
    }

    function getParamFromUrl(param) {
        const currentUrl = window.location.href;

        const params = getQueryParams(currentUrl);

        if (params.has(param))
            var parameter_value = params.get(param);

        return parameter_value;
    }

    function getIP() {

        $.ajax({
            url: 'https://api.ipify.org?format=json',
            method: 'GET',
            success: (succ) => {
                console.log(succ);
            },
            error: (err) => {
                return err;
            }
        })

    }

    function orderBookingForm(data) {
        const book_hash = getParamFromUrl('book_hash');
        const price = getParamFromUrl('price');
    
        data.push({'book_hash' : book_hash});
        data.push({'price' : price});
    
        return $.ajax({
            url: 'https://staging.balkanea.com/wp-plugin/APIs/order_booking_form.php',
            method: 'post',
            data: {
                'type': 'order_booking_form',
                'data': data
            },
            dataType: 'json'
        });
    }

    "use strict";
    let SingleHotelDetail = {
        $body: $('body'),
        isValidated: {},
        renderHtmlHotel() {
            let number_room_search = $('input[name="room_num_search"]').val();
            let max_adult = $('input[name="adult_number"]').attr('data-max-adult');
            let max_child = $('input[name="child_number"]').attr('data-max-child');
            var data = jQuery('form.hotel-room-booking-form').serializeArray();
            jQuery('.loader-wrapper').hide();
            let priceWrapper = $('#st-price-render');
            data.push({
                name: 'security',
                value: st_params._s
            });
            for (var i = 0; i < data.length; i++) {

                if (data[i].name === 'action') {
                    data[i]['value'] = 'st_format_hotel_price';
                }
            };
            jQuery.ajax({
                method: "post",
                dataType: 'json',
                data: data,
                url: st_params.ajax_url,
                beforeSend: function () {
                    jQuery('.loader-wrapper').show();
                    jQuery('div.message-wrapper').html("");
                    jQuery('.message_box').html('');
                },
                success: function (response) {
                    jQuery('.loader-wrapper').hide();
                    if (response) {
                        if (response.price_html) {
                            if (jQuery('.form-head').length > 0) {
                                if (response.price_html) {
                                    jQuery('.form-head').html(response.price_html);
                                }
                            }
                            if (jQuery('.hotel-target-book-mobile').length > 0) {
                                jQuery('.hotel-target-book-mobile .price-wrapper').html(response.price_html);
                            }

                            if (priceWrapper.length) {
                                $('input[name="adult_number"]').attr('data-max', max_adult * number_room_search);
                                $('input[name="child_number"]').attr('data-max', max_child * number_room_search);
                                let priceWrapper = $('#st-price-render');
                                $('.number-night', priceWrapper).text(response.data.number_night);
                                $('.sale-price', priceWrapper).text(response.data.sale_price);
                                $('.total-price', priceWrapper).text(response.data.total_price);
                                priceWrapper.show();
                            }

                            jQuery('.message_box').html('');
                            jQuery('div.message-wrapper').html("");
                        } else {
                            if (priceWrapper.length) {
                                priceWrapper.hide();
                            }
                            if (response.message) {
                                jQuery('.message-wrapper').html(response.message);
                            }
                        }
                    }
                }
            });
        },
        init: function () {
            let base = this;
            base._resize(base.$body);
            base._share();
            base._stContent(base.$body);
            base._stReviewSingle();
            base._stMapSingle(base.$body);
            base._stcheckavailabilityHotel(base.$body);
            base._stReviewSingleRoom(base.$body);
            base._stToggleExtra(base.$body);
            base._stSentMessageToOwner();
            base._stSentMailInquiry();
            base._stDateFieldCheckInCheckOut(base.$body);
            base._bookingRoomAjax();
            base._bookingRoomChangeAjax();
            base._dropdownselectDate();
            base._popupGallery();
            base._putGuestName();
            base._scroll_reserve();
        },
        formatDateDdMmYy: function (input) {
            if (st_params.date_format == 'dd/mm/yyyy' || (st_params.date_format == 'dd-mm-yyyy')) {
                var datePart = input.match(/\d+/g),
                    day = datePart[0],
                    month = datePart[1],
                    year = datePart[2];
                return month + '/' + day + '/' + year;
            } else {
                return input;
            }
        },
        _dropdownselectDate: function () {
            let base = this;
            $('.form-date-search', 'body').each(function () {
                var parent = $(this),
                    date_wrapper = $('.date-wrapper', parent),
                    check_in_input = $('.check-in-input', parent),
                    check_out_input = $('.check-out-input', parent),
                    check_in_out = $('.check-in-out', parent),
                    check_in_render = $('.check-in-render', parent),
                    check_out_render = $('.check-out-render', parent);
                var timepicker = parent.data('timepicker');
                if (typeof timepicker == 'undefined' || timepicker == '') {
                    timepicker = false;
                } else {
                    timepicker = true;
                }

                var options = {
                    singleDatePicker: true,
                    sameDate: false,
                    autoApply: true,
                    opens: 'right',
                    minDate: new Date(),
                    dateFormat: 'YYYY-MM-DD',
                    customClass: '',
                    widthSingle: 500,
                    onlyShowCurrentMonth: true,
                    timePicker: timepicker,
                    timePicker24Hour: (st_params.time_format == '12h') ? false : true,
                };
                if (typeof locale_daterangepicker == 'object') {
                    options.locale = locale_daterangepicker;
                }

                if (check_in_input.val() != null && check_in_input.val() != '' && check_out_input.val() != null && check_out_input.val() != '') {
                    options.startDate = base.formatDateDdMmYy(check_in_input.val());
                    options.endDate = base.formatDateDdMmYy(check_out_input.val());
                }
                check_in_out.daterangepicker(options,
                    function (start, end, label) {
                        check_in_input.val(start.format(parent.data('format'))).trigger('change');
                        $('#tp_hotel .form-date-search .check-in-input').val(start.format('YYYY-MM-DD')).trigger('change');
                        check_in_render.html(start.format(parent.data('format'))).trigger('change');
                        check_out_input.val(end.format(parent.data('format'))).trigger('change');
                        $('#tp_hotel .form-date-search .check-out-input').val(end.format('YYYY-MM-DD')).trigger('change');
                        check_out_render.html(end.format(parent.data('format'))).trigger('change');
                        if (timepicker) {
                            check_in_input.val(start.format(parent.data('date-format'))).trigger('change');
                            $('.check-in-input-time', parent).val(start.format(parent.data('time-format'))).trigger('change');
                            check_out_input.val(end.format(parent.data('date-format'))).trigger('change');
                            $('.check-out-input-time', parent).val(end.format(parent.data('time-format'))).trigger('change');
                            $('.check-out-input-time', parent).val(end.format(parent.data('time-format'))).trigger('change');
                        }
                        check_in_out.trigger('daterangepicker_change', [start, end]);
                        if (window.matchMedia('(max-width: 767px)').matches) {
                            $('label', parent).hide();
                            $('.render', parent).show();
                            $('.check-in-wrapper span', parent).show();
                        }
                    }
                );

                date_wrapper.on('click', function (e) {
                    check_in_out.trigger('click');
                });
            });

        },
        _scroll_reserve: function () {
            function goToByScroll(id) {
                var id = id.replace("_link", "");
                // Scroll
                $('html,body').animate({
                    scrollTop: $("#" + id).offset().top
                }, 'slow');
            }
            $(".single .button_reserve").on('click', function (e) {
                // Prevent a page reload when a link is pressed
                e.preventDefault();
                // Call the scroll function
                goToByScroll($(this).attr("id"));
            });
        },
        _bookingRoomChangeAjax: function () {
            let base = this;
            $(".hotel-room-booking-form").on("hotel-room-booking-form", function (event) {
                SingleHotelDetail.renderHtmlHotel();
            });
            if ($('.hotel-room-booking-form').length > 0) {
                let flag = false;
                $('.form-date-hotel-room  .check-in-out').on('apply.daterangepicker', function () {
                    SingleHotelDetail.renderHtmlHotel();
                });
                if ($('.field-guest').length > 0) {
                    $('.field-guest').each(function () {
                        $(this).find('.form-control.st-input-number').on('change', function () {
                            SingleHotelDetail.renderHtmlHotel();
                        })
                    });
                }
                if ($('.form-more-extra .extras').length > 0) {
                    $('.form-more-extra .extras li').each(function () {
                        $(this).find('.extra-service-select').on('change', function () {
                            SingleHotelDetail.renderHtmlHotel();
                        })
                    });
                }
                if (flag) {
                    SingleHotelDetail.renderHtmlHotel();
                }

            }
        },
        _bookingRoomAjax: function () {
            $('form.hotel-room-booking-form').on('click', 'button.btn-book-ajax', function (e) {
                e.preventDefault();
                var form = $('form.hotel-room-booking-form');
                var data = $('form.hotel-room-booking-form').serializeArray();
                var loadingSubmit = form.find('button[name=submit]');
                $(loadingSubmit).find("i.fa-spin").removeClass("d-none");
                data.push({
                    name: 'security',
                    value: st_params._s
                });
                $('div.message-wrapper').html("");
                var booking_form;
                $.ajax({
                    url: st_params.ajax_url,
                    method: "post",
                    dataType: 'json',
                    data: data,
                    beforeSend: function () {
                        $('div.message-wrapper').html("");
                        booking_form = orderBookingForm(data);
                    },
                    success: function (res) {
                        $(loadingSubmit).find('i.fa-spin').addClass("d-none");
                        if (res) {
                            if (res.status) {
                                if (res.redirect && booking_form.responseText == 'ok') {
                                    window.location = res.redirect;
                                }
                            } else {
                                if (res.message && booking_form.responseText == 'erorr') {
                                    $('div.message-wrapper').html(res.message);
                                }
                            }
                        }
                    },
                    error: function (err) {
                        $('div.message-wrapper').html("");
                        $(loadingSubmit).find('i.fa-spin').addClass("d-none");
                    }
                });
            });
        },
        _stDateFieldCheckInCheckOut: function (body) {
            let base = this;
            $('.form-date-hotel-room', body).each(function () {
                var parent = $(this),
                    date_wrapper = $('.date-wrapper', parent),
                    check_in_input = $('.check-in-input', parent),
                    check_out_input = $('.check-out-input', parent),
                    check_in_out = $('.check-in-out', parent),
                    check_in_render = $('.check-in-render', parent),
                    check_out_render = $('.check-out-render', parent),
                    availabilityDate = $(this).data('availability-date');
                var minimum = check_in_out.data('minimum-day');
                if (typeof minimum !== 'number') {
                    minimum = 0;
                }
                var options = {
                    singleDatePicker: false,
                    autoApply: true,
                    disabledPast: true,
                    dateFormat: 'DD/MM/YYYY',
                    widthSingle: 500,
                    onlyShowCurrentMonth: true,
                    minimumCheckin: minimum,
                    classNotAvailable: ['disabled', 'off'],
                    enableLoading: true,
                    showEventTooltip: true,
                    fetchEvents: function (start, end, el, callback) {
                        var events = [];
                        if (el.flag_get_events) {
                            return false;
                        }
                        el.flag_get_events = true;
                        el.container.find('.loader-wrapper').show();
                        var data = {
                            action: check_in_out.data('action'),
                            start: start.format('YYYY-MM-DD'),
                            end: end.format('YYYY-MM-DD'),
                            post_id: check_in_out.data('room-id'),
                            security: st_params._s
                        };
                        $.post(st_params.ajax_url, data, function (respon) {
                            if (typeof respon === 'object') {
                                if (typeof respon.events === 'object') {
                                    events = respon.events;
                                } else {
                                    events = respon;
                                }
                            } else {
                                console.log('Can not get data');
                            }
                            callback(events, el);
                            el.flag_get_events = false;
                            el.container.find('.loader-wrapper').hide();
                        }, 'json');
                    }
                };

                if (typeof availabilityDate != 'undefined') {
                    options['minDate'] = availabilityDate;
                }

                if (typeof locale_daterangepicker == 'object') {
                    options.locale = locale_daterangepicker;
                }
                check_in_out.daterangepicker(options,
                    function (start, end, label) {
                        check_in_input.val(start.format(parent.data('format'))).trigger('change');
                        check_in_render.html(start.format(parent.data('format'))).trigger('change');
                        check_out_input.val(end.format(parent.data('format'))).trigger('change');
                        check_out_render.html(end.format(parent.data('format'))).trigger('change');
                        if (st_params.caculator_price_single_ajax === 'on') {
                            if ($('.single-st_rental .single-room-form').length > 0) {
                                date_wrapper.trigger('rental-booking-form');
                            }
                        }
                    });


                date_wrapper.on('click', function (e) {
                    check_in_out.trigger('click');

                });


                check_in_out.on('apply.daterangepicker', function (ev, picker) {
                    if (st_params.caculator_price_single_ajax === 'on') {
                        if ($('.single-hotel_room .single-room-form').length > 0) {
                            $('.hotel-room-booking-form').trigger('hotel-room-booking-form');
                        }
                    }

                });
            });

        },
        _stSentMailInquiry() {
            $('.form-st-send-mail .st_send-mail-form .sent-email-st').on('click', function (ev) {
                ev.preventDefault();
                var id_service = $("input[name=id_service]").val();
                var type_service = $("input[name=type_service]").val();
                var name_service = $("input[name=name_service]").val();
                var name_st = $("input[name=name_st]").val();
                var email_st = $("input[name=email_st]").val();
                var phone_st = $("input[name=phone_st]").val();
                var content_st = $("textarea[name=content_st]").val();
                var email_owl = $("input[name=email_owl]").val();
                $('.st-sent-mail-customer .loader-wrapper').show();
                $.ajax({
                    url: st_params.ajax_url,
                    type: "GET",
                    data: {
                        'action': "st_send_email_single_service",
                        'id_service': id_service,
                        'type_service': type_service,
                        'name_service': name_service,
                        'name_st': name_st,
                        'email_st': email_st,
                        'phone_st': phone_st,
                        'content_st': content_st,
                        'email_owl': email_owl
                    },
                    dataType: "json",
                    beforeSend: function () { },
                    error: function (jqXHR, textStatus, errorThrown) { },
                    success: function (res) {

                    },
                    complete: function (xhr, status) {

                        if (xhr.responseJSON.status != 0) {
                            var mess = '<div class="ccv-success"><div class="content-message">' + xhr.responseJSON.message + '</div></div>';
                            $('.form-st-send-mail .st_send-mail-form').html(mess);
                            $('.st-sent-mail-customer .loader-wrapper').hide();
                        } else {
                            var mess = '<div class="alert alert-danger"><button type="button" class="close" data-bs-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>' + xhr.responseJSON.message + '</div>';
                            $('.form-st-send-mail .message-wrapper-sendemail').html(mess);
                            $('.st-sent-mail-customer .loader-wrapper').hide();
                        }
                    }
                });
            });
        },
        _stSentMessageToOwner() {
            $(document).on("ready", function () {
                $('.single .st_ask_question #btn-send-message-owner').on('click', function (e) {
                    e.preventDefault();
                    jQuery(".btn-send-message").trigger('click');
                });
            })
        },
        _stToggleExtra(body) {
            $('.form-more-extra', body).each(function () {
                var t = $(this),
                    parent = t.closest('.form-more-extra');
                $('.dropdown', parent).on('click', function (ev) {
                    ev.preventDefault();
                    $('.extras', parent).slideToggle(200);
                    $('.arrow', parent).toggleClass('fa-caret-up fa-caret-down');
                });
            });
        },
        _stReviewSingleRoom: function (body) {
            $('.review-form .review-items .rates .fa').each(function () {
                var list = $(this).parent();
                listItems = list.children(), $('.st-list-rooms .fetch').html('<div class="alert alert-danger" role="alert">Oops! Something went wrong please try again later</div>');
                $('.st-list-rooms .loader-wrapper').hide();
                $('.fixed-on-mobile .loader-wrapper').hide();
                loader.hide();
                itemIndex = $(this).index(),
                    parentItem = list.parent();

                $(this).on('mouseenter', function () {
                    for (var i = 0; i < listItems.length; i++) {
                        if (i <= itemIndex) {
                            $(listItems[i]).addClass('hovered');
                        } else {
                            break;
                        }
                    }
                    $(this).on('click', function () {
                        for (var i = 0; i < listItems.length; i++) {
                            if (i <= itemIndex) {
                                $(listItems[i]).addClass('selected');
                            } else {
                                $(listItems[i]).removeClass('selected');
                            }
                        };
                        parentItem.children('.st_review_stats').val(itemIndex + 1);
                    });
                });

                $(this).on('mouseleave', function () {
                    listItems.removeClass('hovered');
                });
            });
            $('.review-form .st-stars .fa').each(function () {
                var list = $(this).parent(),
                    listItems = list.children(),
                    itemIndex = $(this).index(),
                    parentItem = list.parent();
                $(this).on('mouseenter', function () {
                    for (var i = 0; i < listItems.length; i++) {
                        if (i <= itemIndex) {
                            $(listItems[i]).addClass('hovered');
                        } else {
                            break;
                        }
                    }
                    $(this).on('click', function () {
                        for (var i = 0; i < listItems.length; i++) {
                            if (i <= itemIndex) {
                                $(listItems[i]).addClass('selected');
                            } else {
                                $(listItems[i]).removeClass('selected');
                            }
                        }
                        parentItem.children('.st_review_stats').val(itemIndex + 1);
                    });
                });
                $(this).on('mouseleave', function () {
                    listItems.removeClass('hovered');
                });
            });
        },
        _stcheckavailabilityHotel: function (body) {
            $('.form-check-availability-hotel .submit-group [name="submit"]', body).on('click', function (ev) {

                ev.preventDefault();

                var form = $(this).closest('.form-check-availability-hotel'),
                    parent = form.parent(),
                    loader = $('.loader-wrapper', parent),
                    message = $('.message-wrapper', form);
                ev.preventDefault();
                var has_fixed = form.closest('.fixed-on-mobile');
                if (has_fixed.hasClass('open')) {
                    has_fixed.removeClass('open').hide();
                    body.removeClass('st_overflow');
                }
                var data = form.serializeArray();
                data.push({
                    name: 'security',
                    value: st_params._s
                });
                var hotel_id;
                message.html('');
                loader.show();
                $('.fixed-on-mobile .loader-wrapper').show();
                $('.st-list-rooms .loader-wrapper').show();
                $('html, body').animate({ scrollTop: $('.st-list-rooms', body).offset().top - 150 }, 500);

                let current_url = window.location.href;
                hotel_id = current_url.split('/')[4];

                loadPage(1, hotel_id, data);

                $(document).on('click', '.pagination-link', function (e) {
                    e.preventDefault();
                    let page = $(this).data('page');
                    $('html, body').animate({ scrollTop: $('.st-list-rooms', body).offset().top - 100 }, 300);
                    loadPage(page, hotel_id, data);
                });

                //         $.ajax({
                //             url: 'https://staging.balkanea.com/wp-plugin/APIs/search_hotel.php',
                //             type: 'GET',
                //             data: {
                //                 "checkin": formatDate(data[5].value),
                //                 "checkout": formatDate(data[6].value),
                //                 "guests": JSON.stringify([{
                //                     "adults": data[9].value,
                //                     "children": [data[10].value]
                //                 }]),
                //                 "hotel_id": hotel_id,
                //             }
                //         }).done((succ) => {

                //             let book_information = {
                //                 'adult_number': data[9].value,
                //                 'child_number': data[10].value,
                //                 'checkin': data[5].value,
                //                 'checkout': data[6].value,
                //             }

                //             console.log(succ);

                //             if (succ == 'No rooms'){
                //                 $('.st-list-rooms .fetch div').hide();
                //                 $('<div class="alert alert-danger" role="alert">Sorry! No available rooms found</div>').appendTo('.st-list-rooms .fetch')
                //                 $('.st-list-rooms .loader-wrapper').hide();
                //                 $('.fixed-on-mobile .loader-wrapper').hide();
                //                 loader.hide();
                //                 return;
                //             }

                //             $('.st-list-rooms .fetch div').show();
                //             $('.st-list-rooms .fetch .alert').hide();

                //             $('.st-list-rooms .fetch').html(succ);
                //             $('[data-toggle="tooltip"]').tooltip();
                //             $('.st-list-rooms .loader-wrapper').hide();
                //             $('.fixed-on-mobile .loader-wrapper').hide();
                //             loader.hide();
                //         }).fail((err) => {
                //             $('.st-list-rooms .fetch').html('<div class="alert alert-danger" role="alert">Oops! Something went wrong please try again later</div>')
                //             $('.st-list-rooms .loader-wrapper').hide();
                //             $('.fixed-on-mobile .loader-wrapper').hide();
                //             loader.hide();
                //             console.log(err);
                //         })
                //         },

                // $.post(st_params.ajax_url, data, function (respon) {
                //     if (typeof respon == 'object') {
                //         if (respon.message) {
                //             message.html(respon.message);
                //         }
                //         $('.st-list-rooms .fetch').html(respon.html);
                //         console.log(respon);
                //         $('html, body').animate({
                //             scrollTop: $('.st-list-rooms', body).offset().top - 150
                //         }, 500);
                //         $('[data-toggle="tooltip"]').tooltip();
                //     }
                //     $('.st-list-rooms .loader-wrapper').hide();
                //     $('.fixed-on-mobile .loader-wrapper').hide();
                //     loader.hide();
                // }, 'json');
            });
            body.on('click', '.st-list-rooms .btn-show-price', function (ev) {
                ev.preventDefault();
                $('.form-check-availability-hotel .submit-group [name="submit"]', body).trigger('click');
            });
            body.on('click', '.st-list-rooms .btn-show-price', function (ev) {
                ev.preventDefault();
                $('.form-check-availability-hotel .submit-group [name="submit"]', body).trigger('click');
            });

            //Mobile
            $('.hotel-target-book-mobile .btn-mpopup', body).on('click', function (ev) {
                ev.preventDefault();
                $('.fixed-on-mobile', body).toggleClass('open').fadeToggle(300);
                $('.hotel-target-book-mobile', body).addClass('hide');
                $(body).addClass('st_overflow');
            });
            $('.fixed-on-mobile .close-icon', body).on('click', function (ev) {
                ev.preventDefault();
                $('.fixed-on-mobile', body).toggleClass('open').fadeToggle(300);
                $('.hotel-target-book-mobile', body).removeClass('hide');
                $(body).removeClass('st_overflow');
            });
        },
        initPano: function (lat_center, lng_center) {
            const panorama = new google.maps.StreetViewPanorama(
                document.getElementById("list_map"), {
                position: {
                    lat: lat_center,
                    lng: lng_center
                },
                addressControlOptions: {
                    position: google.maps.ControlPosition.BOTTOM_CENTER,
                },
                linksControl: false,
                panControl: false,
                enableCloseButton: false,
            }
            );
        },
        _stMapSingle: function (body) {
            if ($(".single-st_hotel").length || $(".single-st_tours").length || $(".single-st_activity").length || $(".single-st_rental").length || $(".single-st_cars").length ||
                $(".single-hotel_room").length
            ) {
                window.initPano = SingleHotelDetail.initPano;
                let is_mapbox = $('.google-map-mapbox').length;

                if (!is_mapbox) {
                    $('.map-view').on('click', function () {
                        $('.map-view-popup').fadeIn();
                    })
                    $('.map-view-popup.style-2', body).each(function () {
                        var parent = $(this),
                            mapEl = $('#list_map', parent),
                            mapData = mapEl.data('data_show'),
                            lat_center = mapEl.data('lat'),
                            lng_center = mapEl.data('lng'),
                            data_zoom = mapEl.data('zoom'),
                            mapIcon = mapEl.data('icon'),
                            street_views = mapEl.data('street_views');
                        if (street_views == 'on') {
                            window.initPano = SingleHotelDetail.initPano(lat_center, lng_center);
                        } else {
                            initMapDetail(mapEl, mapData, lat_center, lng_center, data_zoom, mapIcon);
                        }


                    });
                    $('.st-map', body).each(function () {
                        var parent = $(this),
                            mapEl = $('.google-map', parent),
                            mapData = mapEl.data('data_show'),
                            lat_center = mapEl.data('lat'),
                            lng_center = mapEl.data('lng'),
                            data_zoom = mapEl.data('zoom'),
                            mapIcon = mapEl.data('icon'),
                            street_views = mapEl.data('street_views');

                        if (street_views == 'on') {
                            window.initPano = SingleHotelDetail.initPano(lat_center, lng_center);
                        } else {
                            initMapDetail(mapEl, mapData, lat_center, lng_center, data_zoom, mapIcon);
                        }


                    });
                    var mapGobal;

                    function initMapDetail(mapEl, mapData, mapLat, mapLng, mapZoom, mapIcon, mapVersion = false, isMapMove = false) {
                        mapGobal = new google.maps.Map(mapEl.get(0), {
                            zoom: mapZoom,
                            center: {
                                lat: parseFloat(mapLat),
                                lng: parseFloat(mapLng)
                            },
                            disableDefaultUI: true
                        });
                        var popupPos = mapEl.data('popup-position');
                        if (mapData.length <= 0)
                            mapData = mapEl.data('data_show');
                        if (mapLat.length <= 0)
                            mapLat = mapEl.data('lat');
                        if (mapLng.length <= 0)
                            mapLng = mapEl.data('lng');
                        if (mapZoom.length <= 0)
                            mapZoom = typeof mapEl.data('zoom') !== 'undefined' ? mapEl.data('zoom') : 13;
                        if (mapIcon.length <= 0)
                            mapIcon = mapEl.data('icon');

                        if (typeof isMapMove === 'undefined')
                            isMapMove = false;

                        if (!isMapMove) {
                            mapGobal = new google.maps.Map(mapEl.get(0), {
                                zoom: mapZoom,
                                center: {
                                    lat: parseFloat(mapLat),
                                    lng: parseFloat(mapLng)
                                },
                                disableDefaultUI: true
                            });
                        }




                        if (typeof mapData != 'undefined' && Object.keys(mapData).length) {
                            var marker = [];
                            var ib = [];
                            var c = {};
                            var markerGolbal;
                            markerGolbal = jQuery.map(mapData, function (location, i) {

                                marker[i] = new google.maps.Marker({
                                    position: {
                                        lat: parseFloat(location.lat),
                                        lng: parseFloat(location.lng)
                                    },
                                    options: {
                                        icon: location.icon_mk,
                                        animation: isMapMove ? google.maps.Animation.NONE : google.maps.Animation.DROP
                                    },
                                    map: mapGobal
                                });

                                var ibOptions = {
                                    content: '',
                                    disableAutoPan: true,
                                    maxWidth: 0,
                                    pixelOffset: new google.maps.Size(-135, 20),
                                    zIndex: null,
                                    boxStyle: {
                                        padding: "0px 0px 0px 0px",
                                        width: "270px",
                                    },
                                    closeBoxURL: "",
                                    cancelBubble: true,
                                    infoBoxClearance: new google.maps.Size(1, 1),
                                    isHidden: false,
                                    pane: "floatPane",
                                    enableEventPropagation: true,
                                    alignBottom: true
                                };
                                if (window.matchMedia("(min-width: 768px)").matches) {
                                    if (popupPos == 'right') {
                                        ibOptions.pixelOffset = new google.maps.Size(35, -208);
                                        ibOptions.alignBottom = false;
                                    }
                                }
                                jQuery(window).on('resize', function () {
                                    if (window.matchMedia("(min-width: 768px)").matches) {
                                        if (popupPos == 'right') {
                                            ibOptions.pixelOffset = new google.maps.Size(35, -208);
                                            ibOptions.alignBottom = false;
                                        }
                                    }
                                });
                                if (location.lat != mapLat && location.lng != mapLng) {
                                    google.maps.event.addListener(marker[i], 'click', (function () {
                                        var source = location.content_html;
                                        var boxText = document.createElement("div");
                                        if (window.matchMedia("(min-width: 768px)").matches) {
                                            if (popupPos == 'right') {
                                                boxText.classList.add("right-box");
                                            }
                                        }
                                        jQuery(window).on('resize', function () {
                                            if (window.matchMedia("(min-width: 768px)").matches) {
                                                if (popupPos == 'right') {
                                                    boxText.classList.add("right-box");
                                                }
                                            } else {
                                                boxText.classList.remove("right-box");
                                            }
                                        });
                                        boxText.style.cssText = "border-radius: 5px; background: #fff; padding: 0px;";
                                        boxText.innerHTML = source;
                                        ibOptions.content = boxText;
                                        var ks = Object.keys(c);
                                        if (ks.length) {
                                            for (var j = 0; j < ks.length; j++) {
                                                c[ks[j]].close();
                                            }
                                        }
                                        ib[i] = new InfoBox(ibOptions);
                                        c[i] = ib[i];
                                        ib[i].open(mapGobal, this);
                                        mapGobal.panTo(ib[i].getPosition());
                                        google.maps.event.addListener(ib[i], 'domready', function () {
                                            var closeInfoBox = document.getElementById("close-popup-on-map");
                                            google.maps.event.addDomListener(closeInfoBox, 'click', function () {
                                                ib[i].close();
                                            });
                                        });
                                    }));
                                }


                                return marker[i];
                            });

                        }
                        var listener = google.maps.event.addListener(mapGobal, "idle", function () {
                            if (mapGobal.getZoom() > 16)
                                mapGobal.setZoom(16);
                            google.maps.event.removeListener(listener);
                        });

                    }
                } else {

                    function InitItemmap(item_map, key) {
                        var singleObj = {};
                        singleObj['type'] = 'Feature';
                        singleObj['geometry'] = {
                            type: 'Point',
                            coordinates: [item_map.lng, item_map.lat]
                        };
                        singleObj['properties'] = {
                            title: item_map.name,
                            description: item_map.content_html,
                            icon_mk: item_map.icon_mk,
                        };
                        return singleObj;

                    }
                    var body = 'body.single';
                    $('.st-map-box', body).each(function () {
                        //Start mapbox
                        if (!$('#st-content-wrapper').hasClass('singe-hotel-layout-4')) {
                            var parent = $(this),
                                mapEl = $('.google-map-mapbox', parent);
                            var mapData = mapEl.data('data_show');
                            mapboxgl.accessToken = st_params.token_mapbox;
                            var initRtlMapbox = null;
                            if (typeof st_params.text_rtl_mapbox === 'undefined' || st_params.text_rtl_mapbox === "") {
                                // mapboxgl.setRTLTextPlugin(st_params.text_rtl_mapbox);
                                initRtlMapbox = 1;
                            }
                            var listOfObjects = [];
                            jQuery.map(mapData, function (location, i) {
                                var item_map = InitItemmap(location, i);
                                listOfObjects.push(item_map);
                            });

                            const geojson = {
                                'type': 'FeatureCollection',
                                'features': listOfObjects
                            };

                            var map = new mapboxgl.Map({
                                container: "st-map",
                                style: "mapbox://styles/mapbox/streets-v10?optimize=true",
                                zoom: mapEl.data().zoom,
                                center: [mapEl.data().lng, mapEl.data().lat]
                            });


                            for (const marker of geojson.features) {

                                // Create a DOM element for each marker.
                                const el = document.createElement('div');
                                el.className = 'marker';
                                el.style.backgroundImage = `url(${marker.properties.icon_mk})`;

                                el.style.backgroundSize = '100%';
                                el.style.backgroundRepeat = 'no-repeat';
                                el.style.width = '40px';
                                el.style.height = '50px';
                                el.style.objectFit = 'contain';
                                el.className = 'marker';
                                const description = marker.properties.description;
                                // Add markers to the map.
                                new mapboxgl.Marker(el)
                                    .setLngLat(marker.geometry.coordinates)
                                    .setPopup(
                                        new mapboxgl.Popup({
                                            offset: [150, 150]
                                        }) // add popups
                                            .setHTML(
                                                `${description}`
                                            )
                                    )
                                    .addTo(map);
                            }
                            map.resize();
                        }

                        //End

                        $('.map-view').on('click', function () {
                            $(".map-view-popup").fadeIn("fast", function () {
                                $(this).trigger("fadeInComplete");
                            });
                            $('.map-view-popup').on("fadeInComplete", function () {
                                var parent = $(this),
                                    mapEl = $('.google-map-mapbox', parent);
                                var mapData = mapEl.data('data_show');
                                mapboxgl.accessToken = st_params.token_mapbox;
                                var initRtlMapbox = null;
                                if (typeof st_params.text_rtl_mapbox !== 'undefined' && st_params.text_rtl_mapbox !== '') {
                                    // mapboxgl.setRTLTextPlugin(st_params.text_rtl_mapbox);
                                    initRtlMapbox = 1;
                                }
                                var listOfObjects = [];
                                jQuery.map(mapData, function (location, i) {
                                    var item_map = InitItemmap(location, i);
                                    listOfObjects.push(item_map);
                                });

                                const geojson = {
                                    'type': 'FeatureCollection',
                                    'features': listOfObjects
                                };

                                var map = new mapboxgl.Map({
                                    container: "st-map",
                                    style: "mapbox://styles/mapbox/streets-v10?optimize=true",
                                    zoom: mapEl.data().zoom,
                                    center: [mapEl.data().lng, mapEl.data().lat]
                                });


                                for (const marker of geojson.features) {

                                    // Create a DOM element for each marker.
                                    const el = document.createElement('div');
                                    el.className = 'marker';
                                    el.style.backgroundImage = `url(${marker.properties.icon_mk})`;

                                    el.style.backgroundSize = '100%';
                                    el.style.backgroundRepeat = 'no-repeat';
                                    el.style.width = '40px';
                                    el.style.height = '50px';
                                    el.style.objectFit = 'contain';
                                    el.className = 'marker';
                                    const description = marker.properties.description;
                                    // Add markers to the map.
                                    var popup = new mapboxgl.Popup({
                                        offset: [150, 150]
                                    }) // add popups
                                        .setHTML(
                                            `${description}`
                                        );
                                    new mapboxgl.Marker(el)
                                        .setLngLat(marker.geometry.coordinates)
                                        .setPopup(
                                            popup
                                        )
                                        .addTo(map);

                                }
                                map.resize();

                            });



                        });

                    });


                }
            }

        },
        _stReviewSingle: function () {
            $('.review-form .review-items .rates i').each(function () {
                var list = $(this).parent(),
                    listItems = list.children(),
                    itemIndex = $(this).index(),
                    parentItem = list.parent();

                $(this).on('mouseenter', function () {
                    for (var i = 0; i < listItems.length; i++) {
                        if (i <= itemIndex) {
                            $(listItems[i]).addClass('hovered');
                        } else {
                            break;
                        }
                    }
                    $(this).on('click', function () {
                        for (var i = 0; i < listItems.length; i++) {
                            if (i <= itemIndex) {
                                $(listItems[i]).addClass('selected');
                            } else {
                                $(listItems[i]).removeClass('selected');
                            }
                        };
                        parentItem.children('.st_review_stats').val(itemIndex + 1);
                    });
                });

                $(this).on('mouseleave', function () {
                    listItems.removeClass('hovered');
                });
            });
            $('.review-form .st-stars i').each(function () {
                var list = $(this).parent(),
                    listItems = list.children(),
                    itemIndex = $(this).index(),
                    parentItem = list.parent();
                $(this).on('mouseenter', function () {
                    for (var i = 0; i < listItems.length; i++) {
                        if (i <= itemIndex) {
                            $(listItems[i]).addClass('hovered');
                        } else {
                            break;
                        }
                    }
                    $(this).on('click', function () {
                        for (var i = 0; i < listItems.length; i++) {
                            if (i <= itemIndex) {
                                $(listItems[i]).addClass('selected');
                            } else {
                                $(listItems[i]).removeClass('selected');
                            }
                        }
                        parentItem.children('.st_review_stats').val(itemIndex + 1);
                    });
                });
                $(this).on('mouseleave', function () {
                    listItems.removeClass('hovered');
                });
            });
        },
        _stContent: function (body) {
            //----------- Description Tour-------------------------------

            $('.st-description-more .stt-more').on('click', function () {
                $('.st-description-more').hide();
                $('.st-description-less').show();
            });
            $('.st-description-less .stt-less').on('click', function () {
                $('.st-description-less').hide();
                $('.st-description-more').show();
            });
            $('[data-show-all]', body).each(function () {
                var t = $(this);
                var height = t.data('height');
                t.css('height', height);
            });
            body.on('click', '[data-show-target]', function (ev) {
                ev.preventDefault();
                var target = $(this).data('show-target');
                $('.fa', this).toggleClass('fa-caret-up fa-caret-down');
                if ($('.fa', this).hasClass('fa-caret-up')) {
                    $('.text', this).html($(this).data('text-less'));
                } else {
                    $('.text', this).html($(this).data('text-more'));
                }
                if ($('[data-show-all="' + target + '"]', body).hasClass('open')) {
                    $('[data-show-all="' + target + '"]', body).css({
                        height: $('[data-show-all="' + target + '"]', body).data('height')
                    });
                } else {
                    $('[data-show-all="' + target + '"]', body).css({
                        height: ''
                    });
                }
                $('[data-show-all="' + target + '"]', body).toggleClass('open');
            });
            //----------- End Description Tour---------------------------
        },

        _putGuestName: function () {
            //--------------- Guest Name Inputs -------------------------

            var adultNumber = $('.field-guest input[name="adult_number"]');
            var childrenNumber = $('.field-guest input[name="child_number"]');
            var guestNameInput = $('.field-guest .guest_name_input');
            adultNumber.on('change', triggerGuestInputChange);
            childrenNumber.on('change', triggerGuestInputChange);

            function triggerGuestInputChange(e) {
                guestNameInput.trigger('guest-change', {
                    'adult': parseInt(adultNumber.val()),
                    'children': parseInt(childrenNumber.val()),
                });
            };

            guestNameInput.on('guest-change', function (e, number) {
                var adult = number.adult;
                var children = number.children;
                var hideAdult = $(this).data('hide-adult');
                var hideChildren = $(this).data('hide-children');
                var controlWraps = $(this).find('.guest_name_control');
                var controls = controlWraps.find('.control-item');
                if (isNaN(children)) {
                    children = 0;
                }

                if (hideAdult == 'on') {
                    adult = 0;
                }

                if (typeof hideChildren == 'undefined' || hideChildren != 'on') {
                    adult += children;
                }



                //adult-=1;// Only input guest >=2 name

                if (adult <= 0) {
                    $(this).addClass('d-none');
                } else {
                    // Append
                    for (var i = controls.length ? (controls.length) : 0; i < adult; i++) {
                        var div = $($('#guest_name_control_item').clone().html());
                        var p = div.find('input').attr('placeholder');
                        div.find('input').attr('placeholder', p.replace('%d', i + 1));

                        controlWraps.append(div);
                    }

                    // Remove
                    controls.each(function () {
                        if ($(this).index() > adult - 1) {
                            $(this).remove();
                        }
                    });

                    $(this).removeClass('d-none');
                }
            });

            triggerGuestInputChange();
            //------------------End Guest Name Inputs -------------------
        },
        _popupGallery: function () {

            if ($('.st-style-elementor').length) {
                $('.st-gallery-popup').on('click', function (e) {
                    e.preventDefault();
                    var gallery = $(this).attr('href');
                    $(gallery).magnificPopup({
                        delegate: 'a',
                        type: 'image',
                        closeMarkup: '<button class="mfp-close"><i class="stt-icon-close"></i></button>',
                        gallery: {
                            enabled: true,
                            arrowMarkup: '<button title="%title%" type="button" class="mfp-arrow stt-icon-arrow-%dir%"></button>',
                            tCounter: '%curr% / %total%'
                        },
                    }).magnificPopup('open');
                });

                $('.st-list-item-gallery').magnificPopup({
                    delegate: 'a', // child items selector, by clicking on it popup will open
                    type: 'image',
                    mainClass: 'stt-single-popup'
                    // other options
                });


            } else {
                if ($('.single-hotel_room').length || $('.single-st_hotel').length) {
                    $('.st-gallery-popup').on('click', function (e) {
                        e.preventDefault();
                        var gallery = $(this).attr('href');
                        $(gallery).magnificPopup({
                            delegate: 'a',
                            type: 'image',
                        }).magnificPopup('open');
                    });
                }

            }
            $(document).on('click', '.mfp-close', function (e) {
                e.preventDefault();
                $.magnificPopup.close();
            });
        },
        _share: function () {
            $('.shares .social-share').on('click', function (ev) {
                ev.preventDefault();
                $('.shares .share-wrapper').slideToggle(200);
            });
            if ($('.st-style-elementor').length) {
                $(".st-video-popup").each(function () {
                    $(this).magnificPopup({
                        type: 'iframe',
                        closeMarkup: '<button class="mfp-close"><i class="stt-icon-close"></i></button>',
                    })
                });
            } else {
                $(".st-video-popup").each(function () {
                    $(this).magnificPopup({
                        type: 'iframe'
                    })
                });
            }

            $(document).on('click', '.btn_add_wishlist', function (event) {
                event.preventDefault();
                var $this = $(this);
                $.ajax({
                    url: st_params.ajax_url,
                    type: "POST",
                    data: {
                        action: "st_add_wishlist",
                        data_id: $(this).data('id'),
                        data_type: $(this).data('type')
                    },
                    dataType: "json",
                }).done(function (html) {
                    $this.html(html.icon).attr("data-original-title", html.title)
                })
            });
        },
        _resize: function (body) {
            var timeout_fixed_item;
            $(window).on('resize', function () {
                clearTimeout(timeout_fixed_item);
                timeout_fixed_item = setTimeout(function () {

                    $('.st-hotel-content', 'body').each(function () {
                        var t = $(this);
                        $(window).on('scroll', function () {
                            if ($(window).scrollTop() >= 50 && window.matchMedia('(max-width: 991px)').matches) {
                                t.css('display', 'flex');
                            } else {
                                t.css('display', 'none');
                            }
                        });
                    });
                }, 1000);
            }).trigger('resize');
            //Slider gallery single hotel detail
            if (window.matchMedia('(min-width: 992px)').matches) {
                $('.st-gallery', body).each(function () {
                    var parent = $(this);
                    var $fotoramaDiv = $('.fotorama', parent).fotorama({
                        width: parent.data('width'),
                        nav: parent.data('nav'),
                        thumbwidth: '135',
                        thumbheight: '135',
                        allowfullscreen: parent.data('allowfullscreen')
                    });
                    parent.data('fotorama', $fotoramaDiv.data('fotorama'));
                });
            } else {
                $('.st-gallery', body).each(function () {
                    var parent = $(this);
                    if (typeof parent.data('fotorama') !== 'undefined') {
                        parent.data('fotorama').destroy();
                    }
                    var $fotoramaDiv = $('.fotorama', parent).fotorama({
                        width: parent.data('width'),
                        nav: parent.data('nav'),
                        thumbwidth: '80',
                        thumbheight: '80',
                        allowfullscreen: parent.data('allowfullscreen')
                    });
                    parent.data('fotorama', $fotoramaDiv.data('fotorama'));
                });
            }
            if (window.matchMedia('(min-width: 992px)').matches) {
                $('.full-map').show();
            } else {
                $('.full-map').hide();
            }
            if (window.matchMedia('(max-width: 991px)').matches) {
                $('.as').slideDown();
            }

        }
    }
    SingleHotelDetail.init();
})(jQuery);