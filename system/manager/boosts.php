<?php
require_once '../../config.php';
require_once '../auth_guard.php';
requireRole(['manager']);

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
    ORDER BY pb.created_at DESC
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Boosts - Manager Console</title>
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
            <div class="flex items-center gap-4 flex-1">
                <div class="w-12 h-12 bg-amber-500/20 text-amber-400 rounded-xl flex items-center justify-center text-xl shadow-inner border border-amber-500/30">
                    <i class="fas fa-rocket"></i>
                </div>
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-black text-white">Manage Boosts</h1>
                    <p class="text-xs text-sky-300 font-bold uppercase tracking-widest hidden sm:block">Configure packages and approve property boosts</p>
                </div>
            </div>
        </header>

        <div class="p-4 lg:p-8">
            <?php if ($msg): ?>
                <div class="bg-emerald-500/20 text-emerald-300 px-4 py-3 rounded-xl border border-emerald-500/30 mb-6 font-bold">
                    <i class="fas fa-check-circle mr-2"></i><?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <!-- Packages Section -->
            <div class="glass-card overflow-hidden mb-8">
                <div class="p-6 border-b border-white/10 flex justify-between items-center">
                    <h3 class="font-bold text-white">Boost Packages</h3>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <form method="POST" class="border border-dashed border-white/20 rounded-xl p-5 relative bg-white/5">
                        <input type="hidden" name="action" value="create_package">

                        <div class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-sky-300 uppercase tracking-widest mb-1">New Package Name</label>
                                <input type="text" name="name" required class="w-full px-4 py-2 custom-input">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-sky-300 uppercase tracking-widest mb-1">Duration (Days)</label>
                                    <input type="number" name="duration" min="1" required class="w-full px-4 py-2 custom-input">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-sky-300 uppercase tracking-widest mb-1">Price (LKR)</label>
                                    <input type="number" name="price" min="0" step="0.01" required class="w-full px-4 py-2 custom-input">
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="checkbox" name="is_active" checked class="w-4 h-4 rounded border-white/20 bg-black/20 text-amber-500 focus:ring-amber-500">
                                <label class="text-sm font-bold text-sky-300 cursor-pointer">Active</label>
                            </div>
                        </div>
                        <div class="mt-4 text-right">
                            <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-white px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest transition-all">Create Package</button>
                        </div>
                    </form>

                    <?php foreach ($packages as $pkg): ?>
                    <form method="POST" class="border border-white/10 rounded-xl p-5 relative bg-white/5">
                        <input type="hidden" name="action" value="edit_package">
                        <input type="hidden" name="package_id" value="<?php echo $pkg['id']; ?>">
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-sky-300 uppercase tracking-widest mb-1">Package Name</label>
                                <input type="text" name="name" value="<?php echo htmlspecialchars($pkg['name']); ?>" class="w-full px-4 py-2 custom-input">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-sky-300 uppercase tracking-widest mb-1">Duration (Days)</label>
                                    <input type="number" name="duration" value="<?php echo $pkg['duration_days']; ?>" class="w-full px-4 py-2 custom-input">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-sky-300 uppercase tracking-widest mb-1">Price (LKR)</label>
                                    <input type="number" name="price" value="<?php echo $pkg['price_lkr']; ?>" class="w-full px-4 py-2 custom-input">
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="checkbox" name="is_active" <?php echo $pkg['is_active'] ? 'checked' : ''; ?> id="active_<?php echo $pkg['id']; ?>" class="w-4 h-4 rounded border-white/20 bg-black/20 text-amber-500 focus:ring-amber-500">
                                <label for="active_<?php echo $pkg['id']; ?>" class="text-sm font-bold text-sky-300 cursor-pointer">Active</label>
                            </div>
                        </div>
                        <div class="mt-4 text-right">
                            <button type="submit" class="bg-sky-600 hover:bg-sky-500 text-white px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest transition-all">Update Package</button>
                        </div>
                    </form>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Current Boosts Section -->
            <div class="glass-card overflow-hidden">
                <div class="p-6 border-b border-white/10 flex justify-between items-center">
                    <h3 class="font-bold text-white">Property Boosts</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="text-[10px] font-bold text-sky-300 uppercase tracking-wider border-b border-white/10">
                            <tr>
                                <th class="px-6 py-4">Property / Owner</th>
                                <th class="px-6 py-4">Package</th>
                                <th class="px-6 py-4">Start / End Date</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            <?php foreach ($boosts as $b): 
                                $end_date = date('M d, Y', strtotime($b['start_date'] . ' + ' . $b['duration_days'] . ' days'));
                                $start_date = date('M d, Y', strtotime($b['start_date']));
                            ?>
                            <tr class="hover:bg-white/5 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-white text-sm"><?php echo htmlspecialchars($b['property_name']); ?></div>
                                    <div class="text-[11px] text-sky-300 mt-0.5"><?php echo htmlspecialchars($b['first_name'] . ' ' . $b['last_name']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-gray-300 text-sm"><?php echo htmlspecialchars($b['package_name'] ?? 'Custom Package'); ?></div>
                                    <div class="text-[11px] font-bold text-amber-400 mt-0.5">LKR <?php echo number_format($b['amount']); ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-xs text-gray-300 font-medium"><?php echo $start_date; ?></div>
                                    <div class="text-[10px] text-sky-400 font-bold uppercase tracking-wider mt-1">To: <?php echo $end_date; ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($b['status'] == 'active'): ?>
                                        <span class="bg-emerald-500/20 text-emerald-300 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest border border-emerald-500/30">Active</span>
                                    <?php elseif ($b['status'] == 'pending'): ?>
                                        <span class="bg-amber-500/20 text-amber-300 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest border border-amber-500/30">Pending</span>
                                    <?php else: ?>
                                        <span class="bg-white/10 text-gray-400 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest border border-white/20"><?php echo $b['status']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <?php if ($b['status'] == 'pending'): ?>
                                    <div class="flex items-center justify-end gap-2">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="activate_boost">
                                            <input type="hidden" name="boost_id" value="<?php echo $b['id']; ?>">
                                            <button type="submit" title="Activate" class="w-8 h-8 rounded-lg bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/40 border border-emerald-500/30 flex items-center justify-center transition-colors"><i class="fas fa-check"></i></button>
                                        </form>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="reject_boost">
                                            <input type="hidden" name="boost_id" value="<?php echo $b['id']; ?>">
                                            <button type="submit" title="Reject" class="w-8 h-8 rounded-lg bg-red-500/20 text-red-400 hover:bg-red-500/40 border border-red-500/30 flex items-center justify-center transition-colors"><i class="fas fa-times"></i></button>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($boosts)): ?>
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sky-300/70 font-medium">No boosts requested yet.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
