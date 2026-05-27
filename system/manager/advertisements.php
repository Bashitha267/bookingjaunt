<?php
require_once '../../config.php';
require_once '../auth_guard.php';
requireRole(['manager']);

$success_msg = '';
$error_msg = '';

$package_type_options = [
    'sidebar_ad' => 'Sidebar Vertical Unit',
    'horizontal_strip_ad' => 'Results Horizontal Strip',
    'mobile_scroll_ad' => 'Mobile Scroll Unit'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $ad_id = (int) ($_POST['ad_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? '';

    if ($ad_id > 0 && in_array($new_status, ['active', 'inactive'], true)) {
        $stmt = $pdo->prepare("UPDATE advertisements SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $ad_id]);
        $success_msg = "Advertisement status updated.";
    } else {
        $error_msg = "Invalid request. Please try again.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['edit_package', 'create_package'], true)) {
    $package_name = trim($_POST['package_name'] ?? '');
    $package_type = $_POST['package_type'] ?? '';
    $duration_days = (int) ($_POST['duration_days'] ?? 0);
    $price = (float) ($_POST['price'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($package_name === '' || !isset($package_type_options[$package_type]) || $duration_days < 1 || $price < 0) {
        $error_msg = "Please enter valid package details.";
    } else {
        try {
            if ($_POST['action'] === 'create_package') {
                $stmt = $pdo->prepare("INSERT INTO advertisement_packages (package_name, package_type, price, duration_days, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$package_name, $package_type, $price, $duration_days, $is_active]);
                $success_msg = "Advertisement package created.";
            } else {
                $package_id = (int) ($_POST['package_id'] ?? 0);
                if ($package_id > 0) {
                    $stmt = $pdo->prepare("UPDATE advertisement_packages SET package_name = ?, package_type = ?, price = ?, duration_days = ?, is_active = ? WHERE id = ?");
                    $stmt->execute([$package_name, $package_type, $price, $duration_days, $is_active, $package_id]);
                    $success_msg = "Advertisement package updated.";
                } else {
                    $error_msg = "Invalid package selected.";
                }
            }
        } catch (PDOException $e) {
            $error_msg = "Unable to save package details.";
        }
    }
}

$ads = [];
try {
    $stmt = $pdo->query("SELECT a.*, u.first_name, u.last_name FROM advertisements a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC");
    $ads = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Unable to load advertisements.";
}

$ad_packages = [];
try {
    $stmt = $pdo->query("SELECT * FROM advertisement_packages ORDER BY created_at DESC");
    $ad_packages = $stmt->fetchAll();
} catch (PDOException $e) {
    // Graceful fail if table not ready
}

$current_ads = [];
$history_ads = [];
$today = new DateTime('today');

foreach ($ads as $ad) {
    $end_date = null;
    if (!empty($ad['created_at']) && !empty($ad['package_duration_days'])) {
        $end_date = (new DateTime($ad['created_at']))->modify('+' . (int) $ad['package_duration_days'] . ' days');
    }

    $is_expired = $end_date ? $end_date < $today : false;

    if ($ad['status'] === 'active' && !$is_expired) {
        $current_ads[] = $ad + ['end_date' => $end_date, 'is_expired' => $is_expired];
    } else {
        $history_ads[] = $ad + ['end_date' => $end_date, 'is_expired' => $is_expired];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Advertisements - Manager Console</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="flex min-h-screen overflow-hidden">
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 ml-0 lg:ml-64 overflow-y-auto h-screen relative z-10">
        <header class="glass-header sticky top-0 z-40 px-4 lg:px-8 py-6 flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center text-white hover:bg-white/20 transition-all">
                    <i class="fas fa-bars-staggered"></i>
                </button>
                <div class="w-12 h-12 bg-purple-500/20 text-purple-400 rounded-xl flex items-center justify-center text-xl shadow-inner border border-purple-500/30">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-black text-white">Manage Advertisements</h1>
                    <p class="text-xs text-sky-300 font-bold uppercase tracking-widest hidden sm:block">Monitor active campaigns and packages</p>
                </div>
            </div>
            <div class="flex items-center gap-3 text-xs font-bold text-sky-300">
                <span class="px-3 py-1 rounded-xl bg-sky-500/20 border border-sky-500/30">Active: <?php echo count($current_ads); ?></span>
                <span class="px-3 py-1 rounded-xl bg-white/10 border border-white/20">History: <?php echo count($history_ads); ?></span>
            </div>
        </header>

        <div class="p-4 lg:p-8 space-y-8">
            <?php if ($success_msg): ?>
                <div class="bg-emerald-500/20 border border-emerald-500/30 text-emerald-300 px-4 py-3 rounded-xl flex items-center gap-3 font-bold">
                    <i class="fas fa-check-circle"></i>
                    <span class="text-sm"><?php echo htmlspecialchars($success_msg); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($error_msg): ?>
                <div class="bg-red-500/20 border border-red-500/30 text-red-300 px-4 py-3 rounded-xl flex items-center gap-3 font-bold">
                    <i class="fas fa-exclamation-circle"></i>
                    <span class="text-sm"><?php echo htmlspecialchars($error_msg); ?></span>
                </div>
            <?php endif; ?>

            <section class="glass-card overflow-hidden">
                <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between">
                    <h2 class="font-bold text-white">Advertisement Packages</h2>
                    <span class="text-xs font-bold text-sky-300">Create and manage placements</span>
                </div>

                <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <form method="POST" class="bg-white/5 border border-dashed border-white/20 rounded-2xl p-6">
                        <input type="hidden" name="action" value="create_package">
                        <div class="text-[10px] font-bold text-purple-400 uppercase tracking-widest mb-4">New Package</div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-sky-300 uppercase tracking-widest mb-2">Package Name</label>
                                <input type="text" name="package_name" required class="w-full custom-input px-4 py-2">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-sky-300 uppercase tracking-widest mb-2">Package Type</label>
                                <select name="package_type" required class="w-full custom-input px-4 py-2">
                                    <?php foreach ($package_type_options as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" class="text-black"><?php echo htmlspecialchars($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[10px] font-bold text-sky-300 uppercase tracking-widest mb-2">Duration (Days)</label>
                                    <input type="number" name="duration_days" min="1" required class="w-full custom-input px-4 py-2">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-sky-300 uppercase tracking-widest mb-2">Price (LKR)</label>
                                    <input type="number" name="price" min="0" step="0.01" required class="w-full custom-input px-4 py-2">
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="checkbox" name="is_active" checked class="w-4 h-4 rounded border-white/20 bg-black/20 text-purple-500 focus:ring-purple-500">
                                <label class="text-sm font-bold text-sky-300">Active</label>
                            </div>
                        </div>
                        <div class="mt-5 text-right">
                            <button type="submit" class="px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold uppercase tracking-widest transition-all">Create Package</button>
                        </div>
                    </form>

                    <?php if (empty($ad_packages)): ?>
                        <div class="bg-white/5 border border-white/10 rounded-2xl p-10 text-center flex flex-col items-center justify-center">
                            <i class="fas fa-box-open text-4xl text-sky-300/50 mb-3"></i>
                            <p class="text-sky-300 font-semibold">No advertisement packages yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($ad_packages as $pkg): ?>
                            <form method="POST" class="bg-white/5 border border-white/10 rounded-2xl p-6">
                                <input type="hidden" name="action" value="edit_package">
                                <input type="hidden" name="package_id" value="<?php echo (int) $pkg['id']; ?>">
                                <div class="text-[10px] font-bold text-sky-400 uppercase tracking-widest mb-4">Package #<?php echo (int) $pkg['id']; ?></div>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-[10px] font-bold text-sky-300 uppercase tracking-widest mb-2">Package Name</label>
                                        <input type="text" name="package_name" value="<?php echo htmlspecialchars($pkg['package_name']); ?>" required class="w-full custom-input px-4 py-2">
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-sky-300 uppercase tracking-widest mb-2">Package Type</label>
                                        <select name="package_type" required class="w-full custom-input px-4 py-2">
                                            <?php foreach ($package_type_options as $value => $label): ?>
                                                <option value="<?php echo $value; ?>" class="text-black" <?php echo $pkg['package_type'] === $value ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-[10px] font-bold text-sky-300 uppercase tracking-widest mb-2">Duration (Days)</label>
                                            <input type="number" name="duration_days" min="1" value="<?php echo (int) $pkg['duration_days']; ?>" required class="w-full custom-input px-4 py-2">
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-bold text-sky-300 uppercase tracking-widest mb-2">Price (LKR)</label>
                                            <input type="number" name="price" min="0" step="0.01" value="<?php echo htmlspecialchars($pkg['price']); ?>" required class="w-full custom-input px-4 py-2">
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" name="is_active" <?php echo (int) $pkg['is_active'] === 1 ? 'checked' : ''; ?> class="w-4 h-4 rounded border-white/20 bg-black/20 text-sky-500 focus:ring-sky-500">
                                        <label class="text-sm font-bold text-sky-300">Active</label>
                                    </div>
                                </div>
                                <div class="mt-5 text-right">
                                    <button type="submit" class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold uppercase tracking-widest transition-all">Update Package</button>
                                </div>
                            </form>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="glass-card overflow-hidden">
                <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between">
                    <h2 class="font-bold text-white">Current Campaigns</h2>
                    <span class="text-xs font-bold text-emerald-400">Live placements</span>
                </div>

                <?php if (empty($current_ads)): ?>
                    <div class="p-10 text-center">
                        <i class="fas fa-bullhorn text-4xl text-sky-300/50 mb-3"></i>
                        <p class="text-sky-300 font-semibold">No active advertisements.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto p-6">
                        <table class="w-full text-left text-sm">
                            <thead class="text-sky-300 text-[10px] font-bold uppercase tracking-widest border-b border-white/10">
                                <tr>
                                    <th class="px-6 py-4">Ad</th>
                                    <th class="px-6 py-4">Owner</th>
                                    <th class="px-6 py-4">Placement</th>
                                    <th class="px-6 py-4">Price</th>
                                    <th class="px-6 py-4">End Date</th>
                                    <th class="px-6 py-4 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php foreach ($current_ads as $ad): ?>
                                    <tr class="hover:bg-white/5 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-16 h-12 bg-white/10 rounded-xl overflow-hidden flex items-center justify-center border border-white/20">
                                                    <?php if (!empty($ad['image_path'])): ?>
                                                        <img src="../../<?php echo htmlspecialchars($ad['image_path']); ?>" class="w-full h-full object-cover" alt="Ad image">
                                                    <?php else: ?>
                                                        <i class="fas fa-image text-white/50"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <div class="font-bold text-white"><?php echo htmlspecialchars($ad['ad_title'] ?: $ad['owner_name']); ?></div>
                                                    <div class="text-xs text-sky-300 font-semibold">#<?php echo (int) $ad['id']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-semibold text-gray-300"><?php echo htmlspecialchars(trim(($ad['first_name'] ?? '') . ' ' . ($ad['last_name'] ?? '')) ?: 'Guest'); ?></div>
                                            <div class="text-[10px] text-sky-400 font-bold uppercase"><?php echo htmlspecialchars($ad['owner_name'] ?? ''); ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-semibold text-gray-300"><?php echo htmlspecialchars($ad['package_name'] ?? 'Custom'); ?></div>
                                            <div class="text-[10px] text-purple-400 font-bold uppercase"><?php echo htmlspecialchars($ad['package_type'] ?? ''); ?></div>
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-white">LKR <?php echo number_format((float) ($ad['price'] ?? 0), 2); ?></td>
                                        <td class="px-6 py-4">
                                            <?php if (!empty($ad['end_date'])): ?>
                                                <div class="font-semibold text-white"><?php echo $ad['end_date']->format('M d, Y'); ?></div>
                                            <?php else: ?>
                                                <div class="text-sky-300/70 text-xs font-semibold">Not set</div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="ad_id" value="<?php echo (int) $ad['id']; ?>">
                                                <input type="hidden" name="new_status" value="inactive">
                                                <button type="submit" class="px-4 py-2 rounded-lg bg-rose-500/20 text-rose-300 border border-rose-500/30 text-[10px] uppercase tracking-widest font-bold hover:bg-rose-500/40 transition-colors">Deactivate</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section class="glass-card overflow-hidden">
                <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between">
                    <h2 class="font-bold text-white">History</h2>
                    <span class="text-xs font-bold text-sky-300">Inactive or expired</span>
                </div>

                <?php if (empty($history_ads)): ?>
                    <div class="p-10 text-center">
                        <i class="fas fa-clock text-4xl text-sky-300/50 mb-3"></i>
                        <p class="text-sky-300 font-semibold">No historical advertisements.</p>
                    </div>
                <?php else: ?>
                    <div class="p-6 grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <?php foreach ($history_ads as $ad): ?>
                            <div class="bg-white/5 border border-white/10 rounded-2xl p-6">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-[10px] font-bold text-sky-400 uppercase tracking-widest">#<?php echo (int) $ad['id']; ?></p>
                                        <h3 class="text-lg font-black text-white mt-1"><?php echo htmlspecialchars($ad['ad_title'] ?: $ad['owner_name']); ?></h3>
                                        <p class="text-xs text-sky-300 font-semibold mt-2"><?php echo htmlspecialchars($ad['package_name'] ?? 'Custom'); ?></p>
                                    </div>
                                    <span class="text-[10px] font-bold px-3 py-1 rounded-full <?php echo $ad['status'] === 'inactive' ? 'bg-white/10 text-gray-400 border border-white/20' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30'; ?>">
                                        <?php echo $ad['status'] === 'inactive' ? 'Inactive' : 'Expired'; ?>
                                    </span>
                                </div>
                                <div class="grid grid-cols-2 gap-4 mt-4 text-[10px] font-bold text-sky-300 uppercase tracking-widest">
                                    <div>
                                        <div>Owner</div>
                                        <div class="text-white mt-1 capitalize tracking-normal text-xs"><?php echo htmlspecialchars(trim(($ad['first_name'] ?? '') . ' ' . ($ad['last_name'] ?? '')) ?: 'Guest'); ?></div>
                                    </div>
                                    <div>
                                        <div>Placed</div>
                                        <div class="text-white mt-1 tracking-normal text-xs"><?php echo date('M d, Y', strtotime($ad['created_at'])); ?></div>
                                    </div>
                                    <div>
                                        <div>Price</div>
                                        <div class="text-white mt-1 tracking-normal text-xs">LKR <?php echo number_format((float) ($ad['price'] ?? 0), 2); ?></div>
                                    </div>
                                    <div>
                                        <div>End Date</div>
                                        <div class="text-white mt-1 tracking-normal text-xs"><?php echo !empty($ad['end_date']) ? $ad['end_date']->format('M d, Y') : 'Not set'; ?></div>
                                    </div>
                                </div>
                                <div class="mt-4 flex items-center justify-between">
                                    <span class="text-[10px] font-bold text-purple-400 uppercase tracking-widest"><?php echo htmlspecialchars($ad['package_type'] ?? ''); ?></span>
                                    <?php if ($ad['status'] === 'inactive'): ?>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="ad_id" value="<?php echo (int) $ad['id']; ?>">
                                            <input type="hidden" name="new_status" value="active">
                                            <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-500/20 border border-emerald-500/30 text-emerald-300 text-[10px] uppercase tracking-widest font-bold hover:bg-emerald-500/40 transition-colors">Reactivate</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</body>
</html>
