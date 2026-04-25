<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
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
        <a href="users.php" class="sidebar-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
            <i class="fas fa-users w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
            User Management
        </a>
        <a href="bookings.php" class="sidebar-link <?php echo $current_page == 'bookings.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
            <i class="fas fa-calendar-check w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
            Bookings
        </a>
        <a href="payouts.php" class="sidebar-link <?php echo $current_page == 'payouts.php' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
            <i class="fas fa-wallet w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
            Payouts
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

    <div class="p-4 bg-black/20 m-4 rounded-2xl border border-white/5">
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
</style>
