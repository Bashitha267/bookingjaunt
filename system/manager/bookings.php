<?php
require_once '../../config.php';
require_once '../auth_guard.php';
requireRole(['manager']);

// Handle booking cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_booking') {
    $booking_id = $_POST['booking_id'];
    $reason = trim($_POST['reason']);
    
    if (!empty($reason)) {
        // Update booking status
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
        if ($stmt->execute([$booking_id])) {
            logBookingCancellation($pdo, $booking_id, $_SESSION['user_id'], $reason);
            notifyAdminsOfCancellation($pdo, $booking_id, $_SESSION['user_id'], $reason);
            $success_msg = "Booking #$booking_id has been cancelled successfully.";
        } else {
            $error_msg = "Failed to cancel the booking.";
        }
    } else {
        $error_msg = "Please provide a reason for cancellation.";
    }
}

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

$where = [];
$params = [];

if ($search !== '') {
    $like = '%' . $search . '%';
    $where[] = "(p.property_name LIKE ? OR b.guest_name LIKE ? OR r.room_name LIKE ? OR b.guest_phone LIKE ? OR p.contact_number LIKE ?)";
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}
if ($status !== '') {
    $where[] = "b.status = ?";
    $params[] = $status;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$list_sql = "SELECT b.*, p.property_name, p.contact_number, r.room_name
             FROM bookings b
             JOIN properties p ON b.property_id = p.id
             LEFT JOIN property_rooms r ON b.room_id = r.id
             $where_sql
             ORDER BY b.created_at DESC";
$stmt = $pdo->prepare($list_sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings - Manager Console</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="flex min-h-screen overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <main class="flex-1 lg:ml-64 overflow-y-auto h-screen relative z-10">
        <header class="glass-header sticky top-0 z-40 px-4 lg:px-8 py-6 flex items-center gap-4">
            <button onclick="toggleSidebar()" class="lg:hidden w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center text-white hover:bg-white/20 transition-all">
                <i class="fas fa-bars-staggered"></i>
            </button>
            <div class="flex flex-col gap-1">
                <h1 class="text-2xl font-black text-white">All Bookings</h1>
                <p class="text-xs text-sky-300 font-bold uppercase tracking-widest hidden sm:block">Manage property bookings</p>
            </div>
        </header>

        <div class="p-4 lg:p-8 space-y-6">
            <?php if (isset($success_msg)): ?>
                <div class="bg-emerald-500/20 text-emerald-300 p-4 rounded-xl font-bold border border-emerald-500/30">
                    <i class="fas fa-check-circle mr-2"></i> <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            <?php if (isset($error_msg)): ?>
                <div class="bg-red-500/20 text-red-300 p-4 rounded-xl font-bold border border-red-500/30">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <div class="glass-card p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-widest text-sky-300">Search</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Property or Guest" class="mt-2 w-full px-4 py-2 custom-input">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-widest text-sky-300">Status</label>
                        <select name="status" class="mt-2 w-full px-4 py-2 custom-input">
                            <option value="" class="text-black">All</option>
                            <option value="pending" class="text-black" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" class="text-black" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="checked_in" class="text-black" <?php echo $status === 'checked_in' ? 'selected' : ''; ?>>Checked In</option>
                            <option value="checked_out" class="text-black" <?php echo $status === 'checked_out' ? 'selected' : ''; ?>>Checked Out</option>
                            <option value="cancelled" class="text-black" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="flex gap-3">
                        <button class="px-5 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold uppercase tracking-widest transition-all">Filter</button>
                        <a href="bookings.php" class="px-5 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold uppercase tracking-widest transition-all">Reset</a>
                    </div>
                </form>
            </div>

            <div class="glass-card overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr>
                                <th class="px-6 py-4">Property</th>
                                <th class="px-6 py-4">Guest Details</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-400">No bookings found.</td>
                            </tr>
                            <?php else: foreach ($bookings as $booking): ?>
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-white"><?php echo htmlspecialchars($booking['property_name']); ?></div>
                                        <div class="text-xs text-sky-300"><?php echo htmlspecialchars($booking['room_name'] ?? 'N/A'); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-white"><?php echo htmlspecialchars($booking['guest_name']); ?></div>
                                        <div class="text-xs text-gray-300"><?php echo htmlspecialchars($booking['check_in_date']); ?> to <?php echo htmlspecialchars($booking['check_out_date']); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-[10px] font-bold uppercase rounded border bg-white/10 text-white border-white/20">
                                            <?php echo ucwords(str_replace('_', ' ', $booking['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="../shared/booking_download.php?booking_id=<?php echo (int)$booking['id']; ?>&view=1" target="_blank" class="text-white px-3 py-1 bg-white/10 hover:bg-white/20 rounded-lg text-xs font-bold transition-colors inline-block">
                                            View
                                        </a>
                                        <a href="../shared/booking_download.php?booking_id=<?php echo (int)$booking['id']; ?>" class="text-sky-300 hover:text-white px-3 py-1 bg-sky-500/10 hover:bg-sky-500/20 rounded-lg text-xs font-bold transition-colors inline-block">
                                            Download
                                        </a>
                                        <?php if ($booking['status'] !== 'cancelled' && $booking['status'] !== 'checked_out'): ?>
                                        <button onclick="openCancelModal(<?php echo $booking['id']; ?>)" class="text-red-400 hover:text-red-300 px-3 py-1 bg-red-500/10 hover:bg-red-500/20 rounded-lg text-xs font-bold transition-colors">
                                            Cancel
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Cancel Modal -->
    <div id="cancelModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeCancelModal()"></div>
        <div class="bg-slate-800 border border-white/10 p-6 rounded-2xl shadow-2xl relative z-10 w-full max-w-md mx-4">
            <h3 class="text-xl font-bold text-white mb-4 text-red-400"><i class="fas fa-exclamation-triangle mr-2"></i>Cancel Booking</h3>
            <p class="text-sm text-gray-300 mb-4">Are you sure you want to cancel this booking? This action cannot be undone and admins will be notified.</p>
            <form method="POST">
                <input type="hidden" name="action" value="cancel_booking">
                <input type="hidden" name="booking_id" id="modalBookingId" value="">
                
                <div class="mb-4">
                    <label class="block text-[10px] font-bold uppercase tracking-widest text-sky-300 mb-2">Reason for cancellation</label>
                    <textarea name="reason" required class="w-full px-4 py-2 custom-input bg-black/20 text-white placeholder-gray-500 rounded-lg outline-none focus:border-red-400 border border-white/10" rows="3" placeholder="Enter reason here..."></textarea>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeCancelModal()" class="px-4 py-2 rounded-lg bg-white/10 text-white text-sm font-bold hover:bg-white/20 transition-all">Go Back</button>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-red-500 hover:bg-red-600 text-white text-sm font-bold transition-all">Confirm Cancellation</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCancelModal(bookingId) {
            document.getElementById('modalBookingId').value = bookingId;
            document.getElementById('cancelModal').classList.remove('hidden');
        }
        function closeCancelModal() {
            document.getElementById('cancelModal').classList.add('hidden');
        }
    </script>
</body>
</html>
