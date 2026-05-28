<?php
require_once '../../config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../admin.php');
    exit();
}

$day = $_GET['day'] ?? '';
$month = $_GET['month'] ?? '';
$year = $_GET['year'] ?? '';

$date_filter_clause = '';
$date_params = [];
$date_label = 'All time';

if ($day !== '') {
    $date_filter_clause = "DATE(created_at) = ?";
    $date_params = [$day];
    $date_label = 'Day ' . $day;
} elseif ($month !== '') {
    $date_filter_clause = "DATE_FORMAT(created_at, '%Y-%m') = ?";
    $date_params = [$month];
    $date_label = 'Month ' . $month;
} elseif ($year !== '') {
    $date_filter_clause = "YEAR(created_at) = ?";
    $date_params = [$year];
    $date_label = 'Year ' . $year;
}

$where_sql = $date_filter_clause !== '' ? 'WHERE ' . $date_filter_clause : '';

$summary_stmt = $pdo->prepare("SELECT COUNT(*) AS total_bookings, COALESCE(SUM(total_price), 0) AS total_revenue, COALESCE(SUM(amount_paid), 0) AS total_paid FROM bookings $where_sql");
$summary_stmt->execute($date_params);
$summary = $summary_stmt->fetch();

$total_bookings = (int)($summary['total_bookings'] ?? 0);
$total_revenue = (float)($summary['total_revenue'] ?? 0);
$total_paid = (float)($summary['total_paid'] ?? 0);
$total_due = max(0, $total_revenue - $total_paid);

$users_sql = "SELECT COUNT(*) FROM users WHERE role != 'admin'";
$properties_sql = "SELECT COUNT(*) FROM properties";
$boosts_sql = "SELECT COALESCE(SUM(amount), 0) FROM property_boosts WHERE payment_status = 'success'";
$users_params = [];
$properties_params = [];
$boosts_params = [];

if ($date_filter_clause !== '') {
    $users_sql .= " AND $date_filter_clause";
    $properties_sql .= " WHERE $date_filter_clause";
    $boosts_sql .= " AND $date_filter_clause";
    $users_params = $date_params;
    $properties_params = $date_params;
    $boosts_params = $date_params;
}

$users_stmt = $pdo->prepare($users_sql);
$users_stmt->execute($users_params);
$total_users = (int)$users_stmt->fetchColumn();

$properties_stmt = $pdo->prepare($properties_sql);
$properties_stmt->execute($properties_params);
$total_properties = (int)$properties_stmt->fetchColumn();

$boosts_stmt = $pdo->prepare($boosts_sql);
$boosts_stmt->execute($boosts_params);
$total_boost_revenue = (float)$boosts_stmt->fetchColumn();

$total_active_boosts = (int)$pdo->query("SELECT COUNT(*) FROM property_boosts WHERE status = 'active' AND DATE_ADD(start_date, INTERVAL duration_days DAY) >= CURDATE()")->fetchColumn();

$commission_rate = 0.2;
$commission_start = date('Y-m-01');
$commission_end = date('Y-m-t');
$commission_stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) AS total_price, COALESCE(SUM(amount_paid), 0) AS total_paid FROM bookings WHERE booking_type = 'online' AND DATE(created_at) BETWEEN ? AND ?");
$commission_stmt->execute([$commission_start, $commission_end]);
$commission_row = $commission_stmt->fetch();
$commission_total = (float)($commission_row['total_price'] ?? 0) * $commission_rate;
$commission_paid = (float)($commission_row['total_paid'] ?? 0) * $commission_rate;
$commission_due = max(0, $commission_total - $commission_paid);

function format_money($value) {
    return number_format((float)$value, 2, '.', ',');
}

$filename = 'admin_report_' . date('Ymd_His') . '.html';
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
    <title>Admin Report</title>
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
    </style>
</head>
<body>
    <h1>Admin Summary Report</h1>
    <div class="subtle">Period: <?php echo htmlspecialchars($date_label); ?> | Generated: <?php echo date('Y-m-d H:i:s'); ?></div>

    <fieldset>
        <legend>Platform Totals</legend>
        <table>
            <tr><th>Total Bookings</th><td><?php echo number_format($total_bookings); ?></td></tr>
            <tr><th>Total Revenue</th><td>LKR <?php echo format_money($total_revenue); ?></td></tr>
            <tr><th>Total Paid</th><td>LKR <?php echo format_money($total_paid); ?></td></tr>
            <tr><th>Total Due</th><td>LKR <?php echo format_money($total_due); ?></td></tr>
            <tr><th>Total Users</th><td><?php echo number_format($total_users); ?></td></tr>
            <tr><th>Total Properties</th><td><?php echo number_format($total_properties); ?></td></tr>
            <tr><th>Active Boosts</th><td><?php echo number_format($total_active_boosts); ?></td></tr>
            <tr><th>Boost Revenue</th><td>LKR <?php echo format_money($total_boost_revenue); ?></td></tr>
        </table>
    </fieldset>

    <fieldset>
        <legend>Commission (This Month)</legend>
        <table>
            <tr><th>Commission Rate</th><td><?php echo (int)($commission_rate * 100); ?>%</td></tr>
            <tr><th>Total Commission</th><td>LKR <?php echo format_money($commission_total); ?></td></tr>
            <tr><th>Paid Commission</th><td>LKR <?php echo format_money($commission_paid); ?></td></tr>
            <tr><th>Commission Due</th><td>LKR <?php echo format_money($commission_due); ?></td></tr>
        </table>
    </fieldset>
</body>
</html>
