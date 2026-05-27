<?php
require_once '../../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
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
$property_id = 0;
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
    die("Property not found.");
}

// Handle Status Updates
if (isset($_POST['update_status'])) {
    $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ? AND property_id = ?");
    $stmt->execute([$_POST['status'], $_POST['booking_id'], $property_id]);
    header("Location: bookings.php?success=status_updated&property_id=$property_id");
    exit();
}

// Fetch Bookings
$bookings_stmt = $pdo->prepare("
    SELECT b.*, pr.room_name
    FROM bookings b
    JOIN property_rooms pr ON b.room_id = pr.id
    WHERE b.property_id = ? AND b.booking_type = 'online'
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
</head>
<body class="flex min-h-screen overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <main class="main-content overflow-y-auto" style="position:relative; z-index:1;">

        <!-- Header -->
        <header class="glass-header sticky top-0 z-40 px-2 lg:px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden w-10 h-10 rounded-xl btn-glass flex items-center justify-center">
                    <i class="fas fa-bars-staggered"></i>
                </button>
                <form method="GET" class="hidden sm:block">
                    <label class="sr-only" for="propertySelect">Property</label>
                    <select id="propertySelect" name="property_id" onchange="this.form.submit()" class="glass-input px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest">
                        <?php foreach ($properties as $prop): ?>
                            <option value="<?php echo (int)$prop['id']; ?>" <?php echo (int)$prop['id'] === (int)$property_id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($prop['property_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <div class="relative hidden sm:block">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-xs" style="color:var(--text-muted);"></i>
                    <input type="text" id="tableSearch" placeholder="Search bookings, guests..." class="glass-input pl-11 pr-4 py-2.5 rounded-xl text-xs w-64" oninput="filterTable()">
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="../../index.php" target="_blank" class="btn-glass hidden xl:flex"><i class="fas fa-external-link-alt"></i> Visit Site</a>
                <?php if ($property_id): ?>
                    <a href="../../hotel_info.php?id=<?php echo $property_id; ?>" target="_blank" class="btn-primary">
                        <i class="fas fa-plus"></i><span class="hidden sm:inline">New Booking</span>
                    </a>
                <?php else: ?>
                    <span class="btn-glass opacity-40 cursor-not-allowed"><i class="fas fa-plus"></i><span class="hidden sm:inline">New Booking</span></span>
                <?php endif; ?>
            </div>
        </header>

        <div class="py-4 px-2 lg:py-8 lg:px-4">

            <?php if (isset($_GET['success'])): ?>
            <div class="glass-card mb-6 p-4 flex items-center gap-3 anim-up" style="border-color:rgba(74,222,128,0.3); background:rgba(74,222,128,0.10);">
                <i class="fas fa-check-circle" style="color:#4ade80;"></i>
                <span style="color:#86efac; font-weight:700; font-size:0.875rem;">Booking status updated successfully!</span>
            </div>
            <?php endif; ?>

            <!-- Page Header -->
            <div class="flex justify-between items-end mb-8 anim-up">
                <div>
                    <h1 class="text-2xl font-black" style="color:white;">Bookings Management</h1>
                    <p class="text-sm mt-1" style="color:var(--text-secondary);">Monitor and manage all online reservations for <?php echo htmlspecialchars($property['property_name'] ?? 'your property'); ?></p>
                </div>
                <div class="flex rounded-xl p-1 gap-1" style="background:rgba(255,255,255,0.08); border:1px solid var(--glass-border);">
                    <button onclick="filterStatus('all')" id="filter-all" class="filter-btn active-filter px-4 py-2 rounded-lg text-[10px] font-bold uppercase tracking-widest transition-all">All</button>
                    <button onclick="filterStatus('confirmed')" id="filter-confirmed" class="filter-btn px-4 py-2 rounded-lg text-[10px] font-bold uppercase tracking-widest transition-all" style="color:var(--text-muted);">Confirmed</button>
                    <button onclick="filterStatus('pending')" id="filter-pending" class="filter-btn px-4 py-2 rounded-lg text-[10px] font-bold uppercase tracking-widest transition-all" style="color:var(--text-muted);">Pending</button>
                </div>
            </div>

            <!-- Bookings Table -->
            <div class="glass-table anim-up-2">
                <div class="overflow-x-auto">
                    <table class="w-full text-left" id="bookingsTable">
                        <thead>
                            <tr>
                                <th class="glass-th">Guest & Stay</th>
                                <th class="glass-th">Room</th>
                                <th class="glass-th">Dates</th>
                                <th class="glass-th">Payment</th>
                                <th class="glass-th">Amount Paid</th>
                                <th class="glass-th">Status</th>
                                <th class="glass-th text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="7" class="glass-td text-center py-16" style="color:var(--text-muted);">
                                        <i class="fas fa-calendar-xmark text-4xl mb-3 block opacity-30"></i>
                                        No bookings found yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($bookings as $b): ?>
                            <tr class="glass-tr booking-row" data-status="<?php echo $b['status']; ?>" data-search="<?php echo strtolower(htmlspecialchars($b['guest_name'] . ' ' . $b['guest_phone'] . ' ' . $b['room_name'])); ?>" onclick="if(!event.target.closest('select,button,a,form')) window.location='booking_details.php?id=<?php echo $b['id']; ?>'">
                                <td class="glass-td">
                                    <div class="font-bold" style="color:white;"><?php echo htmlspecialchars($b['guest_name']); ?></div>
                                    <div class="text-[11px] mt-1" style="color:var(--text-muted);"><?php echo htmlspecialchars($b['guest_phone']); ?></div>
                                    <span class="inline-block mt-2 px-2 py-0.5 rounded text-[9px] font-bold uppercase" style="background:rgba(255,255,255,0.08); color:var(--text-secondary);">
                                        <?php echo $b['booking_type']; ?>
                                    </span>
                                </td>
                                <td class="glass-td" style="font-weight:600; font-size:0.85rem;">
                                    <?php echo htmlspecialchars($b['room_name']); ?>
                                    <?php if($b['room_number']): ?>
                                        <span class="block text-[10px] mt-1" style="color:#93c5fd;">No: <?php echo $b['room_number']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="glass-td">
                                    <div class="font-bold text-sm" style="color:white;"><?php echo date('M d', strtotime($b['check_in_date'])); ?> – <?php echo date('M d', strtotime($b['check_out_date'])); ?></div>
                                    <div class="text-[10px] font-bold uppercase mt-1" style="color:var(--text-muted);">
                                        <?php
                                            $nights = (strtotime($b['check_out_date']) - strtotime($b['check_in_date'])) / (60*60*24);
                                            echo $nights . ' Night' . ($nights > 1 ? 's' : '');
                                        ?>
                                    </div>
                                </td>
                                <td class="glass-td">
                                    <div class="font-bold" style="color:white;">LKR <?php echo number_format($b['total_price']); ?></div>
                                    <div class="flex items-center gap-2 mt-2">
                                        <div class="h-1.5 w-14 rounded-full overflow-hidden" style="background:rgba(255,255,255,0.1);">
                                            <?php $perc = ($b['total_price'] > 0) ? min(100, ($b['amount_paid'] / $b['total_price']) * 100) : 0; ?>
                                            <div class="h-full rounded-full" style="width:<?php echo $perc; ?>%; background:linear-gradient(90deg,#4ade80,#22c55e);"></div>
                                        </div>
                                        <span class="text-[10px] font-bold <?php echo $b['payment_status'] == 'complete' ? 'text-green-400' : ''; ?>" style="color:<?php echo $b['payment_status'] == 'complete' ? '#86efac' : 'var(--text-muted)'; ?>">
                                            <?php echo $b['payment_status']; ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="glass-td font-bold" style="color:white;">LKR <?php echo number_format($b['amount_paid']); ?></td>
                                <td class="glass-td">
                                    <span class="badge badge-<?php echo $b['status']; ?>"><?php echo str_replace('_', ' ', $b['status']); ?></span>
                                </td>
                                <td class="glass-td text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="booking_details.php?id=<?php echo $b['id']; ?>" class="btn-primary" style="padding:6px 14px; font-size:0.55rem;">View</a>
                                        <form method="POST" class="inline-block">
                                            <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                            <select name="status" onchange="this.form.submit()" class="glass-input rounded-lg text-[11px] font-bold px-2 py-1.5 outline-none" style="font-size:0.65rem;">
                                                <option value="">Update</option>
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
        </div>
    </main>

    <script>
        // Filter by status buttons
        let currentStatus = 'all';

        function filterStatus(status) {
            currentStatus = status;
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active-filter');
                btn.style.color = 'var(--text-muted)';
                btn.style.background = '';
            });
            const activeBtn = document.getElementById('filter-' + status);
            if (activeBtn) {
                activeBtn.classList.add('active-filter');
                activeBtn.style.color = 'white';
                activeBtn.style.background = 'rgba(255,255,255,0.12)';
            }
            applyFilters();
        }

        function filterTable() { applyFilters(); }

        function applyFilters() {
            const search = (document.getElementById('tableSearch')?.value || '').toLowerCase();
            document.querySelectorAll('.booking-row').forEach(row => {
                const rowStatus = row.dataset.status;
                const rowSearch = row.dataset.search;
                const statusMatch = currentStatus === 'all' || rowStatus === currentStatus;
                const searchMatch = !search || rowSearch.includes(search);
                row.style.display = (statusMatch && searchMatch) ? '' : 'none';
            });
        }

        // Init active filter style
        document.addEventListener('DOMContentLoaded', () => {
            const allBtn = document.getElementById('filter-all');
            if (allBtn) { allBtn.style.color = 'white'; allBtn.style.background = 'rgba(255,255,255,0.12)'; }
        });
    </script>
</body>
</html>
