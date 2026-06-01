<?php
require_once '../../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$booking_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT b.*, pr.room_name, p.property_name, p.owner_id, p.street_address, p.city, p.district
    FROM bookings b 
    JOIN property_rooms pr ON b.room_id = pr.id 
    JOIN properties p ON b.property_id = p.id 
    WHERE b.id = ?
");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

if (!$booking || ($booking['owner_id'] != $user_id && $booking['user_id'] != $user_id && $_SESSION['role'] !== 'admin')) {
    die("Booking not found or access denied.");
}

$is_owner = ($booking['owner_id'] == $user_id || $_SESSION['role'] === 'admin');

if ($is_owner && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_payment'])) {
        $payment_amount = (float)($_POST['payment_amount'] ?? 0);
        if ($payment_amount > 0) {
            $pdo->prepare("INSERT INTO booking_payments (booking_id, amount) VALUES (?, ?)")
                ->execute([$booking_id, $payment_amount]);

            $pdo->prepare("UPDATE bookings SET amount_paid = amount_paid + ? WHERE id = ?")
                ->execute([$payment_amount, $booking_id]);

            $pdo->prepare("UPDATE bookings SET payment_status = CASE WHEN amount_paid >= total_price THEN 'complete' ELSE 'pending' END WHERE id = ?")
                ->execute([$booking_id]);
        }

        header("Location: booking_details.php?id=" . $booking_id);
        exit();
    }

    if (isset($_POST['add_expense'])) {
        $expense_desc = trim($_POST['expense_desc'] ?? '');
        $expense_amount = (float)($_POST['expense_amount'] ?? 0);

        if ($expense_desc !== '' && $expense_amount > 0) {
            $pdo->prepare("INSERT INTO booking_expenses (booking_id, description, amount) VALUES (?, ?, ?)")
                ->execute([$booking_id, $expense_desc, $expense_amount]);

            $pdo->prepare("UPDATE bookings SET total_price = total_price + ? WHERE id = ?")
                ->execute([$expense_amount, $booking_id]);

            $pdo->prepare("UPDATE bookings SET payment_status = CASE WHEN amount_paid >= total_price THEN 'complete' ELSE 'pending' END WHERE id = ?")
                ->execute([$booking_id]);
        }

        header("Location: booking_details.php?id=" . $booking_id);
        exit();
    }
}

$exp_stmt = $pdo->prepare("SELECT * FROM booking_expenses WHERE booking_id = ? ORDER BY created_at DESC");
$exp_stmt->execute([$booking_id]);
$expenses = $exp_stmt->fetchAll();

$pay_stmt = $pdo->prepare("SELECT * FROM booking_payments WHERE booking_id = ? ORDER BY created_at DESC");
$pay_stmt->execute([$booking_id]);
$payments = $pay_stmt->fetchAll();

