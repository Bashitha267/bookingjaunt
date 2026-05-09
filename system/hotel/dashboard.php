<?php
require_once '../../config.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$view = $_GET['view'] ?? 'dashboard';

// Fetch Property for this user
$stmt = $pdo->prepare("SELECT * FROM properties WHERE owner_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$property = $stmt->fetch();

if (!$property && $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

$property_id = $property['id'] ?? 0;

// --- Post Handlers ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'new_inplace_booking') {
    $room_id = $_POST['room_id'];
    $guest_name = $_POST['guest_name'];
    $guest_email = $_POST['guest_email'] ?? '';
    $guest_nic = $_POST['guest_nic'] ?? '';
    $guest_address = $_POST['guest_address'] ?? '';
    $country = $_POST['country'] ?? 'Sri Lanka';
    $status = 'confirmed'; 
    
    $ins = $pdo->prepare("INSERT INTO bookings (property_id, room_id, booking_type, guest_name, guest_phone, guest_email, guest_nic, guest_address, country, room_number, check_in_date, check_out_date, adults, children, price_per_room, total_price, amount_paid, status) VALUES (?, ?, 'inplace', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $ins->execute([$property_id, $room_id, $guest_name, $guest_phone, $guest_email, $guest_nic, $guest_address, $country, $room_number, $check_in, $check_out, $adults, $children, $price, $price, $paid, $status]);
    
    header("Location: dashboard.php?msg=success");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_boost') {
    $package_id = $_POST['package_id'];
    
    // Fetch package details
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


// Fetch all room types for this property
$rooms_stmt = $pdo->prepare("SELECT id, room_name FROM property_rooms WHERE property_id = ?");
$rooms_stmt->execute([$property_id]);
$property_rooms = $rooms_stmt->fetchAll();

// --- Real Data Fetching for Dashboard ---
$total_bookings_stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE property_id = ?");
$total_bookings_stmt->execute([$property_id]);
$total_bookings_count = $total_bookings_stmt->fetchColumn();

$monthly_revenue_stmt = $pdo->prepare("SELECT SUM(amount_paid) FROM bookings WHERE property_id = ? AND MONTH(created_at) = MONTH(CURRENT_DATE())");
$monthly_revenue_stmt->execute([$property_id]);
$monthly_revenue = $monthly_revenue_stmt->fetchColumn() ?? 0;

$pending_bookings_stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE property_id = ? AND status = 'pending'");
$pending_bookings_stmt->execute([$property_id]);
$pending_bookings = $pending_bookings_stmt->fetchColumn();

// Fetch Recent Bookings for the table
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

// Fetch Calendar Events
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
    $color = '#fb923c'; // Pending
    if ($b['status'] == 'confirmed') $color = '#4ade80';
    if ($b['status'] == 'checked_in') $color = '#3b82f6';
    if ($b['status'] == 'checked_out') $color = '#94a3b8';
    if ($b['status'] == 'cancelled') $color = '#ef4444';
    
    $calendar_events[] = [
        'title' => $b['guest_name'] . ' (' . ($b['room_number'] ? '#' . $b['room_number'] : $b['room_name']) . ')',
        'start' => $b['check_in_date'],
        'end' => $b['check_out_date'],
        'color' => $color
    ];
}

// Fetch Room Availability for Today
$today = date('Y-m-d');
$rooms_availability_stmt = $pdo->prepare("
    SELECT pr.id, pr.room_name, pr.total_rooms, 
           (SELECT COUNT(*) FROM bookings b 
            WHERE b.room_id = pr.id 
              AND b.property_id = ? 
              AND b.check_in_date <= ? 
              AND b.check_out_date > ?
              AND b.status IN ('confirmed', 'checked_in')) as booked_count
    FROM property_rooms pr 
    WHERE pr.property_id = ?
");
$rooms_availability_stmt->execute([$property_id, $today, $today, $property_id]);
$rooms_availability = $rooms_availability_stmt->fetchAll();

// Fetch Boost Packages
$boost_packages_stmt = $pdo->query("SELECT * FROM boost_packages WHERE is_active = 1");
$boost_packages = $boost_packages_stmt->fetchAll();

// Fetch Current Boost Status
$active_boost_stmt = $pdo->prepare("SELECT * FROM property_boosts WHERE property_id = ? AND status IN ('pending', 'active') AND (status = 'pending' OR DATE_ADD(start_date, INTERVAL duration_days DAY) >= CURDATE()) ORDER BY created_at DESC LIMIT 1");
$active_boost_stmt->execute([$property_id]);
$current_boost = $active_boost_stmt->fetch();
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
    <!-- FullCalendar -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
        }

        .sidebar-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 4px solid #febb02;
            color: white;
        }

        .fc {
            background: white;
            padding: 20px;
            border-radius: 1.5rem;
            border: 1px solid #f1f5f9;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }

        .fc .fc-toolbar-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: #003580;
        }

        .fc .fc-button-primary {
            background-color: white;
            border-color: #e2e8f0;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.05em;
            padding: 0.5rem 1rem;
        }

        .fc .fc-button-primary:hover {
            background-color: #f8fafc;
            color: #003580;
        }

        .fc .fc-button-active {
            background-color: #003580 !important;
            border-color: #003580 !important;
            color: white !important;
        }

        .fc th {
            padding: 12px 0;
            font-size: 0.7rem;
            font-weight: 800;
            text-transform: uppercase;
            color: #94a3b8;
            letter-spacing: 0.1em;
        }

        .fc td {
            font-size: 0.8rem;
            color: #64748b;
        }
        
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #003580; border-radius: 10px; }
    </style>
</head>

<body class="flex min-h-screen overflow-hidden">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 ml-64 overflow-y-auto h-screen bg-[#f8fafc]">
        
        <!-- Top Nav -->
        <header class="bg-white/80 backdrop-blur-md border-b border-gray-200 sticky top-0 z-40 px-8 py-4 flex justify-between items-center">
            <div class="flex-1">
                <div class="relative max-w-md">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input type="text" placeholder="Search bookings, guests, or rooms..." class="w-full pl-11 pr-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs focus:ring-2 focus:ring-[#003580] outline-none transition-all">
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <a href="../../index.php" target="_blank" class="hidden md:flex items-center gap-2 px-5 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-gray-50 transition-all">
                    <i class="fas fa-external-link-alt"></i>
                    Visit Site
                </a>
                <button class="bg-[#003580] text-white px-6 py-2.5 rounded-xl font-bold text-[10px] uppercase tracking-widest flex items-center gap-2 hover:bg-[#002560] transition-all shadow-lg shadow-blue-900/20">
                    <i class="fas fa-plus"></i>
                    New Booking
                </button>
                <button onclick="toggleRoomAvailability()" class="bg-indigo-50 text-indigo-600 px-6 py-2.5 rounded-xl font-bold text-[10px] uppercase tracking-widest flex items-center gap-2 hover:bg-indigo-100 transition-all border border-indigo-100 shadow-sm">
                    <i class="fas fa-door-open"></i>
                    Room Status
                </button>
                <div class="w-[1px] h-8 bg-gray-100 mx-2"></div>
                <button class="w-10 h-10 text-gray-400 hover:text-[#003580] transition-colors relative">
                    <i class="far fa-bell text-lg"></i>
                    <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
                </button>
            </div>
        </header>

        <div class="p-8">
            
            <?php if ($view === 'dashboard'): ?>
                <!-- Boost Banner -->
                <?php if ($current_boost): ?>
                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-[2rem] p-8 mb-8 text-white flex justify-between items-center shadow-lg shadow-indigo-500/20">
                        <div>
                            <h2 class="text-2xl font-black mb-2">Your property boost is <?php echo htmlspecialchars($current_boost['status']); ?>!</h2>
                            <p class="opacity-90 font-medium">
                                <?php if ($current_boost['status'] === 'active'): ?>
                                    Valid until <?php echo date('M d, Y', strtotime($current_boost['start_date'] . ' + ' . $current_boost['duration_days'] . ' days')); ?>.
                                <?php else: ?>
                                    Your request is currently under review by the admin team.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="bg-gradient-to-r from-amber-500 to-orange-500 rounded-[2rem] p-8 mb-8 text-white flex justify-between items-center shadow-lg shadow-orange-500/20">
                        <div>
                            <h2 class="text-2xl font-black mb-2">Need more reservations? Make your property featured!</h2>
                            <p class="opacity-90 font-medium">Boost your property to the top of our listings and reach thousands of daily visitors.</p>
                        </div>
                        <button onclick="openBoostModal()" class="bg-white text-orange-600 px-6 py-3 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-orange-50 transition-all shadow-sm shrink-0">
                            Click here to see more details
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Stats Grid (from image) -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex flex-col">
                        <div class="flex justify-between items-start mb-6">
                            <div class="w-12 h-12 bg-blue-50 text-blue-500 rounded-2xl flex items-center justify-center text-xl">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                        </div>
                        <p class="text-gray-400 text-[10px] font-bold uppercase tracking-widest mb-1">Total Bookings</p>
                        <h3 class="text-2xl font-black text-[#003580]"><?php echo number_format($total_bookings_count); ?></h3>
                    </div>

                    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex flex-col">
                        <div class="flex justify-between items-start mb-6">
                            <div class="w-12 h-12 bg-green-50 text-green-500 rounded-2xl flex items-center justify-center text-xl">
                                <i class="fas fa-wallet"></i>
                            </div>
                        </div>
                        <p class="text-gray-400 text-[10px] font-bold uppercase tracking-widest mb-1">Monthly Revenue</p>
                        <h3 class="text-2xl font-black text-[#003580]">LKR <?php echo number_format($monthly_revenue); ?></h3>
                    </div>

                    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex flex-col">
                        <div class="flex justify-between items-start mb-6">
                            <div class="w-12 h-12 bg-orange-50 text-orange-500 rounded-2xl flex items-center justify-center text-xl">
                                <i class="fas fa-clock"></i>
                            </div>
                        </div>
                        <p class="text-gray-400 text-[10px] font-bold uppercase tracking-widest mb-1">Pending Bookings</p>
                        <h3 class="text-2xl font-black text-[#003580]"><?php echo $pending_bookings; ?></h3>
                    </div>

                    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex flex-col">
                        <div class="flex justify-between items-start mb-6">
                            <div class="w-12 h-12 bg-purple-50 text-purple-500 rounded-2xl flex items-center justify-center text-xl">
                                <i class="fas fa-bed"></i>
                            </div>
                        </div>
                        <p class="text-gray-400 text-[10px] font-bold uppercase tracking-widest mb-1">Total Rooms</p>
                        <h3 class="text-2xl font-black text-[#003580]">
                            <?php 
                                $rooms_count_stmt = $pdo->prepare("SELECT SUM(total_rooms) FROM property_rooms WHERE property_id = ?");
                                $rooms_count_stmt->execute([$property_id]);
                                echo $rooms_count_stmt->fetchColumn() ?: 0;
                            ?>
                        </h3>
                    </div>
                </div>

                <!-- Recent Bookings Table -->
                <div class="mb-8">
                    <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm overflow-hidden">
                        <div class="p-6 border-b border-gray-50 flex justify-between items-center">
                            <h2 class="text-lg font-black text-[#003580]">Recent Bookings</h2>
                            <a href="bookings.php" class="text-[10px] font-bold text-blue-500 uppercase tracking-widest hover:underline">View All</a>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-gray-50 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                    <tr>
                                        <th class="px-6 py-4">Guest</th>
                                        <th class="px-6 py-4">Room</th>
                                        <th class="px-6 py-4">Stay</th>
                                        <th class="px-6 py-4">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 text-sm">
                                    <?php foreach ($recent_bookings as $rb): ?>
                                        <tr class="hover:bg-gray-50/50 transition-colors">
                                            <td class="px-6 py-4">
                                                <div class="font-bold text-gray-800"><?php echo htmlspecialchars($rb['guest_name']); ?></div>
                                                <div class="text-[10px] text-gray-400"><?php echo htmlspecialchars($rb['guest_phone']); ?></div>
                                            </td>
                                            <td class="px-6 py-4 font-semibold text-gray-600 text-xs"><?php echo htmlspecialchars($rb['room_name']); ?></td>
                                            <td class="px-6 py-4 text-xs">
                                                <?php echo date('M d', strtotime($rb['check_in_date'])); ?> - <?php echo date('M d', strtotime($rb['check_out_date'])); ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="px-2 py-1 rounded-full text-[9px] font-bold uppercase tracking-wider
                                                    <?php 
                                                        if($rb['status'] == 'confirmed') echo 'bg-green-50 text-green-600';
                                                        elseif($rb['status'] == 'pending') echo 'bg-orange-50 text-orange-600';
                                                        elseif($rb['status'] == 'cancelled') echo 'bg-red-50 text-red-600';
                                                        else echo 'bg-gray-50 text-gray-600';
                                                    ?>">
                                                    <?php echo $rb['status']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

                <!-- Calendar View (Booking Schedule) -->
                <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-8 border-b border-gray-50 flex justify-between items-center">
                        <div>
                            <h2 class="text-xl font-black text-[#003580]">Booking Schedule</h2>
                            <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mt-1">Manage your room availability</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-6 mr-6">
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-green-400"></div>
                                    <span class="text-[10px] font-bold text-gray-500 uppercase">Confirmed</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-orange-400"></div>
                                    <span class="text-[10px] font-bold text-gray-500 uppercase">Pending</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                                    <span class="text-[10px] font-bold text-gray-500 uppercase">Checked-in</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-6">
                        <div id='calendar'></div>
                    </div>
                </div>

            <?php elseif ($view === 'properties'): ?>
                <!-- My Properties View -->
                <div class="mb-8 flex justify-between items-end">
                    <div>
                        <h2 class="text-2xl font-black text-[#003580]">My Properties</h2>
                        <p class="text-sm text-gray-400 font-medium">Manage and update your registered properties</p>
                    </div>
                    <a href="../../property_wizard.php" class="bg-blue-50 text-[#006ce4] px-6 py-3 rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-[#006ce4] hover:text-white transition-all">Add New Property</a>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8">
                    <!-- Property Card -->
                    <div class="bg-white rounded-[2.5rem] overflow-hidden border border-gray-100 shadow-xl shadow-blue-900/5 group">
                        <div class="h-56 relative overflow-hidden">
                            <img src="../../<?php echo $property['cover_image'] ?: 'assets/placeholder.png'; ?>" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent flex flex-col justify-end p-6">
                                <div class="flex justify-between items-end">
                                    <div>
                                        <span class="bg-[#febb02] text-[#003580] px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest mb-2 inline-block"><?php echo str_replace('_', ' ', $property['business_type']); ?></span>
                                        <h3 class="text-white text-xl font-black"><?php echo htmlspecialchars($property['property_name']); ?></h3>
                                        <p class="text-white/60 text-[10px] font-bold uppercase tracking-widest"><i class="fas fa-map-marker-alt mr-1"></i> <?php echo htmlspecialchars($property['city']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="p-8">
                            <div class="grid grid-cols-2 gap-6 mb-8">
                                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1">Status</p>
                                    <p class="text-xs font-black text-green-600 uppercase">Live & Active</p>
                                </div>
                                <div class="p-4 bg-gray-50 rounded-2xl border border-gray-100">
                                    <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1">Rooms</p>
                                    <p class="text-xs font-black text-[#003580] uppercase">12 Total</p>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <a href="../../property_wizard.php?edit=<?php echo $property['id']; ?>" class="flex-1 bg-[#003580] text-white py-4 rounded-2xl font-bold text-xs uppercase tracking-widest text-center hover:bg-[#002560] transition-all shadow-lg shadow-blue-900/20">
                                    <i class="fas fa-edit mr-2"></i> Edit
                                </a>
                                <button onclick="requestDeletion(<?php echo $property['id']; ?>)" class="px-6 border-2 border-red-50 text-red-400 rounded-2xl hover:bg-red-500 hover:text-white hover:border-red-500 transition-all">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

        </div>
    </main>

    <!-- Room Availability Offcanvas -->
    <div id="roomAvailabilityOverlay" onclick="toggleRoomAvailability()" class="fixed inset-0 bg-black/20 z-40 hidden backdrop-blur-sm transition-opacity"></div>
    <div id="roomAvailabilitySidebar" class="fixed inset-y-0 right-0 w-80 bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
            <div>
                <h3 class="text-lg font-black text-[#003580]">Today's Status</h3>
                <p class="text-[10px] text-gray-500 font-bold uppercase tracking-widest"><?php echo date('M d, Y'); ?></p>
            </div>
            <button onclick="toggleRoomAvailability()" class="text-gray-400 hover:text-red-500 transition-colors w-8 h-8 flex items-center justify-center rounded-full hover:bg-red-50">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto p-6 bg-[#f8fafc] custom-scrollbar">
            <?php foreach ($rooms_availability as $room): 
                $free = max(0, $room['total_rooms'] - $room['booked_count']);
            ?>
            <div class="bg-white rounded-2xl p-5 mb-4 border border-gray-100 shadow-sm relative overflow-hidden group hover:border-indigo-100 transition-colors">
                <div class="absolute top-0 left-0 w-1 h-full <?php echo $free > 0 ? 'bg-green-400' : 'bg-red-400'; ?>"></div>
                <h4 class="font-bold text-gray-800 mb-4 ml-2 flex items-center gap-2">
                    <i class="fas fa-bed text-gray-400 text-sm"></i>
                    <?php echo htmlspecialchars($room['room_name']); ?>
                </h4>
                <div class="flex justify-between items-center ml-2 bg-gray-50 rounded-xl p-3">
                    <div class="text-center">
                        <span class="block text-[9px] text-gray-400 uppercase tracking-widest font-bold mb-1">Total</span>
                        <span class="block text-sm font-black text-gray-700"><?php echo $room['total_rooms']; ?></span>
                    </div>
                    <div class="w-[1px] h-6 bg-gray-200"></div>
                    <div class="text-center">
                        <span class="block text-[9px] text-gray-400 uppercase tracking-widest font-bold mb-1">Booked</span>
                        <span class="block text-sm font-black text-red-500"><?php echo $room['booked_count']; ?></span>
                    </div>
                    <div class="w-[1px] h-6 bg-gray-200"></div>
                    <div class="text-center">
                        <span class="block text-[9px] text-gray-400 uppercase tracking-widest font-bold mb-1">Free</span>
                        <span class="block text-sm font-black text-green-500"><?php echo $free; ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            
            <?php if (empty($rooms_availability)): ?>
                <div class="text-center py-8 text-gray-400">
                    <i class="fas fa-door-closed text-3xl mb-3 opacity-50"></i>
                    <p class="text-xs uppercase font-bold tracking-widest">No rooms configured.</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="p-6 border-t border-gray-100 bg-white">
            <button onclick="toggleRoomAvailability()" class="w-full bg-gray-50 text-gray-600 px-6 py-3 rounded-xl font-bold text-[10px] uppercase tracking-widest hover:bg-gray-100 transition-all border border-gray-200">
                Close Panel
            </button>
        </div>
    </div>

    <!-- New Booking Modal -->
    <div id="bookingModal" class="fixed inset-0 z-[60] hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-black/40 backdrop-blur-sm" onclick="closeBookingModal()"></div>
            <div class="inline-block w-full max-w-2xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-[2.5rem] border border-gray-100">
                <div class="p-8 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
                    <div>
                        <h3 class="text-xl font-black text-[#003580]">New In-place Booking</h3>
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mt-1">Manual reservation entry</p>
                    </div>
                    <button onclick="closeBookingModal()" class="text-gray-400 hover:text-red-500 transition-colors w-10 h-10 flex items-center justify-center rounded-full hover:bg-red-50">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form action="dashboard.php" method="POST" class="p-8">
                    <input type="hidden" name="action" value="new_inplace_booking">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Guest Details -->
                        <div class="space-y-4">
                            <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-50 pb-2">Guest Information</h4>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1 ml-1">Full Name</label>
                                <input type="text" name="guest_name" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-xs focus:ring-2 focus:ring-[#003580] outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1 ml-1">Phone Number</label>
                                <input type="text" name="guest_phone" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-xs focus:ring-2 focus:ring-[#003580] outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1 ml-1">Email Address (Optional)</label>
                                <input type="email" name="guest_email" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-xs focus:ring-2 focus:ring-[#003580] outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1 ml-1">NIC / Passport (Optional)</label>
                                <input type="text" name="guest_nic" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-xs focus:ring-2 focus:ring-[#003580] outline-none">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1 ml-1">Country</label>
                                    <input type="text" name="country" value="Sri Lanka" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-xs focus:ring-2 focus:ring-[#003580] outline-none">
                                </div>
                                <div class="hidden"></div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1 ml-1">Full Address (Optional)</label>
                                <textarea name="guest_address" rows="2" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-xs focus:ring-2 focus:ring-[#003580] outline-none"></textarea>
                            </div>
                        </div>
                        
                        <!-- Room Selection -->
                        <div class="space-y-4">
                            <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-50 pb-2">Room Details</h4>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1 ml-1">Room Type</label>
                                <select name="room_id" required onchange="updateRoomNumbers()" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-xs focus:ring-2 focus:ring-[#003580] outline-none appearance-none">
                                    <option value="">Select Room Type</option>
                                    <?php foreach ($property_rooms as $pr): ?>
                                        <option value="<?php echo $pr['id']; ?>"><?php echo htmlspecialchars($pr['room_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1 ml-1">Room Number (Optional)</label>
                                <div id="room_number_input_container">
                                    <input type="text" name="room_number" id="modal_room_number_input" placeholder="e.g. 101" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-xs focus:ring-2 focus:ring-[#003580] outline-none">
                                </div>
                                <div id="room_number_select_container" class="hidden">
                                    <select id="modal_room_number_select" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-xs focus:ring-2 focus:ring-[#003580] outline-none appearance-none">
                                        <!-- Options populated by JS -->
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Stay Dates -->
                        <div class="space-y-4">
                            <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-50 pb-2">Stay Schedule</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1 ml-1">Check-in</label>
                                    <input type="date" name="check_in" id="modal_check_in" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-xs focus:ring-2 focus:ring-[#003580] outline-none">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1 ml-1">Check-out</label>
                                    <input type="date" name="check_out" id="modal_check_out" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-xs focus:ring-2 focus:ring-[#003580] outline-none">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1 ml-1">Adults</label>
                                    <input type="number" name="adults" value="1" min="1" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-xs focus:ring-2 focus:ring-[#003580] outline-none">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1 ml-1">Children</label>
                                    <input type="number" name="children" value="0" min="0" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-xs focus:ring-2 focus:ring-[#003580] outline-none">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pricing -->
                        <div class="space-y-4">
                            <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b border-gray-50 pb-2">Payment Info</h4>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1 ml-1">Total Price (LKR)</label>
                                <input type="number" name="price" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-xs focus:ring-2 focus:ring-[#003580] outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1 ml-1">Amount Paid (LKR)</label>
                                <input type="number" name="paid" value="0" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-xs focus:ring-2 focus:ring-[#003580] outline-none">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-10 flex gap-4">
                        <button type="button" onclick="closeBookingModal()" class="flex-1 px-6 py-4 bg-gray-100 text-gray-500 rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-gray-200 transition-all">Cancel</button>
                        <button type="submit" class="flex-[2] px-6 py-4 bg-[#003580] text-white rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-[#002560] transition-all shadow-lg shadow-blue-900/20">Confirm Booking</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Boost Modal -->
    <div id="boostModal" class="fixed inset-0 z-[60] hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-black/40 backdrop-blur-sm" onclick="closeBoostModal()"></div>
            <div class="inline-block w-full max-w-2xl my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-[2.5rem] border border-gray-100">
                <div class="p-8 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
                    <div>
                        <h3 class="text-xl font-black text-[#003580]">Select a Boosting Package</h3>
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mt-1">Get more visibility</p>
                    </div>
                    <button onclick="closeBoostModal()" class="text-gray-400 hover:text-red-500 transition-colors w-10 h-10 flex items-center justify-center rounded-full hover:bg-red-50">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form action="dashboard.php" method="POST" class="p-8">
                    <input type="hidden" name="action" value="request_boost">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach($boost_packages as $pkg): ?>
                        <label class="cursor-pointer">
                            <input type="radio" name="package_id" value="<?php echo $pkg['id']; ?>" required class="peer hidden">
                            <div class="border-2 border-gray-100 rounded-2xl p-6 hover:border-orange-200 peer-checked:border-orange-500 peer-checked:bg-orange-50 transition-all text-center">
                                <h4 class="font-black text-lg text-[#003580] mb-2"><?php echo htmlspecialchars($pkg['name']); ?></h4>
                                <div class="text-3xl font-black text-orange-500 mb-1">LKR <?php echo number_format($pkg['price_lkr']); ?></div>
                                <p class="text-xs text-gray-500 font-bold uppercase tracking-widest"><?php echo $pkg['duration_days']; ?> Days Duration</p>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-8 bg-blue-50 p-4 rounded-xl text-xs text-blue-800 flex items-start gap-3">
                        <i class="fas fa-info-circle mt-0.5"></i>
                        <p>Once you request a boost, our team will review it. You will be notified once the payment process is initiated and the boost becomes active.</p>
                    </div>
                    
                    <div class="mt-8 flex gap-4">
                        <button type="button" onclick="closeBoostModal()" class="flex-1 px-6 py-4 bg-gray-100 text-gray-500 rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-gray-200 transition-all">Cancel</button>
                        <button type="submit" class="flex-[2] px-6 py-4 bg-orange-500 text-white rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-orange-600 transition-all shadow-lg shadow-orange-500/20">Request Boost</button>
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
                    numbers.forEach(n => {
                        select.innerHTML += `<option value="${n}">${n}</option>`;
                    });
                    
                    inputContainer.classList.add('hidden');
                    selectContainer.classList.remove('hidden');
                    
                    // Sync select value to hidden input or just use select value on submit
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
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    events: <?php echo json_encode($calendar_events); ?>,
                    height: 'auto',
                    contentHeight: 600,
                    firstDay: 1,
                    dateClick: function(info) {
                        openBookingModal(info.dateStr);
                    }
                });
                calendar.render();
            }
        });

        function openBookingModal(date) {
            document.getElementById('modal_check_in').value = date;
            // Default checkout to next day
            let nextDay = new Date(date);
            nextDay.setDate(nextDay.getDate() + 1);
            document.getElementById('modal_check_out').value = nextDay.toISOString().split('T')[0];
            
            document.getElementById('bookingModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeBookingModal() {
            document.getElementById('bookingModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function openBoostModal() {
            document.getElementById('boostModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeBoostModal() {
            document.getElementById('boostModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
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
            if (confirm('Are you sure you want to request deletion of this property? This requires admin approval and will take effect once reviewed.')) {
                const formData = new FormData();
                formData.append('action', 'request_delete');
                formData.append('property_id', id);

                try {
                    const response = await fetch('../../register.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();
                    if (result.success) {
                        alert('Deletion request sent to admin successfully.');
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while submitting the request.');
                }
            }
        }
    </script>

</body>

</html>
