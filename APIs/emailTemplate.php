<?php
// Sample PHP data (Replace with actual booking details from your system)
$customer_name = "Nikola Petrovski";
$customer_email = "nikola_petrovski02@outlook.com";
$booking_date = date('M d,Y');
$address = "Address here"; // You can replace this with the customer's real address if available
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Receipt</title>
    <style>
        body {
            font-family: BlinkMacSystemFont, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            padding: 0;
            margin: 0;
        }
        .container {
            max-width: 640px;
            margin: 0 auto;
            /* padding: 16px; */
            background-color: #ffffff;
            border: 1px solid #e6e6e6;
            border-radius: 8px;
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
            margin-top: 16px;
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
        .header-logo{
            background-color: #003580;
            border-radius: 4px;
            padding: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-logo" >
            <img src="https://balkanea.com/wp-content/uploads/2022/04/Balkanea-logo-yelow.png" width="200" alt="Balkanea Logo">
        </div>
        <h1 class="header">This is your receipt</h1>

        <div class="receipt-box">
            <div class="details">
                <strong>Your details</strong>
                <div class="divider"></div>
                
                <table width="100%" cellpadding="2" cellspacing="2">
                    <tr>
                        <td><strong>Name</strong></td>
                        <td><?php echo $customer_name; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Address</strong></td>
                        <td><?php echo $address; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Email</strong></td>
                        <td><?php echo $customer_email; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Date</strong></td>
                        <td><?php echo $booking_date; ?></td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="receipt-box">
        <div class="details">
            <strong>Booking Summary</strong>
            <div class="divider"></div>
            
            <table width="100%" cellpadding="2" cellspacing="2">
                <tr>
                    <td><strong>Booking number</strong></td>
                    <td>123123</td>
                </tr>
                <tr>
                    <td><strong>Hotel Name</strong></td>
                    <td>Hotel Name</td>
                </tr>
                <tr>
                    <td><strong>Room Type</strong></td>
                    <td>Deluxe Suite</td>
                </tr>
                <tr>
                    <td><strong>Address</strong></td>
                    <td>Address</td>
                </tr>
                <tr>
                    <td><strong>Check-in Date</strong></td>
                    <td>Aug 28, 2024</td>
                </tr>
                <tr>
                    <td><strong>Check-out Date</strong></td>
                    <td>Aug 30, 2024</td>
                </tr>
                <tr>
                    <td><strong>Total Cost</strong></td>
                    <td>$500.00</td>
                </tr>
                <tr>
                    <td><strong>Payment Status</strong></td>
                    <td>Paid</td>
                </tr>
            </table>
        </div>
    </div>

    </div>
</body>
</html>
