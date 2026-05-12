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
        <a href="bookings.php" class="sidebar-link <?php echo $current_page == 'bookings.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
            <i class="fas fa-calendar-check w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
            Bookings
        </a>
        <a href="feedbacks.php" class="sidebar-link <?php echo $current_page == 'feedbacks.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
            <i class="fas fa-comment-dots w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
            Feedbacks
        </a>
        <a href="reports.php" class="sidebar-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
            <i class="fas fa-chart-line w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
            Reports
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

