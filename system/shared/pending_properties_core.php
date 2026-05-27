<?php
// Included by admin, manager, staff
$stmt = $pdo->query("SELECT p.id, p.property_name, p.business_type, p.city, p.created_at, u.first_name, u.last_name, u.email 
                     FROM properties p 
                     JOIN users u ON p.owner_id = u.id 
                     WHERE p.approval_status = 'pending' 
                     ORDER BY p.created_at DESC");
$pending_properties = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Property Approvals</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0f172a; }
        .glass-header { background: rgba(15, 23, 42, 0.35); backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px); border-bottom: 1px solid rgba(255,255,255,0.1); }
        .glass-card { background: rgba(15, 23, 42, 0.35); backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px); border: 1px solid rgba(255,255,255,0.1); border-radius: 24px; box-shadow: 0 4px 30px rgba(0,0,0,0.1); }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: rgba(255,255,255,0.02); }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
    </style>
</head>
<body class="flex min-h-screen overflow-hidden bg-slate-900">
    <?php include $sidebar_path; ?>
    <main class="flex-1 lg:ml-64 overflow-y-auto h-screen relative z-10 custom-scrollbar">
        <header class="glass-header sticky top-0 z-40 px-4 lg:px-8 py-6 flex items-center gap-4">
            <button onclick="toggleSidebar()" class="lg:hidden w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center text-white hover:bg-white/20 transition-all">
                <i class="fas fa-bars-staggered"></i>
            </button>
            <div class="flex items-center gap-4 flex-1">
                <div class="w-12 h-12 bg-indigo-500/20 text-indigo-400 rounded-xl flex items-center justify-center text-xl shadow-inner border border-indigo-500/30">
                    <i class="fas fa-check-to-slot"></i>
                </div>
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-black text-white">Pending Approvals</h1>
                    <p class="text-xs text-indigo-300 font-bold uppercase tracking-widest hidden sm:block">Review new property registrations</p>
                </div>
            </div>
        </header>

        <div class="p-4 lg:p-8 space-y-6">
            <?php if (empty($pending_properties)): ?>
                <div class="glass-card p-16 text-center">
                    <div class="w-20 h-20 bg-white/10 text-indigo-400 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-check-circle text-3xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-white mb-2">No Pending Properties</h2>
                    <p class="text-indigo-300/70 text-sm">All property registrations have been processed.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                    <?php foreach ($pending_properties as $prop): ?>
                        <div class="glass-card p-6 flex flex-col md:flex-row justify-between gap-6 hover:bg-white/5 transition-all">
                            <div>
                                <h3 class="text-xl font-black text-white mb-1"><?php echo htmlspecialchars($prop['property_name']); ?></h3>
                                <p class="text-xs font-bold text-indigo-300 uppercase tracking-widest mb-4">
                                    <i class="fas fa-map-marker-alt mr-1"></i> <?php echo htmlspecialchars($prop['city'] ?? 'Unknown'); ?> &bull; 
                                    <i class="fas fa-building ml-2 mr-1"></i> <?php echo htmlspecialchars(ucfirst($prop['business_type'] ?? 'Unknown')); ?>
                                </p>
                                <div class="bg-white/5 rounded-xl p-4 border border-white/10">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-2">Owner Details</p>
                                    <p class="text-sm font-bold text-white"><?php echo htmlspecialchars($prop['first_name'] . ' ' . $prop['last_name']); ?></p>
                                    <p class="text-xs text-gray-300"><?php echo htmlspecialchars($prop['email']); ?></p>
                                </div>
                                <p class="text-[10px] text-gray-500 font-bold italic uppercase tracking-tighter mt-4">
                                    Registered on <?php echo date('M d, Y @ H:i', strtotime($prop['created_at'])); ?>
                                </p>
                            </div>
                            <div class="flex flex-col justify-center min-w-[160px]">
                                <a href="property_approval_details.php?id=<?php echo $prop['id']; ?>" class="bg-indigo-600 hover:bg-indigo-500 text-white text-center py-3 px-6 rounded-xl font-bold text-sm uppercase tracking-widest transition-all shadow-lg">
                                    Review Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            if(sidebar) sidebar.classList.toggle('-translate-x-full');
        }
    </script>
</body>
</html>
