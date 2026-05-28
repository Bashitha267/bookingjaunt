<?php
require_once '../../config.php';
require_once '../auth_guard.php';

requireRole(['admin', 'manager', 'site_staff'], '../../login.php');

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
if ($booking_id <= 0) {
    http_response_code(400);
    echo 'Invalid booking id.';
    exit();
}

$booking_sql = "SELECT b.*, p.property_name, p.contact_number, p.city, p.district, p.province, p.country, r.room_name
                FROM bookings b
                JOIN properties p ON b.property_id = p.id
                LEFT JOIN property_rooms r ON b.room_id = r.id
                WHERE b.id = ?";
$booking_stmt = $pdo->prepare($booking_sql);
$booking_stmt->execute([$booking_id]);
$booking = $booking_stmt->fetch();

if (!$booking) {
    http_response_code(404);
    echo 'Booking not found.';
    exit();
}

$payments_stmt = $pdo->prepare("SELECT amount, created_at FROM booking_payments WHERE booking_id = ? ORDER BY created_at ASC");
$payments_stmt->execute([$booking_id]);
$payments = $payments_stmt->fetchAll();

$expenses_stmt = $pdo->prepare("SELECT description, amount, created_at FROM booking_expenses WHERE booking_id = ? ORDER BY created_at ASC");
$expenses_stmt->execute([$booking_id]);
$expenses = $expenses_stmt->fetchAll();

$total_payments = 0.0;
foreach ($payments as $payment) {
    $total_payments += (float)$payment['amount'];
}

$total_expenses = 0.0;
foreach ($expenses as $expense) {
    $total_expenses += (float)$expense['amount'];
}

$filename = 'booking_' . $booking_id . '.html';
$view_mode = isset($_GET['view']) && $_GET['view'] === '1';
header('Content-Type: text/html; charset=UTF-8');
if (!$view_mode) {
    header('Content-Disposition: attachment; filename="' . $filename . '"');
}

$property_name = $booking['property_name'] ?? '';
$room_name = $booking['room_name'] ?? '';
$contact_number = $booking['contact_number'] ?? '';

$booking_fields = $booking;
unset($booking_fields['property_name'], $booking_fields['room_name'], $booking_fields['contact_number']);

function format_money($value) {
    return number_format((float)$value, 2, '.', ',');
}

function is_value_present($value) {
    if ($value === null) {
        return false;
    }
    $string_value = is_string($value) ? trim($value) : $value;
    if ($string_value === '') {
        return false;
    }
    if ($string_value === '0000-00-00') {
        return false;
    }
    return true;
}

$visible_booking_fields = [];
foreach ($booking_fields as $key => $value) {
    if (is_value_present($value)) {
        $visible_booking_fields[$key] = $value;
    }
}

$property_rows = [];
if (is_value_present($property_name)) {
    $property_rows[] = ['Property Name', $property_name];
}
if (is_value_present($contact_number)) {
    $property_rows[] = ['Contact Number', $contact_number];
}
if (is_value_present($room_name)) {
    $property_rows[] = ['Room Name', $room_name];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Details #<?php echo (int)$booking_id; ?></title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #111111;
            background: #ffffff;
            margin: 24px;
        }
        h1 {
            color: #003580;
            font-size: 22px;
            margin-bottom: 6px;
        }
        .subtle {
            color: #444444;
            font-size: 12px;
            margin-bottom: 18px;
        }
        fieldset {
            border: 1px solid #003580;
            padding: 12px 16px 16px;
            margin-bottom: 16px;
        }
        legend {
            color: #003580;
            font-weight: bold;
            padding: 0 6px;
            text-decoration: underline;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #c7c7c7;
            padding: 6px 8px;
            vertical-align: top;
        }
        th {
            background: #f2f4f8;
            text-align: left;
            color: #003580;
        }
        .two-col {
            display: flex;
            gap: 16px;
        }
        .two-col > div {
            flex: 1;
        }
        .label {
            color: #003580;
            font-weight: bold;
        }
        .no-data {
            color: #666666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <h1>Booking Details</h1>
    <div class="subtle">Booking ID: <?php echo (int)$booking_id; ?> | Generated: <?php echo date('Y-m-d H:i:s'); ?></div>

    <?php if (!empty($property_rows)): ?>
        <fieldset>
            <legend>Property & Room</legend>
            <table>
                <tr>
                    <th>Field</th>
                    <th>Value</th>
                </tr>
                <?php foreach ($property_rows as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row[0]); ?></td>
                        <td><?php echo htmlspecialchars($row[1]); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </fieldset>
    <?php endif; ?>

    <?php if (!empty($visible_booking_fields)): ?>
        <fieldset>
            <legend>Booking Fields</legend>
            <table>
                <tr>
                    <th>Field</th>
                    <th>Value</th>
                </tr>
                <?php foreach ($visible_booking_fields as $key => $value): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($key); ?></td>
                        <td><?php echo htmlspecialchars((string)$value); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </fieldset>
    <?php endif; ?>

    <fieldset>
        <legend>Payment Summary</legend>
        <div class="two-col">
            <div>
                <table>
                    <tr><th>Total Price</th><td><?php echo format_money($booking['total_price'] ?? 0); ?></td></tr>
                    <tr><th>Amount Paid</th><td><?php echo format_money($booking['amount_paid'] ?? 0); ?></td></tr>
                    <tr><th>Security Deposit</th><td><?php echo format_money($booking['security_deposit'] ?? 0); ?></td></tr>
                    <tr><th>Advance Payment</th><td><?php echo format_money($booking['advance_payment'] ?? 0); ?></td></tr>
                    <tr><th>Payment Status</th><td><?php echo htmlspecialchars($booking['payment_status'] ?? ''); ?></td></tr>
                </table>
            </div>
            <div>
                <table>
                    <tr><th>Total Payments</th><td><?php echo format_money($total_payments); ?></td></tr>
                    <tr><th>Total Expenses</th><td><?php echo format_money($total_expenses); ?></td></tr>
                    <tr><th>Balance (Total - Paid)</th><td><?php echo format_money(max(0, (float)($booking['total_price'] ?? 0) - (float)($booking['amount_paid'] ?? 0))); ?></td></tr>
                </table>
            </div>
        </div>
    </fieldset>

    <?php if (!empty($payments)): ?>
        <fieldset>
            <legend>Payments</legend>
            <table>
                <tr>
                    <th>Amount</th>
                    <th>Date</th>
                </tr>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?php echo format_money($payment['amount']); ?></td>
                        <td><?php echo htmlspecialchars($payment['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </fieldset>
    <?php endif; ?>

    <?php if (!empty($expenses)): ?>
        <fieldset>
            <legend>Expenses</legend>
            <table>
                <tr>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Date</th>
                </tr>
                <?php foreach ($expenses as $expense): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($expense['description']); ?></td>
                        <td><?php echo format_money($expense['amount']); ?></td>
                        <td><?php echo htmlspecialchars($expense['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </fieldset>
    <?php endif; ?>
</body>
</html>
