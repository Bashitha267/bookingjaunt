<?php
require_once '../../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch first property for sidebar context
$prop_stmt = $pdo->prepare("SELECT * FROM properties WHERE owner_id = ? LIMIT 1");
$prop_stmt->execute([$user_id]);
$property = $prop_stmt->fetch();

// Pagination
$per_page = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

// Status filter
$status_filter = $_GET['status'] ?? 'all';
$allowed_statuses = ['all', 'pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled'];
if (!in_array($status_filter, $allowed_statuses)) $status_filter = 'all';

// Handle status update
if (isset($_POST['update_status'])) {
    $upd = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ? AND property_id IN (SELECT id FROM properties WHERE owner_id = ?)");
    $upd->execute([$_POST['status'], $_POST['booking_id'], $user_id]);
    $redirect_params = http_build_query(['status' => $status_filter, 'page' => $page, 'success' => 'status_updated']);
    header("Location: vehicle_bookings.php?$redirect_params");
    exit();
}

// Build status WHERE clause
$status_sql = $status_filter !== 'all' ? "AND b.status = " . $pdo->quote($status_filter) : "";

// Count total bookings for pagination
$count_stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM bookings b
    JOIN properties p ON b.property_id = p.id
    WHERE p.owner_id = ? AND b.booking_category = 'vehicle'
    $status_sql
