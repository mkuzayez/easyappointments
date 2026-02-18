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
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f0f0f0; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Order Confirmed</h1>
        </div>

        <div class="content">
            <p>Dear <?php echo $customer_name; ?>,</p>

            <p>Your pharmacy order has been confirmed and is being processed.</p>

            <div class="details">
                <h3>Order Details</h3>

                <?php if (!empty($order_items)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Medicine</th>
                            <th>Qty</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><?php echo $item['medicineNameEn'] ?? $item['medicine_name_en'] ?? ''; ?></td>
                            <td><?php echo $item['quantity'] ?? 1; ?></td>
                            <td><?php echo $currency; ?> <?php echo number_format((float) ($item['lineTotal'] ?? $item['line_total'] ?? 0), 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>

                <p><strong>Total:</strong> <?php echo $currency; ?> <?php echo number_format((float) $total, 2); ?></p>

                <p><strong>Fulfillment:</strong>
                    <?php if ($fulfillment_method === 'home_delivery'): ?>
                        Home Delivery
                        <?php if (!empty($delivery_address)): ?>
                            <br><small><?php echo $delivery_address; ?></small>
                        <?php endif; ?>
                    <?php else: ?>
                        In-Clinic Pickup
                        <?php if (!empty($branch_name)): ?>
                            - <?php echo $branch_name; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </p>
            </div>

            <p>We will notify you when your order is ready. If you have any questions, please contact our pharmacy team.</p>
        </div>

        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> Expert Medical Center. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
