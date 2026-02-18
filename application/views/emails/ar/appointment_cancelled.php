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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>تم إلغاء الموعد</h1>
        </div>

        <div class="content">
            <p>عزيزي/عزيزتي <?php echo $customer_name; ?>,</p>

            <p>تم إلغاء موعدك.</p>

            <div class="details">
                <h3>تفاصيل الموعد الملغي</h3>
                <p><strong>الطبيب:</strong> <?php echo $provider_name; ?></p>
                <p><strong>التاريخ:</strong> <?php echo $appointment_date; ?></p>
                <p><strong>الوقت:</strong> <?php echo $appointment_time; ?></p>
                <p><strong>الفرع:</strong> <?php echo $branch_name; ?></p>
                <p><strong>الخدمة:</strong> <?php echo $service_name; ?></p>

                <?php if (!empty($refund_amount)): ?>
                <p><strong>الاسترداد:</strong> <?php echo $refund_amount; ?> <?php echo $refund_currency; ?></p>
                <?php endif; ?>
            </div>

            <p>إذا لم تطلب هذا الإلغاء أو لديك أي استفسارات، يرجى الاتصال بنا على <?php echo $branch_phone; ?></p>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> مركز الخبراء الطبي. جميع الحقوق محفوظة.</p>
        </div>
    </div>
</body>
</html>