$nights = (strtotime($booking['check_out_date']) - strtotime($booking['check_in_date'])) / 86400;
if ($nights <= 0) $nights = 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $booking['id']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #f8fafc; 
            color: #111827; 
            margin: 0;
            padding: 0;
            line-height: 1.5;
        }
        .app-header {
            background-color: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            padding: 0.875rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .app-header-logo {
            font-weight: 800;
            color: #003580;
            font-size: 1.25rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .app-header-logo span {
            color: #111827;
        }
        .header-actions {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.625rem 1.25rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            border: 1px solid transparent;
        }
        .btn-back {
            color: #4b5563;
            background-color: transparent;
        }
        .btn-back:hover {
            color: #111827;
            background-color: #f3f4f6;
        }
        .btn-outline-blue {
            color: #003580;
            background-color: #ffffff;
            border-color: #003580;
        }
        .btn-outline-blue:hover {
            background-color: #e6f0fa;
        }
        .btn-solid-blue {
            color: #ffffff;
            background-color: #003580;
            border-color: #003580;
        }
        .btn-solid-blue:hover {
            background-color: #002560;
            border-color: #002560;
        }
        .invoice-wrapper {
            max-width: 900px;
            margin: 2.5rem auto;
            padding: 0 1.5rem;
        }
        .invoice-container {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 3rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.01);
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #111827;
            padding-bottom: 2rem;
            margin-bottom: 2rem;
        }
        .invoice-brand {
            font-size: 1.75rem;
            font-weight: 800;
            color: #111827;
            margin: 0;
        }
        .invoice-brand-subtitle {
            color: #4b5563;
            font-size: 0.875rem;
            margin-top: 0.35rem;
        }
        .invoice-meta {
            text-align: right;
        }
        .invoice-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #003580;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 0;
        }
        .invoice-meta-item {
            font-size: 0.875rem;
            color: #4b5563;
            margin-top: 0.25rem;
        }
        .invoice-status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            margin-top: 0.5rem;
        }
        .badge-pending {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .badge-confirmed {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .badge-checked_in {
            background-color: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        .badge-checked_out {
            background-color: #ede9fe;
            color: #5b21b6;
            border: 1px solid #ddd6fe;
        }
        .badge-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        @media (max-width: 640px) {
            .details-grid {
                grid-template-columns: 1fr;
            }
        }
        .invoice-fieldset {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 1.25rem 1.5rem;
            background-color: #ffffff;
            margin: 0;
        }
        .invoice-legend {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #003580;
            padding: 0 0.5rem;
            background-color: #ffffff;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.8125rem;
        }
        .info-row:last-child {
            margin-bottom: 0;
        }
        .info-label {
            color: #4b5563;
        }
        .info-value {
            font-weight: 600;
            color: #111827;
        }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin: 2rem 0;
        }
        .invoice-table th {
            border-bottom: 2px solid #111827;
            padding: 0.75rem 0.5rem;
            text-align: left;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #111827;
            font-weight: 700;
        }
        .invoice-table td {
            padding: 1rem 0.5rem;
            border-bottom: 1px solid #e5e7eb;
            font-size: 0.8125rem;
            color: #374151;
        }
        .invoice-table th.text-right, .invoice-table td.text-right {
            text-align: right;
        }
        .invoice-totals {
            margin-left: auto;
            width: 50%;
            margin-bottom: 3rem;
        }
        @media (max-width: 640px) {
            .invoice-totals {
                width: 100%;
            }
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            font-size: 0.8125rem;
        }
        .total-row.grand-total {
            border-top: 2px solid #111827;
            border-bottom: 2px solid #111827;
            font-size: 1.125rem;
            font-weight: 800;
            color: #003580;
            padding: 0.75rem 0;
            margin-top: 0.5rem;
        }
        .signatures-container {
            display: flex;
            justify-content: space-between;
            margin-top: 4rem;
            margin-bottom: 2rem;
            gap: 2rem;
        }
        .signature-box {
            width: 200px;
            border-top: 1px solid #9ca3af;
            text-align: center;
            padding-top: 0.5rem;
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #6b7280;
            font-weight: 700;
        }
        .invoice-footer {
            border-top: 1px solid #e5e7eb;
            padding-top: 1.5rem;
            text-align: center;
            margin-top: 3rem;
        }
        .invoice-footer-brand {
            font-weight: 800;
            color: #003580;
            font-size: 0.875rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .invoice-footer-brand span {
            color: #111827;
        }
        .invoice-footer-studio {
            font-size: 0.65rem;
            color: #9ca3af;
            margin-top: 0.35rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(2px);
            z-index: 100;
        }
        .modal-container {
            position: fixed;
            top: 40%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
            z-index: 101;
            overflow: hidden;
        }
        .modal-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f9fafb;
        }
        .modal-title {
            font-size: 1rem;
            font-weight: 800;
            color: #003580;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .modal-close {
            background: none;
            border: none;
            color: #9ca3af;
            font-size: 1.5rem;
            cursor: pointer;
            transition: color 0.2s;
            line-height: 1;
        }
        .modal-close:hover {
            color: #111827;
        }
        .modal-body {
            padding: 1.25rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-label {
            display: block;
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #4b5563;
            margin-bottom: 0.375rem;
        }
        .form-input {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 0.8125rem;
            color: #111827;
            background-color: #ffffff;
            box-sizing: border-box;
        }
        .form-input:focus {
            border-color: #003580;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 53, 128, 0.15);
        }
        .btn-submit {
            width: 100%;
            padding: 0.625rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #ffffff;
            background-color: #003580;
            border: 1px solid #003580;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-submit:hover {
            background-color: #002560;
        }
        
        @media print {
            body {
                background-color: #ffffff;
                color: #000000;
                font-size: 10pt;
            }
            .no-print {
                display: none !important;
            }
            .invoice-wrapper {
                margin: 0;
                padding: 0;
                max-width: 100%;
            }
            .invoice-container {
                border: none;
                box-shadow: none;
                padding: 0;
            }
            .invoice-legend {
                background-color: #ffffff !important;
            }
        }
    </style>
</head>
<body>
    <?php if (isset($_GET['download'])): ?>
        <script>
            window.addEventListener('load', () => {
                window.print();
            });
        </script>
    <?php endif; ?>

    <!-- Consistent Top Header -->
    <header class="app-header no-print">
        <a href="bookings.php?property_id=<?php echo $booking['property_id']; ?>" class="app-header-logo">
            <i class="fas fa-hotel"></i> Booking<span>jaunt</span>
        </a>
        <div class="header-actions">
            <a href="bookings.php?property_id=<?php echo $booking['property_id']; ?>" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <?php if ($is_owner): ?>
                <button onclick="openPaymentModal()" class="btn btn-outline-blue">
                    <i class="fas fa-plus"></i> Add Payment
                </button>
                <button onclick="openExpenseModal()" class="btn btn-outline-blue">
                    <i class="fas fa-receipt"></i> Add Expense
                </button>
            <?php endif; ?>
            <button onclick="window.print()" class="btn btn-solid-blue">
                <i class="fas fa-download"></i> Download / Print
            </button>
        </div>
    </header>

    <div class="invoice-wrapper">
        <div class="invoice-container">
            <!-- Property & Invoice Info -->
            <div class="invoice-header">
                <div>
                    <h1 class="invoice-brand"><?php echo htmlspecialchars($booking['property_name']); ?></h1>
                    <p class="invoice-brand-subtitle"><?php echo htmlspecialchars($booking['street_address'] . ', ' . $booking['city'] . ', ' . $booking['district']); ?></p>
                </div>
                <div class="invoice-meta">
                    <h2 class="invoice-title">Invoice #<?php echo $booking['id']; ?></h2>
                    <p class="invoice-meta-item">Issued on <?php echo date('M d, Y', strtotime($booking['created_at'])); ?></p>
                    <span class="invoice-status-badge badge-<?php echo $booking['status']; ?>">
                        <?php echo str_replace('_', ' ', $booking['status']); ?>
                    </span>
                </div>
            </div>

            <!-- Billing & Stay Info using Fieldsets and Legends -->
            <div class="details-grid">
                <fieldset class="invoice-fieldset">
                    <legend class="invoice-legend"><i class="fas fa-user-circle"></i> Guest Details</legend>
                    <div class="info-row">
                        <span class="info-label">Name</span>
                        <span class="info-value"><?php echo htmlspecialchars($booking['guest_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone</span>
                        <span class="info-value"><?php echo htmlspecialchars($booking['guest_phone']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?php echo htmlspecialchars($booking['guest_email'] ?: 'N/A'); ?></span>
                    </div>
                </fieldset>

                <fieldset class="invoice-fieldset">
                    <legend class="invoice-legend"><i class="fas fa-calendar-alt"></i> Stay Details</legend>
                    <div class="info-row">
                        <span class="info-label">Room Type</span>
                        <span class="info-value"><?php echo htmlspecialchars($booking['room_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Check-In</span>
                        <span class="info-value"><?php echo date('M d, Y', strtotime($booking['check_in_date'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Check-Out</span>
                        <span class="info-value"><?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Duration</span>
                        <span class="info-value"><?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?></span>
                    </div>
                </fieldset>

                <!-- Payment History Details inside a fieldset -->
                <fieldset class="invoice-fieldset" style="grid-column: span 2;">
                    <legend class="invoice-legend"><i class="fas fa-history"></i> Payment History</legend>
                    <?php if (empty($payments)): ?>
                        <p style="font-size: 0.8125rem; color: #6b7280; font-style: italic; margin: 0; text-align: center; padding: 0.5rem 0;">No payments recorded yet.</p>
                    <?php else: ?>
                        <table style="width: 100%; font-size: 0.8125rem; border-collapse: collapse;">
                            <thead>
                                <tr style="border-bottom: 1px solid #e5e7eb; text-align: left;">
                                    <th style="padding: 0.5rem; font-weight: 700; color: #374151; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em;">Date & Time</th>
                                    <th style="padding: 0.5rem; font-weight: 700; color: #374151; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; text-align: right;">Amount Paid (LKR)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $pay): ?>
                                    <tr style="border-bottom: 1px dashed #f3f4f6;">
                                        <td style="padding: 0.5rem; color: #4b5563;"><?php echo date('M d, Y - h:i A', strtotime($pay['created_at'])); ?></td>
                                        <td style="padding: 0.5rem; color: #111827; font-weight: 700; text-align: right;">LKR <?php echo number_format($pay['amount']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </fieldset>
            </div>

            <!-- Itemized Invoice Table -->
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-right">Amount (LKR)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <div style="font-weight: 700; color: #111827;">Accommodation Fee</div>
                            <div style="color: #6b7280; font-size: 0.75rem; margin-top: 0.25rem;">
                                <?php echo htmlspecialchars($booking['room_name']); ?> • <?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?> @ LKR <?php echo number_format($booking['price_per_room']); ?> / night
                            </div>
                        </td>
                        <td class="text-right" style="font-weight: 700; color: #111827;">
                            <?php echo number_format($booking['price_per_room'] * $nights); ?>
                        </td>
                    </tr>
                    <?php foreach ($expenses as $exp): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 700; color: #111827;"><?php echo htmlspecialchars($exp['description']); ?></div>
                            <div style="color: #6b7280; font-size: 0.75rem; margin-top: 0.25rem;">Expense added on <?php echo date('M d, Y', strtotime($exp['created_at'])); ?></div>
                        </td>
                        <td class="text-right" style="font-weight: 700; color: #111827;">
                            + <?php echo number_format($exp['amount']); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Invoice Summary Totals -->
            <div class="invoice-totals">
                <div class="total-row">
                    <span class="info-label">Subtotal</span>
                    <span class="info-value">LKR <?php echo number_format($booking['total_price']); ?></span>
                </div>
                <div class="total-row">
                    <span class="info-label">Total Paid</span>
                    <span class="info-value" style="color: #059669; font-weight: 700;">LKR <?php echo number_format($booking['amount_paid']); ?></span>
                </div>
                <div class="total-row grand-total">
                    <span>Balance Due</span>
                    <span>LKR <?php echo number_format($booking['total_price'] - $booking['amount_paid']); ?></span>
                </div>
            </div>

            <!-- Signatures Section -->
            <div class="signatures-container">
                <div class="signature-box">Guest Signature</div>
                <div class="signature-box">Manager Signature</div>
            </div>

            <!-- Brand Footers -->
            <div class="invoice-footer">
                <div class="invoice-footer-brand">Booking<span>jaunt</span></div>
                <div class="invoice-footer-studio">system by primeX Studios</div>
            </div>
        </div>
    </div>

    <?php if ($is_owner): ?>
        <!-- Payment Modal -->
        <div id="paymentModal" class="no-print" style="display: none;">
            <div class="modal-overlay" onclick="closePaymentModal()"></div>
            <div class="modal-container">
                <div class="modal-header">
                    <h3 class="modal-title">Add Payment</h3>
                    <button onclick="closePaymentModal()" class="modal-close">&times;</button>
                </div>
                <form method="POST" class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Amount (LKR)</label>
                        <input type="number" step="0.01" min="0.01" name="payment_amount" required class="form-input" placeholder="0.00" autofocus>
                    </div>
                    <button type="submit" name="add_payment" class="btn-submit">Add Payment</button>
                </form>
            </div>
        </div>

        <!-- Expense Modal -->
        <div id="expenseModal" class="no-print" style="display: none;">
            <div class="modal-overlay" onclick="closeExpenseModal()"></div>
            <div class="modal-container">
                <div class="modal-header">
                    <h3 class="modal-title">Add Expense</h3>
                    <button onclick="closeExpenseModal()" class="modal-close">&times;</button>
                </div>
                <form method="POST" class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <input type="text" name="expense_desc" required class="form-input" placeholder="Extra service / charges">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Amount (LKR)</label>
                        <input type="number" step="0.01" min="0.01" name="expense_amount" required class="form-input" placeholder="0.00">
                    </div>
                    <button type="submit" name="add_expense" class="btn-submit">Add Expense</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script>
        function openPaymentModal() {
            document.getElementById('paymentModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        function openExpenseModal() {
            document.getElementById('expenseModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeExpenseModal() {
            document.getElementById('expenseModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    </script>
</body>
</html>
