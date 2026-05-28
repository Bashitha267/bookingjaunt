<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside id="sidebar" class="w-64 text-white/90 flex flex-col fixed h-full z-50 transition-transform duration-300 -translate-x-full lg:translate-x-0 glass-sidebar">
    <div class="p-6 flex justify-between items-center lg:block">
        <a href="../../index.php" class="flex items-center gap-2 no-underline">
            <span class="text-white text-2xl font-black tracking-tighter">
                Booking<span class="text-[#0ea5e9]">Jaunt</span>
            </span>
        </a>
        <button onclick="toggleSidebar()" class="lg:hidden text-white/60 hover:text-white transition-colors">
            <i class="fas fa-times text-xl"></i>
        </button>
        <p class="text-[10px] font-bold text-sky-300 mt-1 uppercase tracking-widest opacity-80 lg:block hidden">Manager Console</p>
    </div>

    <nav class="flex-1 mt-4 px-3 space-y-1 overflow-y-auto custom-scrollbar">
        <a href="dashboard.php" class="sidebar-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group">
            <i class="fas fa-th-large w-5 text-center text-sky-400 group-hover:text-[#0ea5e9]"></i>
            Dashboard
        </a>
        <a href="approvals.php" class="sidebar-link <?php echo $current_page == 'approvals.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group">
            <i class="fas fa-clipboard-check w-5 text-center text-sky-400 group-hover:text-[#0ea5e9]"></i>
            Edit Approvals
        </a>
        <a href="pending_properties.php" class="sidebar-link <?php echo $current_page == 'pending_properties.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group">
            <i class="fas fa-check-to-slot w-5 text-center text-sky-400 group-hover:text-[#0ea5e9]"></i>
            New Approvals
        </a>
        <a href="bookings.php" class="sidebar-link <?php echo $current_page == 'bookings.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group">
            <i class="fas fa-calendar-check w-5 text-center text-sky-400 group-hover:text-[#0ea5e9]"></i>
            Bookings
        </a>
        <a href="properties.php" class="sidebar-link <?php echo in_array($current_page, ['properties.php', 'property_details.php']) ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group">
            <i class="fas fa-hotel w-5 text-center text-sky-400 group-hover:text-[#0ea5e9]"></i>
            Properties
        </a>
        <a href="vehicles.php" class="sidebar-link <?php echo $current_page == 'vehicles.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group">
            <i class="fas fa-car w-5 text-center text-sky-400 group-hover:text-[#0ea5e9]"></i>
            Vehicles
        </a>
        <a href="users.php" class="sidebar-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group">
            <i class="fas fa-users w-5 text-center text-sky-400 group-hover:text-[#0ea5e9]"></i>
            User Details
        </a>
        <a href="manage_staff.php" class="sidebar-link <?php echo $current_page == 'manage_staff.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-emerald-300">
            <i class="fas fa-sitemap w-5 text-center text-emerald-400 group-hover:text-emerald-300"></i>
            Manage Staff
        </a>
        <a href="boosts.php" class="sidebar-link <?php echo $current_page == 'boosts.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group">
            <i class="fas fa-rocket w-5 text-center text-sky-400 group-hover:text-[#0ea5e9]"></i>
            Boosts
        </a>
        <a href="advertisements.php" class="sidebar-link <?php echo $current_page == 'advertisements.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group">
            <i class="fas fa-bullhorn w-5 text-center text-sky-400 group-hover:text-[#0ea5e9]"></i>
            Advertisements
        </a>
        <a href="amenities.php" class="sidebar-link <?php echo $current_page == 'amenities.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group">
            <i class="fas fa-list-ul w-5 text-center text-sky-400 group-hover:text-[#0ea5e9]"></i>
            Amenities
        </a>

        <div class="pt-10 pb-4">
            <p class="px-4 text-[10px] font-bold text-sky-300 uppercase tracking-widest opacity-60">System</p>
        </div>
        
        <a href="settings.php" class="sidebar-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group">
            <i class="fas fa-cog w-5 text-center text-sky-400 group-hover:text-[#0ea5e9]"></i>
            Settings
        </a>
        <a href="../../logout.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-red-500/20 hover:text-red-300 group">
            <i class="fas fa-sign-out-alt w-5 text-center opacity-80 group-hover:opacity-100 text-red-400"></i>
            Logout
        </a>
    </nav>

    <div class="p-4 bg-white/5 m-4 rounded-2xl border border-white/10 lg:block hidden backdrop-blur-md">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-[#0ea5e9] rounded-xl flex items-center justify-center font-bold text-white shadow-lg shadow-sky-900/30">
                <?php echo substr($_SESSION['user_name'] ?? 'M', 0, 1); ?>
            </div>
            <div>
                <p class="text-xs font-bold text-white truncate w-32"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Manager'); ?></p>
                <p class="text-[9px] text-sky-300 uppercase tracking-wider font-bold">Manager</p>
            </div>
        </div>
    </div>
</aside>

<!-- Sidebar Overlay -->
<div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-40 hidden lg:hidden transition-opacity duration-300"></div>

<?php
$bg_url = '../../uploads/admin_bg/default.png';
if (isset($pdo)) {
    try {
        // First try to get user's specific background
        if (isset($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("SELECT manager_bg FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user_bg = $stmt->fetchColumn();
            if (!empty($user_bg)) {
                $bg_url = '../../' . $user_bg;
            } else {
                // Fallback to admin default
                $stmt = $pdo->prepare("SELECT setting_value FROM admin_settings WHERE setting_key = 'background_path'");
                $stmt->execute();
                $db_bg = $stmt->fetchColumn();
                if (!empty($db_bg)) {
                    $bg_url = '../../' . $db_bg;
                }
            }
        }
    } catch (PDOException $e) {}
}
?>

<style>
    /* Extreme Glassmorphism Styles for Manager/Staff */
    body {
        background: linear-gradient(rgba(15, 23, 42, 0.45), rgba(15, 23, 42, 0.45)), url('<?php echo $bg_url; ?>') no-repeat center center fixed !important;
        background-size: cover !important;
        background-attachment: fixed !important;
        color: #f8fafc !important;
        font-family: 'Plus Jakarta Sans', sans-serif;
    }

    /* Style native select options to match the dark glassmorphism theme */
    select option {
        background-color: #0f172a !important;
        color: #ffffff !important;
    }

    main {
        background: transparent !important;
    }

    .glass-sidebar {
        background: rgba(15, 23, 42, 0.35) !important;
        backdrop-filter: blur(25px) saturate(180%) !important;
        -webkit-backdrop-filter: blur(25px) saturate(180%) !important;
        border-right: 1px solid rgba(255, 255, 255, 0.15) !important;
    }

    .sidebar-link.active {
        background-color: rgba(255, 255, 255, 0.15);
        border-left: 4px solid #0ea5e9;
        color: white;
        border-radius: 0 12px 12px 0;
        margin-left: -12px;
        padding-left: 24px;
        box-shadow: inset 0 0 20px rgba(255,255,255,0.05);
    }
    
    header.glass-header {
        background: rgba(15, 23, 42, 0.25) !important;
        backdrop-filter: blur(20px) saturate(150%) !important;
        -webkit-backdrop-filter: blur(20px) saturate(150%) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    }

    .glass-card {
        background: rgba(255, 255, 255, 0.10) !important;
        backdrop-filter: blur(20px) saturate(200%) !important;
        -webkit-backdrop-filter: blur(20px) saturate(200%) !important;
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.3) !important;
        border-radius: 1.5rem !important;
        color: #f8fafc !important;
    }
    
    .glass-card:hover {
        background: rgba(255, 255, 255, 0.13) !important;
        border-color: rgba(255,255,255,0.3) !important;
    }

    .custom-input {
        background: rgba(0, 0, 0, 0.2) !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        color: white !important;
        border-radius: 0.75rem;
    }
    
    .custom-input:focus {
        background: rgba(0, 0, 0, 0.3) !important;
        border-color: #0ea5e9 !important;
        outline: none;
        box-shadow: 0 0 0 2px rgba(14, 165, 233, 0.2);
    }

    .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 10px; }
    
    /* Table styles for glass mode */
    table thead { background: rgba(255,255,255,0.05); }
    table th { color: rgba(255,255,255,0.7) !important; text-transform: uppercase; font-size: 0.7rem; font-weight: 800; letter-spacing: 0.05em; }
    table tr { border-bottom: 1px solid rgba(255,255,255,0.05); }
    table tr:hover { background: rgba(255,255,255,0.05); }
    
    #sidebar.show { transform: translateX(0); }
</style>
<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        
        if (sidebar.classList.contains('-translate-x-full')) {
            sidebar.classList.remove('-translate-x-full');
            sidebar.classList.add('show');
            overlay.classList.remove('hidden');
        } else {
            sidebar.classList.add('-translate-x-full');
            sidebar.classList.remove('show');
            overlay.classList.add('hidden');
        }
    }
</script>
