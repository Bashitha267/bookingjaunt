<?php
require_once '../../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$selected_property_id = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;

if ($_SESSION['role'] === 'admin') {
    $properties_stmt = $pdo->query("SELECT id, property_name FROM properties ORDER BY property_name");
    $properties = $properties_stmt->fetchAll();
} else {
    $properties_stmt = $pdo->prepare("SELECT id, property_name FROM properties WHERE owner_id = ? ORDER BY property_name");
    $properties_stmt->execute([$user_id]);
    $properties = $properties_stmt->fetchAll();
}

$property = null;
if ($selected_property_id) {
    if ($_SESSION['role'] === 'admin') {
        $stmt = $pdo->prepare("SELECT id, property_name FROM properties WHERE id = ? LIMIT 1");
        $stmt->execute([$selected_property_id]);
        $property = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare("SELECT id, property_name FROM properties WHERE id = ? AND owner_id = ? LIMIT 1");
        $stmt->execute([$selected_property_id, $user_id]);
        $property = $stmt->fetch();
    }
}

if (!$property && !empty($properties)) {
    $first_property_id = (int)$properties[0]['id'];
    if ($_SESSION['role'] === 'admin') {
        $stmt = $pdo->prepare("SELECT id, property_name FROM properties WHERE id = ? LIMIT 1");
        $stmt->execute([$first_property_id]);
        $property = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare("SELECT id, property_name FROM properties WHERE id = ? AND owner_id = ? LIMIT 1");
        $stmt->execute([$first_property_id, $user_id]);
        $property = $stmt->fetch();
    }
}

$property_id = $property['id'] ?? 0;
if (!$property_id && $_SESSION['role'] !== 'admin') {
    echo 'Property not found.';
    exit();
}

$from   = $_GET['from']   ?? '';
$to     = $_GET['to']     ?? '';
$type   = $_GET['type']   ?? '';
$status = $_GET['status'] ?? '';

$where_parts = ["b.property_id = ?"];
$params = [$property_id];
if ($from !== '') { $where_parts[] = "DATE(b.created_at) >= ?"; $params[] = $from; }
if ($to   !== '') { $where_parts[] = "DATE(b.created_at) <= ?"; $params[] = $to; }
if ($type !== '') { $where_parts[] = "b.booking_type = ?"; $params[] = $type; }
if ($status !== '') { $where_parts[] = "b.status = ?"; $params[] = $status; }
$where_sql = 'WHERE ' . implode(' AND ', $where_parts);

$summary_stmt = $pdo->prepare("SELECT COUNT(*) AS total_bookings, COALESCE(SUM(b.total_price),0) AS total_value, COALESCE(SUM(b.amount_paid),0) AS total_paid, COALESCE(SUM(CASE WHEN b.booking_type='online' THEN b.amount_paid ELSE 0 END),0) AS online_paid, COALESCE(SUM(CASE WHEN b.booking_type='inplace' THEN b.amount_paid ELSE 0 END),0) AS inplace_paid FROM bookings b $where_sql");
$summary_stmt->execute($params);
$summary = $summary_stmt->fetch();

$boost_where = ["property_id = ?", "payment_status = 'success'"];
$boost_params = [$property_id];
if ($from !== '') { $boost_where[] = "DATE(created_at) >= ?"; $boost_params[] = $from; }
if ($to   !== '') { $boost_where[] = "DATE(created_at) <= ?"; $boost_params[] = $to; }
$boost_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) AS boost_spend FROM property_boosts WHERE " . implode(' AND ', $boost_where));
$boost_stmt->execute($boost_params);
$boost_spend = $boost_stmt->fetch()['boost_spend'] ?? 0;

$list_stmt = $pdo->prepare("SELECT b.*, pr.room_name FROM bookings b LEFT JOIN property_rooms pr ON b.room_id = pr.id $where_sql ORDER BY b.created_at DESC");
$list_stmt->execute($params);
$bookings = $list_stmt->fetchAll();

$payments_stmt = $pdo->prepare("SELECT bp.amount, bp.created_at, b.id AS booking_id, b.guest_name FROM booking_payments bp JOIN bookings b ON bp.booking_id = b.id $where_sql ORDER BY bp.created_at DESC");
$payments_stmt->execute($params);
$payments = $payments_stmt->fetchAll();

