<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside id="sidebar" class="w-64 bg-[#003580] text-white/80 flex flex-col fixed h-full z-50 transition-transform duration-300 -translate-x-full lg:translate-x-0">
    <div class="p-6 flex justify-between items-center lg:block">
        <a href="../../index.php" class="flex items-center gap-2 no-underline">
            <span class="text-white text-2xl font-black tracking-tighter">
                Booking<span class="text-[#febb02]">Jaunt</span>
            </span>
        </a>
        <button onclick="toggleSidebar()" class="lg:hidden text-white/60 hover:text-white transition-colors">
            <i class="fas fa-times text-xl"></i>
        </button>
        <p class="text-[10px] font-bold text-blue-300 mt-1 uppercase tracking-widest opacity-60 lg:block hidden">Admin Console</p>
    </div>

    <nav class="flex-1 mt-4 px-3 space-y-1 overflow-y-auto custom-scrollbar">
        <a href="dashboard.php" class="sidebar-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group">
            <i class="fas fa-th-large w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
            Dashboard
        </a>
        <a href="actions.php" class="sidebar-link <?php echo $current_page == 'actions.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group">
            <i class="fas fa-clipboard-check w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
            Approvals
        </a>
        <a href="properties.php" class="sidebar-link <?php echo $current_page == 'properties.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
            <i class="fas fa-hotel w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
            Properties
        </a>
        <a href="boosts.php" class="sidebar-link <?php echo $current_page == 'boosts.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
            <i class="fas fa-rocket w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
            Manage Boosts
        </a>
        <a href="users.php" class="sidebar-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
            <i class="fas fa-users w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
            User Management
        </a>
        <a href="team.php" class="sidebar-link <?php echo $current_page == 'team.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
            <i class="fas fa-sitemap w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
            Team Management
        </a>
        <a href="bookings.php" class="sidebar-link <?php echo $current_page == 'bookings.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
            <i class="fas fa-calendar-check w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
            Bookings
        </a>
        <a href="vehicles.php" class="sidebar-link <?php echo $current_page == 'vehicles.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
            <i class="fas fa-car w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
            Vehicles
        </a>
        <a href="vehicle_bookings.php" class="sidebar-link <?php echo $current_page == 'vehicle_bookings.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
            <i class="fas fa-car-side w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
            Vehicle Bookings
        </a>
        <a href="feedbacks.php" class="sidebar-link <?php echo $current_page == 'feedbacks.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
            <i class="fas fa-comment-dots w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
            Feedbacks
        </a>
        <a href="reports.php" class="sidebar-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
            <i class="fas fa-chart-line w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
            Reports
        </a>
        <a href="monthly_report.php" class="sidebar-link <?php echo $current_page == 'monthly_report.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
            <i class="fas fa-calendar-alt w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
            Monthly Reports
        </a>
        <a href="payouts.php" class="sidebar-link <?php echo $current_page == 'payouts.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
            <i class="fas fa-wallet w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
            Payouts
        </a>
        <a href="manage_add.php" class="sidebar-link <?php echo $current_page == 'manage_add.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
            <i class="fas fa-bullhorn w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
            Manage Advertisements
        </a>
        <a href="amenities.php" class="sidebar-link <?php echo $current_page == 'amenities.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
            <i class="fas fa-list-ul w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
            Amenities Master
        </a>

        <div class="pt-10 pb-4">
            <p class="px-4 text-[10px] font-bold text-blue-300 uppercase tracking-widest opacity-40">System</p>
        </div>
        
        <a href="settings.php" class="sidebar-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
            <i class="fas fa-cog w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
            Settings
        </a>
        <a href="../../logout.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-red-500/10 hover:text-red-400 group text-white/60">
            <i class="fas fa-sign-out-alt w-5 text-center opacity-60 group-hover:opacity-100"></i>
            Logout
        </a>
    </nav>

    <div class="p-4 bg-black/20 m-4 rounded-2xl border border-white/5 lg:block hidden">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-[#006ce4] rounded-xl flex items-center justify-center font-bold text-white shadow-lg shadow-blue-900/20">
                S
            </div>
            <div>
                <p class="text-xs font-bold text-white">System Admin</p>
                <p class="text-[9px] text-blue-300 uppercase tracking-wider font-bold">Main Admin</p>
            </div>
        </div>
    </div>
</aside>

<!-- Sidebar Overlay -->
<div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-40 hidden lg:hidden transition-opacity duration-300"></div>

<style>
    .sidebar-link.active {
        background-color: rgba(255, 255, 255, 0.1);
        border-left: 4px solid #febb02;
        color: white;
        border-radius: 0 12px 12px 0;
        margin-left: -12px;
        padding-left: 24px;
    }
    
    .custom-scrollbar::-webkit-scrollbar {
        width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }

    #sidebar.show {
        transform: translateX(0);
    }
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

