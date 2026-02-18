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
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px 12px; text-align: right; border-bottom: 1px solid #ddd; }
        th { background: #f0f0f0; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>تم تأكيد الطلب</h1>
        </div>

        <div class="content">
            <p>عزيزي/عزيزتي <?php echo $customer_name; ?>,</p>

            <p>تم تأكيد طلبك من الصيدلية وجاري تجهيزه.</p>

            <div class="details">
                <h3>تفاصيل الطلب</h3>

                <?php if (!empty($order_items)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>الدواء</th>
                            <th>الكمية</th>
                            <th>المجموع</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><?php echo $item['medicineNameAr'] ?? $item['medicine_name_ar'] ?? ''; ?></td>
                            <td><?php echo $item['quantity'] ?? 1; ?></td>
                            <td><?php echo number_format((float) ($item['lineTotal'] ?? $item['line_total'] ?? 0), 2); ?> <?php echo $currency; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>

                <p><strong>المجموع الكلي:</strong> <?php echo number_format((float) $total, 2); ?> <?php echo $currency; ?></p>

                <p><strong>طريقة الاستلام:</strong>
                    <?php if ($fulfillment_method === 'home_delivery'): ?>
                        توصيل منزلي
                        <?php if (!empty($delivery_address)): ?>
                            <br><small><?php echo $delivery_address; ?></small>
                        <?php endif; ?>
                    <?php else: ?>
                        استلام من العيادة
                        <?php if (!empty($branch_name)): ?>
                            - <?php echo $branch_name; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </p>
            </div>

            <p>سنقوم بإعلامك عندما يكون طلبك جاهزاً. إذا كان لديك أي استفسارات، يرجى التواصل مع فريق الصيدلية.</p>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> مركز الخبراء الطبي. جميع الحقوق محفوظة.</p>
        </div>
    </div>
</body>
</html>
