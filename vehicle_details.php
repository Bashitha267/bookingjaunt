<?php
require_once 'config.php';
session_start();
if (isset($_GET['currency']) && in_array(strtoupper($_GET['currency']), ['LKR', 'USD'])) {
    $_SESSION['currency'] = strtoupper($_GET['currency']);
}
$currency = $_SESSION['currency'] ?? 'LKR';
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: rides.php'); exit; }
// Fetch vehicle with owner info
$stmt = $pdo->prepare("SELECT p.*, u.first_name, u.last_name, u.email as owner_email, u.phone_number as owner_phone, u.whatsapp_number as owner_whatsapp FROM properties p JOIN users u ON p.owner_id = u.id WHERE p.id = ? AND p.business_type = 'vehicle'");
$stmt->execute([$id]);
$v = $stmt->fetch();
if (!$v) { header('Location: rides.php'); exit; }
// Fetch gallery images
$media_stmt = $pdo->prepare("SELECT media_path FROM property_media WHERE property_id = ? AND media_type = 'image' ORDER BY sort_order ASC LIMIT 4");
$media_stmt->execute([$id]);
$gallery = $media_stmt->fetchAll(PDO::FETCH_COLUMN);
// Check if boosted/featured
$is_featured = $pdo->prepare("SELECT id FROM property_boosts WHERE property_id = ? AND status = 'active' AND start_date <= CURDATE() AND DATE_ADD(start_date, INTERVAL duration_days DAY) >= CURDATE()");
$is_featured->execute([$id]);
$featured = $is_featured->fetch();

