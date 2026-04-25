<?php 
require_once 'config.php'; 
session_start();

// Currency Handling
if (isset($_GET['currency']) && in_array(strtoupper($_GET['currency']), ['LKR', 'USD'])) {
    $_SESSION['currency'] = strtoupper($_GET['currency']);
}
if (!isset($_SESSION['currency'])) {
    $_SESSION['currency'] = 'LKR';
}
$currency = $_SESSION['currency'];
$exchange_rate = 300; // Standard approximation: 1 USD = 300 LKR

// Initialize properties as empty array to avoid errors
$properties = [];

try {
    // Fetch properties and their cheapest room using a fully compatible query (avoids ONLY_FULL_GROUP_BY errors)
    $query = "SELECT p.*, r.room_name, r.adults, r.children, r.price_lkr, r.room_image as first_room_image 
              FROM properties p 
              LEFT JOIN property_rooms r ON r.id = (
                  SELECT id FROM property_rooms 
                  WHERE property_id = p.id 
                  ORDER BY price_lkr ASC 
                  LIMIT 1
              )
              ORDER BY p.created_at DESC";

    $stmt = $pdo->query($query);
    $properties = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Query failed: " . $e->getMessage());
    // $properties remains an empty array
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookingjaunt - Find your next stay</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#155E63',
                        secondary: '#F97316',
                        tropical: '#2CA6A4',
                        gold: '#FBBF24',
                        palm: '#2F855A',
                        neutral: {
                            50: '#F9FAFB',
                            100: '#F3F4F6',
                            800: '#1F2937',
                        },
                        coral: '#FF6B4A',
                        ocean: '#0F3D3E',
                    }
                }
            }
        }
    </script>
