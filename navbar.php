<!-- Header Wrapper -->
<header class="bg-primary sticky top-0 z-50 shadow-md">
    <nav class="max-w-[1400px] mx-auto px-4 xl:px-6 flex flex-col xl:flex-row xl:items-center justify-between gap-y-4 xl:gap-4 py-3 xl:py-0 xl:h-16">
        
        <!-- Mobile Top Row / Desktop Left -->
        <div class="flex items-center justify-between w-full xl:w-auto">
            <!-- 1. Logo -->
            <a href="index.php" class="flex-shrink-0 group">
                <img src="assets/logo.png" alt="Bookingjaunt" class="h-8 xl:h-10 w-auto transition-transform group-hover:scale-105 bg-white rounded p-1 shadow-sm xl:p-0 xl:bg-transparent xl:shadow-none">
            </a>

            <!-- Mobile Action Icons (User & Hamburger) - Hidden on desktop -->
            <div class="flex items-center gap-5 xl:hidden">
                <button class="text-white text-[24px] hover:text-white/80 transition-colors leading-none relative">
                    <i class="far fa-user-circle"></i>
                    <!-- Optional Notification Dot -->
                    <div class="absolute top-0 right-0 w-2.5 h-2.5 bg-red-500 rounded-full border-[2px] border-primary"></div>
                </button>
                <button class="text-white text-[20px] hover:text-white/80 transition-colors leading-none">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>

        <!-- 2. Navbar Items (Stays, Safari, etc.) -->
        <div class="flex items-center justify-start xl:justify-center gap-1.5 xl:gap-1 flex-shrink-0 xl:flex-1 overflow-x-auto no-scrollbar w-full xl:w-auto pb-1 xl:pb-0">
            <?php 
            $current_type = isset($_GET['type']) ? $_GET['type'] : 'hotel';
            $nav_items = [
                ['id' => 'hotel', 'label' => 'Hotels', 'icon' => 'fa-hotel'],
                ['id' => 'reception_hall', 'label' => 'Reception Halls', 'icon' => 'fa-glass-cheers'],
                ['id' => 'hostel', 'label' => 'Hostels', 'icon' => 'fa-bed'],
                ['id' => 'rest_hall', 'label' => 'Rest Halls', 'icon' => 'fa-building'],
                ['id' => 'dayouts', 'label' => 'Dayouts', 'icon' => 'fa-sun'],
                ['id' => 'safari', 'label' => 'Safari', 'icon' => 'fa-hippo'],
                ['id' => 'about_contact', 'label' => 'About & Contact', 'icon' => 'fa-address-card'],
            ];
            
            foreach ($nav_items as $item):
                $active = ($current_type == $item['id']);
            ?>
                <a href="index.php?type=<?php echo $item['id']; ?>" 
                   class="flex items-center gap-1.5 px-3 py-1.5 xl:px-3 xl:py-1.5 text-[13px] xl:text-[13px] font-bold transition-all rounded-full no-underline whitespace-nowrap <?php echo $active ? 'bg-white text-primary shadow-sm border border-white' : 'text-white/80 hover:bg-white/10 hover:text-white border border-transparent'; ?>">
                    <i class="fas <?php echo $item['icon']; ?> <?php echo $active ? 'text-primary' : 'text-white/60'; ?>"></i>
                    <span><?php echo $item['label']; ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- 3. Actions / Buttons - Hidden on Mobile -->
        <div class="hidden xl:flex items-center gap-2 xl:gap-3 flex-shrink-0">
            <!-- Currency & Language -->
            <div class="flex items-center gap-1.5">
                <?php 
                $current_currency = $_SESSION['currency'] ?? 'LKR'; 
                $flag_url = $current_currency == 'USD' ? 'https://flagcdn.com/w20/us.png' : 'https://flagcdn.com/w20/lk.png';
                ?>
                <div class="relative group">
                    <button class="text-white hover:bg-white/10 px-2 py-1.5 rounded font-bold text-[13px] transition-colors flex items-center gap-1 cursor-pointer">
                        <?php echo $current_currency; ?> 
                        <i class="fas fa-chevron-down text-[9px] opacity-60 group-hover:rotate-180 transition-transform"></i>
                    </button>
                    <!-- Currency Dropdown -->
                    <div class="absolute top-full right-0 mt-1 w-24 bg-white rounded-lg shadow-[0_10px_40px_rgba(0,0,0,0.2)] border border-border hidden group-hover:block z-50 overflow-hidden">
                        <a href="?currency=LKR" class="block px-3 py-2 text-xs font-bold <?php echo $current_currency == 'LKR' ? 'text-primary bg-primary/10' : 'text-neutral-600 hover:bg-neutral-50 hover:text-primary'; ?> transition-colors">LKR</a>
                        <a href="?currency=USD" class="block px-3 py-2 text-xs font-bold <?php echo $current_currency == 'USD' ? 'text-primary bg-primary/10' : 'text-neutral-600 hover:bg-neutral-50 hover:text-primary'; ?> transition-colors">USD</a>
                    </div>
                </div>
                
                <button class="hover:bg-white/10 px-1.5 py-1.5 rounded transition-colors cursor-default">
                    <img src="<?php echo $flag_url; ?>" width="20" alt="<?php echo $current_currency; ?>" class="rounded-sm shadow-sm">
                </button>
            </div>

            <div class="h-5 w-[1px] bg-white/20 mx-0.5"></div>

            <!-- Auth Buttons -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="list_your_property.php" class="text-white hover:bg-white/10 px-3 py-1.5 rounded-lg font-bold text-[13px]">List property</a>
                <div class="flex items-center gap-2 bg-white/10 px-2.5 py-1 rounded-full border border-white/20 cursor-pointer transition-all hover:bg-white/20">
                    <div class="w-7 h-7 bg-white text-primary rounded-full flex items-center justify-center font-black text-[11px]">
                        <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                    </div>
                </div>
            <?php else: ?>
                <a href="register.php" class="text-white hover:bg-white/10 px-3 py-1.5 rounded font-bold text-[13px] transition-colors">Register</a>
                <a href="login.php" class="bg-white text-primary hover:bg-gold hover:text-primary px-4 py-1.5 rounded font-bold text-[13px] transition-all shadow-md">Sign in</a>
            <?php endif; ?>
        </div>
    </nav>
</header>

<style>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
