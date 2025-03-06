<?php

class InsertProviderData{
    private $order_id;

    function __construct($order_id){
        $this->order_id = $order_id;
    }

    function insertProviderData(){
        
        $order_id = $this->order_id;

        // get cookie 
        error_log("Order Id" . $order_id);
        $partner_order_id = $_COOKIE['partner_order_id'];
        error_log('cookie: ' . $partner_order_id);
        $payment_details = $_COOKIE[$partner_order_id];
        error_log('cookie: ' . $payment_details);

    }

}

?>