<?php
require_once '../../config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../admin.php");
    exit();
}

$vehicle_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$vehicle = null;
$error = '';

if ($vehicle_id <= 0) {
    $error = 'Vehicle ID is missing.';
} else {
    $stmt = $pdo->prepare("SELECT p.*, u.first_name, u.last_name, u.email AS owner_email,
                                  u.phone_number AS owner_phone, u.whatsapp_number AS owner_whatsapp,
                                  u.nic_passport AS owner_nic
                           FROM properties p
                           JOIN users u ON p.owner_id = u.id
                           WHERE p.id = ? AND p.business_type = 'vehicle'");
    $stmt->execute([$vehicle_id]);
    $vehicle = $stmt->fetch();
    if (!$vehicle) {
        $error = 'Vehicle not found.';
    }
}

function h($value) {
    return htmlspecialchars((string)$value);
}

function is_value_present($value) {
    if ($value === null) {
        return false;
    }
    if (is_string($value) && trim($value) === '') {
        return false;
    }
    if ($value === '0000-00-00') {
        return false;
    }
    return true;
}

function format_bool($value) {
    return ((int)$value === 1) ? 'Yes' : 'No';
}

function format_money($value) {
    return number_format((float)$value, 2, '.', ',');
}

function add_row(&$rows, $label, $value) {
    if (is_value_present($value)) {
        $rows[] = [$label, $value];
    }
}

$overview_rows = [];
$capacity_rows = [];
$pricing_rows = [];
$driver_rows = [];
$delivery_rows = [];
$docs_rows = [];
$owner_rows = [];

if ($vehicle) {
    add_row($overview_rows, 'Vehicle Name', $vehicle['property_name'] ?? '');
    add_row($overview_rows, 'Category', $vehicle['vehicle_category'] ?? '');
    add_row($overview_rows, 'Brand', $vehicle['brand'] ?? '');
    add_row($overview_rows, 'Model', $vehicle['model'] ?? '');
    add_row($overview_rows, 'Manufactured Year', $vehicle['manufactured_year'] ?? '');
    add_row($overview_rows, 'Registration Number', $vehicle['registration_number'] ?? '');
    add_row($overview_rows, 'Vehicle Color', $vehicle['vehicle_color'] ?? '');
    add_row($overview_rows, 'Fuel Type', $vehicle['fuel_type'] ?? '');
    add_row($overview_rows, 'Transmission', $vehicle['transmission_type'] ?? '');
    add_row($overview_rows, 'Condition', $vehicle['vehicle_condition'] ?? '');

    add_row($capacity_rows, 'Seat Count', $vehicle['seat_count'] ?? '');
    add_row($capacity_rows, 'Luggage Count', $vehicle['luggage_count'] ?? '');
    add_row($capacity_rows, 'Max Passengers', $vehicle['max_passengers'] ?? '');

    add_row($pricing_rows, 'Pricing Type', $vehicle['pricing_type'] ?? '');
    if (is_value_present($vehicle['price_per_day'] ?? null)) {
        add_row($pricing_rows, 'Price Per Day', 'LKR ' . format_money($vehicle['price_per_day']));
    }
    if (is_value_present($vehicle['price_per_km'] ?? null)) {
        add_row($pricing_rows, 'Price Per Km', 'LKR ' . format_money($vehicle['price_per_km']));
    }
    if (is_value_present($vehicle['included_km_per_day'] ?? null)) {
        add_row($pricing_rows, 'Included Km Per Day', $vehicle['included_km_per_day']);
    }
    if (is_value_present($vehicle['extra_km_price'] ?? null)) {
        add_row($pricing_rows, 'Extra Km Price', 'LKR ' . format_money($vehicle['extra_km_price']));
    }
    if (is_value_present($vehicle['hourly_price'] ?? null)) {
        add_row($pricing_rows, 'Hourly Price', 'LKR ' . format_money($vehicle['hourly_price']));
    }
    if (is_value_present($vehicle['weekly_price'] ?? null)) {
        add_row($pricing_rows, 'Weekly Price', 'LKR ' . format_money($vehicle['weekly_price']));
    }
    if (is_value_present($vehicle['monthly_price'] ?? null)) {
        add_row($pricing_rows, 'Monthly Price', 'LKR ' . format_money($vehicle['monthly_price']));
    }

    add_row($driver_rows, 'Driver Option', $vehicle['driver_option'] ?? '');
    add_row($driver_rows, 'Driver Name', $vehicle['driver_name'] ?? '');
    add_row($driver_rows, 'Driver Contact', $vehicle['driver_contact'] ?? '');
    add_row($driver_rows, 'Driver License', $vehicle['driver_license'] ?? '');
    add_row($driver_rows, 'Driver Experience', $vehicle['driver_experience'] ?? '');
    add_row($driver_rows, 'Driver Languages', $vehicle['driver_languages'] ?? '');

    add_row($delivery_rows, 'Delivery Available', format_bool($vehicle['delivery_available'] ?? 0));
    add_row($delivery_rows, 'Pickup Available', format_bool($vehicle['pickup_available'] ?? 0));
    if (is_value_present($vehicle['delivery_fee'] ?? null)) {
        add_row($delivery_rows, 'Delivery Fee', 'LKR ' . format_money($vehicle['delivery_fee']));
    }
    add_row($delivery_rows, 'Airport Delivery', format_bool($vehicle['airport_delivery'] ?? 0));
    add_row($delivery_rows, 'Hotel Delivery', format_bool($vehicle['hotel_delivery'] ?? 0));
    add_row($delivery_rows, 'Exact Pickup Location', $vehicle['exact_pickup_location'] ?? '');

    add_row($docs_rows, 'Chassis Number', $vehicle['chassis_number'] ?? '');
    add_row($docs_rows, 'Engine Number', $vehicle['engine_number'] ?? '');
    add_row($docs_rows, 'Insurance Details', $vehicle['insurance_details'] ?? '');
    add_row($docs_rows, 'Insurance Expiry', $vehicle['insurance_expiry'] ?? '');
    add_row($docs_rows, 'Revenue License Expiry', $vehicle['revenue_license_expiry'] ?? '');
    add_row($docs_rows, 'Vehicle Reg Doc', $vehicle['vehicle_reg_doc'] ?? '');
    add_row($docs_rows, 'Owner NIC Doc', $vehicle['owner_nic_doc'] ?? '');
    add_row($docs_rows, 'Driver License Doc', $vehicle['driver_license_doc'] ?? '');

    add_row($owner_rows, 'Owner Name', trim(($vehicle['first_name'] ?? '') . ' ' . ($vehicle['last_name'] ?? '')));
    add_row($owner_rows, 'Owner Email', $vehicle['owner_email'] ?? '');
    add_row($owner_rows, 'Owner Phone', $vehicle['owner_phone'] ?? '');
    add_row($owner_rows, 'Owner WhatsApp', $vehicle['owner_whatsapp'] ?? '');
    add_row($owner_rows, 'Owner NIC', $vehicle['owner_nic'] ?? '');
}

$features = [];
if ($vehicle) {
    $feature_map = [
        'A/C' => $vehicle['has_ac'] ?? 0,
        'GPS' => $vehicle['has_gps'] ?? 0,
        'Bluetooth' => $vehicle['has_bluetooth'] ?? 0,
        'WiFi' => $vehicle['has_wifi'] ?? 0,
        'Music System' => $vehicle['has_music_system'] ?? 0,
        'Charging Ports' => $vehicle['has_charging_ports'] ?? 0,
        'Baby Seat' => $vehicle['has_baby_seat'] ?? 0,
        'Sunroof' => $vehicle['has_sunroof'] ?? 0,
        'Reverse Camera' => $vehicle['has_reverse_camera'] ?? 0,
        'Airbags' => $vehicle['has_airbags'] ?? 0
    ];
    foreach ($feature_map as $label => $enabled) {
        if ((int)$enabled === 1) {
            $features[] = $label;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Details - Bookingjaunt</title>
    <style>
        body {
            font-family: "Times New Roman", serif;
            color: #0f172a;
            background: #f5f7fb;
            margin: 0;
        }
        .container {
            max-width: 1000px;
            margin: 24px auto 40px;
            padding: 0 16px 40px;
        }
        h1 {
            font-size: 24px;
            margin: 0 0 6px;
            color: #0b3a8a;
        }
        .meta {
            font-size: 12px;
            color: #334155;
            margin-bottom: 18px;
        }
        .section {
            margin-top: 18px;
            background: #ffffff;
            border: 1px solid #d9e2f0;
            border-radius: 12px;
            padding: 14px 16px 16px;
        }
        .section h2 {
            font-size: 14px;
            margin: 0 0 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #c7d5ee;
            color: #0b3a8a;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #d0d7e5;
            padding: 7px 9px;
            vertical-align: top;
        }
        th {
            text-align: left;
            width: 35%;
            font-weight: bold;
            color: #0b3a8a;
            background: #f3f7ff;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 12px;
            font-size: 12px;
            color: #0b3a8a;
            text-decoration: none;
            padding: 6px 10px;
            border: 1px solid #c7d5ee;
            border-radius: 10px;
            background: #ffffff;
        }
        .feature-list {
            font-size: 12px;
            color: #1f2937;
        }
        .empty {
            font-style: italic;
            color: #64748b;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a class="back-link" href="vehicles.php">Back to Vehicles</a>

        <?php if ($error !== ''): ?>
            <h1>Vehicle Details</h1>
            <p><?php echo h($error); ?></p>
        <?php else: ?>
            <h1><?php echo h($vehicle['property_name'] ?? 'Vehicle'); ?> - Vehicle Report</h1>
            <div class="meta">Vehicle ID: <?php echo (int)$vehicle_id; ?> | Created: <?php echo h($vehicle['created_at'] ?? ''); ?></div>

            <?php if (!empty($overview_rows)): ?>
                <div class="section">
                    <h2>Overview</h2>
                    <table>
                        <?php foreach ($overview_rows as $row): ?>
                            <tr>
                                <th><?php echo h($row[0]); ?></th>
                                <td><?php echo h($row[1]); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($capacity_rows)): ?>
                <div class="section">
                    <h2>Capacity</h2>
                    <table>
                        <?php foreach ($capacity_rows as $row): ?>
                            <tr>
                                <th><?php echo h($row[0]); ?></th>
                                <td><?php echo h($row[1]); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($pricing_rows)): ?>
                <div class="section">
                    <h2>Pricing</h2>
                    <table>
                        <?php foreach ($pricing_rows as $row): ?>
                            <tr>
                                <th><?php echo h($row[0]); ?></th>
                                <td><?php echo h($row[1]); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($driver_rows)): ?>
                <div class="section">
                    <h2>Driver</h2>
                    <table>
                        <?php foreach ($driver_rows as $row): ?>
                            <tr>
                                <th><?php echo h($row[0]); ?></th>
                                <td><?php echo h($row[1]); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($delivery_rows)): ?>
                <div class="section">
                    <h2>Delivery</h2>
                    <table>
                        <?php foreach ($delivery_rows as $row): ?>
                            <tr>
                                <th><?php echo h($row[0]); ?></th>
                                <td><?php echo h($row[1]); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>

            <div class="section">
                <h2>Features</h2>
                <?php if (empty($features)): ?>
                    <div class="empty">No feature details available.</div>
                <?php else: ?>
                    <div class="feature-list"><?php echo h(implode(', ', $features)); ?></div>
                <?php endif; ?>
            </div>

            <?php if (!empty($docs_rows)): ?>
                <div class="section">
                    <h2>Documents</h2>
                    <table>
                        <?php foreach ($docs_rows as $row): ?>
                            <tr>
                                <th><?php echo h($row[0]); ?></th>
                                <td><?php echo h($row[1]); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($owner_rows)): ?>
                <div class="section">
                    <h2>Owner</h2>
                    <table>
                        <?php foreach ($owner_rows as $row): ?>
                            <tr>
                                <th><?php echo h($row[0]); ?></th>
                                <td><?php echo h($row[1]); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
