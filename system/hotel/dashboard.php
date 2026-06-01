<?php
require_once '../../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$view = $_GET['view'] ?? 'dashboard';
$selected_property_id = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;

// Fetch properties for selector
if ($_SESSION['role'] === 'admin') {
    $properties_stmt = $pdo->query("SELECT id, property_name FROM properties ORDER BY property_name");
    $properties = $properties_stmt->fetchAll();
} else {
    $properties_stmt = $pdo->prepare("SELECT id, property_name FROM properties WHERE owner_id = ? ORDER BY property_name");
    $properties_stmt->execute([$user_id]);
    $properties = $properties_stmt->fetchAll();
}

// Resolve selected property
$property = null;
$property_id = 0;
if ($selected_property_id) {
    if ($_SESSION['role'] === 'admin') {
        $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ? LIMIT 1");
        $stmt->execute([$selected_property_id]);
        $property = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ? AND owner_id = ? LIMIT 1");
        $stmt->execute([$selected_property_id, $user_id]);
        $property = $stmt->fetch();
    }
}

if (!$property && !empty($properties)) {
    $first_property_id = (int)$properties[0]['id'];
    if ($_SESSION['role'] === 'admin') {
        $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ? LIMIT 1");
        $stmt->execute([$first_property_id]);
        $property = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ? AND owner_id = ? LIMIT 1");
        $stmt->execute([$first_property_id, $user_id]);
        $property = $stmt->fetch();
    }
}

if (!$property && $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

$property_id = $property['id'] ?? 0;

// --- Post Handlers ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'new_inplace_booking') {
    $room_id = $_POST['room_id'];
    $guest_name = $_POST['guest_name'];
    $guest_phone = $_POST['guest_phone'];
    $guest_email = $_POST['guest_email'] ?? '';
    $guest_nic = $_POST['guest_nic'] ?? '';
    $guest_address = $_POST['guest_address'] ?? '';
    $country = $_POST['country'] ?? 'Sri Lanka';
    $room_number = $_POST['room_number'] ?? '';
    $check_in = $_POST['check_in'] ?? date('Y-m-d');
    $check_out = $_POST['check_out'] ?? date('Y-m-d', strtotime('+1 day'));
    $adults = $_POST['adults'] ?? 1;
    $children = $_POST['children'] ?? 0;
    $price = $_POST['price'] ?? 0;
    $paid = $_POST['paid'] ?? 0;
    $status = 'confirmed';

    $ins = $pdo->prepare("INSERT INTO bookings (property_id, room_id, booking_type, guest_name, guest_phone, guest_email, guest_nic, guest_address, country, room_number, check_in_date, check_out_date, adults, children, price_per_room, total_price, amount_paid, status) VALUES (?, ?, 'inplace', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $ins->execute([$property_id, $room_id, $guest_name, $guest_phone, $guest_email, $guest_nic, $guest_address, $country, $room_number, $check_in, $check_out, $adults, $children, $price, $price, $paid, $status]);

    header("Location: dashboard.php?msg=success");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_boost') {
    $package_id = $_POST['package_id'];
    $pkg_stmt = $pdo->prepare("SELECT duration_days, price_lkr FROM boost_packages WHERE id = ?");
    $pkg_stmt->execute([$package_id]);
    $pkg = $pkg_stmt->fetch();
    if ($pkg) {
        $start_date = date('Y-m-d');
        $duration = $pkg['duration_days'];
        $amount = $pkg['price_lkr'];
        $ins = $pdo->prepare("INSERT INTO property_boosts (property_id, package_id, start_date, duration_days, amount, status, payment_status) VALUES (?, ?, ?, ?, ?, 'pending', 'pending')");
        $ins->execute([$property_id, $package_id, $start_date, $duration, $amount]);
        header("Location: dashboard.php?msg=boost_requested");
        exit();
    }
}

// --- Deal of the Day Handlers ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_deal') {
    $deal_room_id   = (int)($_POST['deal_room_id'] ?? 0);
    $original_price = (float)($_POST['original_price'] ?? 0);
    $deal_price     = (float)($_POST['deal_price'] ?? 0);
    $deal_label     = trim($_POST['deal_label'] ?? '');
    $valid_from     = $_POST['valid_from'] ?? date('Y-m-d');
    $valid_until    = $_POST['valid_until'] ?? date('Y-m-d', strtotime('+7 days'));

    // Fetch room name for snapshot
    $rn_stmt = $pdo->prepare("SELECT room_name FROM property_rooms WHERE id = ? AND property_id = ?");
    $rn_stmt->execute([$deal_room_id, $property_id]);
    $rn_row = $rn_stmt->fetch();
    $deal_room_name = $rn_row['room_name'] ?? '';

    if ($deal_room_id && $original_price > 0 && $deal_price > 0 && $deal_price < $original_price && $deal_room_name) {
        $ins = $pdo->prepare("INSERT INTO deals_of_the_day (property_id, room_id, room_name, original_price, deal_price, deal_label, valid_from, valid_until, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $ins->execute([$property_id, $deal_room_id, $deal_room_name, $original_price, $deal_price, $deal_label ?: null, $valid_from, $valid_until]);
        header("Location: dashboard.php?view=deals&msg=deal_saved&property_id=$property_id");
        exit();
    } else {
        header("Location: dashboard.php?view=deals&msg=deal_error&property_id=$property_id");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_deal') {
    $deal_id = (int)($_POST['deal_id'] ?? 0);
    $del = $pdo->prepare("DELETE FROM deals_of_the_day WHERE id = ? AND property_id = ?");
    $del->execute([$deal_id, $property_id]);
    header("Location: dashboard.php?view=deals&msg=deal_deleted&property_id=$property_id");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_deal') {
    $deal_id     = (int)($_POST['deal_id'] ?? 0);
    $new_status  = (int)($_POST['new_status'] ?? 0);
    $upd = $pdo->prepare("UPDATE deals_of_the_day SET is_active = ? WHERE id = ? AND property_id = ?");
    $upd->execute([$new_status, $deal_id, $property_id]);
    header("Location: dashboard.php?view=deals&property_id=$property_id");
    exit();
}

// Fetch all room types for this property
$rooms_stmt = $pdo->prepare("SELECT id, room_name, price_lkr FROM property_rooms WHERE property_id = ?");
$rooms_stmt->execute([$property_id]);
$property_rooms = $rooms_stmt->fetchAll();

// --- Real Data Fetching for Dashboard ---
$total_bookings_stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE property_id = ?");
$total_bookings_stmt->execute([$property_id]);
$total_bookings_count = $total_bookings_stmt->fetchColumn();

$online_revenue_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid), 0) FROM bookings WHERE property_id = ? AND booking_type = 'online'");
$online_revenue_stmt->execute([$property_id]);
$online_revenue = $online_revenue_stmt->fetchColumn() ?? 0;

