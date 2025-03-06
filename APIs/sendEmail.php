<?php 

namespace APIs;

class sendEmail{

    private $customer_name;
    private $customer_email;
    private $booking_date;
    private $address;
    private $booking_number;
    private $hotel_name;
    private $room_type;
    private $checkin_date;
    private $checkout_date;
    private $total_cost;
    private $payment_status;

    function __construct(){}

    function sendEmail(){
        
        $subject = "Your Booking Receipt";

        $message = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Booking Receipt</title>
            <style>
                body {
                    font-family: BlinkMacSystemFont, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
                    background-color: #f9f9f9;
                    color: #333;
                    padding: 0;
                    margin: 0;
                }
                .container {
                    max-width: 640px;
                    margin: 0 auto;
                    background-color: #ffffff;
                    border: 1px solid #e6e6e6;
                    border-radius: 8px;
                }
                .header-logo {
                    background-color: #003580;
                    border-radius: 4px;
                    padding: 16px;
                }
                .header {
                    font-size: 24px;
                    font-weight: 700;
                    color: #333;
                    margin: 16px;
                }
                .receipt-box {
                    border: 1px solid #E6E6E6;
                    border-radius: 4px;
                    margin: 16px;
                }
                .details {
                    padding: 16px;
                    font-size: 16px;
                    line-height: 24px;
                    color: #666;
                }
                .details strong {
                    color: #333;
                }
                .divider {
                    border-top: 1px solid #E6E6E6;
                    margin-top: 16px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header-logo'>
                    <img src='https://balkanea.com/wp-content/uploads/2022/04/Balkanea-logo-yelow.png' width='200' alt='Balkanea Logo'>
                </div>
                <h1 class='header'>This is your receipt</h1>
                <div class='receipt-box'>
                    <div class='details'>
                        <strong>Your details</strong>
                        <div class='divider'></div>
                        <table width='100%' cellpadding='2' cellspacing='2'>
                            <tr>
                                <td><strong>Name</strong></td>
                                <td>$this->customer_name</td>
                            </tr>
                            <tr>
                                <td><strong>Address</strong></td>
                                <td>$this->address</td>
                            </tr>
                            <tr>
                                <td><strong>Email</strong></td>
                                <td>$this->customer_email</td>
                            </tr>
                            <tr>
                                <td><strong>Date</strong></td>
                                <td>$this->booking_date</td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class='receipt-box'>
                    <div class='details'>
                        <strong>Booking Summary</strong>
                        <div class='divider'></div>
                        <table width='100%' cellpadding='2' cellspacing='2'>
                            <tr>
                                <td><strong>Booking number</strong></td>
                                <td>$this->booking_number</td>
                            </tr>
                            <tr>
                                <td><strong>Hotel Name</strong></td>
                                <td>$this->hotel_name</td>
                            </tr>
                            <tr>
                                <td><strong>Room Type</strong></td>
                                <td>$this->room_type</td>
                            </tr>
                            <tr>
                                <td><strong>Check-in Date</strong></td>
                                <td>$this->checkin_date</td>
                            </tr>
                            <tr>
                                <td><strong>Check-out Date</strong></td>
                                <td>$this->checkout_date</td>
                            </tr>
                            <tr>
                                <td><strong>Total Cost</strong></td>
                                <td>$this->total_cost</td>
                            </tr>
                            <tr>
                                <td><strong>Payment Status</strong></td>
                                <td>$this->payment_status</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </body>
        </html>
        ";

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        
        $headers .= "From: bookings@balkanea.com" . "\r\n";
        $headers .= "Reply-To: no-reply@balkanea.com" . "\r\n";
        
        if (mail($this->customer_email, $subject, $message, $headers)) {
            echo "Email sent successfully to $this->customer_email";
        } else {
            echo "Failed to send email.";
        }        

    }

}

?>