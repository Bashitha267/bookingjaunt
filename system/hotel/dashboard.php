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
    SELECT b.guest_name, b.check_in_date, b.check_out_date, b.status, pr.room_name 
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
        'title' => $b['guest_name'] . ' (' . $b['room_name'] . ')',
        'start' => $b['check_in_date'],
        'end' => $b['check_out_date'],
        'color' => $color
    ];
}
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
                <div class="w-[1px] h-8 bg-gray-100 mx-2"></div>
                <button class="w-10 h-10 text-gray-400 hover:text-[#003580] transition-colors relative">
                    <i class="far fa-bell text-lg"></i>
                    <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
                </button>
            </div>
        </header>

        <div class="p-8">
            
            <?php if ($view === 'dashboard'): ?>
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

    <script>
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
                    firstDay: 1
                });
                calendar.render();
            }
        });

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