$inplace_revenue_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid), 0) FROM bookings WHERE property_id = ? AND booking_type = 'inplace'");
$inplace_revenue_stmt->execute([$property_id]);
$inplace_revenue = $inplace_revenue_stmt->fetchColumn() ?? 0;

$pending_bookings_stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE property_id = ? AND status = 'pending'");
$pending_bookings_stmt->execute([$property_id]);
$pending_bookings = $pending_bookings_stmt->fetchColumn();

$service_fee_total_stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price * 0.2), 0) FROM bookings WHERE property_id = ? AND booking_type = 'online'");
$service_fee_total_stmt->execute([$property_id]);
$service_fee_total = (float)$service_fee_total_stmt->fetchColumn();

$service_fee_paid_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM hotel_service_payments WHERE property_id = ?");
$service_fee_paid_stmt->execute([$property_id]);
$service_fee_paid = (float)$service_fee_paid_stmt->fetchColumn();
$service_fee_due = max(0, $service_fee_total - $service_fee_paid);

$recent_bookings_stmt = $pdo->prepare("
    SELECT b.*, pr.room_name
    FROM bookings b
    JOIN property_rooms pr ON b.room_id = pr.id
    WHERE b.property_id = ?
    ORDER BY b.created_at DESC
    LIMIT 5
");
$recent_bookings_stmt->execute([$property_id]);
$recent_bookings = $recent_bookings_stmt->fetchAll();

$events_stmt = $pdo->prepare("
    SELECT b.guest_name, b.room_number, b.check_in_date, b.check_out_date, b.status, pr.room_name
    FROM bookings b
    JOIN property_rooms pr ON b.room_id = pr.id
    WHERE b.property_id = ?
");
$events_stmt->execute([$property_id]);
$bookings_raw = $events_stmt->fetchAll();

$calendar_events = [];
foreach ($bookings_raw as $b) {
    $color = '#fb923c';
    if ($b['status'] == 'confirmed') $color = '#4ade80';
    if ($b['status'] == 'checked_in') $color = '#3b82f6';
    if ($b['status'] == 'checked_out') $color = '#94a3b8';
    if ($b['status'] == 'cancelled') $color = '#ef4444';
    $calendar_events[] = [
        'title' => $b['guest_name'] . ' (' . ($b['room_number'] ? '#' . $b['room_number'] : $b['room_name']) . ')',
        'start' => $b['check_in_date'],
        'end'   => $b['check_out_date'],
        'color' => $color
    ];
}

$today = date('Y-m-d');
$rooms_availability_stmt = $pdo->prepare("
    SELECT pr.id, pr.room_name, pr.total_rooms,
           (SELECT COUNT(*) FROM bookings b
            WHERE b.room_id = pr.id AND b.property_id = ? AND b.check_in_date <= ? AND b.check_out_date > ? AND b.status IN ('confirmed', 'checked_in')) as booked_count
    FROM property_rooms pr WHERE pr.property_id = ?
");
$rooms_availability_stmt->execute([$property_id, $today, $today, $property_id]);
$rooms_availability = $rooms_availability_stmt->fetchAll();

$boost_packages_stmt = $pdo->query("SELECT * FROM boost_packages WHERE is_active = 1");
$boost_packages = $boost_packages_stmt->fetchAll();

$active_boost_stmt = $pdo->prepare("SELECT * FROM property_boosts WHERE property_id = ? AND status IN ('pending', 'active') AND (status = 'pending' OR DATE_ADD(start_date, INTERVAL duration_days DAY) >= CURDATE()) ORDER BY created_at DESC LIMIT 1");
$active_boost_stmt->execute([$property_id]);
$current_boost = $active_boost_stmt->fetch();

// --- Vehicle Stats ---
$total_vehicles = $pdo->prepare("SELECT COUNT(*) FROM properties WHERE owner_id = ? AND business_type = 'vehicle'");
$total_vehicles->execute([$_SESSION['user_id']]);
$vehicle_count = $total_vehicles->fetchColumn();

$total_vbookings = $pdo->prepare("SELECT COUNT(*) FROM bookings b JOIN properties p ON b.property_id = p.id WHERE p.owner_id = ? AND b.booking_category = 'vehicle'");
$total_vbookings->execute([$_SESSION['user_id']]);
$vehicle_booking_count = $total_vbookings->fetchColumn();

// --- Vehicle Calendar Events ---
$vehicle_events_stmt = $pdo->prepare("
    SELECT b.guest_name, b.check_in_date, b.check_out_date, b.status, p.property_name
    FROM bookings b
    JOIN properties p ON b.property_id = p.id
    WHERE p.owner_id = ? AND b.booking_category = 'vehicle'
");
$vehicle_events_stmt->execute([$_SESSION['user_id']]);
$vehicle_bookings_raw = $vehicle_events_stmt->fetchAll();

foreach ($vehicle_bookings_raw as $vb) {
    $calendar_events[] = [
        'title' => '🚗 ' . $vb['guest_name'] . ' (' . $vb['property_name'] . ')',
        'start' => $vb['check_in_date'],
        'end'   => $vb['check_out_date'],
        'color' => '#f59e0b'
    ];
}

// --- Deals of the Day ---
$property_deals_stmt = $pdo->prepare("
    SELECT d.*,
           CASE WHEN d.is_active = 1 AND d.valid_from <= CURDATE() AND d.valid_until >= CURDATE() THEN 'active' ELSE 'ended' END as deal_status
    FROM deals_of_the_day d
    WHERE d.property_id = ?
    ORDER BY d.created_at DESC
");
$property_deals_stmt->execute([$property_id]);
$property_deals = $property_deals_stmt->fetchAll();
$active_deals_count = count(array_filter($property_deals, fn($d) => $d['deal_status'] === 'active'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Dashboard - <?php echo htmlspecialchars($property['property_name'] ?? 'Bookingjaunt'); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
</head>

<body class="flex min-h-screen overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content overflow-y-auto" style="position:relative; z-index:1;">

        <!-- Top Nav (Glass) -->
        <header class="glass-header sticky top-0 z-40 px-2 lg:px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden w-10 h-10 rounded-xl flex items-center justify-center transition-all btn-glass">
                    <i class="fas fa-bars-staggered"></i>
                </button>
                <form method="GET" class="hidden md:block">
                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                    <label class="sr-only" for="propertySelect">Property</label>
                    <select id="propertySelect" name="property_id" onchange="this.form.submit()" class="glass-input px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest">
                        <?php if (!empty($properties)): ?>
                            <?php foreach ($properties as $prop): ?>
                                <option value="<?php echo (int)$prop['id']; ?>" <?php echo (int)$prop['id'] === (int)$property_id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($prop['property_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="">No properties</option>
                        <?php endif; ?>
                    </select>
                </form>
                <div class="relative max-w-md hidden md:block">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-xs" style="color:var(--text-muted);"></i>
                    <input type="text" placeholder="Search bookings, guests..." class="glass-input w-full pl-11 pr-4 py-2.5 rounded-xl text-xs">
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="../../index.php" target="_blank" class="btn-glass hidden xl:flex">
                    <i class="fas fa-external-link-alt"></i> Visit Site
                </a>
                <button onclick="document.getElementById('bookingModal').classList.remove('hidden')" class="btn-primary">
                    <i class="fas fa-plus"></i>
                    <span class="hidden sm:inline">New Booking</span>
                </button>
                <button onclick="toggleRoomAvailability()" class="btn-glass">
                    <i class="fas fa-door-open"></i>
                    <span class="hidden sm:inline">Room Status</span>
                </button>
                <div class="w-px h-8 mx-2" style="background:var(--glass-border);"></div>
                <button class="w-10 h-10 rounded-xl transition-colors btn-glass relative">
                    <i class="far fa-bell"></i>
                    <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-transparent"></span>
                </button>
            </div>
        </header>

        <div class="py-4 px-2 lg:py-8 lg:px-4">

            <?php if ($view === 'dashboard'): ?>

                <!-- Success Msg -->
                <?php if (isset($_GET['msg']) && $_GET['msg'] === 'success'): ?>
                <div class="glass-card mb-6 p-4 flex items-center gap-3 anim-up" style="border-color:rgba(74,222,128,0.3); background:rgba(74,222,128,0.10);">
                    <i class="fas fa-check-circle" style="color:#4ade80;"></i>
                    <span style="color:#86efac; font-weight:700; font-size:0.875rem;">Booking created successfully!</span>
                </div>
                <?php endif; ?>

                <!-- Boost Banner -->
                <?php if ($current_boost): ?>
                    <div class="glass-card mb-8 p-8 flex justify-between items-center anim-up" style="background:rgba(59,130,246,0.18); border-color:rgba(96,165,250,0.35);">
                        <div>
                            <h2 class="text-2xl font-black mb-2" style="color:white;">Your property boost is <?php echo htmlspecialchars($current_boost['status']); ?>!</h2>
                            <p style="color:rgba(147,197,253,0.9); font-weight:500;">
                                <?php if ($current_boost['status'] === 'active'): ?>
                                    Valid until <?php echo date('M d, Y', strtotime($current_boost['start_date'] . ' + ' . $current_boost['duration_days'] . ' days')); ?>.
                                <?php else: ?>
                                    Your request is currently under review by the admin team.
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background:rgba(59,130,246,0.25);">
                            <i class="fas fa-rocket text-2xl" style="color:#93c5fd;"></i>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="glass-card mb-8 p-8 flex justify-between items-center anim-up" style="background:rgba(251,146,60,0.15); border-color:rgba(251,146,60,0.3);">
                        <div>
                            <h2 class="text-2xl font-black mb-2" style="color:white;">Need more reservations? Make your property featured!</h2>
                            <p style="color:rgba(253,186,116,0.85); font-weight:500;">Boost your property to the top of our listings and reach thousands of daily visitors.</p>
                        </div>
                        <button onclick="openBoostModal()" class="btn-glass shrink-0" style="border-color:rgba(251,146,60,0.5); color:#fdba74;">
                            <i class="fas fa-bolt"></i> See Packages
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
                    <div class="glass-card p-5 flex flex-col anim-up">
                        <div class="stat-icon mb-4" style="background:rgba(96,165,250,0.15);"><i class="fas fa-calendar-check" style="color:#93c5fd;"></i></div>
                        <p class="stat-label">Total Bookings</p>
                        <h3 class="stat-value"><?php echo number_format($total_bookings_count); ?></h3>
                    </div>
                    <div class="glass-card p-5 flex flex-col anim-up-2">
                        <div class="stat-icon mb-4" style="background:rgba(52,211,153,0.15);"><i class="fas fa-globe" style="color:#6ee7b7;"></i></div>
                        <p class="stat-label">Online Revenue</p>
                        <h3 class="stat-value" style="font-size:1.1rem;">LKR <?php echo number_format($online_revenue); ?></h3>
                    </div>
                    <div class="glass-card p-5 flex flex-col anim-up">
                        <div class="stat-icon mb-4" style="background:rgba(125,211,252,0.15);"><i class="fas fa-receipt" style="color:#7dd3fc;"></i></div>
                        <p class="stat-label">Physical Revenue</p>
                        <h3 class="stat-value" style="font-size:1.1rem;">LKR <?php echo number_format($inplace_revenue); ?></h3>
                    </div>
                    <div class="glass-card p-5 flex flex-col anim-up-2">
                        <div class="stat-icon mb-4" style="background:rgba(251,146,60,0.15);"><i class="fas fa-clock" style="color:#fdba74;"></i></div>
                        <p class="stat-label">Pending</p>
                        <h3 class="stat-value"><?php echo $pending_bookings; ?></h3>
                    </div>
                    <div class="glass-card p-5 flex flex-col anim-up">
                        <div class="stat-icon mb-4" style="background:rgba(196,181,253,0.15);"><i class="fas fa-bed" style="color:#c4b5fd;"></i></div>
                        <p class="stat-label">Total Rooms</p>
                        <h3 class="stat-value">
                            <?php
                                $rooms_count_stmt = $pdo->prepare("SELECT SUM(total_rooms) FROM property_rooms WHERE property_id = ?");
                                $rooms_count_stmt->execute([$property_id]);
                                echo $rooms_count_stmt->fetchColumn() ?: 0;
                            ?>
                        </h3>
                    </div>
                    <div class="glass-card p-5 flex flex-col anim-up-2">
                        <div class="stat-icon mb-4" style="background:rgba(248,113,113,0.15);"><i class="fas fa-file-invoice" style="color:#fca5a5;"></i></div>
                        <p class="stat-label">Service Fee Due</p>
                        <h3 class="stat-value" style="font-size:1.1rem;">LKR <?php echo number_format($service_fee_due); ?></h3>
                    </div>
                    <div class="glass-card p-5 flex flex-col anim-up">
                        <div class="stat-icon mb-4" style="background:rgba(245,158,11,0.18);"><i class="fas fa-car" style="color:#fbbf24;"></i></div>
                        <p class="stat-label">My Vehicles</p>
                        <h3 class="stat-value"><?php echo number_format($vehicle_count); ?></h3>
                    </div>
                    <div class="glass-card p-5 flex flex-col anim-up-2">
                        <div class="stat-icon mb-4" style="background:rgba(251,191,36,0.15);"><i class="fas fa-car-side" style="color:#fcd34d;"></i></div>
                        <p class="stat-label">Vehicle Bookings</p>
                        <h3 class="stat-value"><?php echo number_format($vehicle_booking_count); ?></h3>
                    </div>
                </div>

                <!-- Recent Bookings Table -->
                <div class="glass-table mb-8 anim-up">
                    <div class="px-6 py-5 flex justify-between items-center" style="border-bottom:1px solid var(--glass-border);">
                        <h2 class="text-lg font-black" style="color:white;">Recent Bookings</h2>
                        <a href="bookings.php" class="btn-glass" style="font-size:0.55rem;">View All</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr>
                                    <th class="glass-th">Guest</th>
                                    <th class="glass-th">Room</th>
                                    <th class="glass-th">Stay</th>
                                    <th class="glass-th">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_bookings)): ?>
                                    <tr><td colspan="4" class="glass-td text-center" style="color:var(--text-muted);">No bookings yet.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($recent_bookings as $rb): ?>
                                    <tr class="glass-tr">
                                        <td class="glass-td">
                                            <div class="font-bold" style="color:white;"><?php echo htmlspecialchars($rb['guest_name']); ?></div>
                                            <div class="text-[10px]" style="color:var(--text-muted);"><?php echo htmlspecialchars($rb['guest_phone']); ?></div>
                                        </td>
                                        <td class="glass-td" style="font-weight:600; color:var(--text-secondary); font-size:0.8rem;"><?php echo htmlspecialchars($rb['room_name']); ?></td>
                                        <td class="glass-td" style="color:var(--text-secondary); font-size:0.8rem;">
                                            <?php echo date('M d', strtotime($rb['check_in_date'])); ?> – <?php echo date('M d', strtotime($rb['check_out_date'])); ?>
                                        </td>
                                        <td class="glass-td">
                                            <span class="badge badge-<?php echo $rb['status']; ?>"><?php echo $rb['status']; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Booking Calendar -->
                <div class="glass-card mb-8 overflow-hidden anim-up-2" style="border-radius:1.5rem;">
                    <div class="px-8 py-6 flex justify-between items-center" style="border-bottom:1px solid var(--glass-border);">
                        <div>
                            <h2 class="text-xl font-black" style="color:white;">Booking Schedule</h2>
                            <p class="text-xs font-bold uppercase tracking-widest mt-1" style="color:var(--text-muted);">Manage your room availability</p>
                        </div>
                        <div class="flex items-center gap-5">
                            <div class="flex items-center gap-2"><div class="w-3 h-3 rounded-full bg-green-400"></div><span class="text-[10px] font-bold uppercase" style="color:var(--text-muted);">Confirmed</span></div>
                            <div class="flex items-center gap-2"><div class="w-3 h-3 rounded-full bg-orange-400"></div><span class="text-[10px] font-bold uppercase" style="color:var(--text-muted);">Pending</span></div>
                            <div class="flex items-center gap-2"><div class="w-3 h-3 rounded-full bg-blue-400"></div><span class="text-[10px] font-bold uppercase" style="color:var(--text-muted);">Checked-in</span></div>
                        </div>
                    </div>
                    <div class="p-6">
                        <div id='calendar'></div>
                    </div>
                </div>

            <?php elseif ($view === 'properties'): ?>
                <!-- My Properties View -->
                <div class="mb-8 flex justify-between items-end anim-up">
                    <div>
                        <h2 class="text-2xl font-black" style="color:white;">My Properties</h2>
                        <p class="text-sm font-medium mt-1" style="color:var(--text-secondary);">Manage and update your registered properties</p>
                    </div>
                    <a href="../../property_wizard.php" class="btn-primary">Add New Property</a>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8">
                    <div class="glass-card overflow-hidden group" style="border-radius:2rem;">
                        <div class="h-56 relative overflow-hidden">
                            <img src="../../<?php echo $property['cover_image'] ?: 'assets/placeholder.png'; ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                            <div class="absolute inset-0 flex flex-col justify-end p-6" style="background:linear-gradient(to top, rgba(0,0,0,0.85), transparent);">
                                <span class="text-[9px] font-black uppercase tracking-widest mb-2 inline-block px-3 py-1 rounded-full" style="background:var(--accent); color:#003580;"><?php echo str_replace('_', ' ', $property['business_type']); ?></span>
                                <h3 class="text-xl font-black" style="color:white;"><?php echo htmlspecialchars($property['property_name']); ?></h3>
                                <p class="text-[10px] font-bold uppercase tracking-widest mt-1" style="color:rgba(255,255,255,0.6);"><i class="fas fa-map-marker-alt mr-1"></i><?php echo htmlspecialchars($property['city']); ?></p>
                            </div>
                        </div>
                        <div class="p-8">
                            <div class="grid grid-cols-2 gap-4 mb-8">
                                <div class="p-4 rounded-2xl" style="background:rgba(0,0,0,0.2); border:1px solid var(--glass-border);">
                                    <p class="text-[9px] font-bold uppercase tracking-widest mb-1" style="color:var(--text-muted);">Status</p>
                                    <p class="text-xs font-black uppercase" style="color:#4ade80;">Live & Active</p>
                                </div>
                                <div class="p-4 rounded-2xl" style="background:rgba(0,0,0,0.2); border:1px solid var(--glass-border);">
                                    <p class="text-[9px] font-bold uppercase tracking-widest mb-1" style="color:var(--text-muted);">City</p>
                                    <p class="text-xs font-black uppercase" style="color:#93c5fd;"><?php echo htmlspecialchars($property['city']); ?></p>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <a href="../../<?php echo $property['business_type'] === 'vehicle' ? 'vehicle_register_wizard.php' : 'property_wizard.php'; ?>?edit=<?php echo $property['id']; ?>" class="btn-primary flex-1 justify-center py-4">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <button onclick="requestDeletion(<?php echo $property['id']; ?>)" class="px-5 py-4 rounded-2xl border transition-all" style="border-color:rgba(248,113,113,0.3); color:#fca5a5; background:rgba(248,113,113,0.08);">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($view === 'deals'): ?>
                <!-- ===== DEALS OF THE DAY VIEW ===== -->
                <div class="mb-6 flex justify-between items-end anim-up">
                    <div>
                        <h2 class="text-2xl font-black" style="color:white;"><i class="fas fa-tags mr-3" style="color:#febb02;"></i>Deals of the Day</h2>
                        <p class="text-sm font-medium mt-1" style="color:var(--text-secondary);">Publish limited-time discounts to appear on the homepage slider</p>
                    </div>
                    <button onclick="document.getElementById('dealModal').classList.remove('hidden')" class="btn-primary">
                        <i class="fas fa-plus"></i> Add New Deal
                    </button>
                </div>

                <?php if (isset($_GET['msg'])): ?>
                    <?php $msg_deal = $_GET['msg']; ?>
                    <div class="glass-card mb-6 p-4 flex items-center gap-3 anim-up" style="border-color:rgba(<?php echo $msg_deal==='deal_saved'?'74,222,128':($msg_deal==='deal_deleted'?'248,113,113':'251,191,36'); ?>,0.3);background:rgba(<?php echo $msg_deal==='deal_saved'?'74,222,128':($msg_deal==='deal_deleted'?'248,113,113':'251,191,36'); ?>,0.10);">
                        <i class="fas fa-<?php echo $msg_deal==='deal_saved'?'check-circle':($msg_deal==='deal_deleted'?'trash-alt':'exclamation-circle'); ?>" style="color:<?php echo $msg_deal==='deal_saved'?'#4ade80':($msg_deal==='deal_deleted'?'#f87171':'#fbbf24'); ?>;"></i>
                        <span style="color:white;font-weight:700;font-size:0.875rem;">
                            <?php echo $msg_deal==='deal_saved'?'Deal published successfully!':($msg_deal==='deal_deleted'?'Deal removed.':'Error: Ensure deal price is less than original price.'); ?>
                        </span>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-3 gap-4 mb-8">
                    <div class="glass-card p-5 flex flex-col anim-up">
                        <div class="stat-icon mb-4" style="background:rgba(251,190,36,0.15);"><i class="fas fa-tags" style="color:#febb02;"></i></div>
                        <p class="stat-label">Total Deals</p>
                        <h3 class="stat-value"><?php echo count($property_deals); ?></h3>
                    </div>
                    <div class="glass-card p-5 flex flex-col anim-up-2">
                        <div class="stat-icon mb-4" style="background:rgba(74,222,128,0.15);"><i class="fas fa-bolt" style="color:#4ade80;"></i></div>
                        <p class="stat-label">Active Now</p>
                        <h3 class="stat-value"><?php echo $active_deals_count; ?></h3>
                    </div>
                    <div class="glass-card p-5 flex flex-col anim-up">
                        <div class="stat-icon mb-4" style="background:rgba(148,163,184,0.15);"><i class="fas fa-clock" style="color:#94a3b8;"></i></div>
                        <p class="stat-label">Ended</p>
                        <h3 class="stat-value"><?php echo count($property_deals) - $active_deals_count; ?></h3>
                    </div>
                </div>

                <?php if (empty($property_deals)): ?>
                    <div class="glass-card p-12 text-center anim-up">
                        <div class="w-20 h-20 rounded-2xl mx-auto mb-6 flex items-center justify-center" style="background:rgba(251,190,36,0.15);">
                            <i class="fas fa-tags text-3xl" style="color:#febb02;"></i>
                        </div>
                        <h3 class="text-xl font-black mb-2" style="color:white;">No Deals Yet</h3>
                        <p style="color:var(--text-secondary);font-size:0.875rem;" class="mb-6">Create a deal to feature on the homepage slider and attract more guests!</p>
                        <button onclick="document.getElementById('dealModal').classList.remove('hidden')" class="btn-primary">
                            <i class="fas fa-plus"></i> Create First Deal
                        </button>
                    </div>
                <?php else: ?>
                    <div class="glass-table anim-up" style="overflow-x:auto;">
                        <div class="px-6 py-5" style="border-bottom:1px solid var(--glass-border);">
                            <h3 class="text-lg font-black" style="color:white;">Your Deal Offers</h3>
                        </div>
                        <table class="w-full text-left" style="min-width:700px;">
                            <thead><tr>
                                <th class="glass-th">Room</th>
                                <th class="glass-th">Original Price</th>
                                <th class="glass-th">Deal Price</th>
                                <th class="glass-th">Discount</th>
                                <th class="glass-th">Label</th>
                                <th class="glass-th">Valid From</th>
                                <th class="glass-th">Valid Until</th>
                                <th class="glass-th">Status</th>
                                <th class="glass-th">Actions</th>
                            </tr></thead>
                            <tbody>
                                <?php foreach ($property_deals as $deal):
                                    $disc = round((($deal['original_price'] - $deal['deal_price']) / $deal['original_price']) * 100);
                                    $is_act = ($deal['deal_status'] === 'active');
                                ?>
                                <tr class="glass-tr">
                                    <td class="glass-td font-bold" style="color:white;"><?php echo htmlspecialchars($deal['room_name']); ?></td>
                                    <td class="glass-td" style="color:#94a3b8;text-decoration:line-through;">LKR <?php echo number_format($deal['original_price']); ?></td>
                                    <td class="glass-td"><span style="color:#4ade80;font-weight:800;">LKR <?php echo number_format($deal['deal_price']); ?></span></td>
                                    <td class="glass-td"><span class="badge" style="background:rgba(251,190,36,0.25);color:#febb02;">-<?php echo $disc; ?>%</span></td>
                                    <td class="glass-td" style="color:#94a3b8;"><?php echo htmlspecialchars($deal['deal_label'] ?: '—'); ?></td>
                                    <td class="glass-td" style="color:#94a3b8;"><?php echo date('M d, Y', strtotime($deal['valid_from'])); ?></td>
                                    <td class="glass-td" style="color:#94a3b8;"><?php echo date('M d, Y', strtotime($deal['valid_until'])); ?></td>
                                    <td class="glass-td"><?php if ($is_act): ?><span class="badge badge-confirmed">Active</span><?php else: ?><span class="badge" style="background:rgba(148,163,184,0.2);color:#94a3b8;">Ended</span><?php endif; ?></td>
                                    <td class="glass-td">
                                        <div class="flex gap-2">
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="toggle_deal">
                                                <input type="hidden" name="deal_id" value="<?php echo $deal['id']; ?>">
                                                <input type="hidden" name="new_status" value="<?php echo $deal['is_active'] ? 0 : 1; ?>">
                                                <button type="submit" class="btn-glass" style="padding:5px 10px;font-size:0.55rem;<?php echo $deal['is_active']?'color:#fbbf24;':'color:#4ade80;'; ?>">
                                                    <i class="fas fa-<?php echo $deal['is_active']?'pause':'play'; ?>"></i> <?php echo $deal['is_active']?'Pause':'Enable'; ?>
                                                </button>
                                            </form>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this deal?')">
                                                <input type="hidden" name="action" value="delete_deal">
                                                <input type="hidden" name="deal_id" value="<?php echo $deal['id']; ?>">
                                                <button type="submit" class="btn-glass" style="padding:5px 10px;font-size:0.55rem;color:#fca5a5;"><i class="fas fa-trash-alt"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </main>

    <!-- Room Availability Offcanvas -->
    <div id="roomAvailabilityOverlay" onclick="toggleRoomAvailability()" class="fixed inset-0 z-40 hidden" style="background:rgba(0,0,0,0.5); backdrop-filter:blur(4px);"></div>
    <div id="roomAvailabilitySidebar" class="fixed inset-y-0 right-0 w-80 z-50 transform translate-x-full transition-transform duration-300 flex flex-col" style="background:rgba(5,18,60,0.85); backdrop-filter:blur(24px); border-left:1px solid var(--glass-border);">
        <div class="p-6 flex justify-between items-center" style="border-bottom:1px solid var(--glass-border);">
            <div>
                <h3 class="text-lg font-black" style="color:white;">Today's Status</h3>
                <p class="text-[10px] font-bold uppercase tracking-widest" style="color:var(--text-muted);"><?php echo date('M d, Y'); ?></p>
            </div>
            <button onclick="toggleRoomAvailability()" class="w-8 h-8 rounded-full flex items-center justify-center transition-colors" style="color:var(--text-secondary);">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto p-6 custom-scrollbar">
            <?php foreach ($rooms_availability as $room):
                $free = max(0, $room['total_rooms'] - $room['booked_count']);
            ?>
            <div class="mb-4 p-5 rounded-2xl" style="background:rgba(255,255,255,0.07); border:1px solid var(--glass-border);">
                <div class="absolute top-0 left-0 w-1 h-full rounded-l-2xl <?php echo $free > 0 ? 'bg-green-400' : 'bg-red-400'; ?>"></div>
                <h4 class="font-bold mb-4 flex items-center gap-2" style="color:white;">
                    <i class="fas fa-bed" style="color:var(--text-muted);"></i>
                    <?php echo htmlspecialchars($room['room_name']); ?>
                </h4>
                <div class="flex justify-between items-center rounded-xl p-3" style="background:rgba(0,0,0,0.2);">
                    <div class="text-center">
                        <span class="block text-[9px] uppercase tracking-widest font-bold mb-1" style="color:var(--text-muted);">Total</span>
                        <span class="block text-sm font-black" style="color:white;"><?php echo $room['total_rooms']; ?></span>
                    </div>
                    <div class="w-px h-6" style="background:var(--glass-border);"></div>
                    <div class="text-center">
                        <span class="block text-[9px] uppercase tracking-widest font-bold mb-1" style="color:var(--text-muted);">Booked</span>
                        <span class="block text-sm font-black" style="color:#fca5a5;"><?php echo $room['booked_count']; ?></span>
                    </div>
                    <div class="w-px h-6" style="background:var(--glass-border);"></div>
                    <div class="text-center">
                        <span class="block text-[9px] uppercase tracking-widest font-bold mb-1" style="color:var(--text-muted);">Free</span>
                        <span class="block text-sm font-black" style="color:#86efac;"><?php echo $free; ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($rooms_availability)): ?>
                <div class="text-center py-8" style="color:var(--text-muted);">
                    <i class="fas fa-door-closed text-3xl mb-3 opacity-50"></i>
                    <p class="text-xs uppercase font-bold tracking-widest">No rooms configured.</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="p-6" style="border-top:1px solid var(--glass-border);">
            <button onclick="toggleRoomAvailability()" class="btn-glass w-full justify-center">Close Panel</button>
        </div>
    </div>

    <!-- New Booking Modal -->
    <div id="bookingModal" class="fixed inset-0 z-[60] hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-md" onclick="closeBookingModal()"></div>
            <div class="relative w-full max-w-2xl my-8 rounded-[2rem] overflow-hidden" style="background:rgba(5,18,60,0.90); backdrop-filter:blur(24px); border:1px solid var(--glass-border);">
                <div class="p-8 flex justify-between items-center" style="border-bottom:1px solid var(--glass-border);">
                    <div>
                        <h3 class="text-xl font-black" style="color:white;">New In-place Booking</h3>
                        <p class="text-xs font-bold uppercase tracking-widest mt-1" style="color:var(--text-muted);">Manual reservation entry</p>
                    </div>
                    <button onclick="closeBookingModal()" class="w-10 h-10 rounded-full btn-glass flex items-center justify-center">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form action="dashboard.php" method="POST" class="p-8">
                    <input type="hidden" name="action" value="new_inplace_booking">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Guest Details -->
                        <div class="space-y-4">
                            <h4 class="text-[10px] font-bold uppercase tracking-widest pb-2" style="color:var(--text-muted); border-bottom:1px solid var(--glass-border);">Guest Information</h4>
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-widest mb-1" style="color:var(--text-secondary);">Full Name</label>
                                <input type="text" name="guest_name" required class="glass-input w-full px-4 py-3 rounded-xl text-xs">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-widest mb-1" style="color:var(--text-secondary);">Phone Number</label>
                                <input type="text" name="guest_phone" required class="glass-input w-full px-4 py-3 rounded-xl text-xs">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-widest mb-1" style="color:var(--text-secondary);">Email (Optional)</label>
                                <input type="email" name="guest_email" class="glass-input w-full px-4 py-3 rounded-xl text-xs">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-widest mb-1" style="color:var(--text-secondary);">NIC / Passport (Optional)</label>
                                <input type="text" name="guest_nic" class="glass-input w-full px-4 py-3 rounded-xl text-xs">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-widest mb-1" style="color:var(--text-secondary);">Country</label>
                                    <input type="text" name="country" value="Sri Lanka" class="glass-input w-full px-4 py-3 rounded-xl text-xs">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-widest mb-1" style="color:var(--text-secondary);">Full Address (Optional)</label>
                                <textarea name="guest_address" rows="2" class="glass-input w-full px-4 py-3 rounded-xl text-xs"></textarea>
                            </div>
                        </div>

                        <!-- Room + Stay + Pricing -->
                        <div class="space-y-4">
                            <h4 class="text-[10px] font-bold uppercase tracking-widest pb-2" style="color:var(--text-muted); border-bottom:1px solid var(--glass-border);">Room Details</h4>
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-widest mb-1" style="color:var(--text-secondary);">Room Type</label>
                                <select name="room_id" required onchange="updateRoomNumbers()" class="glass-input w-full px-4 py-3 rounded-xl text-xs appearance-none">
                                    <option value="">Select Room Type</option>
                                    <?php foreach ($property_rooms as $pr): ?>
                                        <option value="<?php echo $pr['id']; ?>"><?php echo htmlspecialchars($pr['room_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-widest mb-1" style="color:var(--text-secondary);">Room Number (Optional)</label>
                                <div id="room_number_input_container">
                                    <input type="text" name="room_number" id="modal_room_number_input" placeholder="e.g. 101" class="glass-input w-full px-4 py-3 rounded-xl text-xs">
                                </div>
                                <div id="room_number_select_container" class="hidden">
                                    <select id="modal_room_number_select" class="glass-input w-full px-4 py-3 rounded-xl text-xs appearance-none"></select>
                                </div>
                            </div>

                            <h4 class="text-[10px] font-bold uppercase tracking-widest pb-2 pt-2" style="color:var(--text-muted); border-bottom:1px solid var(--glass-border);">Stay Schedule</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-widest mb-1" style="color:var(--text-secondary);">Check-in</label>
                                    <input type="date" name="check_in" id="modal_check_in" required class="glass-input w-full px-4 py-3 rounded-xl text-xs">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-widest mb-1" style="color:var(--text-secondary);">Check-out</label>
                                    <input type="date" name="check_out" id="modal_check_out" required class="glass-input w-full px-4 py-3 rounded-xl text-xs">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-widest mb-1" style="color:var(--text-secondary);">Adults</label>
                                    <input type="number" name="adults" value="1" min="1" class="glass-input w-full px-4 py-3 rounded-xl text-xs">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-widest mb-1" style="color:var(--text-secondary);">Children</label>
                                    <input type="number" name="children" value="0" min="0" class="glass-input w-full px-4 py-3 rounded-xl text-xs">
                                </div>
                            </div>

                            <h4 class="text-[10px] font-bold uppercase tracking-widest pb-2 pt-2" style="color:var(--text-muted); border-bottom:1px solid var(--glass-border);">Payment Info</h4>
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-widest mb-1" style="color:var(--text-secondary);">Total Price (LKR)</label>
                                <input type="number" name="price" required class="glass-input w-full px-4 py-3 rounded-xl text-xs">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase tracking-widest mb-1" style="color:var(--text-secondary);">Amount Paid (LKR)</label>
                                <input type="number" name="paid" value="0" class="glass-input w-full px-4 py-3 rounded-xl text-xs">
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex gap-4">
                        <button type="button" onclick="closeBookingModal()" class="flex-1 btn-glass justify-center py-4">Cancel</button>
                        <button type="submit" class="flex-[2] btn-primary justify-center py-4">
                            <i class="fas fa-check"></i> Confirm Booking
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Boost Modal -->
    <div id="boostModal" class="fixed inset-0 z-[60] hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-md" onclick="closeBoostModal()"></div>
            <div class="relative w-full max-w-2xl my-8 rounded-[2rem] overflow-hidden" style="background:rgba(5,18,60,0.90); backdrop-filter:blur(24px); border:1px solid var(--glass-border);">
                <div class="p-8 flex justify-between items-center" style="border-bottom:1px solid var(--glass-border);">
                    <div>
                        <h3 class="text-xl font-black" style="color:white;">Select a Boosting Package</h3>
                        <p class="text-xs font-bold uppercase tracking-widest mt-1" style="color:var(--text-muted);">Get more visibility</p>
                    </div>
                    <button onclick="closeBoostModal()" class="w-10 h-10 rounded-full btn-glass flex items-center justify-center"><i class="fas fa-times"></i></button>
                </div>
                <form action="dashboard.php" method="POST" class="p-8">
                    <input type="hidden" name="action" value="request_boost">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach($boost_packages as $pkg): ?>
                        <label class="cursor-pointer">
                            <input type="radio" name="package_id" value="<?php echo $pkg['id']; ?>" required class="peer hidden">
                            <div class="rounded-2xl p-6 text-center transition-all" style="border:2px solid var(--glass-border); background:rgba(255,255,255,0.05);" 
                                 onmouseover="this.style.borderColor='rgba(251,146,60,0.6)'"
                                 onmouseout="this.style.borderColor='var(--glass-border)'">
                                <h4 class="font-black text-lg mb-2" style="color:white;"><?php echo htmlspecialchars($pkg['name']); ?></h4>
                                <div class="text-3xl font-black mb-1" style="color:#fdba74;">LKR <?php echo number_format($pkg['price_lkr']); ?></div>
                                <p class="text-xs font-bold uppercase tracking-widest" style="color:var(--text-muted);"><?php echo $pkg['duration_days']; ?> Days Duration</p>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-6 p-4 rounded-xl flex items-start gap-3" style="background:rgba(96,165,250,0.1); border:1px solid rgba(96,165,250,0.25);">
                        <i class="fas fa-info-circle mt-0.5" style="color:#93c5fd;"></i>
                        <p class="text-xs" style="color:rgba(147,197,253,0.85);">Once you request a boost, our team will review it. You will be notified once the payment process is initiated and the boost becomes active.</p>
                    </div>
                    <div class="mt-8 flex gap-4">
                        <button type="button" onclick="closeBoostModal()" class="flex-1 btn-glass justify-center py-4">Cancel</button>
                        <button type="submit" class="flex-[2] justify-center py-4 rounded-2xl font-bold text-xs uppercase tracking-widest text-white transition-all" style="background:linear-gradient(135deg,#f97316,#ea580c); box-shadow:0 4px 15px rgba(249,115,22,0.4);">
                            <i class="fas fa-bolt mr-2"></i> Request Boost
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Deal of the Day Modal -->
    <div id="dealModal" class="fixed inset-0 z-[60] hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-md" onclick="closeDealModal()"></div>
            <div class="relative w-full max-w-lg my-8 rounded-[2rem] overflow-hidden" style="background:rgba(5,18,60,0.95);backdrop-filter:blur(24px);border:1px solid var(--glass-border);">
                <div class="p-8 flex justify-between items-center" style="border-bottom:1px solid var(--glass-border);">
                    <div>
                        <h3 class="text-xl font-black" style="color:white;"><i class="fas fa-tags mr-2" style="color:#febb02;"></i>Add New Deal</h3>
                        <p class="text-xs font-bold uppercase tracking-widest mt-1" style="color:var(--text-muted);">Create a limited-time discount offer</p>
                    </div>
                    <button onclick="closeDealModal()" class="w-10 h-10 rounded-full btn-glass flex items-center justify-center"><i class="fas fa-times"></i></button>
                </div>
                <form action="dashboard.php?view=deals" method="POST" class="p-8 space-y-5">
                    <input type="hidden" name="action" value="save_deal">
                    <?php if ($property_id): ?>
                    <input type="hidden" name="property_id" value="<?php echo $property_id; ?>">
                    <?php endif; ?>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest mb-2" style="color:var(--text-secondary);">Room Type</label>
                        <select name="deal_room_id" id="dealRoomSelect" required onchange="fillOriginalPrice(this)" class="glass-input w-full px-4 py-3 rounded-xl text-xs appearance-none">
                            <option value="">— Select Room —</option>
                            <?php foreach ($property_rooms as $pr): ?>
                                <option value="<?php echo $pr['id']; ?>" data-price="<?php echo $pr['price_lkr']; ?>">
                                    <?php echo htmlspecialchars($pr['room_name']); ?> (LKR <?php echo number_format($pr['price_lkr']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest mb-2" style="color:var(--text-secondary);">Original Price (LKR/night)</label>
                            <input type="number" name="original_price" id="dealOriginalPrice" required min="1" step="0.01"
                                   class="glass-input w-full px-4 py-3 rounded-xl text-xs" placeholder="Auto-filled">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest mb-2" style="color:var(--text-secondary);">Deal Price (LKR/night)</label>
                            <input type="number" name="deal_price" required min="1" step="0.01"
                                   class="glass-input w-full px-4 py-3 rounded-xl text-xs" placeholder="e.g. 8500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest mb-2" style="color:var(--text-secondary);">Deal Label <span style="color:var(--text-muted);">(optional)</span></label>
                        <input type="text" name="deal_label" maxlength="100"
                               class="glass-input w-full px-4 py-3 rounded-xl text-xs" placeholder="e.g. Flash Sale, Weekend Deal, Limited Offer">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest mb-2" style="color:var(--text-secondary);">Valid From</label>
                            <input type="date" name="valid_from" required value="<?php echo date('Y-m-d'); ?>"
                                   class="glass-input w-full px-4 py-3 rounded-xl text-xs">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-widest mb-2" style="color:var(--text-secondary);">Valid Until</label>
                            <input type="date" name="valid_until" required value="<?php echo date('Y-m-d', strtotime('+7 days')); ?>"
                                   class="glass-input w-full px-4 py-3 rounded-xl text-xs">
                        </div>
                    </div>

                    <div class="p-4 rounded-xl flex items-start gap-3" style="background:rgba(251,190,36,0.1);border:1px solid rgba(251,190,36,0.25);">
                        <i class="fas fa-info-circle mt-0.5" style="color:#febb02;"></i>
                        <p class="text-xs" style="color:rgba(251,190,36,0.85);">Deal price must be lower than the original price. The deal will appear on the homepage slider for all visitors.</p>
                    </div>

                    <div class="flex gap-4 pt-2">
                        <button type="button" onclick="closeDealModal()" class="flex-1 btn-glass justify-center py-4">Cancel</button>
                        <button type="submit" class="flex-[2] justify-center py-4 rounded-2xl font-bold text-xs uppercase tracking-widest text-white" style="background:linear-gradient(135deg,#febb02,#e0a800);color:#003580;box-shadow:0 4px 15px rgba(254,187,2,0.4);">
                            <i class="fas fa-bolt mr-2"></i> Publish Deal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const roomData = <?php echo json_encode($property_rooms); ?>;

        function updateRoomNumbers() {
            const roomId = document.querySelector('select[name="room_id"]').value;
            const room = roomData.find(r => r.id == roomId);
            const inputContainer = document.getElementById('room_number_input_container');
            const selectContainer = document.getElementById('room_number_select_container');
            const input = document.getElementById('modal_room_number_input');
            const select = document.getElementById('modal_room_number_select');

            if (room && room.room_numbers && room.room_numbers.trim() !== '') {
                const numbers = room.room_numbers.split(',').map(n => n.trim()).filter(n => n !== '');
                if (numbers.length > 0) {
                    select.innerHTML = '<option value="">Select Room Number</option>';
                    numbers.forEach(n => { select.innerHTML += `<option value="${n}">${n}</option>`; });
                    inputContainer.classList.add('hidden');
                    selectContainer.classList.remove('hidden');
                    select.onchange = () => { input.value = select.value; };
                    return;
                }
            }
            inputContainer.classList.remove('hidden');
            selectContainer.classList.add('hidden');
            input.value = '';
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('calendar')) {
                var calendarEl = document.getElementById('calendar');
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay' },
                    events: <?php echo json_encode($calendar_events); ?>,
                    height: 'auto',
                    contentHeight: 600,
                    firstDay: 1,
                    dateClick: function(info) { openBookingModal(info.dateStr); }
                });
                calendar.render();
            }
        });

        function openBookingModal(date) {
            if (date) {
                document.getElementById('modal_check_in').value = date;
                let nextDay = new Date(date);
                nextDay.setDate(nextDay.getDate() + 1);
                document.getElementById('modal_check_out').value = nextDay.toISOString().split('T')[0];
            }
            document.getElementById('bookingModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeBookingModal() {
            document.getElementById('bookingModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        function openBoostModal() {
            document.getElementById('boostModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeBoostModal() {
            document.getElementById('boostModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        function closeDealModal() {
            document.getElementById('dealModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        function fillOriginalPrice(select) {
            const price = select.options[select.selectedIndex]?.dataset?.price || '';
            const field = document.getElementById('dealOriginalPrice');
            if (field && price) field.value = price;
        }

        function toggleRoomAvailability() {
            const sidebar = document.getElementById('roomAvailabilitySidebar');
            const overlay = document.getElementById('roomAvailabilityOverlay');
            if (sidebar.classList.contains('translate-x-full')) {
                sidebar.classList.remove('translate-x-full');
                overlay.classList.remove('hidden');
            } else {
                sidebar.classList.add('translate-x-full');
                overlay.classList.add('hidden');
            }
        }

        async function requestDeletion(id) {
            if (confirm('Are you sure you want to request deletion of this property?')) {
                const formData = new FormData();
                formData.append('action', 'request_delete');
                formData.append('property_id', id);
                try {
                    const response = await fetch('../../register.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    alert(result.success ? 'Deletion request sent to admin.' : 'Error: ' + result.message);
                } catch (error) {
                    alert('An error occurred.');
                }
            }
        }
    </script>

</body>
</html>
