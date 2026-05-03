<?php
require_once '../../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch Property for this user
$stmt = $pdo->prepare("SELECT id FROM properties WHERE owner_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$property = $stmt->fetch();
$property_id = $property['id'] ?? 0;

if (!$property_id && $_SESSION['role'] !== 'admin') {
    die("Property not found.");
}

// If admin, they might be viewing a specific property or all
if ($_SESSION['role'] === 'admin' && isset($_GET['property_id'])) {
    $property_id = $_GET['property_id'];
}

// Handle Status Updates
if (isset($_POST['update_status'])) {
    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ? AND property_id = ?");
    $stmt->execute([$_POST['status'], $_POST['booking_id'], $property_id]);
    header("Location: bookings.php?success=status_updated");
    exit();
}

// Fetch Bookings
$bookings_stmt = $pdo->prepare("
    SELECT b.*, pr.room_name 
    FROM bookings b 
    JOIN property_rooms pr ON b.room_id = pr.id 
    WHERE b.property_id = ? 
    ORDER BY b.created_at DESC
");
$bookings_stmt->execute([$property_id]);
$bookings = $bookings_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - Bookingjaunt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        .status-badge { @apply px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider; }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-confirmed { background-color: #dcfce7; color: #166534; }
        .status-checked_in { background-color: #dbeafe; color: #1e40af; }
        .status-checked_out { background-color: #f1f5f9; color: #475569; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body class="flex min-h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>

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
                    <i class="fas fa-external-link-alt"></i> Visit Site
                </a>
                <a href="../../hotel_info.php?id=<?php echo $property_id; ?>" target="_blank" class="bg-[#003580] text-white px-6 py-2.5 rounded-xl font-bold text-[10px] uppercase tracking-widest flex items-center gap-2 hover:bg-[#002560] transition-all shadow-lg shadow-blue-900/20">
                    <i class="fas fa-plus"></i> New Booking
                </a>
            </div>
        </header>

        <div class="p-8">
            <div class="flex justify-between items-end mb-8">
                <div>
                    <h1 class="text-2xl font-black text-slate-800">Bookings Management</h1>
                    <p class="text-slate-500 text-sm">Monitor and manage all guest reservations for <?php echo htmlspecialchars($property['property_name'] ?? 'your property'); ?></p>
                </div>
                <div class="flex bg-white p-1 rounded-xl border border-slate-200 gap-1">
                    <button class="px-4 py-2 text-[10px] font-bold uppercase tracking-widest bg-slate-100 text-slate-800 rounded-lg">All</button>
                    <button class="px-4 py-2 text-[10px] font-bold uppercase tracking-widest text-slate-400 hover:text-slate-600 rounded-lg">Confirmed</button>
                    <button class="px-4 py-2 text-[10px] font-bold uppercase tracking-widest text-slate-400 hover:text-slate-600 rounded-lg">Pending</button>
                </div>
            </div>

        <!-- Bookings Table -->
        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="p-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Guest & Stay</th>
                            <th class="p-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Room</th>
                            <th class="p-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Dates</th>
                            <th class="p-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Payment</th>
                            <th class="p-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Status</th>
                            <th class="p-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="6" class="p-10 text-center text-slate-400 font-medium">No bookings found yet.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($bookings as $b): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors cursor-pointer" onclick="if(!event.target.closest('select, button, a, form')) window.location='booking_details.php?id=<?php echo $b['id']; ?>'">
                            <td class="p-4">
                                <div class="font-bold text-slate-800 text-sm"><?php echo htmlspecialchars($b['guest_name']); ?></div>
                                <div class="text-[11px] text-slate-500 mt-1"><?php echo htmlspecialchars($b['guest_phone']); ?></div>
                                <div class="inline-block mt-2 px-2 py-0.5 bg-slate-100 rounded text-[9px] font-bold text-slate-600 uppercase">
                                    <?php echo $b['booking_type']; ?>
                                </div>
                            </td>
                            <td class="p-4 text-sm font-semibold text-slate-600">
                                <?php echo htmlspecialchars($b['room_name']); ?>
                                <?php if($b['room_number']): ?>
                                    <span class="block text-[10px] text-blue-600">No: <?php echo $b['room_number']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="p-4">
                                <div class="text-[12px] font-bold text-slate-700">
                                    <?php echo date('M d', strtotime($b['check_in_date'])); ?> - <?php echo date('M d', strtotime($b['check_out_date'])); ?>
                                </div>
                                <div class="text-[10px] text-slate-400 mt-1 uppercase font-bold">
                                    <?php 
                                        $nights = (strtotime($b['check_out_date']) - strtotime($b['check_in_date'])) / (60*60*24);
                                        echo $nights; 
                                    ?> Night<?php echo $nights > 1 ? 's' : ''; ?>
                                </div>
                            </td>
                            <td class="p-4">
                                <div class="font-bold text-slate-800 text-sm">LKR <?php echo number_format($b['total_price']); ?></div>
                                <div class="flex items-center gap-2 mt-1">
                                    <div class="h-1.5 w-12 bg-slate-100 rounded-full overflow-hidden">
                                        <?php $perc = ($b['total_price'] > 0) ? min(100, ($b['amount_paid'] / $b['total_price']) * 100) : 0; ?>
                                        <div class="h-full bg-green-500" style="width: <?php echo $perc; ?>%"></div>
                                    </div>
                                    <span class="text-[10px] font-bold <?php echo $b['payment_status'] == 'complete' ? 'text-green-600' : 'text-slate-400'; ?>">
                                        <?php echo $b['payment_status']; ?>
                                    </span>
                                </div>
                            </td>
                            <td class="p-4">
                                <span class="status-badge status-<?php echo $b['status']; ?>">
                                    <?php echo str_replace('_', ' ', $b['status']); ?>
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="booking_details.php?id=<?php echo $b['id']; ?>" class="bg-blue-50 text-blue-600 px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-widest hover:bg-blue-600 hover:text-white transition-all">View</a>
                                    <form method="POST" class="inline-block">
                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                        <select name="status" onchange="this.form.submit()" class="bg-white border border-slate-200 rounded-lg text-[11px] font-bold px-2 py-1.5 outline-none focus:ring-2 focus:ring-blue-500/20">
                                            <option value="">Update Status</option>
                                            <option value="pending" <?php echo $b['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="confirmed" <?php echo $b['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                            <option value="checked_in" <?php echo $b['status'] == 'checked_in' ? 'selected' : ''; ?>>Checked In</option>
                                            <option value="checked_out" <?php echo $b['status'] == 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                                            <option value="cancelled" <?php echo $b['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
