<?php
require_once '../../config.php';
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // For demo purposes, we allow viewing if no session but in production this should be active
    // header("Location: ../../login.php"); exit();
}

// Fetch Stats
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_properties = $pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();
$total_rooms = $pdo->query("SELECT COUNT(*) FROM property_rooms")->fetchColumn();
$total_amenities = $pdo->query("SELECT COUNT(*) FROM amenities_master")->fetchColumn();

// Fetch Recent Properties
$stmt = $pdo->query("SELECT p.*, u.first_name, u.last_name FROM properties p JOIN users u ON p.owner_id = u.id ORDER BY p.created_at DESC LIMIT 5");
$recent_properties = $stmt->fetchAll();

// Fetch Recent Users
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
$recent_users = $stmt->fetchAll();

// Group properties by type for chart
$type_counts = $pdo->query("SELECT business_type, COUNT(*) as count FROM properties GROUP BY business_type")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bookingjaunt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
        }

        .sidebar-link.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 4px solid #febb02;
            color: white;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stat-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #003580;
            border-radius: 10px;
        }
    </style>
</head>

<body class="flex min-h-screen overflow-hidden">

    <!-- Sidebar -->
    <aside class="w-64 bg-[#003580] text-white/80 flex flex-col fixed h-full z-50">
        <div class="p-6">
            <a href="../../index.php" class="flex items-center gap-2 no-underline">
                <span class="text-white text-2xl font-black tracking-tighter">
                    Booking<span class="text-[#febb02]">Jaunt</span>
                </span>
            </a>
            <p class="text-[10px] font-bold text-blue-300 mt-1 uppercase tracking-widest opacity-60">Admin Console</p>
        </div>

        <nav class="flex-1 mt-4 px-3 space-y-1 overflow-y-auto custom-scrollbar">
            <a href="#" class="sidebar-link active flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold transition-all hover:bg-white/10 hover:text-white group">
                <i class="fas fa-th-large w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
                Dashboard
            </a>
            <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
                <i class="fas fa-hotel w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
                Properties
            </a>
            <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
                <i class="fas fa-users w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
                User Management
            </a>
            <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
                <i class="fas fa-concierge-bell w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
                Bookings
            </a>
            <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
                <i class="fas fa-wallet w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
                Payouts
            </a>
            <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
                <i class="fas fa-list-ul w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
                Amenities Master
            </a>

            <div class="pt-10 pb-4">
                <p class="px-4 text-[10px] font-bold text-blue-300 uppercase tracking-widest opacity-40">System</p>
            </div>
            
            <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
                <i class="fas fa-cog w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
                Settings
            </a>
            <a href="../../logout.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold transition-all hover:bg-red-500/10 hover:text-red-400 group text-white/60">
                <i class="fas fa-sign-out-alt w-5 text-center opacity-60 group-hover:opacity-100"></i>
                Logout
            </a>
        </nav>

        <div class="p-4 bg-black/20 m-4 rounded-xl border border-white/5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-[#006ce4] rounded-lg flex items-center justify-center font-bold text-white shadow-lg">
                    <?php echo strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)); ?>
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs font-bold text-white truncate"><?php echo $_SESSION['user_name'] ?? 'Admin User'; ?></p>
                    <p class="text-[9px] text-blue-300 uppercase tracking-wider font-bold">Main Admin</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 ml-64 overflow-y-auto h-screen bg-[#f8fafc]">
        
        <!-- Top Nav -->
        <header class="bg-white/80 backdrop-blur-md border-b border-gray-200 sticky top-0 z-40 px-8 py-4 flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold text-[#003580]">Overview</h1>
                <p class="text-xs text-gray-500 font-medium">Welcome back, here's what's happening today.</p>
            </div>
            
            <div class="flex items-center gap-4">
                <div class="relative">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input type="text" placeholder="Search anything..." class="pl-9 pr-4 py-2 bg-gray-100 border-none rounded-lg text-xs w-64 focus:ring-2 focus:ring-[#006ce4] outline-none transition-all">
                </div>
                <button class="w-10 h-10 bg-white border border-gray-200 rounded-lg flex items-center justify-center text-gray-500 hover:text-[#006ce4] hover:border-[#006ce4] transition-all relative">
                    <i class="far fa-bell"></i>
                    <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
                </button>
            </div>
        </header>

        <div class="p-8">
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Stat Card 1 -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 stat-card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-12 h-12 bg-blue-50 text-[#006ce4] rounded-xl flex items-center justify-center text-xl">
                            <i class="fas fa-users"></i>
                        </div>
                        <span class="text-[10px] font-bold text-green-500 bg-green-50 px-2 py-1 rounded-full">+12%</span>
                    </div>
                    <p class="text-gray-500 text-xs font-bold uppercase tracking-widest mb-1">Total Users</p>
                    <h3 class="text-2xl font-black text-[#003580]"><?php echo number_format($total_users); ?></h3>
                </div>

                <!-- Stat Card 2 -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 stat-card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-12 h-12 bg-orange-50 text-orange-500 rounded-xl flex items-center justify-center text-xl">
                            <i class="fas fa-hotel"></i>
                        </div>
                        <span class="text-[10px] font-bold text-green-500 bg-green-50 px-2 py-1 rounded-full">+5%</span>
                    </div>
                    <p class="text-gray-500 text-xs font-bold uppercase tracking-widest mb-1">Active Properties</p>
                    <h3 class="text-2xl font-black text-[#003580]"><?php echo number_format($total_properties); ?></h3>
                </div>

                <!-- Stat Card 3 -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 stat-card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center text-xl">
                            <i class="fas fa-door-open"></i>
                        </div>
                        <span class="text-[10px] font-bold text-red-500 bg-red-50 px-2 py-1 rounded-full">-2%</span>
                    </div>
                    <p class="text-gray-500 text-xs font-bold uppercase tracking-widest mb-1">Total Rooms</p>
                    <h3 class="text-2xl font-black text-[#003580]"><?php echo number_format($total_rooms); ?></h3>
                </div>

                <!-- Stat Card 4 -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 stat-card">
                    <div class="flex justify-between items-start mb-4">
                        <div class="w-12 h-12 bg-green-50 text-green-600 rounded-xl flex items-center justify-center text-xl">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <span class="text-[10px] font-bold text-green-500 bg-green-50 px-2 py-1 rounded-full">+18%</span>
                    </div>
                    <p class="text-gray-500 text-xs font-bold uppercase tracking-widest mb-1">Amenities</p>
                    <h3 class="text-2xl font-black text-[#003580]"><?php echo number_format($total_amenities); ?></h3>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Recent Properties -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="font-bold text-[#003580]">Recent Properties</h3>
                        <a href="#" class="text-[10px] font-bold text-[#006ce4] uppercase tracking-widest hover:underline">View All</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                                <tr>
                                    <th class="px-6 py-4">Property</th>
                                    <th class="px-6 py-4">Owner</th>
                                    <th class="px-6 py-4">Type</th>
                                    <th class="px-6 py-4">Created</th>
                                    <th class="px-6 py-4 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php foreach($recent_properties as $prop): ?>
                                <tr class="hover:bg-gray-50 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                                <img src="../../<?php echo $prop['cover_image'] ?: 'assets/placeholder.png'; ?>" class="w-full h-full object-cover">
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($prop['property_name']); ?></p>
                                                <p class="text-[10px] text-gray-500"><?php echo htmlspecialchars($prop['city']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-xs font-medium text-gray-600">
                                        <?php echo htmlspecialchars($prop['first_name'] . ' ' . $prop['last_name']); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-[10px] font-bold px-2 py-1 rounded-full bg-blue-50 text-blue-600 uppercase">
                                            <?php echo str_replace('_', ' ', $prop['business_type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-xs text-gray-500">
                                        <?php echo date('M d, Y', strtotime($prop['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button class="text-gray-400 hover:text-[#006ce4] transition-colors"><i class="fas fa-ellipsis-v"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Users -->
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="font-bold text-[#003580]">Latest Users</h3>
                        <i class="fas fa-user-plus text-gray-300"></i>
                    </div>
                    <div class="p-4 space-y-4">
                        <?php foreach($recent_users as $user): ?>
                        <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded-xl transition-colors cursor-pointer group">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-[#003580]/5 text-[#003580] rounded-full flex items-center justify-center font-bold text-xs border border-[#003580]/10">
                                    <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                                </div>
                                <div class="overflow-hidden">
                                    <p class="text-xs font-bold text-gray-800 truncate"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                                    <p class="text-[10px] text-gray-400 truncate"><?php echo htmlspecialchars($user['email']); ?></p>
                                </div>
                            </div>
                            <span class="text-[9px] font-bold text-gray-400 bg-gray-100 px-2 py-1 rounded-full uppercase group-hover:bg-[#006ce4] group-hover:text-white transition-all"><?php echo $user['role']; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="p-4 bg-gray-50 mt-2">
                        <button class="w-full py-2 text-[10px] font-bold text-[#006ce4] uppercase tracking-widest hover:bg-white rounded-lg border border-transparent hover:border-blue-100 transition-all">Manage All Users</button>
                    </div>
                </div>
            </div>

            <!-- Additional Stats / Chart Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
                <div class="bg-white p-8 rounded-2xl border border-gray-100 shadow-sm">
                    <h3 class="font-bold text-[#003580] mb-6">Property Distribution</h3>
                    <div class="space-y-4">
                        <?php
                        $colors = ['hotel' => 'bg-blue-500', 'reception_hall' => 'bg-orange-500', 'hostel' => 'bg-purple-500', 'rest_hall' => 'bg-green-500'];
                        foreach($type_counts as $type => $count):
                            $percentage = ($total_properties > 0) ? ($count / $total_properties) * 100 : 0;
                        ?>
                        <div>
                            <div class="flex justify-between text-[11px] font-bold mb-1.5 uppercase tracking-wide text-gray-600">
                                <span><?php echo str_replace('_', ' ', $type); ?></span>
                                <span><?php echo $count; ?> (<?php echo round($percentage); ?>%)</span>
                            </div>
                            <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="<?php echo $colors[$type] ?? 'bg-gray-400'; ?> h-full" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="bg-gradient-to-br from-[#003580] to-[#006ce4] p-8 rounded-3xl text-white relative overflow-hidden shadow-xl shadow-blue-900/20">
                    <div class="relative z-10">
                        <p class="text-blue-200 text-xs font-bold uppercase tracking-[0.2em] mb-2">Platform Performance</p>
                        <h2 class="text-3xl font-black mb-6">Growth Analysis</h2>
                        <p class="text-blue-100 text-sm leading-relaxed mb-8 opacity-80">Your platform has seen a <span class="text-white font-bold">24% increase</span> in property listings over the last 30 days. Payout requests are processed within an average of <span class="text-white font-bold">1.2 days</span>.</p>
                        <div class="flex gap-4">
                            <button class="bg-[#febb02] text-[#003580] px-6 py-2.5 rounded-xl font-bold text-xs uppercase tracking-widest shadow-lg shadow-yellow-600/20 hover:scale-105 active:scale-95 transition-all">Download Report</button>
                            <button class="bg-white/10 hover:bg-white/20 text-white px-6 py-2.5 rounded-xl font-bold text-xs uppercase tracking-widest transition-all">Analytics</button>
                        </div>
                    </div>
                    <!-- Decorative shapes -->
                    <div class="absolute -right-20 -bottom-20 w-64 h-64 bg-white/5 rounded-full"></div>
                    <div class="absolute -right-10 top-0 w-32 h-32 bg-white/5 rounded-full"></div>
                </div>
            </div>
        </div>
    </main>

</body>

</html>
