// File path: public_html/wp-content/themes/traveler/js/init
jQuery(function ($) {
    if ($(".st_template_checkout").length < 1) return;
    var old_order_id = !1;
    var new_nonce = !1;
    function st_validate_checkout(me) {
        me.find('.form_alert').addClass('hidden');
        var data = me.serializeArray();
        var dataobj = {};
        var form_validate = !0;
        for (var i = 0; i < data.length; ++i) {
            dataobj[data[i].name] = data[i].value
        }
        me.find('input.required,select.required,textarea.required').removeClass('error');
        me.find('input.required,select.required,textarea.required').each(function () {
            if (!$(this).val()) {
                $(this).addClass('error');
                form_validate = !1
            }
        });
        if (form_validate == !1) {
            me.find('.form_alert').addClass('alert-danger').removeClass('hidden');
            me.find('.form_alert').html(st_checkout_text.validate_form);
            return !1
        }
        if (!dataobj.term_condition && $('[name=term_condition]', me).length) {
            me.find('.form_alert').addClass('alert-danger').removeClass('hidden');
            me.find('.form_alert').html(st_checkout_text.error_accept_term);
            return !1
        }
        return !0
    }
    /* Start rewrite booking event */
    ;(function ($) {
        $.fn.STSendAjax = function (order_id, payment_information, free_cancelation_before) {
           
            
            this.each(function () {
                console.log(free_cancelation_before);
                var me = $(this);
                var button = $('.btn-st-checkout-submit', this);
                var data = me.serializeArray();
                data.push({name: 'action', value: 'booking_form_direct_submit'});
                data.push({name : 'partner_order_id', value: order_id});
                data.push({name: 'payment_information', value: payment_information})
                me.find('.form-control').removeClass('error');
                me.find('.form_alert').addClass('hidden');
                var dataobj = {};
                for (var i = 0; i < data.length; ++i) {
                    dataobj[data[i].name] = data[i].value
                }
                dataobj.order_id = old_order_id;
                var validate = st_validate_checkout(me);
                console.log("data obj: ");
                console.log(dataobj);
                if (!validate)
                    return !1;
                button.addClass('loading');
                
                // $.ajax({
                //     type: 'post',
                //     url: 'https://staging.balkanea.com/wp-plugin/APIs/order_booking_form.php',
                //     data:{
                //         'data': dataobj,
                //         'type': 'order_booking_finish'
                //     },
                //     success: (succ) => {
                //         order_status = succ;
                //         if (order_status !== 'ok'){
                //             button.removeClass('loading');
                //             window.history.go(-2);
                //         }
                        $.ajax({
                            type: 'post',
                            url: st_params.ajax_url,
                            data: dataobj,
                            dataType: 'json',
                            success: function (data) {
                            
                                $.ajax({
                                    type: 'get',
                                    url: 'https://staging.balkanea.com/wp-plugin/APIs/request_query.php',
                                    data: {
                                        data_order_id: data.order_id,
                                        partner_timestamp: order_id,
                                        free_cancelation: free_cancelation_before
                                    },
                                    success: (succ) => {
                                        console.log(succ);
                                    },
                                    error: (err) => {
                                        console.log(err);
                                        throw ("Record not saved");
                                    }
                                })

                                // if( $("input[name=st_payment_gateway]").length > 0 && $("input[name=st_payment_gateway]").val() == 'vina_stripe' && typeof(data.order_id) != 'undefined' && typeof(st_vina_stripe_params) != 'undefined' ){
                                //     var stripePublishKey = st_vina_stripe_params.vina_stripe.publishKey;
                                //     if(st_vina_stripe_params.vina_stripe.sanbox == 'sandbox'){
                                //         stripePublishKey = st_vina_stripe_params.vina_stripe.testPublishKey
                                //     }
                                //     var stripe = Stripe(stripePublishKey);
                                
                                //     if (typeof(data.payment_intent_client_secret) != 'undefined' && data.payment_intent_client_secret) {
                                        
                                //         stripe.handleCardAction(
                                //         data.payment_intent_client_secret
                                //         ).then(function(result) {
                                        
                                //         if (result.error) {
                                //             if (data.redirect_form) {
                                //                 setTimeout(function(){
                                //                     window.location.href = data.redirect_form;
                                //                 }, 3000);
                                                
                                //             }
                                //         } else {
                                        
                                //             $.ajax({
                                //                 url: st_params.ajax_url,
                                //                 dataType: 'json',
                                //                 type: 'POST',
                                //                 data: {
                                //                     'action' : 'vina_stripe_confirm_server',
                                //                     'st_order_id' : data.order_id,
                                //                     'payment_intent_id' : result.paymentIntent.id,
                                //                     'data_step2' : data,
                                //                 },
                                //                 beforeSend: function () {
                                //                 //handleServerResponse();
                                //                 },
                                //                 success: function (response_server) {
                                //                     console.log(response_server);
                                //                 },
                                //                 complete: function (jqXHR) {
                                //                     var data_response = jqXHR.responseJSON.data;
                                //                     if (typeof(data_response.order_id) != 'undefined' && data_response.order_id) {
                                //                         old_order_id = data_response.order_id
                                //                     }
                                //                     if (jqXHR.responseJSON.message) {
                                //                         me.find('.form_alert').addClass('alert-danger').removeClass('hidden');
                                //                         me.find('.form_alert').html(jqXHR.responseJSON.message)
                                //                     }
                                //                     if (data_response.redirect) {
                                //                         setTimeout(function(){
                                //                             window.location.href = data_response.redirect
                                //                         }, 3000);
                                                        
                                //                     }
                                //                     if (data_response.redirect_form) {
                                //                         $('body').append(data_response.redirect_form)
                                //                     }
                                //                     if (data_response.new_nonce) {
                                //                     }
                                //                     var widget_id = 'st_recaptchar_' + dataobj.item_id;
                                //                     get_new_captcha(me);
                                //                     button.removeClass('loading')
                                //                 },
                                //             });
                                //         }
                                //         });
                                //     }  else {
                                //         if (typeof(data.order_id) != 'undefined' && data.order_id) {
                                //             old_order_id = data.order_id
                                //         }
                                //         if (data.message) {
                                //             me.find('.form_alert').addClass('alert-danger').removeClass('hidden');
                                //             me.find('.form_alert').html(data.message)
                                //         }
                                //         if (typeof(data.order_id) != 'undefined' && data.order_id) {
                                //             old_order_id = data.order_id
                                //         }
                                //         if (data.message) {
                                //             me.find('.form_alert').addClass('alert-danger').removeClass('hidden');
                                //             me.find('.form_alert').html(data.message)
                                //         }
                                //         if (data.redirect) {
                                //             setTimeout(function(){
                                //                 window.location.href = data.redirect
                                //             }, 3000);
                                            
                                //         }
                                //         if (data.redirect_form) {
                                //             $('body').append(data.redirect_form)
                                //         }
                                //         if (data.new_nonce) {
                                //         }
                                //         var widget_id = 'st_recaptchar_' + dataobj.item_id;
                                //         get_new_captcha(me);
                                //         button.removeClass('loading')

                                //     }
                                // } else {
                                //     if (typeof(data.order_id) != 'undefined' && data.order_id) {
                                //         old_order_id = data.order_id
                                //     }
                                //     if (data.message) {
                                //         me.find('.form_alert').addClass('alert-danger').removeClass('hidden');
                                //         me.find('.form_alert').html(data.message)
                                //     }
                                //     if (data.redirect) {
                                //         // window.location.href = data.redirect
                                //     }
                                //     if (data.redirect_form) {
                                //         $('body').append(data.redirect_form)
                                //     }
                                //     if (data.new_nonce) {
                                //     }
                                //     var widget_id = 'st_recaptchar_' + dataobj.item_id;
                                //     get_new_captcha(me);
                                //     button.removeClass('loading')
                                // }
                            },
                            error: function (xhr, status, errorThrown) {
                                console.log(xhr);
                                if (xhr.responseJSON.message) {
                                    me.find('.form_alert').addClass('alert-danger').removeClass('hidden');
                                    me.find('.form_alert').html(xhr.responseJSON.message);
                                }
                                button.removeClass('loading');
                                get_new_captcha(me)
                            }
                        });
                //     },
                //     error: (err) => {
                //         button.removeClass('loading');
                //         console.log(err);
                //     }
                // })
            });
        };
        $.fn.STSendAjaxPackage = function (){
            this.each(function () {
                var me = $(this);
                var button = $('#st_submit_member_package', this);
                var data = me.serializeArray();
                data.push({name: 'action', value: 'booking_form_package_direct_submit'});
                me.find('.form-control').removeClass('error');
                me.find('.form_alert').addClass('hidden');
                var dataobj = {};
                for (var i = 0; i < data.length; ++i) {
                    dataobj[data[i].name] = data[i].value
                }
                dataobj.order_id = old_order_id;
                // var validate = st_validate_checkout(me);
                // if (!validate)
                //     return !1;
                button.addClass('loading');
                $.ajax({
                    type: 'post',
                    url: st_params.ajax_url,
                    data: dataobj,
                    dataType: 'json',
                    success: function (data) {
                        if(data.message){
                            $('#mpk-form .mt20').text(data.message);
                        }
                    },
                    error: function (e) {
                        button.removeClass('loading');
                        alert('Lost connect to server');
                        //get_new_captcha(me)
                    }
                });
            });
        };
    })(jQuery);
    $('.btn-st-checkout-submit').on('click', function (e) {
        console.log($("input[name=st_payment_gateway]"));
        e.preventDefault();
        var order_id = '';
        var payment_information = '';
        var free_cancelation_before = '';
        document.cookie.split(";").forEach((cookie) => {
            let [cookie_name, cookie_value] = cookie.split('=').map(part => part.trim());
        
            if (cookie_name === 'partner_order_id') {
                order_id = cookie_value;
            }

            if (cookie_name === order_id){
                payment_information = cookie_value;
            }
            
            if (cookie_name === 'free_cancelation'){
                free_cancelation_before = cookie_value;
            }

        });
        
        let json_payment_information = decodeURIComponent(payment_information);
        e.preventDefault();
        var form = $(this).closest('form');
        form.trigger('st_before_checkout');
        var payment = $('input[name="st_payment_gateway"]:checked', form).val();
        var wait_validate = $('input[name="wait_validate_' + payment + '"]', form).val();
        if (wait_validate === 'wait') {
            form.trigger('st_wait_checkout');
            return false;
        }
        console.log(free_cancelation_before);
        form.STSendAjax(order_id, json_payment_information, free_cancelation_before);
    });
    /*Checkout package ajax*/
    /* End start rewrite booking event */
    /*$('.btn-st-checkout-submit').on('click', function () {
        var button = $(this);
        var me = $('#cc-form');
        me.trigger('st_before_checkout');
        var data = me.serializeArray();
        alert('123');
        console.log(data);
        data.push({name: 'action', value: 'booking_form_direct_submit'});
        me.find('.form-control').removeClass('error');
        me.find('.form_alert').addClass('hidden');
        var dataobj = {};
        var form_validate = !0;
        for (var i = 0; i < data.length; ++i) {
            dataobj[data[i].name] = data[i].value
        }
        dataobj.order_id = old_order_id;
        var validate = st_validate_checkout(me);
        if (!validate) return !1;
        button.addClass('loading');
        $.ajax({
            type: 'post', url: st_params.ajax_url, data: dataobj, dataType: 'json', success: function (data) {
                if (typeof(data.order_id) != 'undefined' && data.order_id) {
                    old_order_id = data.order_id
                }
                if (data.message) {
                    me.find('.form_alert').addClass('alert-danger').removeClass('hidden');
                    me.find('.form_alert').html(data.message)
                }
                if (data.redirect) {
                    window.location.href = data.redirect
                }
                if (data.redirect_form) {
                    $('body').append(data.redirect_form)
                }
                if (data.new_nonce) {
                }
                var widget_id = 'st_recaptchar_' + dataobj.item_id;
                get_new_captcha(me);
                button.removeClass('loading')
            }, error: function (e) {
                button.removeClass('loading');
                alert('Lost connect to server');
                get_new_captcha(me)
            }
        })
    });*/
    function get_new_captcha(me) {
        var captcha_box = me.find('.captcha_box');
        url = captcha_box.find('.captcha_img').attr('src');
        captcha_box.find('.captcha_img').attr('src', url)
    }
    $('.payment-item-radio').on('ifChecked', function () {
        var parent = $(this).closest('li.payment-gateway');
        id = parent.data('gateway');
        parent.addClass('active').siblings().removeClass('active');
        $('.st-payment-tab-content .st-tab-content[data-id="' + id + '"]').siblings().fadeOut('fast');
        $('.st-payment-tab-content .st-tab-content[data-id="' + id + '"]').fadeIn('fast')
    })
})