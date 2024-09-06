;(function ($) {
    "use strict";

    let Header = {
        $body: $('body'),
        isValidated: {},
        init: function () {
            let base = this;
            base._choosepaymentCheckout(base.$body);
            base._toggleDetailInforBooking();
        },
        _toggleDetailInforBooking(){
            $('.info-section .detail button').on('click', function () {
                var parent = $(this).closest('.detail');
                $('.detail-list', parent).slideToggle();
            });
        },
        _choosepaymentCheckout: function(){
			if ( $( '.st-checkout-page' ).hasClass( 'style-1' ) ) {
				return;
			}
            if ($('.payment-form .payment-item').length) {
                $('.payment-form .payment-item').eq(0).find('.st-icheck-item input[type="radio"]').prop('checked', true);
                $('.payment-form .payment-item').eq(0).find('.dropdown-menu').slideDown();
            }
            $('.payment-form .payment-item').each(function (l, i) {
                var parent = $(this);
                $('.st-icheck-item input[type="radio"]', parent).on('change',function () {
                    $('.payment-form .payment-item .dropdown-menu').slideUp();
                    if ($(this).is(':checked')) {
                        if ($('.dropdown-menu', parent).length) {
                            $('.dropdown-menu', parent).slideDown();
                        }
                    }
                });
            });
        }
    }
    Header.init();

    $('.coupon-section form .btn').on('click',function (e) {
        e.preventDefault();
        var sform = $(this).closest('form');
        if ($('#field-coupon_code', sform).val() === '') {
            $('#field-coupon_code', sform).addClass('error');
        } else {
            $('#field-coupon_code', sform).removeClass('error');
            $(this).append('<i class="fa fa-spinner fa-spin"></i>');
            var data = {
                'action': 'apply_mdcoupon_function',
                'code': $('#field-coupon_code', sform).val()
            };
            $.post(st_params.ajax_url, data, function (respon, textStatus, xhr) {
                if (respon.status == 1) {
                    sform.trigger('submit');
                }else {
                    $('.coupon-section form .btn i').removeClass('fa-spinner');
                    sform.before("<div class='alert alert-danger'><p>" + respon.message + "</p></div>");
                }
            }, 'json');
        }
    });
})(jQuery);