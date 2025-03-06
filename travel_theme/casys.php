<?php

if (!class_exists('is_available')) {
    class ST_Casys_Payment_Gateway extends STAbstactPaymentGateway
    {
        static private $_ints;

        private $default_status = true;

        private $_gatewayObject = null;

        private $_gateway_id = 'st_casys';

        function __construct()
        {
            add_filter('st_payment_gateway_st_casys_name', array($this, 'get_name'));
        }

        function get_option_fields()
        {
            return array(
                array(
                    'id' => 'skrill_email',
                    'label' => __('Email Address', 'traveler'),
                    'type' => 'text',
                    'section' => 'option_pmgateway',
                    'desc' => __('Your Skrill Email Address', 'traveler'),
                    'condition' => 'pm_gway_st_casys_enable:is(on)'
                ),
                array(
                    'id' => 'skrill_password',
                    'label' => __('Password', 'traveler'),
                    'type' => 'text',
                    'section' => 'option_pmgateway',
                    'desc' => __('Password', 'traveler'),
                    'condition' => 'pm_gway_st_casys_enable:is(on)'
                ),
                array(
                    'id' => 'skrill_enable_sandbox',
                    'label' => __('Enable Test Mode', 'traveler'),
                    'type' => 'on-off',
                    'section' => 'option_pmgateway',
                    'std' => 'on',
                    'desc' => __('Allow you to enable test mode', 'traveler'),
                    'condition' => 'pm_gway_st_casys_enable:is(on)'
                ),
            );
        }

        function _pre_checkout_validate()
        {
            return true;
        }

        function do_checkout($order_id)
        {
            // generate casys form
        }



        function complete_purchase($order_id)
        {
            return true;
        }

        function check_complete_purchase($order_id)
        {
           
        }

        function html()
        {
            echo st()->load_template('gateways/stripe');
        }

        function get_name()
        {
            return __('Casys', 'traveler');
        }

        function get_default_status()
        {
            return $this->default_status;
        }

        function is_available($item_id = false)
        {
            return true;
        }

        function getGatewayId()
        {
            return $this->_gateway_id;
        }

        function is_check_complete_required()
        {
            return true;
        }

        function get_logo()

        {
            return get_template_directory_uri() . '/img/gateway/skrill-logo.svg';
        }

        static function instance()
        {
            if (!self::$_ints) {
                self::$_ints = new self();
            }
            return self::$_ints;
        }

        static function add_payment($payment)
        {
            $payment['st_casys'] = self::instance();

            return $payment;
        }

    }

    add_filter('st_payment_gateways', ['ST_Casys_Payment_Gateway', 'add_payment']);
}