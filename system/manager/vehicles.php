<?php
require_once '../../config.php';
require_once '../auth_guard.php';
requireRole(['manager']);

// Fetch all vehicles with owner info
$sql = "SELECT p.*, u.first_name, u.last_name
        FROM properties p
        JOIN users u ON p.owner_id = u.id
        WHERE p.business_type = 'vehicle'
        ORDER BY p.created_at DESC";
$stmt = $pdo->query($sql);
$vehicles = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicles - Manager Console</title>
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
                <div class="w-12 h-12 bg-sky-500/20 text-sky-400 rounded-xl flex items-center justify-center text-xl shadow-inner border border-sky-500/30">
                    <i class="fas fa-car"></i>
                </div>
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-black text-white">Vehicles</h1>
                    <p class="text-xs text-sky-300 font-bold uppercase tracking-widest hidden sm:block">Manage Registered Vehicles</p>
                </div>
            </div>
            <span class="text-[11px] font-bold uppercase tracking-widest text-white bg-sky-600 px-4 py-2 rounded-xl shadow-lg shadow-sky-900/50">
                Total: <?php echo count($vehicles); ?>
            </span>
        </header>

        <div class="p-4 lg:p-8 space-y-6">
            <div class="glass-card overflow-hidden">
                <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between">
                    <h2 class="font-bold text-white">Vehicle Directory</h2>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-sky-300"><?php echo count($vehicles); ?> Vehicles Listed</span>
                </div>

                <?php if (empty($vehicles)): ?>
                    <div class="p-12 text-center">
                        <div class="w-16 h-16 bg-white/10 text-sky-300 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-car text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-white">No vehicles found</h3>
                        <p class="text-xs text-sky-300/70 mt-2">Once vehicles are registered, they will appear here.</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="text-[10px] font-bold text-sky-300 uppercase tracking-wider">
                                <tr>
                                    <th class="px-6 py-4">ID</th>
                                    <th class="px-6 py-4">Vehicle</th>
                                    <th class="px-6 py-4">Owner</th>
                                    <th class="px-6 py-4">Category</th>
                                    <th class="px-6 py-4">Driver</th>
                                    <th class="px-6 py-4">Price</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5">
                                <?php foreach ($vehicles as $vehicle): ?>
                                    <tr class="hover:bg-white/5 transition-colors">
                                        <td class="px-6 py-4 text-xs text-sky-300 font-mono">#<?php echo (int)$vehicle['id']; ?></td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-12 h-10 bg-white/10 rounded-lg overflow-hidden flex-shrink-0 border border-white/20">
                                                    <?php if (!empty($vehicle['cover_image'])): ?>
                                                        <img src="../../<?php echo htmlspecialchars($vehicle['cover_image']); ?>" class="w-full h-full object-cover" alt="Vehicle">
                                                    <?php else: ?>
                                                        <div class="w-full h-full flex items-center justify-center text-white/50">
                                                            <i class="fas fa-car"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <p class="text-sm font-bold text-white"><?php echo htmlspecialchars($vehicle['property_name']); ?></p>
                                                    <?php if (!empty($vehicle['city'])): ?>
                                                        <p class="text-[10px] text-gray-400 mt-0.5"><?php echo htmlspecialchars($vehicle['city']); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <p class="text-sm font-semibold text-gray-300"><?php echo htmlspecialchars($vehicle['first_name'] . ' ' . $vehicle['last_name']); ?></p>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-purple-500/20 text-purple-300 uppercase tracking-wide border border-purple-500/30">
                                                <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $vehicle['vehicle_category'] ?? 'N/A'))); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php
                                            $driver_option = $vehicle['driver_option'] ?? '';
                                            if ($driver_option === 'with_driver') {
                                                echo '<span class="text-[10px] font-bold px-2 py-1 rounded-full bg-blue-500/20 text-blue-300 uppercase border border-blue-500/30">With Driver</span>';
                                            } elseif ($driver_option === 'self_drive') {
                                                echo '<span class="text-[10px] font-bold px-2 py-1 rounded-full bg-emerald-500/20 text-emerald-300 uppercase border border-emerald-500/30">Self Drive</span>';
                                            } elseif ($driver_option === 'both') {
                                                echo '<span class="text-[10px] font-bold px-2 py-1 rounded-full bg-indigo-500/20 text-indigo-300 uppercase border border-indigo-500/30">Both</span>';
                                            } else {
                                                echo '<span class="text-[10px] font-bold px-2 py-1 rounded-full bg-white/10 text-gray-400 uppercase border border-white/20">N/A</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-300 font-semibold">
                                            <?php
                                            $pricing_type = $vehicle['pricing_type'] ?? '';
                                            if ($pricing_type === 'per_km') {
                                                echo 'LKR ' . number_format((float)($vehicle['price_per_km'] ?? 0), 2) . '/km';
                                            } else {
                                                echo 'LKR ' . number_format((float)($vehicle['price_per_day'] ?? 0), 2) . '/day';
                                            }
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="../../property_wizard.php?edit=<?php echo (int)$vehicle['id']; ?>&type=vehicle" class="px-3 py-2 rounded-lg text-[11px] font-bold uppercase tracking-widest bg-white/10 hover:bg-white/20 text-white transition-all">
                                                Manage
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
