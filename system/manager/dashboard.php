<?php
require_once '../../config.php';
require_once '../auth_guard.php';
requireRole(['manager']);

// Fetch unread notifications
$unreadCount = getUnreadNotificationsCount($pdo, $_SESSION['user_id']);

// Fetch recent bookings (last 5)
$stmt = $pdo->query("SELECT b.*, p.property_name FROM bookings b JOIN properties p ON b.property_id = p.id ORDER BY b.created_at DESC LIMIT 5");
$recent_bookings = $stmt->fetchAll();

// Fetch pending approvals (property_requests)
$stmt2 = $pdo->query("SELECT pr.*, p.property_name, u.first_name, u.last_name FROM property_requests pr JOIN properties p ON pr.property_id = p.id JOIN users u ON pr.user_id = u.id WHERE pr.status = 'pending' ORDER BY pr.created_at DESC LIMIT 5");
$pending_approvals = $stmt2->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard - Bookingjaunt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="flex min-h-screen overflow-hidden">
    
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 lg:ml-64 overflow-y-auto h-screen relative z-10">
        
        <header class="glass-header sticky top-0 z-40 px-4 lg:px-8 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden w-10 h-10 rounded-xl flex items-center justify-center text-white hover:bg-white/10 transition-all">
                    <i class="fas fa-bars-staggered"></i>
                </button>
                <div>
                    <h1 class="text-xl font-bold text-white">Dashboard Overview</h1>
                    <p class="text-xs text-sky-300 font-medium hidden lg:block uppercase tracking-widest">Welcome to the Manager Console</p>
                </div>
            </div>
            
            <div class="flex items-center gap-4">
                <button class="w-10 h-10 glass-card rounded-lg flex items-center justify-center text-white hover:text-sky-400 transition-all relative">
                    <i class="far fa-bell"></i>
                    <?php if ($unreadCount > 0): ?>
                        <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full border border-white text-[9px] font-bold flex items-center justify-center"><?php echo $unreadCount; ?></span>
                    <?php endif; ?>
                </button>
            </div>
        </header>

        <div class="p-4 lg:p-8 space-y-8">
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Bookings -->
                <div class="glass-card p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-bold text-white text-lg flex items-center gap-2"><i class="fas fa-calendar-check text-sky-400"></i> Recent Bookings</h3>
                        <a href="bookings.php" class="text-[10px] font-bold text-sky-300 uppercase tracking-widest hover:text-sky-200">View All</a>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3">Guest</th>
                                    <th class="px-4 py-3">Property</th>
                                    <th class="px-4 py-3">Dates</th>
                                    <th class="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($recent_bookings) > 0): foreach($recent_bookings as $booking): ?>
                                <tr>
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-bold text-white"><?php echo htmlspecialchars($booking['guest_name']); ?></p>
                                        <p class="text-[10px] text-gray-400"><?php echo htmlspecialchars($booking['guest_phone']); ?></p>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-300"><?php echo htmlspecialchars($booking['property_name']); ?></td>
                                    <td class="px-4 py-3">
                                        <p class="text-xs text-white"><?php echo date('M d', strtotime($booking['check_in_date'])); ?> - <?php echo date('M d', strtotime($booking['check_out_date'])); ?></p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php
                                        $colors = [
                                            'pending' => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
                                            'confirmed' => 'bg-blue-500/20 text-blue-300 border-blue-500/30',
                                            'checked_in' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
                                            'checked_out' => 'bg-gray-500/20 text-gray-300 border-gray-500/30',
                                            'cancelled' => 'bg-red-500/20 text-red-300 border-red-500/30'
                                        ];
                                        $c = $colors[$booking['status']] ?? 'bg-white/10 text-white';
                                        ?>
                                        <span class="px-2 py-1 text-[10px] font-bold uppercase rounded border <?php echo $c; ?>"><?php echo $booking['status']; ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-400 text-sm">No recent bookings found.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pending Approvals -->
                <div class="glass-card p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-bold text-white text-lg flex items-center gap-2"><i class="fas fa-clipboard-check text-orange-400"></i> Pending Edits</h3>
                        <a href="approvals.php" class="text-[10px] font-bold text-orange-300 uppercase tracking-widest hover:text-orange-200">View All</a>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3">Property</th>
                                    <th class="px-4 py-3">Requested By</th>
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3">Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($pending_approvals) > 0): foreach($pending_approvals as $req): ?>
                                <tr>
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-bold text-white"><?php echo htmlspecialchars($req['property_name']); ?></p>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-300"><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></td>
                                    <td class="px-4 py-3 text-xs text-gray-400"><?php echo date('M d, Y', strtotime($req['created_at'])); ?></td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-[10px] font-bold uppercase rounded border bg-purple-500/20 text-purple-300 border-purple-500/30"><?php echo $req['request_type']; ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-400 text-sm">No pending edit requests.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>
</body>
</html>
