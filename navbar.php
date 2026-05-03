<!-- Header Wrapper -->
<header class="bg-[#003580] sticky top-0 z-50 shadow-md">
    <nav
        class="max-w-[1400px] mx-auto px-4 xl:px-6 flex flex-col xl:flex-row xl:items-center justify-between gap-y-4 xl:gap-4 py-3 xl:py-0 xl:h-16">

        <!-- Mobile Top Row / Desktop Left -->
        <div class="flex items-center justify-between w-full xl:w-auto">
            <!-- 1. Logo -->
            <a href="index.php" class="flex-shrink-0 group no-underline">
                <span class="text-white text-xl xl:text-2xl font-black tracking-tighter font-['Outfit'] transition-transform group-hover:scale-105 block">
                    Booking<span class="text-[#febb02]">Jaunt</span>
                </span>
            </a>

            <?php
            // Global Nav Items definition
            $nav_items = [
                ['id' => 'hotel', 'label' => 'Hotels', 'icon' => 'fa-hotel'],
                ['id' => 'reception_hall', 'label' => 'Reception Halls', 'icon' => 'fa-glass-cheers'],
                ['id' => 'hostel', 'label' => 'Hostels', 'icon' => 'fa-bed'],
                ['id' => 'rest_hall', 'label' => 'Rest Halls', 'icon' => 'fa-building'],
                ['id' => 'dayouts', 'label' => 'Dayouts', 'icon' => 'fa-sun'],
                ['id' => 'safari', 'label' => 'Safari', 'icon' => 'fa-hippo'],
                ['id' => 'about_contact', 'label' => 'About & Contact', 'icon' => 'fa-address-card'],
            ];
            $current_currency = $_SESSION['currency'] ?? 'LKR';
            ?>

            <!-- Mobile Action Icons (User & Hamburger) - Hidden on desktop -->
            <div class="flex items-center gap-5 xl:hidden">
                <!-- User Icon with Dropdown -->
                <div class="relative">
                <button id="mobileUserBtn" class="flex items-center justify-center transition-all focus:outline-none">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="w-8 h-8 bg-white text-[#003580] rounded-full flex items-center justify-center font-black text-[12px] shadow-sm border border-white/20">
                            <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                        </div>
                    <?php else: ?>
                        <div class="text-white text-[24px] hover:text-white/80 transition-colors leading-none">
                            <i class="far fa-user-circle"></i>
                        </div>
                    <?php endif; ?>
                </button>
                    
                    <!-- Mobile User Dropdown -->
                    <div id="mobileUserDropdown" class="hidden absolute top-full right-[-50px] mt-3 w-56 bg-white rounded-xl shadow-2xl border border-neutral-100 overflow-hidden z-[100] animate-fade-in">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="px-4 py-3 border-b border-neutral-100 bg-neutral-50/50">
                                <p class="text-[10px] text-neutral-400 uppercase font-bold tracking-widest">Signed in as</p>
                                <p class="text-[13px] font-bold text-[#003580] truncate"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                            </div>
                        <div class="py-1">
                            <?php 
                            $dash_link = (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') ? 'system/admin/dashboard.php' : 'system/hotel/dashboard.php';
                            ?>
                            <a href="<?php echo $dash_link; ?>" class="flex items-center gap-3 px-4 py-3 text-[13px] font-bold text-neutral-700 hover:bg-neutral-50 transition-colors no-underline">
                                <i class="fas fa-columns text-neutral-400 w-5"></i>
                                <span>Dashboard</span>
                            </a>
                            <a href="manage_account.php" class="flex items-center gap-3 px-4 py-3 text-[13px] font-bold text-neutral-700 hover:bg-neutral-50 transition-colors no-underline">
                                <i class="far fa-user-circle text-neutral-400 w-5"></i>
                                <span>Manage Account</span>
                            </a>
                            <a href="mybookings.php" class="flex items-center gap-3 px-4 py-3 text-[13px] font-bold text-neutral-700 hover:bg-neutral-50 transition-colors no-underline">
                                <i class="fas fa-briefcase text-neutral-400 w-5"></i>
                                <span>My Bookings</span>
                            </a>
                            <a href="property_wizard.php" class="flex items-center gap-3 px-4 py-3 text-[13px] font-bold text-neutral-700 hover:bg-neutral-50 transition-colors no-underline">
                                <i class="fas fa-plus-circle text-neutral-400 w-5"></i>
                                <span>List your property</span>
                            </a>
                            <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-[13px] font-bold text-red-600 hover:bg-red-50 transition-colors border-t border-neutral-100 no-underline mt-1">
                                <i class="fas fa-sign-out-alt w-5"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                        <?php else: ?>
                            <div class="p-4 flex flex-col gap-3">
                                <a href="login.php" class="w-full bg-[#003580] text-white text-center py-2.5 rounded-lg font-bold text-[14px] hover:bg-[#002b66] transition-colors no-underline">Sign in</a>
                                <a href="register.php" class="w-full bg-white border border-[#003580] text-[#003580] text-center py-2.5 rounded-lg font-bold text-[14px] hover:bg-neutral-50 transition-colors no-underline">Create account</a>
                            </div>
                            <div class="border-t border-neutral-100">
                                <a href="property_wizard.php" class="flex items-center gap-3 px-4 py-3 text-[13px] font-bold text-neutral-700 hover:bg-neutral-50 transition-colors no-underline">
                                    <i class="fas fa-plus-circle text-neutral-400 text-lg"></i>
                                    <span>List your property</span>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Currency Toggle Button (Mobile) -->
                <?php
                $toggle_currency = ($current_currency == 'LKR') ? 'USD' : 'LKR';
                ?>
                <a href="?currency=<?php echo $toggle_currency; ?>" class="text-white text-[13px] font-black bg-white/10 px-3 py-1.5 rounded-lg border border-white/20 transition-all hover:bg-white/20 no-underline">
                    <?php echo $current_currency; ?>
                </a>
            </div>
        </div>

        <!-- 2. Navbar Items (Stays, Safari, etc.) -->
        <div
            class="flex items-center justify-start xl:justify-center gap-1.5 xl:gap-1 flex-shrink-0 xl:flex-1 overflow-x-auto no-scrollbar w-full xl:w-auto pb-1 xl:pb-0">
            <?php
            $current_type = isset($_GET['type']) ? $_GET['type'] : 'hotel';

            foreach ($nav_items as $item):
                $active = ($current_type == $item['id']);
                ?>
                <a href="index.php?type=<?php echo $item['id']; ?>"
                    class="flex items-center gap-1.5 px-3 py-1.5 xl:px-3 xl:py-1.5 text-[13px] xl:text-[13px] font-bold transition-all rounded-full no-underline whitespace-nowrap <?php echo $active ? 'bg-white text-[#003580] shadow-sm border border-white' : 'text-white/80 hover:bg-white/10 hover:text-white border border-transparent'; ?>">
                    <i
                        class="fas <?php echo $item['icon']; ?> <?php echo $active ? 'text-[#003580]' : 'text-white/60'; ?>"></i>
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
                    <button
                        class="text-white hover:bg-white/10 px-2 py-1.5 rounded font-bold text-[13px] transition-colors flex items-center gap-1 cursor-pointer">
                        <?php echo $current_currency; ?>
                        <i
                            class="fas fa-chevron-down text-[9px] opacity-60 group-hover:rotate-180 transition-transform"></i>
                    </button>
                    <!-- Currency Dropdown -->
                    <div
                        class="absolute top-full right-0 pt-1 hidden group-hover:block z-50 animate-fade-in">
                        <div class="w-24 bg-white rounded-lg shadow-[0_10px_40px_rgba(0,0,0,0.2)] border border-[#e7e7e7] overflow-hidden">
                            <a href="?currency=LKR"
                                class="block px-3 py-2 text-xs font-bold <?php echo $current_currency == 'LKR' ? 'text-[#003580] bg-[#003580]/10' : 'text-neutral-600 hover:bg-neutral-50 hover:text-[#003580]'; ?> transition-colors">LKR</a>
                            <a href="?currency=USD"
                                class="block px-3 py-2 text-xs font-bold <?php echo $current_currency == 'USD' ? 'text-[#003580] bg-[#003580]/10' : 'text-neutral-600 hover:bg-neutral-50 hover:text-[#003580]'; ?> transition-colors">USD</a>
                        </div>
                    </div>
                </div>

                <button class="hover:bg-white/10 px-1.5 py-1.5 rounded transition-colors cursor-default">
                    <img src="<?php echo $flag_url; ?>" width="20" alt="<?php echo $current_currency; ?>"
                        class="rounded-sm shadow-sm">
                </button>
            </div>

            <div class="h-5 w-[1px] bg-white/20 mx-0.5"></div>

            <!-- Auth Buttons -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="property_wizard.php"
                    class="bg-[#10b981] hover:bg-[#059669] text-white px-4 py-1.5 rounded-lg font-black text-[13px] transition-all shadow-lg shadow-[#10b981]/20 flex items-center gap-2">
                    <i class="fas fa-plus-circle"></i> List property
                </a>
                
                <div class="relative group">
                    <div
                        class="flex items-center gap-2 bg-white/10 px-2.5 py-1 rounded-full border border-white/20 cursor-pointer transition-all hover:bg-white/20">
                        <div
                            class="w-7 h-7 bg-white text-[#003580] rounded-full flex items-center justify-center font-black text-[11px]">
                            <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                        </div>
                    </div>

                    <!-- User Dropdown Menu -->
                    <div class="absolute top-full right-0 pt-2 hidden group-hover:block z-[100] animate-fade-in">
                        <div class="w-48 bg-white rounded-xl shadow-2xl border border-neutral-100 overflow-hidden">
                            <div class="px-4 py-3 border-b border-neutral-100 bg-neutral-50/50">
                                <p class="text-[10px] text-neutral-400 uppercase font-bold tracking-widest">Signed in as</p>
                                <p class="text-[13px] font-bold text-primary truncate"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                            </div>
                            <?php 
                            $dash_link = (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') ? 'system/admin/dashboard.php' : 'system/hotel/dashboard.php';
                            ?>
                            <a href="<?php echo $dash_link; ?>" class="flex items-center gap-3 px-4 py-3 text-[13px] font-bold text-neutral-700 hover:bg-neutral-50 transition-colors no-underline">
                                <i class="fas fa-columns text-neutral-400"></i>
                                <span>Dashboard</span>
                            </a>
                            <a href="manage_account.php" class="flex items-center gap-3 px-4 py-3 text-[13px] font-bold text-neutral-700 hover:bg-neutral-50 transition-colors">
                                <i class="far fa-user-circle text-neutral-400"></i>
                                <span>Manage Account</span>
                            </a>
                            <a href="mybookings.php" class="flex items-center gap-3 px-4 py-3 text-[13px] font-bold text-neutral-700 hover:bg-neutral-50 transition-colors">
                                <i class="fas fa-briefcase text-neutral-400"></i>
                                <span>My Bookings</span>
                            </a>
                            <a href="logout.php" class="flex items-center gap-3 px-4 py-3 text-[13px] font-bold text-red-600 hover:bg-red-50 transition-colors border-t border-neutral-100">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
<?php else: ?>
                <a href="register.php"
                    class="text-white hover:bg-white/10 px-3 py-1.5 rounded font-bold text-[13px] transition-colors">Register</a>
                <a href="login.php"
                    class="bg-white text-[#003580] hover:bg-[#febb02] px-4 py-1.5 rounded font-bold text-[13px] transition-all shadow-md">Sign
                    in</a>
            <?php endif; ?>
        </div>
    </nav>
</header>

<style>
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }

    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .animate-fade-in {
        animation: fadeIn 0.2s ease-out forwards;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const mobileUserBtn = document.getElementById('mobileUserBtn');
        const mobileUserDropdown = document.getElementById('mobileUserDropdown');

        if (mobileUserBtn) {
            mobileUserBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                mobileUserDropdown.classList.toggle('hidden');
            });
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function() {
            if (mobileUserDropdown) mobileUserDropdown.classList.add('hidden');
        });

        // Prevent closing when clicking inside dropdowns
        if (mobileUserDropdown) {
            mobileUserDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    });
</script>