");
$count_stmt->execute([$user_id]);
$total_count = $count_stmt->fetchColumn();
$total_pages = max(1, ceil($total_count / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

// Fetch vehicle bookings
$bookings_stmt = $pdo->prepare("
    SELECT b.*, p.property_name, p.vehicle_category, p.cover_image, p.district
    FROM bookings b
    JOIN properties p ON b.property_id = p.id
    WHERE p.owner_id = ? AND b.booking_category = 'vehicle'
    $status_sql
    ORDER BY b.created_at DESC
    LIMIT $per_page OFFSET $offset
");
$bookings_stmt->execute([$user_id]);
$bookings = $bookings_stmt->fetchAll();

// Quick stats
$stats_stmt = $pdo->prepare("
    SELECT
        COUNT(*) as total,
        SUM(CASE WHEN b.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN b.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_count,
        SUM(CASE WHEN b.status = 'checked_in' THEN 1 ELSE 0 END) as active_count,
        COALESCE(SUM(b.total_price), 0) as total_revenue
    FROM bookings b
    JOIN properties p ON b.property_id = p.id
    WHERE p.owner_id = ? AND b.booking_category = 'vehicle'
");
$stats_stmt->execute([$user_id]);
$stats = $stats_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Bookings - Bookingjaunt</title>
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
                <div class="relative hidden sm:block">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-xs" style="color:rgba(255,255,255,0.35);"></i>
                    <input type="text" id="tableSearch" placeholder="Search vehicle bookings..." class="glass-input pl-11 pr-4 py-2.5 rounded-xl text-xs w-64" oninput="filterTable()">
                </div>
            </div>
            <div class="flex items-center gap-3">
                <a href="../../index.php" target="_blank" class="btn-glass hidden xl:flex"><i class="fas fa-external-link-alt"></i> Visit Site</a>
                <a href="my_vehicles.php" class="btn-primary">
                    <i class="fas fa-car"></i>
                    <span class="hidden sm:inline">My Vehicles</span>
                </a>
            </div>
        </header>

        <div class="py-4 px-2 lg:py-8 lg:px-4">

            <!-- Success Alert -->
            <?php if (isset($_GET['success'])): ?>
            <div class="glass-card mb-6 p-4 flex items-center gap-3 anim-up" style="border-color:rgba(74,222,128,0.3); background:rgba(74,222,128,0.10);">
                <i class="fas fa-check-circle" style="color:#4ade80;"></i>
                <span style="color:#86efac; font-weight:700; font-size:0.875rem;">Booking status updated successfully!</span>
            </div>
            <?php endif; ?>

            <!-- Page Header + Filters -->
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4 mb-8 anim-up">
                <div>
                    <h1 class="text-2xl font-black" style="color:white;"><i class="fas fa-car-side mr-2" style="color:#fbbf24;"></i>Vehicle Bookings</h1>
                    <p class="text-sm mt-1" style="color:rgba(255,255,255,0.5);">Monitor and manage all vehicle rental reservations</p>
                </div>
                <!-- Status Filter Tabs -->
                <div class="flex flex-wrap rounded-xl p-1 gap-1" style="background:rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.1);">
                    <?php
                    $filter_labels = ['all' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'checked_in' => 'Active', 'cancelled' => 'Cancelled'];
                    foreach ($filter_labels as $key => $label):
                        $is_active = $status_filter === $key;
                    ?>
                    <a href="vehicle_bookings.php?status=<?php echo $key; ?>&page=1"
                       class="filter-btn px-4 py-2 rounded-lg text-[10px] font-bold uppercase tracking-widest transition-all <?php echo $is_active ? 'active-filter' : ''; ?>"
                       style="color:<?php echo $is_active ? 'white' : 'rgba(255,255,255,0.4)'; ?>; background:<?php echo $is_active ? 'rgba(255,255,255,0.12)' : ''; ?>; text-decoration:none;">
                        <?php echo $label; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 anim-up">
                <div class="glass-card p-5 flex items-center gap-4">
                    <div class="stat-icon" style="background:rgba(245,158,11,0.18);"><i class="fas fa-car-side" style="color:#fbbf24;"></i></div>
                    <div>
                        <p class="stat-label">Total</p>
                        <h3 class="stat-value"><?php echo number_format($stats['total']); ?></h3>
                    </div>
                </div>
                <div class="glass-card p-5 flex items-center gap-4">
                    <div class="stat-icon" style="background:rgba(251,146,60,0.15);"><i class="fas fa-clock" style="color:#fdba74;"></i></div>
                    <div>
                        <p class="stat-label">Pending</p>
                        <h3 class="stat-value"><?php echo number_format($stats['pending_count']); ?></h3>
                    </div>
                </div>
                <div class="glass-card p-5 flex items-center gap-4">
                    <div class="stat-icon" style="background:rgba(74,222,128,0.15);"><i class="fas fa-check-circle" style="color:#4ade80;"></i></div>
                    <div>
                        <p class="stat-label">Confirmed</p>
                        <h3 class="stat-value"><?php echo number_format($stats['confirmed_count']); ?></h3>
                    </div>
                </div>
                <div class="glass-card p-5 flex items-center gap-4">
                    <div class="stat-icon" style="background:rgba(52,211,153,0.15);"><i class="fas fa-coins" style="color:#6ee7b7;"></i></div>
                    <div>
                        <p class="stat-label">Revenue</p>
                        <h3 class="stat-value" style="font-size:1rem;">LKR <?php echo number_format($stats['total_revenue']); ?></h3>
                    </div>
                </div>
            </div>

            <!-- Bookings Table -->
            <div class="glass-table anim-up-2">
                <div class="px-6 py-5 flex justify-between items-center" style="border-bottom:1px solid rgba(255,255,255,0.08);">
                    <h2 class="text-lg font-black" style="color:white;">
                        Booking Records
                        <span class="ml-2 text-sm font-medium" style="color:rgba(255,255,255,0.4);">(<?php echo number_format($total_count); ?> total)</span>
                    </h2>
                    <a href="my_vehicles.php" class="btn-glass" style="font-size:0.55rem;">
                        <i class="fas fa-car mr-1"></i> Manage Vehicles
                    </a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left" id="bookingsTable">
                        <thead>
                            <tr>
                                <th class="glass-th">Booking ID</th>
                                <th class="glass-th">Vehicle</th>
                                <th class="glass-th">Guest</th>
                                <th class="glass-th">Pickup → Return</th>
                                <th class="glass-th">Driver</th>
                                <th class="glass-th">Status</th>
                                <th class="glass-th">Total Price</th>
                                <th class="glass-th text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="8" class="glass-td text-center py-16" style="color:rgba(255,255,255,0.3);">
                                        <i class="fas fa-car text-4xl mb-4 block opacity-20"></i>
                                        <p class="text-sm font-bold uppercase tracking-widest">No vehicle bookings found</p>
                                        <?php if ($status_filter !== 'all'): ?>
                                        <a href="vehicle_bookings.php" class="btn-glass mt-4 inline-flex" style="font-size:0.6rem;">Clear Filter</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($bookings as $b): ?>
                            <tr class="glass-tr booking-row"
                                data-status="<?php echo $b['status']; ?>"
                                data-search="<?php echo strtolower(htmlspecialchars($b['guest_name'] . ' ' . $b['guest_phone'] . ' ' . $b['property_name'])); ?>"
                                onclick="if(!event.target.closest('select,button,a,form')) window.location='booking_details.php?id=<?php echo $b['id']; ?>'">

                                <!-- Booking ID -->
                                <td class="glass-td">
                                    <span class="font-mono text-xs font-bold px-2 py-1 rounded" style="background:rgba(255,255,255,0.07); color:#93c5fd;">
                                        #<?php echo str_pad($b['id'], 5, '0', STR_PAD_LEFT); ?>
                                    </span>
                                    <div class="text-[10px] mt-1.5" style="color:rgba(255,255,255,0.35);">
                                        <?php echo date('M d, Y', strtotime($b['created_at'])); ?>
                                    </div>
                                </td>

                                <!-- Vehicle -->
                                <td class="glass-td">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl overflow-hidden flex-shrink-0" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1);">
                                            <?php if ($b['cover_image']): ?>
                                            <img src="../../<?php echo htmlspecialchars($b['cover_image']); ?>" class="w-full h-full object-cover" alt="">
                                            <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center">
                                                <i class="fas fa-car text-xs" style="color:rgba(255,255,255,0.3);"></i>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="font-bold text-sm" style="color:white;"><?php echo htmlspecialchars($b['property_name']); ?></div>
                                            <div class="text-[10px] mt-0.5" style="color:rgba(255,255,255,0.4);">
                                                <i class="fas fa-tag mr-1"></i><?php echo htmlspecialchars($b['vehicle_category'] ?? 'Vehicle'); ?>
                                                <?php if (!empty($b['district'])): ?>
                                                &nbsp;·&nbsp;<i class="fas fa-map-marker-alt mr-0.5"></i><?php echo htmlspecialchars($b['district']); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Guest -->
                                <td class="glass-td">
                                    <div class="font-bold" style="color:white;"><?php echo htmlspecialchars($b['guest_name']); ?></div>
                                    <div class="text-[11px] mt-1 flex items-center gap-1" style="color:rgba(255,255,255,0.4);">
                                        <i class="fas fa-phone text-[9px]"></i>
                                        <?php echo htmlspecialchars($b['guest_phone']); ?>
                                    </div>
                                </td>

                                <!-- Dates -->
                                <td class="glass-td">
                                    <div class="font-bold text-sm" style="color:white;">
                                        <?php echo date('M d', strtotime($b['check_in_date'])); ?>
                                        <i class="fas fa-arrow-right text-[10px] mx-1" style="color:rgba(255,255,255,0.3);"></i>
                                        <?php echo date('M d', strtotime($b['check_out_date'])); ?>
                                    </div>
                                    <div class="text-[10px] font-bold uppercase mt-1" style="color:rgba(255,255,255,0.35);">
                                        <?php
                                            $days = (strtotime($b['check_out_date']) - strtotime($b['check_in_date'])) / (60*60*24);
                                            echo $days . ' Day' . ($days > 1 ? 's' : '');
                                        ?>
                                    </div>
                                </td>

                                <!-- Driver -->
                                <td class="glass-td">
                                    <?php if (!empty($b['with_driver'])): ?>
                                    <span class="badge" style="background:rgba(96,165,250,0.2); color:#93c5fd;">
                                        <i class="fas fa-user-tie mr-1"></i> With Driver
                                    </span>
                                    <?php else: ?>
                                    <span class="badge" style="background:rgba(255,255,255,0.08); color:rgba(255,255,255,0.4);">
                                        <i class="fas fa-steering-wheel mr-1"></i> Self Drive
                                    </span>
                                    <?php endif; ?>
                                </td>

                                <!-- Status -->
                                <td class="glass-td">
                                    <span class="badge badge-<?php echo $b['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $b['status'])); ?>
                                    </span>
                                </td>

                                <!-- Total Price -->
                                <td class="glass-td">
                                    <div class="font-bold" style="color:white;">LKR <?php echo number_format($b['total_price']); ?></div>
                                    <?php
                                        $perc = ($b['total_price'] > 0) ? min(100, ($b['amount_paid'] / $b['total_price']) * 100) : 0;
                                    ?>
                                    <div class="flex items-center gap-2 mt-1.5">
                                        <div class="h-1.5 w-12 rounded-full overflow-hidden" style="background:rgba(255,255,255,0.1);">
                                            <div class="h-full rounded-full" style="width:<?php echo $perc; ?>%; background:linear-gradient(90deg,#4ade80,#22c55e);"></div>
                                        </div>
                                        <span class="text-[10px] font-bold" style="color:<?php echo $b['payment_status'] == 'complete' ? '#86efac' : 'rgba(255,255,255,0.35)'; ?>;">
                                            <?php echo ucfirst($b['payment_status'] ?? 'pending'); ?>
                                        </span>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="glass-td text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="booking_details.php?id=<?php echo $b['id']; ?>" class="btn-primary" style="padding:6px 14px; font-size:0.55rem;">
                                            <i class="fas fa-eye mr-1"></i> View
                                        </a>
                                        <form method="POST" class="inline-block">
                                            <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                            <select name="status" onchange="this.form.submit()" class="glass-input rounded-lg text-[11px] font-bold px-2 py-1.5 outline-none" style="font-size:0.65rem;">
                                                <option value="">Update</option>
                                                <option value="pending" <?php echo $b['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="confirmed" <?php echo $b['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                <option value="checked_in" <?php echo $b['status'] == 'checked_in' ? 'selected' : ''; ?>>Active</option>
                                                <option value="checked_out" <?php echo $b['status'] == 'checked_out' ? 'selected' : ''; ?>>Completed</option>
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

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="px-6 py-5 flex items-center justify-between" style="border-top:1px solid rgba(255,255,255,0.08);">
                    <p class="text-xs font-bold" style="color:rgba(255,255,255,0.4);">
                        Showing <?php echo number_format($offset + 1); ?>–<?php echo number_format(min($offset + $per_page, $total_count)); ?> of <?php echo number_format($total_count); ?> bookings
                    </p>
                    <div class="flex items-center gap-2">
                        <?php if ($page > 1): ?>
                        <a href="vehicle_bookings.php?status=<?php echo $status_filter; ?>&page=<?php echo $page - 1; ?>" class="btn-glass" style="padding:6px 14px; font-size:0.65rem;">
                            <i class="fas fa-chevron-left"></i> Prev
                        </a>
                        <?php endif; ?>

                        <?php
                        $start_p = max(1, $page - 2);
                        $end_p = min($total_pages, $page + 2);
                        for ($i = $start_p; $i <= $end_p; $i++):
                        ?>
                        <a href="vehicle_bookings.php?status=<?php echo $status_filter; ?>&page=<?php echo $i; ?>"
                           class="w-9 h-9 rounded-xl flex items-center justify-center text-xs font-bold transition-all"
                           style="<?php echo $i == $page ? 'background:rgba(0,108,228,0.6); color:white; border:1px solid rgba(0,108,228,0.5);' : 'background:rgba(255,255,255,0.07); color:rgba(255,255,255,0.6); border:1px solid rgba(255,255,255,0.1);'; ?> text-decoration:none;">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                        <a href="vehicle_bookings.php?status=<?php echo $status_filter; ?>&page=<?php echo $page + 1; ?>" class="btn-glass" style="padding:6px 14px; font-size:0.65rem;">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function filterTable() {
            const search = (document.getElementById('tableSearch')?.value || '').toLowerCase();
            document.querySelectorAll('.booking-row').forEach(row => {
                const rowSearch = row.dataset.search;
                row.style.display = !search || rowSearch.includes(search) ? '' : 'none';
            });
        }

        // Make rows clickable (row cursor)
        document.querySelectorAll('.booking-row').forEach(row => {
            row.style.cursor = 'pointer';
        });
    </script>
</body>
</html>
