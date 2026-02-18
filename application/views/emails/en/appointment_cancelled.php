<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; direction: ltr; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #3D2814; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .details { background: white; padding: 15px; margin: 20px 0; border-left: 4px solid #654321; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Appointment Cancelled</h1>
        </div>

        <div class="content">
            <p>Dear <?php echo $customer_name; ?>,</p>

            <p>Your appointment has been cancelled.</p>

            <div class="details">
                <h3>Cancelled Appointment Details</h3>
                <p><strong>Doctor:</strong> <?php echo $provider_name; ?></p>
                <p><strong>Date:</strong> <?php echo $appointment_date; ?></p>
                <p><strong>Time:</strong> <?php echo $appointment_time; ?></p>
                <p><strong>Branch:</strong> <?php echo $branch_name; ?></p>
                <p><strong>Service:</strong> <?php echo $service_name; ?></p>

                <?php if (!empty($refund_amount)): ?>
                <p><strong>Refund:</strong> <?php echo $refund_currency; ?> <?php echo $refund_amount; ?></p>
                <?php endif; ?>
            </div>

            <p>If you did not request this cancellation or have any questions, please contact us at <?php echo $branch_phone; ?></p>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Expert Medical Center. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