<?php
$bg_url = '../../uploads/admin_bg/default.png';
if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM admin_settings WHERE setting_key = 'background_path'");
        $stmt->execute();
        $db_bg = $stmt->fetchColumn();
        if (!empty($db_bg)) {
            $bg_url = '../../' . $db_bg;
        }
    } catch (PDOException $e) {
        // Fallback silently if table does not exist yet
    }
}
?>
<style>
    /* Glassmorphism Global Styles */
    body {
        background: linear-gradient(rgba(15, 23, 42, 0.55), rgba(15, 23, 42, 0.55)), url('<?php echo $bg_url; ?>') no-repeat center center fixed !important;
        background-size: cover !important;
        background-attachment: fixed !important;
        color: #f8fafc !important;
    }

    /* Force transparency on the main content container */
    main {
        background-color: transparent !important;
        background: transparent !important;
    }

    /* Glassmorphism for Headers */
    header {
        background: rgba(15, 23, 42, 0.3) !important;
        backdrop-filter: blur(2px) saturate(120%) !important;
        -webkit-backdrop-filter: blur(2px) saturate(120%) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
    }

    header h1, header p, header span, header i {
        color: #ffffff !important;
    }

    /* Glassmorphism for Cards and main White containers */
    main .bg-white,
    main div.bg-white,
    .glass-card {
        background: rgba(255, 255, 255, 0.08) !important;
        backdrop-filter: blur(2px) saturate(180%) !important;
        -webkit-backdrop-filter: blur(2px) saturate(180%) !important;
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
        box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.25) !important;
        border-radius: 1.5rem !important;
        color: #f8fafc !important;
    }

    /* Ensure text readability inside cards */
    main .bg-white h2, 
    main .bg-white h3, 
    main .bg-white p,
    main .bg-white label,
    main .bg-white td,
    main .bg-white th {
        color: #ffffff !important;
    }

    /* Keep badge text and status texts intact */
    main .bg-white span.bg-blue-50, 
    main .bg-white span.bg-green-50, 
    main .bg-white span.bg-emerald-50,
    main .bg-white span.bg-red-50,
    main .bg-white span.bg-orange-50,
    main .bg-white span.bg-purple-50,
    main .bg-white span.bg-indigo-50,
    main .bg-white span.bg-amber-50,
    main .bg-white span.bg-emerald-100,
    main .bg-white span.bg-red-100 {
        background: rgba(255, 255, 255, 0.15) !important;
        backdrop-filter: blur(1px) !important;
    }

    /* Specific Badge Colors */
    .bg-blue-50 {
        background: rgba(59, 130, 246, 0.25) !important;
        color: #93c5fd !important;
    }
    .bg-green-50, .bg-emerald-50, .bg-emerald-100 {
        background: rgba(16, 185, 129, 0.25) !important;
        color: #a7f3d0 !important;
    }
    .bg-red-50, .bg-red-100 {
        background: rgba(239, 68, 68, 0.25) !important;
        color: #fca5a5 !important;
    }
    .bg-orange-50, .bg-amber-50 {
        background: rgba(245, 158, 11, 0.25) !important;
        color: #fde047 !important;
    }
    .bg-purple-50 {
        background: rgba(139, 92, 246, 0.25) !important;
        color: #ddd6fe !important;
    }
    .bg-indigo-50 {
        background: rgba(99, 102, 241, 0.25) !important;
        color: #c7d2fe !important;
    }

    /* Make all Tailwind text-gray-X, text-blue-300, etc. white-themed for clear readability */
    .text-gray-300, .text-gray-400, .text-gray-500, .text-gray-600, .text-gray-700, .text-gray-800, .text-gray-900, .text-blue-300 {
        color: rgba(255, 255, 255, 0.95) !important;
    }

    #sidebar a:not(.active) {
        color: rgba(255, 255, 255, 0.7) !important;
    }


    /* Tables Styling */
    main table {
        background: transparent !important;
    }
    
    main thead, main thead tr, main thead th {
        background: rgba(255, 255, 255, 0.05) !important;
        color: rgba(255, 255, 255, 0.85) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
    }

    main tbody tr {
        border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important;
    }

    main tbody tr:hover {
        background: rgba(255, 255, 255, 0.04) !important;
    }

    /* Form Fields and Inputs */
    main input[type="text"], 
    main input[type="file"],
    main input[type="number"],
    main input[type="email"],
    main input[type="password"],
    main textarea, 
    main select {
        background: rgba(255, 255, 255, 0.07) !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        color: #ffffff !important;
        backdrop-filter: blur(1px) !important;
        border-radius: 0.75rem !important;
    }

    main input::placeholder, main textarea::placeholder {
        color: rgba(255, 255, 255, 0.4) !important;
    }

    main input:focus, main textarea:focus, main select:focus {
        background: rgba(255, 255, 255, 0.14) !important;
        border-color: rgba(255, 255, 255, 0.3) !important;
        box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.1) !important;
        outline: none !important;
    }

    /* Neutral grey background buttons, lists, search reset buttons */
    main .bg-gray-50, 
    main .bg-gray-100 {
        background: rgba(255, 255, 255, 0.12) !important;
        color: #ffffff !important;
    }
    
    main .bg-gray-50:hover, 
    main .bg-gray-100:hover {
        background: rgba(255, 255, 255, 0.22) !important;
    }

    /* Border line transparency adjustments */
    main .border-gray-100,
    main .border-gray-200,
    main .divide-y {
        border-color: rgba(255, 255, 255, 0.08) !important;
    }

    /* Sidebar Glassmorphism */
    #sidebar {
        background: rgba(15, 23, 42, 0.6) !important;
        backdrop-filter: blur(2px) !important;
        -webkit-backdrop-filter: blur(2px) !important;
        border-right: 1px solid rgba(255, 255, 255, 0.1) !important;
    }

    /* Fix visual details on scrollbar */
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2) !important;
    }
</style>