// Fetch real reviews for this vehicle
$reviews_stmt = $pdo->prepare("
    SELECT r.*, CONCAT(u.first_name, ' ', u.last_name) as user_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.property_id = ? 
    ORDER BY r.created_at DESC
");
$reviews_stmt->execute([$id]);
$reviews = $reviews_stmt->fetchAll();

// Calculate average rating
$avg_rating_stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE property_id = ?");
$avg_rating_stmt->execute([$id]);
$rating_stats = $avg_rating_stmt->fetch();
$avg_rating = round($rating_stats['avg_rating'], 1) ?: 'New';
$review_count = $rating_stats['review_count'];

$exchange_rate = 300;

// Fetch Rides Near Me (same city first, then district)
$rides_near = [];
$current_city = $v['city'] ?? '';
$current_district = $v['district'] ?? '';
if (!empty($current_city) || !empty($current_district)) {
    try {
        $rides_near_stmt = $pdo->prepare("
            SELECT *
            FROM properties
            WHERE business_type = 'vehicle'
              AND approval_status = 'approved'
              AND id != ?
              AND (
                  (? != '' AND LOWER(city) = LOWER(?))
                  OR (? != '' AND LOWER(district) = LOWER(?))
              )
            ORDER BY 
              CASE WHEN (? != '' AND LOWER(city) = LOWER(?)) THEN 1 ELSE 2 END ASC,
              id DESC
            LIMIT 10
        ");
        $rides_near_stmt->execute([
            $id,
            $current_city, $current_city,
            $current_district, $current_district,
            $current_city, $current_city
        ]);
        $rides_near = $rides_near_stmt->fetchAll();
    } catch (PDOException $e) {
        $rides_near = [];
    }
}

// Fetch Hotels Near Me (same city first, then district)
$hotels_near = [];
if (!empty($current_city) || !empty($current_district)) {
    try {
        $hotels_near_stmt = $pdo->prepare("
            SELECT p.*, MIN(pr.price_lkr) as price_lkr
            FROM properties p
            LEFT JOIN property_rooms pr ON pr.property_id = p.id
            WHERE p.business_type != 'vehicle'
              AND p.approval_status = 'approved'
              AND (
                  (? != '' AND LOWER(p.city) = LOWER(?))
                  OR (? != '' AND LOWER(p.district) = LOWER(?))
              )
            GROUP BY p.id
            ORDER BY 
              CASE WHEN (? != '' AND LOWER(p.city) = LOWER(?)) THEN 1 ELSE 2 END ASC,
              p.id DESC
            LIMIT 10
        ");
        $hotels_near_stmt->execute([
            $current_city, $current_city,
            $current_district, $current_district,
            $current_city, $current_city
        ]);
        $hotels_near = $hotels_near_stmt->fetchAll();

        // Check for active deals
        $deal_stmt = $pdo->prepare("SELECT deal_price FROM deals_of_the_day WHERE property_id = ? AND is_active = 1 AND valid_from <= CURDATE() AND valid_until >= CURDATE() LIMIT 1");
        foreach ($hotels_near as &$h) {
            $deal_stmt->execute([$h['id']]);
            $deal = $deal_stmt->fetch();
            if ($deal) {
                $h['original_price_lkr'] = $h['price_lkr'];
                $h['price_lkr'] = $deal['deal_price'];
                $h['is_deal'] = true;
            } else {
                $h['is_deal'] = false;
            }
        }
        unset($h);
    } catch (PDOException $e) {
        $hotels_near = [];
    }
}


// Handle booking form POST
$booking_success = false;
$booking_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_vehicle'])) {
    $guest_name = trim($_POST['guest_name'] ?? '');
    $guest_phone = trim($_POST['guest_phone'] ?? '');
    $guest_email = trim($_POST['guest_email'] ?? '');
    $guest_nic = trim($_POST['guest_nic'] ?? '');
    $guest_driving_license = trim($_POST['guest_driving_license'] ?? '');
    $check_in_date = $_POST['pickup_date'] ?? '';
    $check_out_date = $_POST['return_date'] ?? '';
    $pickup_time = $_POST['pickup_time'] ?? null;
    $return_time = $_POST['return_time'] ?? null;
    $vehicle_with_driver = isset($_POST['with_driver']) ? 1 : 0;
    $delivery_needed = isset($_POST['delivery_needed']) ? 1 : 0;
    $airport_pickup = isset($_POST['airport_pickup']) ? 1 : 0;
    $num_passengers = (int)($_POST['num_passengers'] ?? 1);
    $num_luggage = (int)($_POST['num_luggage'] ?? 0);
    $special_requests = trim($_POST['special_requests'] ?? '');
    $emergency_contact = trim($_POST['emergency_contact'] ?? '');
    
    // Calculate days and total
    $days = 1;
    if ($check_in_date && $check_out_date) {
        $d1 = new DateTime($check_in_date); 
        $d2 = new DateTime($check_out_date);
        $days = max(1, $d2->diff($d1)->days);
    }
    
    $total = 0;
    if ($v['pricing_type'] === 'day_wise') {
        $total = $v['price_per_day'] * $days;
    } else {
        $total = $v['price_per_km'] * 100; // default 100km estimate
    }
    
    if ($delivery_needed) $total += $v['delivery_fee'];
    $security_deposit = $total * 0.2;
    $user_id = $_SESSION['user_id'] ?? null;
    
    try {
        $ins = $pdo->prepare("INSERT INTO bookings (property_id, room_id, user_id, booking_type, booking_category, guest_name, guest_phone, guest_email, guest_nic, guest_driving_license, check_in_date, check_out_date, pickup_time, return_time, num_passengers, num_luggage, vehicle_with_driver, delivery_needed, airport_pickup, special_requests, emergency_contact, total_price, security_deposit, status, payment_status, created_at) VALUES (?, NULL, ?, 'online', 'vehicle', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW())");
        $ins->execute([$id, $user_id, $guest_name, $guest_phone, $guest_email, $guest_nic, $guest_driving_license, $check_in_date, $check_out_date, $pickup_time, $return_time, $num_passengers, $num_luggage, $vehicle_with_driver, $delivery_needed, $airport_pickup, $special_requests, $emergency_contact, $total, $security_deposit]);
        $booking_success = true;
    } catch (Exception $e) {
        $booking_error = 'Booking failed. Please try again. ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($v['property_name']) ?> - BookingJaunt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #dbeafe; }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary: '#003580', secondary: '#006ce4', gold: '#febb02' }
                }
            }
        }
    </script>
</head>
<body class="text-gray-800">
    <?php include 'navbar.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Breadcrumb -->
        <div class="text-sm text-gray-500 mb-6 flex items-center gap-2">
            <a href="index.php" class="hover:text-secondary transition">Home</a>
            <i class="fas fa-chevron-right text-xs"></i>
            <a href="rides.php" class="hover:text-secondary transition">Rides</a>
            <i class="fas fa-chevron-right text-xs"></i>
            <span class="text-gray-900 font-medium"><?= htmlspecialchars($v['property_name']) ?></span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-[1fr_400px] gap-8">
            <!-- Left Column: Vehicle Details -->
            <div class="space-y-8">
                <!-- Image Gallery -->
                <div>
                    <div class="relative h-[400px] rounded-2xl overflow-hidden mb-4 shadow-lg group">
                        <img id="main-vehicle-image" src="<?= htmlspecialchars($v['cover_image'] ?: 'assets/placeholder-vehicle.png') ?>" class="w-full h-full object-cover transition duration-500 group-hover:scale-105" alt="Vehicle Cover">
                        <?php if ($featured): ?>
                        <div class="absolute top-4 left-4 bg-gold text-primary font-bold text-xs uppercase px-3 py-1.5 rounded-lg shadow-md flex items-center gap-1.5">
                            <i class="fas fa-star"></i> Featured
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($gallery)): ?>
                    <div class="grid grid-cols-4 gap-4">
                        <?php foreach($gallery as $img): ?>
                        <button type="button" class="vehicle-thumb h-24 rounded-xl overflow-hidden cursor-pointer hover:opacity-80 transition shadow" data-img="<?= htmlspecialchars($img) ?>">
                            <img src="<?= htmlspecialchars($img) ?>" class="w-full h-full object-cover" alt="Vehicle thumbnail">
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Header Info -->
                <div class="bg-white p-6 md:p-8 rounded-3xl shadow-sm border border-gray-100">
                    <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
                        <div>
                            <div class="flex gap-2 mb-3">
                                <span class="bg-blue-50 text-secondary px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide border border-blue-100"><?= htmlspecialchars($v['vehicle_category'] ?? 'Vehicle') ?></span>
                                <?php if ($v['brand']): ?>
                                <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wide"><?= htmlspecialchars($v['brand']) ?> <?= htmlspecialchars($v['model'] ?? '') ?></span>
                                <?php endif; ?>
                            </div>
                            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($v['property_name']) ?></h1>
                            <div class="flex items-center gap-4 text-sm text-gray-600">
                                <span class="flex items-center gap-1.5"><i class="fas fa-map-marker-alt text-secondary"></i> <?= htmlspecialchars($v['district']) ?> &bull; <?= htmlspecialchars($v['closest_main_town']) ?></span>
                                <?php if ($v['registration_number']): ?>
                                <span class="flex items-center gap-1.5"><i class="fas fa-id-card text-gray-400"></i> Reg: <?= substr(htmlspecialchars($v['registration_number']), 0, 3) ?>***</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-gray-500 font-medium">Starting from</div>
                            <?php if ($v['pricing_type'] === 'day_wise'): ?>
                                <div class="text-3xl font-black text-secondary">LKR <?= number_format($v['price_per_day'], 2) ?></div>
                                <div class="text-xs text-gray-500 mt-1">per day (<?= $v['included_km_per_day'] ?>km included)</div>
                            <?php else: ?>
                                <div class="text-3xl font-black text-secondary">LKR <?= number_format($v['price_per_km'], 2) ?></div>
                                <div class="text-xs text-gray-500 mt-1">per kilometer</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Features Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 py-6 border-y border-gray-100 my-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-secondary"><i class="fas fa-users"></i></div>
                            <div><div class="text-xs text-gray-500 uppercase font-bold">Seats</div><div class="font-bold"><?= $v['seat_count'] ?> Seats</div></div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-secondary"><i class="fas fa-suitcase"></i></div>
                            <div><div class="text-xs text-gray-500 uppercase font-bold">Luggage</div><div class="font-bold"><?= $v['luggage_count'] ?> Bags</div></div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-secondary"><i class="fas fa-gas-pump"></i></div>
                            <div><div class="text-xs text-gray-500 uppercase font-bold">Fuel</div><div class="font-bold capitalize"><?= $v['fuel_type'] ?: 'N/A' ?></div></div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-secondary"><i class="fas fa-cogs"></i></div>
                            <div><div class="text-xs text-gray-500 uppercase font-bold">Transmission</div><div class="font-bold capitalize"><?= $v['transmission_type'] ?: 'N/A' ?></div></div>
                        </div>
                    </div>

                    <div class="prose max-w-none text-gray-600">
                        <h3 class="text-lg font-bold text-gray-900 mb-2">Description</h3>
                        <p><?= nl2br(htmlspecialchars($v['description'])) ?></p>
                    </div>
                </div>

                <!-- Location & Pickup Details -->
                <div class="bg-white p-6 md:p-8 rounded-3xl shadow-sm border border-gray-100">
                    <h3 class="text-xl font-bold text-gray-900 mb-6">Location & Pickup</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm text-gray-700">
                        <div>
                            <div class="text-[10px] text-gray-500 font-bold uppercase">City</div>
                            <div class="font-semibold"><?= htmlspecialchars($v['city'] ?: 'N/A') ?></div>
                        </div>
                        <div>
                            <div class="text-[10px] text-gray-500 font-bold uppercase">District</div>
                            <div class="font-semibold"><?= htmlspecialchars($v['district'] ?: 'N/A') ?></div>
                        </div>
                        <div>
                            <div class="text-[10px] text-gray-500 font-bold uppercase">Province</div>
                            <div class="font-semibold"><?= htmlspecialchars($v['province'] ?: 'N/A') ?></div>
                        </div>
                        <div>
                            <div class="text-[10px] text-gray-500 font-bold uppercase">Closest Town</div>
                            <div class="font-semibold"><?= htmlspecialchars($v['closest_main_town'] ?: 'N/A') ?></div>
                        </div>
                        <div class="md:col-span-2">
                            <div class="text-[10px] text-gray-500 font-bold uppercase">Exact Pickup Location</div>
                            <div class="font-semibold"><?= htmlspecialchars($v['exact_pickup_location'] ?: 'N/A') ?></div>
                        </div>
                        <?php if (!empty($v['google_map_location'])): ?>
                        <div class="md:col-span-2">
                            <a href="<?= htmlspecialchars($v['google_map_location']) ?>" target="_blank" class="inline-flex items-center gap-2 text-secondary font-bold text-sm">
                                <i class="fas fa-map-marker-alt"></i> View on Google Maps
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Vehicle Specs -->
                <div class="bg-white p-6 md:p-8 rounded-3xl shadow-sm border border-gray-100">
                    <h3 class="text-xl font-bold text-gray-900 mb-6">Vehicle Specs</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm text-gray-700">
                        <div>
                            <div class="text-[10px] text-gray-500 font-bold uppercase">Fuel Type</div>
                            <div class="font-semibold capitalize"><?= htmlspecialchars($v['fuel_type'] ?: 'N/A') ?></div>
                        </div>
                        <div>
                            <div class="text-[10px] text-gray-500 font-bold uppercase">Transmission</div>
                            <div class="font-semibold capitalize"><?= htmlspecialchars($v['transmission_type'] ?: 'N/A') ?></div>
                        </div>
                        <div>
                            <div class="text-[10px] text-gray-500 font-bold uppercase">Condition</div>
                            <div class="font-semibold capitalize"><?= htmlspecialchars($v['vehicle_condition'] ?: 'N/A') ?></div>
                        </div>
                        <div>
                            <div class="text-[10px] text-gray-500 font-bold uppercase">Seats</div>
                            <div class="font-semibold"><?= (int)($v['seat_count'] ?? 0) ?></div>
                        </div>
                        <div>
                            <div class="text-[10px] text-gray-500 font-bold uppercase">Max Passengers</div>
                            <div class="font-semibold"><?= (int)($v['max_passengers'] ?? 0) ?></div>
                        </div>
                        <div>
                            <div class="text-[10px] text-gray-500 font-bold uppercase">Luggage</div>
                            <div class="font-semibold"><?= (int)($v['luggage_count'] ?? 0) ?></div>
                        </div>
                        <div>
                            <div class="text-[10px] text-gray-500 font-bold uppercase">Pricing Type</div>
                            <div class="font-semibold"><?= htmlspecialchars($v['pricing_type'] === 'km_wise' ? 'Per KM' : 'Per Day') ?></div>
                        </div>
                        <div>
                            <div class="text-[10px] text-gray-500 font-bold uppercase">Included KM/Day</div>
                            <div class="font-semibold"><?= (int)($v['included_km_per_day'] ?? 0) ?></div>
                        </div>
                        <div>
                            <div class="text-[10px] text-gray-500 font-bold uppercase">Extra KM Price</div>
                            <div class="font-semibold">LKR <?= number_format((float)($v['extra_km_price'] ?? 0), 2) ?></div>
                        </div>
                        <div>
                            <div class="text-[10px] text-gray-500 font-bold uppercase">Price Per KM</div>
                            <div class="font-semibold">LKR <?= number_format((float)($v['price_per_km'] ?? 0), 2) ?></div>
                        </div>
                        <div>
                            <div class="text-[10px] text-gray-500 font-bold uppercase">Delivery Fee</div>
                            <div class="font-semibold">LKR <?= number_format((float)($v['delivery_fee'] ?? 0), 2) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Features -->
                <div class="bg-white p-6 md:p-8 rounded-3xl shadow-sm border border-gray-100">
                    <h3 class="text-xl font-bold text-gray-900 mb-6">Vehicle Features</h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                        <?php 
                        $features = [
                            'has_ac' => ['Air Conditioning', 'fa-snowflake'],
                            'has_gps' => ['GPS Navigation', 'fa-map-marked-alt'],
                            'has_bluetooth' => ['Bluetooth', 'fa-bluetooth-b'],
                            'has_wifi' => ['WiFi', 'fa-wifi'],
                            'has_music_system' => ['Music System', 'fa-music'],
                            'has_charging_ports' => ['Charging Ports', 'fa-plug'],
                            'has_baby_seat' => ['Baby Seat', 'fa-baby'],
                            'has_sunroof' => ['Sunroof', 'fa-sun'],
                            'has_reverse_camera' => ['Reverse Camera', 'fa-camera'],
                            'has_airbags' => ['Airbags', 'fa-shield-alt']
                        ];
                        foreach($features as $key => $details):
                            $active = !empty($v[$key]);
                        ?>
                        <div class="flex items-center gap-3 <?= $active ? 'text-gray-900 font-medium' : 'text-gray-400 opacity-60' ?>">
                            <i class="fas <?= $details[1] ?> <?= $active ? 'text-secondary' : '' ?> text-lg w-6 text-center"></i>
                            <?= $details[0] ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Driver Info -->
                <?php if ($v['driver_option'] === 'with_driver' || $v['driver_option'] === 'both'): ?>
                <div class="bg-white p-6 md:p-8 rounded-3xl shadow-sm border border-gray-100 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-blue-50 rounded-bl-full -z-10"></div>
                    <h3 class="text-xl font-bold text-gray-900 mb-6">Driver Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php if ($v['driver_name']): ?>
                        <div><div class="text-xs text-gray-500 font-bold uppercase mb-1">Driver Name</div><div class="font-bold text-gray-900"><?= htmlspecialchars($v['driver_name']) ?></div></div>
                        <?php endif; ?>
                        <?php if ($v['driver_experience']): ?>
                        <div><div class="text-xs text-gray-500 font-bold uppercase mb-1">Experience</div><div class="font-medium text-gray-900"><?= htmlspecialchars($v['driver_experience']) ?></div></div>
                        <?php endif; ?>
                        <?php if ($v['driver_languages']): ?>
                        <div class="md:col-span-2"><div class="text-xs text-gray-500 font-bold uppercase mb-1">Languages Spoken</div><div class="font-medium text-gray-900"><?= htmlspecialchars($v['driver_languages']) ?></div></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Owner Contacts -->
                <div class="bg-gradient-to-br from-primary to-secondary p-8 rounded-3xl text-white shadow-xl relative overflow-hidden">
                    <i class="fas fa-car absolute -bottom-10 -right-10 text-9xl text-white opacity-10"></i>
                    <h3 class="text-xl font-bold mb-2">Have a question?</h3>
                    <p class="text-blue-100 text-sm mb-6 max-w-sm">Contact the vehicle owner directly for any inquiries, special requests, or custom pricing.</p>
                    <div class="flex flex-wrap gap-4">
                        <?php if ($v['owner_whatsapp']): ?>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $v['owner_whatsapp']) ?>" target="_blank" class="bg-[#25D366] hover:bg-green-600 text-white px-6 py-3 rounded-xl font-bold transition flex items-center gap-2 shadow-lg shadow-green-900/20">
                            <i class="fab fa-whatsapp text-xl"></i> WhatsApp Owner
                        </a>
                        <?php endif; ?>
                        <a href="tel:<?= htmlspecialchars($v['owner_phone']) ?>" class="bg-white/10 hover:bg-white/20 text-white px-6 py-3 rounded-xl font-bold transition border border-white/20 flex items-center gap-2">
                            <i class="fas fa-phone"></i> <?= htmlspecialchars($v['owner_phone']) ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Right Column: Sticky Booking Form -->
            <div>
                <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 p-6 md:p-8 sticky top-24">
                    <h3 class="text-2xl font-bold text-gray-900 mb-6">Book this Vehicle</h3>

                    <?php if ($booking_success): ?>
                        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-4 rounded-2xl mb-6 flex items-start gap-3">
                            <i class="fas fa-check-circle mt-1 text-green-500"></i>
                            <div>
                                <h4 class="font-bold">Booking Request Sent!</h4>
                                <p class="text-sm mt-1">Your booking request has been submitted successfully. The owner will contact you shortly to confirm.</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($booking_error): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 text-sm flex gap-2 items-center">
                            <i class="fas fa-exclamation-circle text-red-500"></i> <?= htmlspecialchars($booking_error) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="bookingForm" class="space-y-5">
                        <input type="hidden" name="book_vehicle" value="1">
                        
                        <!-- Dates -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase">Pickup Date</label>
                                <input type="date" name="pickup_date" id="pickup_date" required min="<?= date('Y-m-d') ?>" class="w-full px-4 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-secondary transition-all">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase">Return Date</label>
                                <input type="date" name="return_date" id="return_date" required min="<?= date('Y-m-d') ?>" class="w-full px-4 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-secondary transition-all">
                            </div>
                        </div>

                        <!-- Times -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase">Pickup Time</label>
                                <input type="time" name="pickup_time" required class="w-full px-4 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-secondary transition-all">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 mb-1.5 uppercase">Return Time</label>
                                <input type="time" name="return_time" class="w-full px-4 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-secondary transition-all">
                            </div>
                        </div>

                        <!-- Options -->
                        <div class="space-y-3 pt-2">
                            <?php if ($v['driver_option'] === 'both'): ?>
                            <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50 transition">
                                <input type="checkbox" name="with_driver" id="with_driver" class="w-5 h-5 text-secondary rounded">
                                <span class="font-medium text-sm text-gray-700">Need a Driver?</span>
                            </label>
                            <?php elseif ($v['driver_option'] === 'with_driver'): ?>
                            <input type="hidden" name="with_driver" value="1">
                            <div class="text-sm text-gray-600 bg-gray-50 p-3 rounded-xl border border-gray-100 flex items-center gap-2">
                                <i class="fas fa-info-circle text-secondary"></i> Includes Driver
                            </div>
                            <?php else: ?>
                            <div class="text-sm text-gray-600 bg-gray-50 p-3 rounded-xl border border-gray-100 flex items-center gap-2">
                                <i class="fas fa-car text-gray-400"></i> Self Drive Only
                            </div>
                            <?php endif; ?>

                            <?php if ($v['delivery_available']): ?>
                            <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50 transition">
                                <input type="checkbox" name="delivery_needed" id="delivery_needed" value="1" class="w-5 h-5 text-secondary rounded">
                                <span class="font-medium text-sm text-gray-700">Require Delivery? (+LKR <?= number_format($v['delivery_fee'], 2) ?>)</span>
                            </label>
                            <?php endif; ?>
                            
                            <?php if ($v['airport_delivery']): ?>
                            <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 cursor-pointer hover:bg-gray-50 transition">
                                <input type="checkbox" name="airport_pickup" value="1" class="w-5 h-5 text-secondary rounded">
                                <span class="font-medium text-sm text-gray-700">Airport Pickup</span>
                            </label>
                            <?php endif; ?>
                        </div>

                        <!-- Guest Details -->
                        <div class="space-y-4 pt-4 border-t border-gray-100">
                            <h4 class="font-bold text-gray-900">Your Details</h4>
                            <input type="text" name="guest_name" required placeholder="Full Name" value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>" class="w-full px-4 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-secondary transition-all">
                            <div class="grid grid-cols-2 gap-4">
                                <input type="text" name="guest_phone" required placeholder="Phone Number" class="w-full px-4 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-secondary transition-all">
                                <input type="email" name="guest_email" required placeholder="Email Address" class="w-full px-4 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-secondary transition-all">
                            </div>
                            <div id="license_field" class="<?= ($v['driver_option'] === 'with_driver') ? 'hidden' : '' ?>">
                                <input type="text" name="guest_driving_license" placeholder="Driving License No (Required for self-drive)" class="w-full px-4 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-secondary transition-all">
                            </div>
                        </div>

                        <!-- Price Calculator Output -->
                        <div class="bg-gray-50 p-5 rounded-2xl border border-gray-200 mt-4">
                            <div class="flex justify-between text-sm text-gray-600 mb-2">
                                <span id="calc_days_text">1 Day Rental</span>
                                <span id="calc_base_price">LKR 0.00</span>
                            </div>
                            <div id="calc_delivery_row" class="flex justify-between text-sm text-gray-600 mb-2 hidden">
                                <span>Delivery Fee</span>
                                <span>LKR <?= number_format($v['delivery_fee'] ?? 0, 2) ?></span>
                            </div>
                            <div class="flex justify-between text-lg font-bold text-gray-900 mt-3 pt-3 border-t border-gray-200">
                                <span>Total Price</span>
                                <span id="calc_total" class="text-secondary">LKR 0.00</span>
                            </div>
                            <div class="text-[10px] text-gray-500 mt-1 text-right">Pay later to the owner</div>
                        </div>

                        <button type="submit" class="w-full bg-gradient-to-r from-primary to-secondary text-white py-4 rounded-xl font-bold text-lg hover:shadow-lg hover:-translate-y-0.5 transition-all shadow-secondary/30">
                            Book Now
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <!-- Guest Reviews Section -->
        <div class="mt-20 pt-12 border-t border-gray-200">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-10">
                <div>
                    <h3 class="text-2xl font-bold text-gray-900 font-display">Guest reviews</h3>
                    <div class="flex items-center gap-3 mt-2">
                        <div class="bg-primary text-white font-black px-3 py-1.5 rounded-lg text-lg"><?php echo $avg_rating; ?></div>
                        <div>
                            <p class="font-bold text-gray-850 leading-none">Overall Score</p>
                            <p class="text-[12px] text-gray-500 mt-1"><?php echo $review_count; ?> real reviews from our guests</p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (empty($reviews)): ?>
                <div class="bg-white rounded-3xl p-10 text-center border border-gray-150 shadow-sm">
                    <p class="text-gray-500 font-medium">No reviews yet for this vehicle. Be the first to share your experience after your ride!</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($reviews as $review): ?>
                        <div class="bg-white p-6 rounded-3xl border border-gray-150 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center font-bold text-primary">
                                            <?php echo strtoupper(substr($review['user_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <p class="font-bold text-sm text-gray-800 leading-tight"><?php echo htmlspecialchars($review['user_name']); ?></p>
                                            <p class="text-[11px] text-gray-500">Guest</p>
                                        </div>
                                    </div>
                                    <div class="flex text-gold text-[10px]">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p class="text-[14px] text-gray-700 leading-relaxed italic">"<?php echo nl2br(htmlspecialchars($review['comment'])); ?>"</p>
                            </div>
                            <p class="text-[10px] text-gray-400 font-bold mt-4 uppercase tracking-tighter"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Rides Near Me Section -->
        <?php if (!empty($rides_near)): ?>
        <div class="mt-20 pt-12 border-t border-gray-200 relative">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <span class="inline-flex items-center gap-2 px-3 py-1 bg-blue-50 text-secondary border border-blue-100 rounded-full text-[10px] font-black uppercase tracking-wider mb-2">
                        <i class="fas fa-car"></i> Rides Near Me
                    </span>
                    <h3 class="text-2xl font-bold font-display text-primary">Rides Near Me</h3>
                    <p class="text-xs text-gray-500 mt-1 font-medium">Explore other transport options nearby in <?php echo htmlspecialchars($v['city'] ?: $v['district']); ?></p>
                </div>
                <div class="flex gap-2">
                    <button id="rides-near-prev" class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center hover:bg-gray-50 hover:border-gray-300 transition-all shadow-sm bg-white" aria-label="Previous rides">
                        <i class="fas fa-chevron-left text-gray-600 text-xs"></i>
                    </button>
                    <button id="rides-near-next" class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center hover:bg-gray-50 hover:border-gray-300 transition-all shadow-sm bg-white" aria-label="Next rides">
                        <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                    </button>
                </div>
            </div>

            <div class="relative overflow-hidden">
                <div id="rides-near-slider" class="flex overflow-x-auto no-scrollbar gap-5 pb-4 snap-x snap-mandatory scroll-smooth w-full">
                    <?php foreach ($rides_near as $rn):
                        $rn_cover = $rn['cover_image'] ?: 'assets/placeholder-vehicle.png';
                        
                        $pricing_type = $rn['pricing_type'] ?? 'day_wise';
                        if ($pricing_type === 'day_wise') {
                            $rn_price_val = $rn['price_per_day'];
                            $rn_price_label = '/day';
                        } else {
                            $rn_price_val = $rn['price_per_km'];
                            $rn_price_label = '/km';
                        }
                        $rn_price = ($currency === 'USD') ? ($rn_price_val / $exchange_rate) : $rn_price_val;
                    ?>
                    <a href="vehicle_details.php?id=<?php echo $rn['id']; ?>" class="flex-none w-[280px] sm:w-[300px] snap-start group" style="text-decoration:none;">
                        <div class="relative rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-all duration-400 border border-gray-200 bg-white">
                            <div class="relative h-44 overflow-hidden">
                                <img src="<?php echo htmlspecialchars($rn_cover); ?>" alt="<?php echo htmlspecialchars($rn['property_name']); ?>"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                                     onerror="this.src='assets/placeholder-vehicle.png'">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                                <div class="absolute top-3 left-3 bg-black/55 text-white text-[10px] font-black px-2 py-0.5 rounded shadow">
                                    <i class="fas fa-car mr-1"></i><?php echo htmlspecialchars(ucfirst($rn['vehicle_category'] ?? 'Vehicle')); ?>
                                </div>
                                <div class="absolute bottom-3 left-3 right-3">
                                    <h4 class="text-white font-bold text-sm leading-tight truncate"><?php echo htmlspecialchars($rn['property_name']); ?></h4>
                                    <p class="text-white/80 text-xs mt-0.5"><i class="fas fa-map-marker-alt mr-1"></i><?php echo htmlspecialchars($rn['city']); ?>, <?php echo htmlspecialchars($rn['district']); ?></p>
                                </div>
                            </div>
                            <div class="p-4 flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] text-neutral-400 font-bold uppercase tracking-wider">Starting from</p>
                                    <p class="text-base font-black text-neutral-900"><?php echo $currency; ?> <?php echo number_format($rn_price, ($currency === 'USD' ? 2 : 0)); ?><span class="text-xs text-neutral-500 font-medium"><?php echo $rn_price_label; ?></span></p>
                                </div>
                                <div class="bg-secondary text-white text-xs font-bold px-3 py-2 rounded-xl group-hover:bg-primary transition-colors">Select</div>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Hotels Near Me Section -->
        <?php if (!empty($hotels_near)): ?>
        <div class="mt-20 pt-12 border-t border-gray-200 relative">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <span class="inline-flex items-center gap-2 px-3 py-1 bg-blue-50 text-secondary border border-blue-100 rounded-full text-[10px] font-black uppercase tracking-wider mb-2">
                        <i class="fas fa-hotel"></i> Stays Near Me
                    </span>
                    <h3 class="text-2xl font-bold font-display text-primary">Hotels Near Me</h3>
                    <p class="text-xs text-gray-500 mt-1 font-medium">Explore stays nearby in <?php echo htmlspecialchars($v['city'] ?: $v['district']); ?></p>
                </div>
                <div class="flex gap-2">
                    <button id="hotels-near-prev" class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center hover:bg-gray-50 hover:border-gray-300 transition-all shadow-sm bg-white" aria-label="Previous stays">
                        <i class="fas fa-chevron-left text-gray-600 text-xs"></i>
                    </button>
                    <button id="hotels-near-next" class="w-10 h-10 rounded-full border border-gray-200 flex items-center justify-center hover:bg-gray-50 hover:border-gray-300 transition-all shadow-sm bg-white" aria-label="Next stays">
                        <i class="fas fa-chevron-right text-gray-600 text-xs"></i>
                    </button>
                </div>
            </div>

            <div class="relative overflow-hidden">
                <div id="hotels-near-slider" class="flex overflow-x-auto no-scrollbar gap-5 pb-4 snap-x snap-mandatory scroll-smooth w-full">
                    <?php foreach ($hotels_near as $hn):
                        $hn_cover = $hn['cover_image'] ?: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=800&q=80';
                        $hn_price = ($currency === 'USD') ? ($hn['price_lkr'] / $exchange_rate) : $hn['price_lkr'];
                    ?>
                    <a href="hotel_info.php?id=<?php echo $hn['id']; ?>" class="flex-none w-[280px] sm:w-[300px] snap-start group" style="text-decoration:none;">
                        <div class="relative rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-all duration-400 border border-gray-200 bg-white">
                            <div class="relative h-44 overflow-hidden">
                                <img src="<?php echo htmlspecialchars($hn_cover); ?>" alt="<?php echo htmlspecialchars($hn['property_name']); ?>"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                                <?php if (isset($hn['is_deal']) && $hn['is_deal']): ?>
                                <div class="absolute top-3 left-3">
                                    <span class="bg-red-500 text-white text-[10px] font-black px-2 py-0.5 rounded shadow">DEAL</span>
                                </div>
                                <?php endif; ?>
                                <div class="absolute bottom-3 left-3 right-3">
                                    <h4 class="text-white font-bold text-sm leading-tight truncate"><?php echo htmlspecialchars($hn['property_name']); ?></h4>
                                    <p class="text-white/80 text-xs mt-0.5"><i class="fas fa-map-marker-alt mr-1"></i><?php echo htmlspecialchars($hn['city']); ?>, <?php echo htmlspecialchars($hn['district']); ?></p>
                                </div>
                            </div>
                            <div class="p-4 flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] text-neutral-400 font-bold uppercase tracking-wider">Starting from</p>
                                    <p class="text-base font-black text-neutral-900"><?php echo $currency; ?> <?php echo number_format($hn_price, ($currency === 'USD' ? 2 : 0)); ?><span class="text-xs text-neutral-500 font-medium">/night</span></p>
                                </div>
                                <div class="bg-secondary text-white text-xs font-bold px-3 py-2 rounded-xl group-hover:bg-primary transition-colors">Book</div>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Slider Styles -->
        <style>
            .no-scrollbar::-webkit-scrollbar { display: none; }
            .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        </style>

        <!-- Sliders JavaScript -->
        <script>
        document.addEventListener("DOMContentLoaded", function () {
            const initSlider = (sliderId, prevId, nextId) => {
                const slider = document.getElementById(sliderId);
                const prev = document.getElementById(prevId);
                const next = document.getElementById(nextId);
                if (!slider) return;

                let autoScrollInterval;
                const startAutoScroll = () => {
                    autoScrollInterval = setInterval(() => {
                        const card = slider.querySelector('.snap-start');
                        const cardWidth = card ? card.offsetWidth : 300;
                        slider.scrollBy({ left: cardWidth + 20, behavior: 'smooth' });
                        // Reset to start if end reached
                        if (slider.scrollLeft + slider.clientWidth >= slider.scrollWidth - 10) {
                            setTimeout(() => { slider.scrollTo({ left: 0, behavior: 'smooth' }); }, 1000);
                        }
                    }, 4000);
                };

                const stopAutoScroll = () => clearInterval(autoScrollInterval);

                startAutoScroll();
                slider.addEventListener('mouseenter', stopAutoScroll);
                slider.addEventListener('mouseleave', startAutoScroll);

                if (prev) {
                    prev.addEventListener('click', () => {
                        const card = slider.querySelector('.snap-start');
                        const cardWidth = card ? card.offsetWidth : 300;
                        slider.scrollBy({ left: -(cardWidth + 20), behavior: 'smooth' });
                    });
                }
                if (next) {
                    next.addEventListener('click', () => {
                        const card = slider.querySelector('.snap-start');
                        const cardWidth = card ? card.offsetWidth : 300;
                        slider.scrollBy({ left: cardWidth + 20, behavior: 'smooth' });
                    });
                }
            };

            initSlider('rides-near-slider', 'rides-near-prev', 'rides-near-next');
            initSlider('hotels-near-slider', 'hotels-near-prev', 'hotels-near-next');
        });
        </script>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        const pricePerDay = <?= (float)$v['price_per_day'] ?>;
        const pricePerKm = <?= (float)$v['price_per_km'] ?>;
        const deliveryFee = <?= (float)($v['delivery_fee'] ?? 0) ?>;
        const pricingType = '<?= $v['pricing_type'] ?>';
        
        const pickupInput = document.getElementById('pickup_date');
        const returnInput = document.getElementById('return_date');
        const deliveryInput = document.getElementById('delivery_needed');
        const driverCheckbox = document.getElementById('with_driver');
        const licenseField = document.getElementById('license_field');
        const licenseInput = document.querySelector('input[name="guest_driving_license"]');

        function calculatePrice() {
            let days = 1;
            if (pickupInput.value && returnInput.value) {
                const start = new Date(pickupInput.value);
                const end = new Date(returnInput.value);
                const diffTime = Math.abs(end - start);
                days = Math.max(1, Math.ceil(diffTime / (1000 * 60 * 60 * 24)));
            }

            let baseTotal = 0;
            if (pricingType === 'day_wise') {
                baseTotal = pricePerDay * days;
                document.getElementById('calc_days_text').innerText = `${days} Day(s) Rental`;
            } else {
                baseTotal = pricePerKm * 100; // placeholder for km logic
                document.getElementById('calc_days_text').innerText = `Estimated (100km)`;
            }

            document.getElementById('calc_base_price').innerText = 'LKR ' + baseTotal.toLocaleString('en-US', {minimumFractionDigits: 2});

            let finalTotal = baseTotal;
            if (deliveryInput && deliveryInput.checked) {
                document.getElementById('calc_delivery_row').classList.remove('hidden');
                finalTotal += deliveryFee;
            } else if (document.getElementById('calc_delivery_row')) {
                document.getElementById('calc_delivery_row').classList.add('hidden');
            }

            document.getElementById('calc_total').innerText = 'LKR ' + finalTotal.toLocaleString('en-US', {minimumFractionDigits: 2});
        }

        if (pickupInput) pickupInput.addEventListener('change', calculatePrice);
        if (returnInput) returnInput.addEventListener('change', calculatePrice);
        if (deliveryInput) deliveryInput.addEventListener('change', calculatePrice);

        if (driverCheckbox) {
            driverCheckbox.addEventListener('change', (e) => {
                if (e.target.checked) {
                    licenseField.classList.add('hidden');
                    licenseInput.removeAttribute('required');
                } else {
                    licenseField.classList.remove('hidden');
                    licenseInput.setAttribute('required', 'required');
                }
            });
        }

        // Thumbnail click to swap main image
        const mainImage = document.getElementById('main-vehicle-image');
        document.querySelectorAll('.vehicle-thumb').forEach(thumb => {
            thumb.addEventListener('click', () => {
                const imgSrc = thumb.getAttribute('data-img');
                if (imgSrc && mainImage) {
                    mainImage.src = imgSrc;
                }
            });
        });

        // Init calc
        calculatePrice();
    </script>
</body>
</html>
