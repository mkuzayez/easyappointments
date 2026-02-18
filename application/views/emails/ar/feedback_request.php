<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Arial', sans-serif; direction: rtl; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #3D2814; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9f9f9; }
        .details { background: white; padding: 15px; margin: 20px 0; border-right: 4px solid #654321; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .button { display: inline-block; padding: 12px 24px; background: #654321; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>كيف كانت زيارتك؟</h1>
        </div>

        <div class="content">
            <p>عزيزي/عزيزتي <?php echo $customer_name; ?>,</p>

            <p>شكراً لزيارتك مركز الخبراء الطبي. نأمل أن تكون تجربتك ممتازة.</p>

            <div class="details">
                <h3>تفاصيل زيارتك</h3>
                <p><strong>الطبيب:</strong> <?php echo $provider_name; ?></p>
                <p><strong>التاريخ:</strong> <?php echo $appointment_date; ?></p>
                <p><strong>الخدمة:</strong> <?php echo $service_name; ?></p>
            </div>

            <p>نقدّر ملاحظاتك. يرجى تخصيص لحظة لمشاركة تجربتك — فهي تساعدنا على تحسين خدماتنا.</p>

            <p style="text-align: center;">
                <a href="<?php echo $feedback_link; ?>" class="button">شاركنا رأيك</a>
            </p>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> مركز الخبراء الطبي. جميع الحقوق محفوظة.</p>
        </div>
    </div>
</body>
</html>