$expenses_stmt = $pdo->prepare("SELECT be.description, be.amount, be.created_at, b.id AS booking_id, b.guest_name FROM booking_expenses be JOIN bookings b ON be.booking_id = b.id $where_sql ORDER BY be.created_at DESC");
$expenses_stmt->execute($params);
$expenses = $expenses_stmt->fetchAll();

$summary_commission = ($summary['online_paid'] ?? 0) * 0.2;

function format_money($value) {
    return number_format((float)$value, 2, '.', ',');
}

$filename = 'property_report_' . $property_id . '_' . date('Ymd_His') . '.html';
$view_mode = isset($_GET['view']) && $_GET['view'] === '1';
header('Content-Type: text/html; charset=UTF-8');
if (!$view_mode) {
    header('Content-Disposition: attachment; filename="' . $filename . '"');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Property Report</title>
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
        .no-data {
            color: #666666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <h1>Property Report</h1>
    <div class="subtle">Property: <?php echo htmlspecialchars($property['property_name'] ?? ''); ?> | Generated: <?php echo date('Y-m-d H:i:s'); ?></div>

    <fieldset>
        <legend>Summary</legend>
        <table>
            <tr><th>Total Bookings</th><td><?php echo number_format($summary['total_bookings'] ?? 0); ?></td></tr>
            <tr><th>Total Value</th><td>LKR <?php echo format_money($summary['total_value'] ?? 0); ?></td></tr>
            <tr><th>Amount Paid</th><td>LKR <?php echo format_money($summary['total_paid'] ?? 0); ?></td></tr>
            <tr><th>Online Paid</th><td>LKR <?php echo format_money($summary['online_paid'] ?? 0); ?></td></tr>
            <tr><th>Physical Paid</th><td>LKR <?php echo format_money($summary['inplace_paid'] ?? 0); ?></td></tr>
            <tr><th>Commission (20%)</th><td>LKR <?php echo format_money($summary_commission); ?></td></tr>
            <tr><th>Boost Spend</th><td>LKR <?php echo format_money($boost_spend); ?></td></tr>
        </table>
    </fieldset>

    <fieldset>
        <legend>Bookings</legend>
        <?php if (empty($bookings)): ?>
            <div class="no-data">No bookings found.</div>
        <?php else: ?>
            <table>
                <tr>
                    <th>Guest</th>
                    <th>Phone</th>
                    <th>Room</th>
                    <th>Dates</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Total</th>
                    <th>Paid</th>
                </tr>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($booking['guest_name']); ?></td>
                        <td><?php echo htmlspecialchars($booking['guest_phone'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($booking['room_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($booking['check_in_date']); ?> to <?php echo htmlspecialchars($booking['check_out_date']); ?></td>
                        <td><?php echo htmlspecialchars($booking['booking_type']); ?></td>
                        <td><?php echo htmlspecialchars($booking['status']); ?></td>
                        <td>LKR <?php echo format_money($booking['total_price']); ?></td>
                        <td>LKR <?php echo format_money($booking['amount_paid']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </fieldset>

    <?php if (!empty($payments)): ?>
        <fieldset>
            <legend>Payments</legend>
            <table>
                <tr>
                    <th>Booking ID</th>
                    <th>Guest</th>
                    <th>Amount</th>
                    <th>Date</th>
                </tr>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?php echo (int)$payment['booking_id']; ?></td>
                        <td><?php echo htmlspecialchars($payment['guest_name']); ?></td>
                        <td>LKR <?php echo format_money($payment['amount']); ?></td>
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
                    <th>Booking ID</th>
                    <th>Guest</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Date</th>
                </tr>
                <?php foreach ($expenses as $expense): ?>
                    <tr>
                        <td><?php echo (int)$expense['booking_id']; ?></td>
                        <td><?php echo htmlspecialchars($expense['guest_name']); ?></td>
                        <td><?php echo htmlspecialchars($expense['description']); ?></td>
                        <td>LKR <?php echo format_money($expense['amount']); ?></td>
                        <td><?php echo htmlspecialchars($expense['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </fieldset>
    <?php endif; ?>
</body>
</html>
