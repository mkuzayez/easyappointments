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
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f0f0f0; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Your Prescription is Ready</h1>
        </div>

        <div class="content">
            <p>Dear <?php echo $customer_name; ?>,</p>

            <p>Your doctor has prepared a prescription for you. You can now order your medicines online.</p>

            <div class="details">
                <h3>Prescription Details</h3>
                <p><strong>Doctor:</strong> <?php echo $provider_name; ?></p>
                <p><strong>Date:</strong> <?php echo $prescription_date; ?></p>

                <?php if (!empty($items)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Medicine</th>
                            <th>Quantity</th>
                            <th>Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo $item['medicineNameEn'] ?? $item['medicine_name_en'] ?? ''; ?></td>
                            <td><?php echo $item['quantity'] ?? 1; ?></td>
                            <td>AED <?php echo number_format((float) ($item['medicinePrice'] ?? $item['medicine_price'] ?? 0), 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <p style="text-align: center;">
                <a href="<?php echo $cart_url; ?>" class="button">View & Order Medicines</a>
            </p>

            <p>If you have any questions about your prescription, please contact your doctor or our pharmacy team.</p>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Expert Medical Center. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
