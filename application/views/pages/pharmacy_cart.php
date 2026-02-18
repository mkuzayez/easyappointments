<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Cart - Expert Medical Center</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; color: #333; }
        .header { background: #3D2814; color: white; padding: 20px; text-align: center; }
        .header h1 { font-size: 24px; margin-bottom: 5px; }
        .header p { font-size: 14px; opacity: 0.8; }
        .container { max-width: 800px; margin: 20px auto; padding: 0 15px; }
        .card { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
        .card-header { background: #f8f8f8; padding: 15px 20px; border-bottom: 1px solid #eee; font-weight: bold; font-size: 16px; }
        .card-body { padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 10px; background: #f0f0f0; border-bottom: 2px solid #ddd; font-size: 13px; text-transform: uppercase; color: #666; }
        td { padding: 10px; border-bottom: 1px solid #eee; }
        .text-right { text-align: right; }
        .total-row td { font-weight: bold; font-size: 18px; border-top: 2px solid #3D2814; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 14px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }
        .fulfillment-toggle { display: flex; gap: 10px; margin-bottom: 15px; }
        .fulfillment-option { flex: 1; padding: 15px; border: 2px solid #ddd; border-radius: 8px; text-align: center; cursor: pointer; transition: all 0.2s; }
        .fulfillment-option.active { border-color: #654321; background: #faf5f0; }
        .fulfillment-option h4 { margin-bottom: 5px; }
        .fulfillment-option p { font-size: 12px; color: #666; }
        .btn-pay { display: block; width: 100%; padding: 15px; background: #654321; color: white; border: none; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer; transition: background 0.2s; }
        .btn-pay:hover { background: #3D2814; }
        .btn-pay:disabled { background: #ccc; cursor: not-allowed; }
        .dosage { font-size: 12px; color: #666; margin-top: 3px; }
        .hidden { display: none; }
        .error-msg { color: #dc3545; font-size: 13px; margin-top: 10px; }
        .loading { text-align: center; padding: 20px; color: #666; }
        @media (max-width: 600px) {
            .form-row { flex-direction: column; gap: 0; }
            .fulfillment-toggle { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Expert Medical Center</h1>
        <p>Pharmacy - Order Your Medicines</p>
    </div>

    <div class="container">
        <!-- Prescription Items -->
        <div class="card">
            <div class="card-header">Your Prescription</div>
            <div class="card-body" style="padding: 0;">
                <table>
                    <thead>
                        <tr>
                            <th>Medicine</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($item['medicine_name_en']); ?>
                                <br><small style="color: #888;"><?php echo htmlspecialchars($item['medicine_name_ar']); ?></small>
                                <?php if (!empty($item['dosage_notes'])): ?>
                                <div class="dosage"><?php echo htmlspecialchars($item['dosage_notes']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo (int) $item['quantity']; ?> <?php echo htmlspecialchars($item['medicine_unit']); ?></td>
                            <td>AED <?php echo number_format((float) $item['medicine_price'], 2); ?></td>
                            <td class="text-right">AED <?php echo number_format((float) $item['line_total'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="3">Total</td>
                            <td class="text-right">AED <?php echo number_format($total, 2); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Fulfillment Method -->
        <div class="card">
            <div class="card-header">Fulfillment Method</div>
            <div class="card-body">
                <div class="fulfillment-toggle">
                    <div class="fulfillment-option active" data-method="home_delivery" onclick="selectFulfillment('home_delivery')">
                        <h4>Home Delivery</h4>
                        <p>We'll deliver to your address</p>
                    </div>
                    <div class="fulfillment-option" data-method="in_clinic_pickup" onclick="selectFulfillment('in_clinic_pickup')">
                        <h4>Clinic Pickup</h4>
                        <p>Pick up from our branch</p>
                    </div>
                </div>

                <div id="delivery-fields">
                    <div class="form-group">
                        <label for="delivery_address">Delivery Address *</label>
                        <textarea id="delivery_address" placeholder="Enter your full delivery address"></textarea>
                    </div>
                </div>

                <div id="pickup-fields" class="hidden">
                    <div class="form-group">
                        <label for="branch_id">Select Branch *</label>
                        <select id="branch_id">
                            <option value="">-- Select Branch --</option>
                            <?php foreach ($branches as $branch): ?>
                            <option value="<?php echo (int) $branch['id']; ?>"><?php echo htmlspecialchars($branch['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Information -->
        <div class="card">
            <div class="card-header">Your Information</div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" value="<?php echo htmlspecialchars($customer['first_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" value="<?php echo htmlspecialchars($customer['last_name'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" value="<?php echo htmlspecialchars($customer['phone_number'] ?? ''); ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Pay Button -->
        <button class="btn-pay" id="btn-pay" onclick="proceedToPayment()">
            Proceed to Payment - AED <?php echo number_format($total, 2); ?>
        </button>
        <div id="error-message" class="error-msg hidden"></div>
    </div>

    <script>
        var currentMethod = 'home_delivery';
        var prescriptionHash = '<?php echo htmlspecialchars($hash); ?>';
        var baseUrl = '<?php echo rtrim($base_url, '/'); ?>';

        function selectFulfillment(method) {
            currentMethod = method;
            document.querySelectorAll('.fulfillment-option').forEach(function(el) {
                el.classList.remove('active');
            });
            document.querySelector('[data-method="' + method + '"]').classList.add('active');

            document.getElementById('delivery-fields').classList.toggle('hidden', method !== 'home_delivery');
            document.getElementById('pickup-fields').classList.toggle('hidden', method !== 'in_clinic_pickup');
        }

        function showError(msg) {
            var el = document.getElementById('error-message');
            el.textContent = msg;
            el.classList.remove('hidden');
        }

        function hideError() {
            document.getElementById('error-message').classList.add('hidden');
        }

        function proceedToPayment() {
            hideError();

            var firstName = document.getElementById('first_name').value.trim();
            var lastName = document.getElementById('last_name').value.trim();
            var email = document.getElementById('email').value.trim();
            var phone = document.getElementById('phone').value.trim();

            if (!firstName || !lastName || !email) {
                showError('Please fill in all required fields (First Name, Last Name, Email).');
                return;
            }

            var payload = {
                customerFirstName: firstName,
                customerLastName: lastName,
                customerEmail: email,
                customerPhone: phone,
                fulfillmentMethod: currentMethod,
                successUrl: window.location.origin + '/index.php/pharmacy/cart/' + prescriptionHash + '?payment=success',
                cancelUrl: window.location.origin + '/index.php/pharmacy/cart/' + prescriptionHash + '?payment=cancelled'
            };

            if (currentMethod === 'home_delivery') {
                var address = document.getElementById('delivery_address').value.trim();
                if (!address) {
                    showError('Please enter your delivery address.');
                    return;
                }
                payload.deliveryAddress = address;
            } else {
                var branchId = document.getElementById('branch_id').value;
                if (!branchId) {
                    showError('Please select a branch for pickup.');
                    return;
                }
                payload.branchId = parseInt(branchId);
            }

            var btn = document.getElementById('btn-pay');
            btn.disabled = true;
            btn.textContent = 'Processing...';

            fetch(baseUrl + '/index.php/api/v1/pharmacy/cart/' + prescriptionHash + '/order', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.checkoutUrl) {
                    window.location.href = data.checkoutUrl;
                } else {
                    showError(data.message || 'Failed to create order. Please try again.');
                    btn.disabled = false;
                    btn.textContent = 'Proceed to Payment - AED <?php echo number_format($total, 2); ?>';
                }
            })
            .catch(function(err) {
                showError('An error occurred. Please try again.');
                btn.disabled = false;
                btn.textContent = 'Proceed to Payment - AED <?php echo number_format($total, 2); ?>';
            });
        }

        // Handle payment return
        (function() {
            var params = new URLSearchParams(window.location.search);
            if (params.get('payment') === 'success' && params.get('session_id')) {
                document.querySelector('.container').innerHTML = '<div class="card"><div class="card-body loading">Verifying payment...</div></div>';

                fetch(baseUrl + '/index.php/api/v1/pharmacy/cart/verify-payment', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ sessionId: params.get('session_id') })
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.status === 'paid') {
                        document.querySelector('.container').innerHTML =
                            '<div class="card"><div class="card-body" style="text-align:center;padding:40px;">' +
                            '<h2 style="color:#28a745;margin-bottom:15px;">Payment Successful!</h2>' +
                            '<p>Your order has been confirmed. You will receive a confirmation email shortly.</p>' +
                            '<p style="margin-top:15px;color:#666;">Order Total: ' + data.currency + ' ' + data.amountTotal + '</p>' +
                            '</div></div>';
                    } else {
                        document.querySelector('.container').innerHTML =
                            '<div class="card"><div class="card-body" style="text-align:center;padding:40px;">' +
                            '<h2 style="color:#ffc107;">Payment Pending</h2>' +
                            '<p>Your payment is being processed. Please check your email for confirmation.</p>' +
                            '</div></div>';
                    }
                })
                .catch(function() {
                    document.querySelector('.container').innerHTML =
                        '<div class="card"><div class="card-body" style="text-align:center;padding:40px;">' +
                        '<p>Could not verify payment. Please contact support.</p>' +
                        '</div></div>';
                });
            } else if (params.get('payment') === 'cancelled') {
                showError('Payment was cancelled. You can try again.');
            }
        })();
    </script>
</body>
</html>
