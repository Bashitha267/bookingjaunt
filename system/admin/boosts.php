<?php
require_once '../../config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../admin.php");
    exit();
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'edit_package') {
            $id = $_POST['package_id'];
            $name = $_POST['name'];
            $duration = $_POST['duration'];
            $price = $_POST['price'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            $stmt = $pdo->prepare("UPDATE boost_packages SET name=?, duration_days=?, price_lkr=?, is_active=? WHERE id=?");
            $stmt->execute([$name, $duration, $price, $is_active, $id]);
            $msg = "Package updated successfully.";
        }
        elseif ($_POST['action'] === 'create_package') {
            $name = $_POST['name'];
            $duration = $_POST['duration'];
            $price = $_POST['price'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            $stmt = $pdo->prepare("INSERT INTO boost_packages (name, duration_days, price_lkr, is_active) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $duration, $price, $is_active]);
            $msg = "Package created successfully.";
        }
        elseif ($_POST['action'] === 'activate_boost') {
            $id = $_POST['boost_id'];
            $stmt = $pdo->prepare("UPDATE property_boosts SET status='active', payment_status='success' WHERE id=?");
            $stmt->execute([$id]);
            $msg = "Boost activated successfully.";
        }
        elseif ($_POST['action'] === 'reject_boost') {
            $id = $_POST['boost_id'];
            $stmt = $pdo->prepare("UPDATE property_boosts SET status='rejected' WHERE id=?");
            $stmt->execute([$id]);
            $msg = "Boost rejected.";
        }
    }
}

$packages = $pdo->query("SELECT * FROM boost_packages")->fetchAll();

$boosts = $pdo->query("
    SELECT pb.*, p.property_name, u.first_name, u.last_name, bp.name as package_name 
    FROM property_boosts pb
    JOIN properties p ON pb.property_id = p.id
    JOIN users u ON p.owner_id = u.id
    LEFT JOIN boost_packages bp ON pb.package_id = bp.id
    WHERE pb.status IN ('active', 'pending')
    ORDER BY pb.created_at DESC
")->fetchAll();

$history_month = trim($_GET['history_month'] ?? '');
$history_from = trim($_GET['history_from'] ?? '');
$history_to = trim($_GET['history_to'] ?? '');
$history_defaulted = false;
if ($history_month === '' && $history_from === '' && $history_to === '') {
    $history_month = date('Y-m');
    $history_defaulted = true;
}
$history_page = max(1, (int)($_GET['history_page'] ?? 1));
$history_page_size = 10;
$history_offset = ($history_page - 1) * $history_page_size;

$history_where = [];
$history_params = [];

if ($history_month !== '') {
    $start = $history_month . '-01';
    $end = (new DateTime($start))->modify('last day of this month')->format('Y-m-d');
    $history_where[] = 'DATE(pb.created_at) BETWEEN ? AND ?';
    $history_params[] = $start;
    $history_params[] = $end;
} elseif ($history_from !== '' || $history_to !== '') {
    $from_month = $history_from !== '' ? $history_from : $history_to;
    $to_month = $history_to !== '' ? $history_to : $history_from;
    if ($from_month !== '' && $to_month !== '') {
        $start = $from_month . '-01';
        $end = (new DateTime($to_month . '-01'))->modify('last day of this month')->format('Y-m-d');
        $history_where[] = 'DATE(pb.created_at) BETWEEN ? AND ?';
        $history_params[] = $start;
        $history_params[] = $end;
    }
}

$history_where_sql = $history_where ? 'WHERE ' . implode(' AND ', $history_where) : '';

$history_count_stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM property_boosts pb
    $history_where_sql
");
$history_count_stmt->execute($history_params);
$history_total = (int)$history_count_stmt->fetchColumn();
$history_pages = max(1, (int)ceil($history_total / $history_page_size));
$history_page = min($history_page, $history_pages);
$history_offset = ($history_page - 1) * $history_page_size;

$history_stmt = $pdo->prepare("
    SELECT pb.*, p.property_name, u.first_name, u.last_name, bp.name as package_name
    FROM property_boosts pb
    JOIN properties p ON pb.property_id = p.id
    JOIN users u ON p.owner_id = u.id
    LEFT JOIN boost_packages bp ON pb.package_id = bp.id
    $history_where_sql
    ORDER BY pb.created_at DESC
    LIMIT $history_page_size OFFSET $history_offset
");
$history_stmt->execute($history_params);
$history_boosts = $history_stmt->fetchAll();

$history_query_params = [
    'history_month' => $history_month,
    'history_from' => $history_from,
    'history_to' => $history_to
];
$history_query_params = array_filter($history_query_params, static function ($value) {
    return $value !== '' && $value !== null;
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Boosts - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; }
        
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 10px; }
    </style>
</head>
<body class="flex min-h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>
    <main class="flex-1 lg:ml-64 overflow-y-auto h-screen bg-[#f8fafc] custom-scrollbar">
        <header class="bg-white/80 backdrop-blur-md border-b border-gray-200 sticky top-0 z-40 px-4 lg:px-8 py-4 flex items-center gap-4">
            <button onclick="toggleSidebar()" class="lg:hidden w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-[#003580] hover:bg-gray-100 transition-all">
                <i class="fas fa-bars-staggered"></i>
            </button>
            <div>
                <h1 class="text-xl font-bold text-[#003580]">Manage Boosts</h1>
                <p class="text-xs text-gray-500 font-medium hidden sm:block">Configure packages and approve property boosts.</p>
            </div>
        </header>

        <div class="p-4 lg:p-8">
            <?php if ($msg): ?>
                <div class="bg-green-50 text-green-600 px-4 py-3 rounded-xl border border-green-100 mb-6 font-bold text-sm">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <!-- Packages Section -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-8">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <h3 class="font-bold text-[#003580]">Boost Packages</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <form method="POST" class="border border-dashed border-gray-300 rounded-xl p-5 relative bg-gray-50/40">
                        <input type="hidden" name="action" value="create_package">

                        <div class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">New Package Name</label>
                                <input type="text" name="name" required class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-[#003580]">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Duration (Days)</label>
                                    <input type="number" name="duration" min="1" required class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-[#003580]">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Price (LKR)</label>
                                    <input type="number" name="price" min="0" step="0.01" required class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-[#003580]">
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="checkbox" name="is_active" checked class="w-4 h-4 text-[#003580]">
                                <label class="text-sm font-bold text-gray-600 cursor-pointer">Active</label>
                            </div>
                        </div>
                        <div class="mt-4 text-right">
                            <button type="submit" class="bg-[#003580] text-white px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-widest hover:bg-[#002560] transition-colors shadow-sm">Create Package</button>
                        </div>
                    </form>
                    <?php foreach ($packages as $pkg): ?>
                    <form method="POST" class="border border-gray-200 rounded-xl p-5 relative">
                        <input type="hidden" name="action" value="edit_package">
                        <input type="hidden" name="package_id" value="<?php echo $pkg['id']; ?>">
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Package Name</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($pkg['name']); ?>" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-[#003580]">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Duration (Days)</label>
                                    <input type="number" name="duration" value="<?php echo $pkg['duration_days']; ?>" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-[#003580]">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Price (LKR)</label>
                                    <input type="number" name="price" value="<?php echo $pkg['price_lkr']; ?>" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-[#003580]">
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="checkbox" name="is_active" <?php echo $pkg['is_active'] ? 'checked' : ''; ?> id="active_<?php echo $pkg['id']; ?>" class="w-4 h-4 text-[#003580]">
                                <label for="active_<?php echo $pkg['id']; ?>" class="text-sm font-bold text-gray-600 cursor-pointer">Active</label>
                            </div>
                        </div>
                        <div class="mt-4 text-right">
                            <button type="submit" class="bg-[#003580] text-white px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-widest hover:bg-[#002560] transition-colors shadow-sm">Update Package</button>
                        </div>
                    </form>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Current Boosts Section -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <h3 class="font-bold text-[#003580]">Property Boosts</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-[10px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-4">Property / Owner</th>
                                <th class="px-6 py-4">Package</th>
                                <th class="px-6 py-4">Start / End Date</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($boosts as $b): 
                                $end_date = date('M d, Y', strtotime($b['start_date'] . ' + ' . $b['duration_days'] . ' days'));
                                $start_date = date('M d, Y', strtotime($b['start_date']));
                            ?>
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-gray-800 text-sm"><?php echo htmlspecialchars($b['property_name']); ?></div>
                                    <div class="text-[11px] text-gray-500 mt-0.5"><?php echo htmlspecialchars($b['first_name'] . ' ' . $b['last_name']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-gray-700 text-sm"><?php echo htmlspecialchars($b['package_name'] ?? 'Custom Package'); ?></div>
                                    <div class="text-[11px] font-bold text-orange-500 mt-0.5">LKR <?php echo number_format($b['amount']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-xs text-gray-600 font-medium"><?php echo $start_date; ?></div>
                                    <div class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mt-1">To: <?php echo $end_date; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($b['status'] == 'active'): ?>
                                        <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest">Active</span>
                                    <?php elseif ($b['status'] == 'pending'): ?>
                                        <span class="bg-orange-100 text-orange-700 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest">Pending</span>
                                    <?php else: ?>
                                        <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest"><?php echo $b['status']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <?php if ($b['status'] == 'pending'): ?>
                                    <div class="flex items-center justify-end gap-2">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="activate_boost">
                                            <input type="hidden" name="boost_id" value="<?php echo $b['id']; ?>">
                                            <button type="submit" title="Activate" class="w-8 h-8 rounded-lg bg-green-50 text-green-600 hover:bg-green-500 hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-check"></i></button>
                                        </form>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="reject_boost">
                                            <input type="hidden" name="boost_id" value="<?php echo $b['id']; ?>">
                                            <button type="submit" title="Reject" class="w-8 h-8 rounded-lg bg-red-50 text-red-600 hover:bg-red-500 hover:text-white flex items-center justify-center transition-colors"><i class="fas fa-times"></i></button>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($boosts)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-400 font-medium">No boosts requested yet.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mt-8">
                <div class="p-6 border-b border-gray-100 flex flex-col gap-4 bg-gray-50/50">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-[#003580]">Boost History</h3>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Showing <?php echo (int)$history_total; ?></span>
                    </div>
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Month</label>
                            <input type="month" name="history_month" value="<?php echo htmlspecialchars($history_month); ?>" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">From Month</label>
                            <input type="month" name="history_from" value="<?php echo htmlspecialchars($history_from); ?>" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">To Month</label>
                            <input type="month" name="history_to" value="<?php echo htmlspecialchars($history_to); ?>" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-sm">
                        </div>
                        <div class="flex gap-3">
                            <button type="submit" class="bg-[#003580] text-white px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-widest hover:bg-[#002560] transition-colors">Apply</button>
                            <a href="boosts.php" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-widest">Reset</a>
                        </div>
                    </form>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-[10px] font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100">
                            <tr>
                                <th class="px-6 py-4">Property / Owner</th>
                                <th class="px-6 py-4">Package</th>
                                <th class="px-6 py-4">Amount</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4">Payment</th>
                                <th class="px-6 py-4">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($history_boosts)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-400 font-medium">No boost history found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($history_boosts as $history): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-gray-800 text-sm"><?php echo htmlspecialchars($history['property_name']); ?></div>
                                            <div class="text-[11px] text-gray-500 mt-0.5"><?php echo htmlspecialchars($history['first_name'] . ' ' . $history['last_name']); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-semibold text-gray-700 text-sm"><?php echo htmlspecialchars($history['package_name'] ?? 'Custom Package'); ?></div>
                                            <div class="text-[11px] text-gray-500 mt-0.5"><?php echo (int)$history['duration_days']; ?> days</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-[11px] font-bold text-orange-600">LKR <?php echo number_format($history['amount']); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest"><?php echo htmlspecialchars($history['status']); ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest"><?php echo htmlspecialchars($history['payment_status']); ?></span>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-gray-500">
                                            <?php echo date('M d, Y', strtotime($history['created_at'])); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                    <span>Page <?php echo (int)$history_page; ?> of <?php echo (int)$history_pages; ?></span>
                    <div class="flex gap-2">
                        <?php if ($history_page > 1): ?>
                            <?php $prev_params = array_merge($history_query_params, ['history_page' => $history_page - 1]); ?>
                            <a href="boosts.php?<?php echo htmlspecialchars(http_build_query($prev_params)); ?>" class="px-3 py-1 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">Prev</a>
                        <?php endif; ?>
                        <?php if ($history_page < $history_pages): ?>
                            <?php $next_params = array_merge($history_query_params, ['history_page' => $history_page + 1]); ?>
                            <a href="boosts.php?<?php echo htmlspecialchars(http_build_query($next_params)); ?>" class="px-3 py-1 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
