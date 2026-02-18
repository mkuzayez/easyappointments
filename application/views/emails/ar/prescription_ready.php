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
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px 12px; text-align: right; border-bottom: 1px solid #ddd; }
        th { background: #f0f0f0; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>وصفتك الطبية جاهزة</h1>
        </div>

        <div class="content">
            <p>عزيزي/عزيزتي <?php echo $customer_name; ?>,</p>

            <p>قام طبيبك بإعداد وصفة طبية لك. يمكنك الآن طلب أدويتك عبر الإنترنت.</p>

            <div class="details">
                <h3>تفاصيل الوصفة الطبية</h3>
                <p><strong>الطبيب:</strong> <?php echo $provider_name; ?></p>
                <p><strong>التاريخ:</strong> <?php echo $prescription_date; ?></p>

                <?php if (!empty($items)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>الدواء</th>
                            <th>الكمية</th>
                            <th>السعر</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo $item['medicineNameAr'] ?? $item['medicine_name_ar'] ?? ''; ?></td>
                            <td><?php echo $item['quantity'] ?? 1; ?></td>
                            <td><?php echo number_format((float) ($item['medicinePrice'] ?? $item['medicine_price'] ?? 0), 2); ?> د.إ</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <p style="text-align: center;">
                <a href="<?php echo $cart_url; ?>" class="button">عرض وطلب الأدوية</a>
            </p>

            <p>إذا كان لديك أي استفسارات حول وصفتك الطبية، يرجى التواصل مع طبيبك أو فريق الصيدلية.</p>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> مركز الخبراء الطبي. جميع الحقوق محفوظة.</p>
        </div>
    </div>
</body>
</html>
