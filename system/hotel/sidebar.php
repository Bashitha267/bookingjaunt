<?php
$current_page = basename($_SERVER['PHP_SELF']);
$view = $_GET['view'] ?? 'dashboard';
?>
<aside class="w-64 bg-[#003580] text-white/80 flex flex-col fixed h-full z-50">
    <div class="p-6">
        <a href="../../index.php" class="flex items-center gap-2 no-underline">
            <span class="text-white text-2xl font-black tracking-tighter">
                Booking<span class="text-[#febb02]">Jaunt</span>
            </span>
        </a>
        <p class="text-[10px] font-bold text-blue-300 mt-1 uppercase tracking-widest opacity-60">Manager Console</p>
    </div>

    <nav class="flex-1 mt-4 px-3 space-y-1 overflow-y-auto custom-scrollbar">
        <a href="dashboard.php?view=dashboard" class="sidebar-link <?php echo ($current_page == 'dashboard.php' && $view === 'dashboard') ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold transition-all hover:bg-white/10 hover:text-white group">
            <i class="fas fa-th-large w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
            Dashboard
        </a>
        <a href="dashboard.php?view=bookings" class="sidebar-link <?php echo ($current_page == 'dashboard.php' && $view === 'bookings') ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
            <i class="fas fa-calendar-check w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
            Bookings
        </a>
        <a href="dashboard.php?view=payments" class="sidebar-link <?php echo ($current_page == 'dashboard.php' && $view === 'payments') ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
            <i class="fas fa-credit-card w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
            Payments
        </a>
        <a href="my_properties.php" class="sidebar-link <?php echo ($current_page == 'my_properties.php') ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold transition-all hover:bg-white/10 hover:text-white group text-white/60">
            <i class="fas fa-hotel w-5 text-center text-blue-400 group-hover:text-[#febb02]"></i>
            My Properties
        </a>

        <div class="pt-10 pb-4">
            <p class="px-4 text-[10px] font-bold text-blue-300 uppercase tracking-widest opacity-40">Account</p>
        </div>
        
        <a href="../../logout.php" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold transition-all hover:bg-red-500/10 hover:text-red-400 group text-white/60">
            <i class="fas fa-sign-out-alt w-5 text-center opacity-60 group-hover:opacity-100"></i>
            Logout
        </a>
    </nav>

    <div class="p-4 bg-black/20 m-4 rounded-xl border border-white/5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-[#006ce4] rounded-lg flex items-center justify-center font-bold text-white shadow-lg overflow-hidden flex-shrink-0">
                <?php if (isset($property['logo_image']) && $property['logo_image']): ?>
                    <img src="../../<?php echo $property['logo_image']; ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <?php echo strtoupper(substr($property['property_name'] ?? 'P', 0, 1)); ?>
                <?php endif; ?>
            </div>
            <div class="overflow-hidden">
                <p class="text-xs font-bold text-white truncate"><?php echo htmlspecialchars($property['property_name'] ?? 'My Property'); ?></p>
                <p class="text-[9px] text-blue-300 uppercase tracking-wider font-bold">Owner</p>
            </div>
        </div>
    </div>
</aside>
