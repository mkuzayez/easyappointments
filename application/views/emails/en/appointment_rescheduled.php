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
        .button { display: inline-block; padding: 12px 24px; background: #654321; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Appointment Rescheduled</h1>
        </div>

        <div class="content">
            <p>Dear <?php echo $customer_name; ?>,</p>

            <p>Your appointment has been rescheduled. Please see the new details below.</p>

            <div class="details">
                <h3>New Appointment Details</h3>
                <p><strong>Doctor:</strong> <?php echo $provider_name; ?></p>
                <p><strong>New Date:</strong> <?php echo $new_appointment_date; ?></p>
                <p><strong>New Time:</strong> <?php echo $new_appointment_time; ?></p>
                <p><strong>Branch:</strong> <?php echo $branch_name; ?></p>
                <p><strong>Service:</strong> <?php echo $service_name; ?></p>
            </div>

            <p style="text-align: center;">
                <a href="<?php echo $reschedule_link; ?>" class="button">Reschedule Again</a>
                <a href="<?php echo $cancel_link; ?>" class="button" style="background: #999;">Cancel</a>
            </p>

            <p>If you have any questions, please contact us at <?php echo $branch_phone; ?></p>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Expert Medical Center. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