</head>
<body>

    <?php include 'navbar.php'; ?>

    <!-- Hero Section -->
    <section class="relative h-[400px] md:h-[550px] flex flex-col justify-center px-[10%] text-white bg-cover bg-center" style="background-image: linear-gradient(rgba(21, 94, 99, 0.7), rgba(44, 166, 164, 0.4)), url('assets/hero_bg.png');">
        <!-- Pattern Overlay -->
        <div class="absolute inset-0 opacity-10 pointer-events-none" style="background-image: url('https://www.transparenttextures.com/patterns/cubes.png');"></div>
        
        <div class="absolute top-10 right-[10%] hidden md:flex items-center gap-4 text-3xl font-bold z-10 text-white/90">
            <i class="fas fa-phone-alt"></i> +94 1000000
        </div>
        
        <div class="relative z-10 max-w-2xl">
            <h1 class="text-5xl md:text-6xl font-bold mb-4 drop-shadow-xl tracking-tight">Discover Sri Lanka</h1>
            <p class="text-xl md:text-2xl font-medium drop-shadow-md text-white/90">Find your next paradise stay from luxury hotels to tropical villas.</p>
        </div>

        <!-- Search Bar -->
        <div class="absolute -bottom-[190px] md:-bottom-[40px] left-1/2 -translate-x-1/2 w-[94%] md:w-[80%] max-w-[1100px] bg-gold p-1 md:p-1.5 rounded-xl md:rounded-2xl flex flex-col md:flex-row shadow-[0_10px_30px_rgba(0,0,0,0.2)] md:shadow-[0_20px_50px_rgba(0,0,0,0.3)] border border-white/20 z-20">
            
            <!-- Row 1: Location -->
            <div class="flex-[1.5] bg-white m-0.5 p-3 md:p-4 rounded-t-lg md:rounded-xl flex items-center gap-3 text-neutral-800">
                <i class="fas fa-search text-neutral-600 md:text-primary md:text-xl"></i>
                <input type="text" placeholder="Around current location" class="border-none outline-none w-full text-[15px] font-bold md:font-medium placeholder:text-neutral-800 md:placeholder:text-neutral-400">
            </div>
            
            <!-- Row 2: Dates -->
            <div class="flex-1 flex m-0.5 gap-1 md:gap-0 bg-transparent md:bg-white md:rounded-xl">
                <!-- Check-in -->
                <div class="flex-1 bg-white p-2 px-3 md:p-4 md:rounded-xl flex flex-col md:flex-row md:items-center md:gap-3 cursor-pointer hover:bg-neutral-50 transition-colors">
                    <div class="text-[12px] text-neutral-600 mb-0.5 md:hidden">Check-in date</div>
                    <div class="text-[15px] font-bold md:font-medium text-neutral-800 flex items-center gap-2">
                        <i class="far fa-calendar-alt text-primary hidden md:inline-block text-xl"></i>
                        <span class="md:hidden">Sat, Apr 25, 2026</span>
                        <span class="hidden md:inline-block">Dates</span>
                    </div>
                </div>
                <!-- Check-out -->
                <div class="flex-1 bg-white p-2 px-3 md:hidden flex flex-col cursor-pointer hover:bg-neutral-50 transition-colors">
                    <div class="text-[12px] text-neutral-600 mb-0.5">Check-out date</div>
                    <div class="text-[15px] font-bold text-neutral-800">Sun, Apr 26, 2026</div>
                </div>
            </div>

            <!-- Row 3: Guests -->
            <div class="flex-1 flex m-0.5 gap-1 md:block bg-transparent md:bg-white md:rounded-xl">
                <!-- Adults -->
                <div class="flex-1 bg-white p-2 px-3 md:p-4 md:rounded-xl flex flex-col md:flex-row md:items-center md:gap-3 cursor-pointer hover:bg-neutral-50 transition-colors">
                    <div class="text-[12px] text-neutral-600 mb-0.5 md:hidden">Adults</div>
                    <div class="text-[15px] font-bold md:font-medium text-neutral-800 flex items-center gap-2">
                        <i class="fas fa-user-friends text-primary hidden md:inline-block text-xl"></i>
                        <span class="md:hidden">2</span>
                        <span class="hidden md:inline-block">Guests</span>
                    </div>
                </div>
                <!-- Children -->
                <div class="flex-1 bg-white p-2 px-3 flex flex-col md:hidden cursor-pointer hover:bg-neutral-50 transition-colors">
                    <div class="text-[12px] text-neutral-600 mb-0.5">Children</div>
                    <div class="text-[15px] font-bold text-neutral-800">0</div>
                </div>
                <!-- Rooms -->
                <div class="flex-1 bg-white p-2 px-3 flex flex-col md:hidden cursor-pointer hover:bg-neutral-50 transition-colors">
                    <div class="text-[12px] text-neutral-600 mb-0.5">Rooms</div>
                    <div class="text-[15px] font-bold text-neutral-800">1</div>
                </div>
            </div>

            <!-- Search Button -->
            <button class="bg-[#006CE4] hover:bg-[#0057b8] text-white px-10 py-3.5 md:py-4 rounded-b-lg md:rounded-xl font-bold text-[18px] md:text-[16px] m-0.5 mt-1 md:mt-0.5 transition-all shadow-md active:scale-95">Search</button>
        </div>
    </section>

    <!-- Main Content -->
    <main class="max-w-[1400px] mx-auto flex flex-col md:grid md:grid-cols-[260px_1fr] lg:grid-cols-[280px_1fr] gap-6 px-0 md:px-4 lg:px-6 md:mt-16 <?php echo !isset($_SESSION['user_id']) ? 'mt-[220px]' : 'mt-[220px]'; ?>">
        
        <!-- Mobile Filters & Quick Buttons (Hidden on Desktop) -->
        <div class="md:hidden px-4 mb-2">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-[18px] text-neutral-800 leading-tight">Quick and easy planner</h3>
                    <p class="text-[13px] text-text-secondary mt-0.5">Pick a vibe and explore top destinations</p>
                </div>
                <!-- Filter Toggle Button -->
                <button class="bg-neutral-100 p-3 rounded-full border border-neutral-200 text-neutral-700 hover:bg-neutral-200 transition-colors shadow-sm flex-shrink-0 ml-2" onclick="alert('Filter menu would slide up here!')">
                    <i class="fas fa-sliders-h"></i>
                </button>
            </div>
            
            <!-- Quick Buttons (Popular Amenities) -->
            <?php
            try {
                $pop_am_stmt = $pdo->query("SELECT amenity_name, icon FROM amenities_master WHERE is_popular = 1 LIMIT 8");
                $pop_amenities = $pop_am_stmt->fetchAll();
                if (count($pop_amenities) > 0):
            ?>
            <div class="flex overflow-x-auto no-scrollbar gap-2.5 pb-2 snap-x">
                <?php foreach ($pop_amenities as $am): ?>
                <button class="flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-primary/20 rounded-full whitespace-nowrap text-[13px] font-bold text-primary hover:bg-primary/5 hover:border-primary transition-all shadow-[0_2px_8px_rgba(0,0,0,0.04)] shrink-0 snap-start">
                    <?php if (!empty($am['icon'])): ?>
                        <i class="fas <?php echo htmlspecialchars($am['icon']); ?> text-primary"></i>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($am['amenity_name']); ?>
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; 
            } catch (PDOException $e) { /* ignore */ }
            ?>
        </div>

        <!-- Sidebar (Hidden on Mobile) -->
        <aside class="sidebar hidden md:block">
            <div class="filter-box">
                <div class="filter-title">Filter by:</div>
                
                <div class="filter-group">
                    <h4 class="filter-category-title">Hotel Category</h4>
                    <label class="filter-option"><input type="checkbox"> Budget Friendly</label>
                    <label class="filter-option"><input type="checkbox"> Luxury</label>
                    <label class="filter-option"><input type="checkbox"> Super Luxury</label>
                </div>

                <div class="filter-group">
                    <h4 class="filter-category-title">Popular filters</h4>
                    <label class="filter-option"><input type="checkbox"> <i class="fas fa-umbrella-beach"></i> Beach</label>
                    <label class="filter-option"><input type="checkbox"> <i class="fas fa-hippo"></i> Safari</label>
                    <label class="filter-option"><input type="checkbox"> <i class="fas fa-wifi"></i> Free WiFi</label>
                    <label class="filter-option"><input type="checkbox"> <i class="fas fa-swimming-pool"></i> Pool</label>
                    <label class="filter-option"><input type="checkbox"> <i class="fas fa-utensils"></i> Breakfast included</label>
                </div>

                <div class="filter-group">
                    <h4 class="filter-category-title">Your budget (per night)</h4>
                    <label class="filter-option"><input type="checkbox"> LKR 0 - 5,000</label>
                    <label class="filter-option"><input type="checkbox"> LKR 5,000 - 10,000</label>
                    <label class="filter-option"><input type="checkbox"> LKR 10,000 - 20,000</label>
                    <label class="filter-option"><input type="checkbox"> LKR 20,000+</label>
                </div>

                <div class="filter-group">
                    <h4 class="filter-category-title">Star rating</h4>
                    <label class="filter-option"><input type="checkbox"> 3 stars</label>
                    <label class="filter-option"><input type="checkbox"> 4 stars</label>
                    <label class="filter-option"><input type="checkbox"> 5 stars</label>
                </div>
            </div>
        </aside>

        <!-- Results -->
        <section class="results">

            <?php
            if (empty($properties)): ?>
                <div class="no-results" style="padding: 40px; text-align: center; background: #fff; border-radius: 8px; border: 1px solid #ddd;">
                    <i class="fas fa-search" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                    <h3>No properties found</h3>
                    <p>Try adjusting your filters or search criteria.</p>
                </div>
            <?php else: ?>
                <!-- Mobile Horizontal Scroll Container -->
                <div class="flex flex-row md:flex-col overflow-x-auto md:overflow-visible gap-4 md:gap-0 pb-4 md:pb-0 px-4 md:px-0 snap-x snap-mandatory no-scrollbar w-full">
                <?php foreach ($properties as $property): 
                    $stars = 0;
                    if ($property['hotel_category'] == 'budget_friendly') $stars = 3;
                    if ($property['hotel_category'] == 'luxury') $stars = 4;
                    if ($property['hotel_category'] == 'super_luxury') $stars = 5;
                    
                    // Format location
                    $location = $property['city'];
                    if ($property['district']) $location .= ", " . $property['district'];
                    
                    $image = !empty($property['cover_image']) ? $property['cover_image'] : 'assets/hotel1.png';
            ?>
                <!-- Property Listing Card -->
                <div class="bg-white border border-border rounded-lg md:rounded-xl overflow-hidden flex flex-col md:flex-row mb-0 md:mb-4 transition-all hover:shadow-[0_4px_20px_rgb(0,0,0,0.08)] group w-[72vw] min-w-[240px] max-w-[280px] md:max-w-none md:w-full snap-start shrink-0 md:shrink cursor-pointer" onclick="window.location.href='#'">
                    <!-- Image Wrapper -->
                    <div class="relative w-full md:w-[240px] md:h-auto h-[150px] p-0 md:p-4 flex-shrink-0">
                        <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($property['property_name']); ?>" class="w-full h-full object-cover rounded-none md:rounded group-hover:scale-[1.02] transition-transform duration-500">
                        <?php if ($property['hotel_category'] == 'super_luxury'): ?>
                            <div class="absolute top-6 left-6 bg-secondary text-white px-2 py-1 rounded text-[10px] font-bold uppercase tracking-widest shadow-md">Featured</div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Content -->
                    <div class="flex-1 p-3 md:p-4 md:pl-0 flex flex-col text-left">
                        <div class="flex justify-between items-start mb-1">
                            <div class="flex-1">
                                <div class="flex flex-col md:flex-row md:items-center justify-start gap-1 md:gap-2 mb-1">
                                    <div class="flex justify-start gap-0.5 text-gold text-[10px] order-1 md:order-2">
                                        <?php for($i=0; $i<$stars; $i++): ?>
                                            <i class="fas fa-star"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <h3 class="text-[16px] md:text-[20px] font-bold text-primary leading-tight hover:text-tropical cursor-pointer transition-colors order-2 md:order-1"><?php echo htmlspecialchars($property['property_name']); ?></h3>
                                </div>
                                <div class="flex flex-wrap items-center justify-start gap-1.5 text-[11px] md:text-[12px] text-tropical mb-2">
                                    <span class="underline decoration-dotted font-bold cursor-pointer"><?php echo htmlspecialchars($location); ?></span>
                                    <span class="font-bold text-neutral-300">•</span>
                                    <span class="font-medium text-ocean"><?php echo htmlspecialchars($property['closest_main_town'] ?? $property['city']); ?></span>
                                </div>
                                <div class="flex justify-start mb-1">
                                    <div class="inline-block text-palm text-[10px] md:text-[11px] font-bold px-2 py-0.5 rounded bg-palm/10">Free cancellation</div>
                                </div>
                            </div>
                            
                            <!-- Rating (Hidden on Mobile) -->
                            <div class="hidden md:flex items-center gap-2 ml-4">
                                <div class="text-right flex flex-col justify-center">
                                    <div class="text-[14px] font-bold text-neutral-800 leading-none">New</div>
                                    <div class="text-[10px] text-text-secondary uppercase tracking-tighter">Review Pending</div>
                                </div>
                                <div class="bg-ocean text-white w-8 h-8 rounded-md flex items-center justify-center font-bold text-sm shadow-sm">-</div>
                            </div>
                        </div>

                        <!-- Body & Footer Split -->
                        <div class="flex flex-col md:flex-row mt-2 flex-1">
                            <!-- Room & Amenities Details -->
                            <div class="flex-1 border-r border-transparent md:border-border/50 md:pr-4">
                                <div class="hidden md:block">
                                    <h4 class="text-[13px] font-bold text-neutral-800 mb-0.5"><?php echo htmlspecialchars($property['room_name'] ?? 'Standard Room'); ?></h4>
                                    <p class="text-[12px] text-text-secondary mb-2"><?php echo htmlspecialchars($property['adults'] ?? 2); ?> Adults, <?php echo htmlspecialchars($property['children'] ?? 0); ?> Children</p>
                                </div>
                                
                                <!-- Property Amenities -->
                                <?php
                                $amenities_stmt = $pdo->prepare("SELECT am.amenity_name, am.icon FROM property_amenities pa JOIN amenities_master am ON pa.amenity_id = am.id WHERE pa.property_id = ? AND am.is_popular = 1 LIMIT 4");
                                $amenities_stmt->execute([$property['id']]);
                                $amenities = $amenities_stmt->fetchAll();
                                if (count($amenities) > 0):
                                ?>
                                <div class="flex flex-wrap justify-start gap-1.5 mb-2 mt-1 md:mt-0">
                                    <?php foreach ($amenities as $amenity): ?>
                                    <div class="flex items-center gap-1 text-[9px] md:text-[10px] font-bold text-neutral-600 bg-neutral-100 px-1.5 py-0.5 rounded border border-neutral-200">
                                        <?php if (!empty($amenity['icon'])): ?>
                                        <i class="fas <?php echo htmlspecialchars($amenity['icon']); ?> text-tropical"></i>
                                        <?php endif; ?>
                                        <span><?php echo htmlspecialchars($amenity['amenity_name']); ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>

                                <div class="flex items-start justify-start gap-1.5 text-palm text-[10px] md:text-[12px] font-bold mt-1">
                                    <i class="fas fa-check mt-0.5"></i>
                                    <span>No prepayment needed – pay at the property</span>
                                </div>
                            </div>

                            <!-- Price Action -->
                            <div class="flex flex-col items-end justify-end mt-3 md:mt-0 md:pl-4 min-w-[140px]">
                                <?php
                                $price = $property['price_lkr'] ?? 0;
                                if ($currency === 'USD') {
                                    $display_price = ceil($price / $exchange_rate);
                                    $currency_symbol = 'USD';
                                } else {
                                    $display_price = $price;
                                    $currency_symbol = 'LKR';
                                }
                                ?>
                                <div class="text-[9px] md:text-[11px] text-text-secondary mt-1 md:mt-0">Starting from</div>
                                <div class="text-[18px] md:text-[22px] font-black text-primary tracking-tight leading-none mb-1"><?php echo $currency_symbol; ?> <?php echo number_format($display_price); ?></div>
                                <div class="text-[8px] md:text-[10px] text-text-secondary uppercase font-bold tracking-wider mb-2">taxes & fees included</div>
                                <button class="hidden md:block bg-secondary hover:bg-primary text-white px-4 py-1.5 md:py-2 rounded font-bold text-[13px] md:text-[14px] w-full md:w-auto transition-colors">Check Availability <i class="fas fa-chevron-right ml-1 text-[9px] md:text-[10px]"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Pagination -->
            <div class="hidden md:flex" style="justify-content:center; gap:5px; margin-top:20px;">
                <button style="padding:8px 12px; border:1px solid #ddd; background:#fff; border-radius:4px;"><i class="fas fa-chevron-left"></i></button>
                <button style="padding:8px 12px; border:1px solid #ddd; background:var(--primary); color:#fff; border-radius:4px;">1</button>
                <button style="padding:8px 12px; border:1px solid #ddd; background:#fff; border-radius:4px;">2</button>
                <button style="padding:8px 12px; border:1px solid #ddd; background:#fff; border-radius:4px;">3</button>
                <span>...</span>
                <button style="padding:8px 12px; border:1px solid #ddd; background:#fff; border-radius:4px;">24</button>
                <button style="padding:8px 12px; border:1px solid #ddd; background:#fff; border-radius:4px;"><i class="fas fa-chevron-right"></i></button>
            </div>

            <!-- Mobile Authentication Promo (Hidden on Desktop) -->
            <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="md:hidden mx-4 mt-6 mb-2 bg-white border border-border shadow-[0_4px_15px_rgba(0,0,0,0.05)] rounded-xl p-4 flex flex-col gap-3">
                <div class="flex items-start gap-3">
                    <div class="bg-primary/10 p-2.5 rounded-full text-primary">
                        <i class="fas fa-gift text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-[15px] text-neutral-800 leading-tight">Sign in, save money</h3>
                        <p class="text-[12px] text-text-secondary leading-tight mt-1">Save 10% or more at participating properties with a free Bookingjaunt account.</p>
                    </div>
                </div>
                <div class="flex gap-2 mt-2">
                    <a href="login.php" class="flex-1 bg-secondary text-white text-center py-2.5 rounded font-bold text-[13px] hover:bg-primary transition-colors shadow-sm">Sign in</a>
                    <a href="register.php" class="flex-1 text-primary border border-primary text-center py-2.5 rounded font-bold text-[13px] hover:bg-neutral-50 transition-colors">Register</a>
                </div>
            </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Footer CTA -->
    <section class="bg-primary-dark text-white py-12 px-4 md:px-[10%] mt-12 flex flex-col md:flex-row justify-center md:justify-between items-center text-center md:text-left gap-6">
        <div>
            <div class="text-[20px] md:text-[24px] font-bold leading-tight">Save time, save money!</div>
            <div class="text-[13px] md:text-[14px] font-normal text-white/80 mt-1 md:mt-0">Sign up and we'll send the best deals to you</div>
        </div>
        <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
            <input type="text" placeholder="Your email address" class="p-3 rounded text-neutral-800 w-full sm:w-[300px] outline-none focus:ring-2 focus:ring-primary">
            <button class="bg-primary hover:bg-[#00224f] text-white font-bold px-6 py-3 rounded transition-colors w-full sm:w-auto">Subscribe</button>
        </div>
    </section>

    <!-- Footer Main -->
    <footer class="bg-white py-10 px-4 md:px-[10%] grid grid-cols-2 md:grid-cols-5 gap-8 md:gap-4 border-b border-border">
        <div>
            <h4 class="font-bold text-[14px] text-neutral-800 mb-3 md:mb-4">Support</h4>
            <ul class="flex flex-col gap-2 text-[13px] text-primary font-medium">
                <li class="cursor-pointer hover:underline">Help Center</li>
                <li class="cursor-pointer hover:underline">Customer Service</li>
                <li class="cursor-pointer hover:underline">Safety Resource Center</li>
                <li class="cursor-pointer hover:underline">Terms & Conditions</li>
            </ul>
        </div>
        <div>
            <h4 class="font-bold text-[14px] text-neutral-800 mb-3 md:mb-4">Discover</h4>
            <ul class="flex flex-col gap-2 text-[13px] text-primary font-medium">
                <li class="cursor-pointer hover:underline">Genius Rewards</li>
                <li class="cursor-pointer hover:underline">Seasonal Deals</li>
                <li class="cursor-pointer hover:underline">Travel Articles</li>
                <li class="cursor-pointer hover:underline">Car rentals</li>
            </ul>
        </div>
        <div class="col-span-2 md:col-span-1">
            <h4 class="font-bold text-[14px] text-neutral-800 mb-3 md:mb-4">Partners</h4>
            <ul class="flex flex-col gap-2 text-[13px] text-primary font-medium">
                <li class="cursor-pointer hover:underline">List your property</li>
                <li class="cursor-pointer hover:underline">Become an affiliate</li>
                <li class="cursor-pointer hover:underline">Connectivity Partners</li>
            </ul>
            <div class="text-[28px] md:text-[32px] font-bold text-neutral-200 mt-6 md:mt-8 tracking-tighter">+94 1000000</div>
        </div>
        <div>
            <h4 class="font-bold text-[14px] text-neutral-800 mb-3 md:mb-4">About</h4>
            <ul class="flex flex-col gap-2 text-[13px] text-primary font-medium">
                <li class="cursor-pointer hover:underline">About Bookingjaunt</li>
                <li class="cursor-pointer hover:underline">Careers</li>
                <li class="cursor-pointer hover:underline">Sustainability</li>
                <li class="cursor-pointer hover:underline">Press center</li>
            </ul>
        </div>
        <div>
            <h4 class="font-bold text-[14px] text-neutral-800 mb-3 md:mb-4">Follow us</h4>
            <div class="flex gap-4 text-primary">
                <i class="fab fa-facebook text-[20px] cursor-pointer hover:text-primary-dark transition-colors"></i>
                <i class="fab fa-instagram text-[20px] cursor-pointer hover:text-primary-dark transition-colors"></i>
                <i class="fab fa-twitter text-[20px] cursor-pointer hover:text-primary-dark transition-colors"></i>
            </div>
        </div>
    </footer>

    <div class="bg-white pt-8 pb-12 px-4 md:px-[10%] flex flex-col items-center gap-6 text-[12px] text-text-secondary text-center">
        <a href="index.php">
            <img src="assets/logo.png" alt="Bookingjaunt" class="h-10 w-auto opacity-90 hover:opacity-100 transition-opacity">
        </a>
        <div class="flex flex-wrap justify-center gap-4">
            <span class="cursor-pointer hover:underline text-primary">Privacy & Cookies</span>
            <span class="cursor-pointer hover:underline text-primary">Manage Cookie Settings</span>
            <span class="cursor-pointer hover:underline text-primary">MSA Statement</span>
        </div>
        <div>© 2026 Bookingjaunt.com All rights reserved.</div>
    </div>

</body>
</html>
