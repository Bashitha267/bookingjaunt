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

try {
    $q = $_GET['q'] ?? '';
    $checkin = $_GET['checkin'] ?? '';
    $checkout = $_GET['checkout'] ?? '';
    $adults = (int) ($_GET['adults'] ?? 1);
    $children = (int) ($_GET['children'] ?? 0);
    $type = $_GET['type'] ?? 'hotel';

    $params = [];
    $where = ["p.business_type = ?"];
    $params[] = $type;

    if (!empty($q)) {
        $where[] = "(p.property_name LIKE ? OR p.city LIKE ? OR p.district LIKE ? OR p.closest_main_town LIKE ?)";
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }

    // Capacity filter
    $where[] = "(r.adults >= ? AND (r.adults + r.children) >= ?)";
    $params[] = $adults;
    $params[] = ($adults + $children);

    // Availability Filter (If dates provided)
    if (!empty($checkin) && !empty($checkout)) {
        $where[] = "r.total_rooms > (
            SELECT COUNT(*) FROM bookings b 
            WHERE b.room_id = r.id 
            AND b.status NOT IN ('cancelled')
            AND (b.check_in_date < ? AND b.check_out_date > ?)
        )";
        $params[] = $checkout;
        $params[] = $checkin;
    }

    $where_sql = implode(" AND ", $where);

    // Fetch unique property types for the "Browse by" section
    $stmt_types = $pdo->query("SELECT DISTINCT business_type FROM properties WHERE business_type IS NOT NULL");
    $db_property_types = $stmt_types->fetchAll(PDO::FETCH_COLUMN);

    // Fetch properties and their cheapest matching room
    $query = "SELECT p.*, r.room_name, r.adults, r.children, r.price_lkr, r.room_image as first_room_image, r.id as room_id,
              AVG(rev.rating) as avg_rating, COUNT(rev.id) as review_count
              FROM properties p 
              JOIN property_rooms r ON r.property_id = p.id
              LEFT JOIN reviews rev ON rev.property_id = p.id
              WHERE $where_sql
              AND r.price_lkr = (
                  SELECT MIN(price_lkr) FROM property_rooms r2 
                  WHERE r2.property_id = p.id 
                  -- Re-apply filters for the cheapest room subquery to ensure it matches search criteria
                  AND r2.adults >= ? AND (r2.adults + r2.children) >= ?
                  " . (!empty($checkin) && !empty($checkout) ? "AND r2.total_rooms > (
                      SELECT COUNT(*) FROM bookings b2 
                      WHERE b2.room_id = r2.id 
                      AND b2.status NOT IN ('cancelled')
                      AND (b2.check_in_date < ? AND b2.check_out_date > ?)
                  )" : "") . "
              )
              GROUP BY p.id
              ORDER BY p.created_at DESC";

    // Add subquery params
    $params[] = $adults;
    $params[] = ($adults + $children);
    if (!empty($checkin) && !empty($checkout)) {
        $params[] = $checkout;
        $params[] = $checkin;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $properties = $stmt->fetchAll();

    // Fetch Ads
    $ads_stmt = $pdo->query("SELECT * FROM advertisements WHERE status = 'active' ORDER BY RAND() LIMIT 2");
    $ads = $ads_stmt->fetchAll();

    // Fetch Featured Hotels (Boosted)
    $stmt_f_hotels = $pdo->query("SELECT p.*, r.price_lkr, r.room_image as first_room_image
                                   FROM properties p
                                   JOIN property_boosts pb ON pb.property_id = p.id
                                   JOIN property_rooms r ON r.property_id = p.id
                                   WHERE pb.status = 'active' 
                                   AND pb.payment_status = 'paid'
                                   AND pb.start_date <= CURDATE() 
                                   AND DATE_ADD(pb.start_date, INTERVAL pb.duration_days DAY) >= CURDATE()
                                   AND p.business_type IN ('hotel', 'resort', 'villa', 'apartment')
                                   AND r.price_lkr = (SELECT MIN(price_lkr) FROM property_rooms r2 WHERE r2.property_id = p.id)
                                   GROUP BY p.id
                                   ORDER BY RAND() LIMIT 8");
    $featured_hotels = $stmt_f_hotels->fetchAll();

    // Fetch Featured Dayouts (Boosted)
    $stmt_f_dayouts = $pdo->query("SELECT p.*, r.price_lkr, r.room_image as first_room_image
                                    FROM properties p
                                    JOIN property_boosts pb ON pb.property_id = p.id
                                    JOIN property_rooms r ON r.property_id = p.id
                                    WHERE pb.status = 'active' 
                                    AND pb.payment_status = 'paid'
                                    AND pb.start_date <= CURDATE() 
                                    AND DATE_ADD(pb.start_date, INTERVAL pb.duration_days DAY) >= CURDATE()
                                    AND p.business_type = 'dayouts'
                                    AND r.price_lkr = (SELECT MIN(price_lkr) FROM property_rooms r2 WHERE r2.property_id = p.id)
                                    GROUP BY p.id
                                    ORDER BY RAND() LIMIT 8");
    $featured_dayouts = $stmt_f_dayouts->fetchAll();

    // Check if logged-in user has properties
    $user_has_properties = false;
    if (isset($_SESSION['user_id'])) {
        $check_stmt = $pdo->prepare("SELECT id FROM properties WHERE owner_id = ? LIMIT 1");
        $check_stmt->execute([$_SESSION['user_id']]);
        $user_has_properties = (bool) $check_stmt->fetch();
    }
} catch (PDOException $e) {
    error_log("Query failed: " . $e->getMessage());
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
                        primary: '#003580',
                        secondary: '#006ce4',
                        gold: '#febb02',
                        palm: '#008009',
                        'text-primary': '#1a1a1a',
                        'text-secondary': '#4a4a4a',
                        neutral: {
                            50: '#f5f5f5',
                            100: '#e7e7e7',
                            800: '#1a1a1a',
                        },
                        ocean: '#003580',
                    }
                }
            }
        }
    </script>
</head>

<body>

    <?php include 'navbar.php'; ?>

    <!-- Hero Section with Slideshow -->
    <section class="hero -mt-20">
        <div class="slideshow-container">
            <div class="slide" style="background-image: url('assets/slideshow/beach.png');"></div>
            <div class="slide" style="background-image: url('assets/slideshow/tea.png');"></div>
            <div class="slide" style="background-image: url('assets/slideshow/sigiriya.png');"></div>
        </div>
        <div class="hero-overlay"></div>

        <div class="hero-content">
            <img src="assets/white_logo.png" alt="BookingJaunt" class="hero-logo">
            <h1>Discover Sri Lanka</h1>
            <p class="tagline">Find your next paradise stay from luxury hotels to tropical villas.</p>
        </div>

        <!-- Redesigned Search Bar V2 -->
        <form action="hotels.php" method="GET" class="search-container-v2">
            <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">

            <!-- Location -->
            <div class="search-field-v2 wide">
                <i class="fas fa-bed"></i>
                <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Where are you going?">
            </div>

            <!-- Dates -->
            <div class="search-field-v2 wide">
                <i class="far fa-calendar-alt"></i>
                <div class="flex flex-1 gap-2">
                    <input type="date" name="checkin" value="<?php echo htmlspecialchars($checkin); ?>" class="w-full" placeholder="Check-in">
                    <span class="text-gray-300">—</span>
                    <input type="date" name="checkout" value="<?php echo htmlspecialchars($checkout); ?>" class="w-full" placeholder="Check-out">
                </div>
            </div>

            <!-- Guests -->
            <div class="search-field-v2 relative" id="guestDropdownContainer" onclick="toggleGuestDropdown()">
                <i class="far fa-user"></i>
                <div class="display-text" id="guestInputDisplay">
                    <?php echo $adults; ?> adults · <?php echo $children; ?> children
                </div>
                <i class="fas fa-chevron-down text-[10px] ml-auto"></i>

                <!-- Guest Popup (Same logic as before but styled for V2) -->
                <div id="guestPopup" class="absolute top-[calc(100%+12px)] left-0 w-[300px] bg-white rounded-xl shadow-2xl border border-neutral-100 p-5 z-[100] hidden animate-in text-left">
                    <div class="flex items-center justify-between mb-4">
                        <span class="font-bold text-neutral-800">Adults</span>
                        <div class="flex items-center border border-neutral-200 rounded-lg overflow-hidden h-9 w-28">
                            <button type="button" onclick="event.stopPropagation(); updateGuestCount('adults', -1)" class="flex-1 h-full flex items-center justify-center text-secondary hover:bg-neutral-50"><i class="fas fa-minus text-xs"></i></button>
                            <div class="w-8 h-full flex items-center justify-center font-bold text-neutral-800 text-sm border-x border-neutral-100">
                                <span id="adultsCount"><?php echo $adults; ?></span>
                                <input type="hidden" name="adults" id="adultsHidden" value="<?php echo $adults; ?>">
                            </div>
                            <button type="button" onclick="event.stopPropagation(); updateGuestCount('adults', 1)" class="flex-1 h-full flex items-center justify-center text-secondary hover:bg-neutral-50"><i class="fas fa-plus text-xs"></i></button>
                        </div>
                    </div>
                    <div class="flex items-center justify-between mb-5">
                        <span class="font-bold text-neutral-800">Children</span>
                        <div class="flex items-center border border-neutral-200 rounded-lg overflow-hidden h-9 w-28">
                            <button type="button" onclick="event.stopPropagation(); updateGuestCount('children', -1)" class="flex-1 h-full flex items-center justify-center text-secondary hover:bg-neutral-50"><i class="fas fa-minus text-xs"></i></button>
                            <div class="w-8 h-full flex items-center justify-center font-bold text-neutral-800 text-sm border-x border-neutral-100">
                                <span id="childrenCount"><?php echo $children; ?></span>
                                <input type="hidden" name="children" id="childrenHidden" value="<?php echo $children; ?>">
                            </div>
                            <button type="button" onclick="event.stopPropagation(); updateGuestCount('children', 1)" class="flex-1 h-full flex items-center justify-center text-secondary hover:bg-neutral-50"><i class="fas fa-plus text-xs"></i></button>
                        </div>
                    </div>
                    <button type="button" onclick="event.stopPropagation(); toggleGuestDropdown()" class="w-full py-2 bg-secondary text-white rounded-lg font-bold hover:bg-primary transition-colors text-sm">Done</button>
                </div>
            </div>

            <button type="submit" class="search-btn-v2">Search</button>
        </form>
    </section>


    <!-- Trending Destinations -->
    <section class="max-w-[1400px] mx-auto px-4 lg:px-6 mt-52 md:mt-16">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl md:text-3xl font-bold text-neutral-800 tracking-tight">Trending destinations in Sri Lanka</h2>
                <p class="text-text-secondary font-medium">Most popular choices for travelers from Sri Lanka</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <a href="hotels.php?q=Kandy" class="group cursor-pointer block">
                <div class="relative h-[220px] rounded-xl overflow-hidden mb-3">
                    <img src="assets/destinations/kandy.png" alt="Kandy"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                    <div class="absolute bottom-4 left-4 text-white">
                        <h3 class="font-bold text-xl">Kandy</h3>
                        <p class="text-sm opacity-90">Temple visits, botanical gardens & lake walks</p>
                    </div>
                </div>
            </a>
            <a href="hotels.php?q=Nuwara+Eliya" class="group cursor-pointer block">
                <div class="relative h-[220px] rounded-xl overflow-hidden mb-3">
                    <img src="assets/destinations/nuwara_eliya.png" alt="Nuwara Eliya"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                    <div class="absolute bottom-4 left-4 text-white">
                        <h3 class="font-bold text-xl">Nuwara Eliya</h3>
                        <p class="text-sm opacity-90">Tea plantations & cool hill-country breeze</p>
                    </div>
                </div>
            </a>
            <a href="hotels.php?q=Galle" class="group cursor-pointer block">
                <div class="relative h-[220px] rounded-xl overflow-hidden mb-3">
                    <img src="assets/destinations/galle.png" alt="Galle"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                    <div class="absolute bottom-4 left-4 text-white">
                        <h3 class="font-bold text-xl">Galle</h3>
                        <p class="text-sm opacity-90">Fort walks & coastal sunsets</p>
                    </div>
                </div>
            </a>
            <a href="hotels.php?q=Negombo" class="group cursor-pointer block">
                <div class="relative h-[220px] rounded-xl overflow-hidden mb-3">
                    <img src="assets/destinations/negombo.png" alt="Negombo"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                    <div class="absolute bottom-4 left-4 text-white">
                        <h3 class="font-bold text-xl">Negombo</h3>
                        <p class="text-sm opacity-90">Beachside relaxation & lagoon boating</p>
                    </div>
                </div>
            </a>
            <a href="hotels.php?q=Colombo" class="group cursor-pointer block">
                <div class="relative h-[220px] rounded-xl overflow-hidden mb-3">
                    <img src="assets/destinations/colombo.png" alt="Colombo"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                    <div class="absolute bottom-4 left-4 text-white">
                        <h3 class="font-bold text-xl">Colombo</h3>
                        <p class="text-sm opacity-90">City tours, parks & street food</p>
                    </div>
                </div>
            </a>
            <a href="hotels.php?q=Anuradhapura" class="group cursor-pointer block">
                <div class="relative h-[220px] rounded-xl overflow-hidden mb-3">
                    <img src="assets/destinations/anuradhapura.png" alt="Anuradhapura"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                    <div class="absolute bottom-4 left-4 text-white">
                        <h3 class="font-bold text-xl">Anuradhapura</h3>
                        <p class="text-sm opacity-90">Ancient ruins & sacred temples</p>
                    </div>
                </div>
            </a>
        </div>
    </section>



    <!-- Browse by property type -->
    <section class="max-w-[1400px] mx-auto px-4 lg:px-6 mt-16">
        <h2 class="text-2xl md:text-3xl font-bold text-neutral-800 tracking-tight mb-6">Browse by property type</h2>
        <div class="flex overflow-x-auto no-scrollbar gap-4 pb-4 snap-x">
            <?php
            $type_meta = [
                'hotel' => ['label' => 'Hotels', 'img' => 'assets/hotel_category.png'],
                'villa' => ['label' => 'Villas', 'img' => 'assets/villa_category.png'],
                'resort' => ['label' => 'Resorts', 'img' => 'assets/resort_category.png'],
                'dayouts' => ['label' => 'Dayouts', 'img' => 'assets/dayouts_category.png'],
                'apartment' => ['label' => 'Apartments', 'img' => 'assets/apartment_category.png'],
                'hostel' => ['label' => 'Hostels', 'img' => 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&w=400&q=80'],
                'reception_hall' => ['label' => 'Reception Halls', 'img' => 'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?auto=format&fit=crop&w=400&q=80'],
                'rest_hall' => ['label' => 'Rest Halls', 'img' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=400&q=80'],
            ];

            // Core types to show prominently
            $core_types = ['hotel', 'villa', 'resort', 'dayouts', 'apartment', 'hostel', 'reception_hall'];
            $display_types = array_unique(array_merge($core_types, $db_property_types));

            foreach ($display_types as $p_type):
                $p_type_lower = strtolower($p_type);
                $meta = $type_meta[$p_type_lower] ?? ['label' => ucfirst(str_replace('_', ' ', $p_type)), 'img' => 'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=400&q=80'];
                
                // Special handling for Dayouts link
                $link = ($p_type_lower === 'dayouts') ? 'srilanka_dayouts.php' : 'hotels.php?type=' . urlencode($p_type);
                ?>
                <a href="<?php echo $link; ?>"
                    class="min-w-[260px] snap-start group cursor-pointer">
                    <div class="relative h-[180px] rounded-xl overflow-hidden mb-3">
                        <img src="<?php echo $meta['img']; ?>" alt="<?php echo $meta['label']; ?>"
                            class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                    </div>
                    <h3 class="font-bold text-neutral-800 text-lg group-hover:text-primary transition-colors">
                        <?php echo $meta['label']; ?>
                    </h3>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    
    <!-- Featured Hotels Section -->
    <?php if (!empty($featured_hotels)): ?>
    <section class="max-w-[1400px] mx-auto px-4 lg:px-6 mt-12 mb-16">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-primary">Featured Hotels</h2>
                <p class="text-text-secondary text-sm">Explore the top-rated boosted properties across Sri Lanka</p>
            </div>
            <div class="flex gap-2">
                <button onclick="scrollSlider('hotel-slider', -1)" class="w-10 h-10 rounded-full border border-neutral-200 flex items-center justify-center hover:bg-neutral-50 transition-colors shadow-sm">
                    <i class="fas fa-chevron-left text-primary"></i>
                </button>
                <button onclick="scrollSlider('hotel-slider', 1)" class="w-10 h-10 rounded-full border border-neutral-200 flex items-center justify-center hover:bg-neutral-50 transition-colors shadow-sm">
                    <i class="fas fa-chevron-right text-primary"></i>
                </button>
            </div>
        </div>
        <div id="hotel-slider" class="flex gap-4 overflow-x-auto snap-x snap-mandatory no-scrollbar scroll-smooth pb-4">
            <?php foreach ($featured_hotels as $hotel): ?>
                <div class="min-w-[280px] md:min-w-[320px] bg-white rounded-xl shadow-sm border border-neutral-100 overflow-hidden snap-start hover:shadow-md transition-shadow group">
                    <div class="relative h-48 overflow-hidden">
                        <img src="<?= htmlspecialchars($hotel['first_room_image'] ?: 'assets/placeholder_hotel.jpg') ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute top-3 right-3 bg-secondary text-white text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider shadow-sm">Featured</div>
                    </div>
                    <div class="p-4">
                        <h3 class="font-bold text-primary truncate mb-1"><?= htmlspecialchars($hotel['property_name']) ?></h3>
                        <div class="flex items-center gap-1.5 text-text-secondary text-xs mb-4">
                            <i class="fas fa-location-dot text-secondary/70"></i>
                            <span class="truncate"><?= htmlspecialchars($hotel['district']) ?>, <?= htmlspecialchars($hotel['closest_main_town']) ?></span>
                        </div>
                        <div class="flex items-center justify-between pt-4 border-t border-neutral-50">
                            <div>
                                <p class="text-[10px] text-text-secondary uppercase font-bold tracking-tighter">Starting from</p>
                                <p class="text-lg font-bold text-primary">LKR <?= number_format($hotel['price_lkr']) ?></p>
                            </div>
                            <a href="property_details.php?id=<?= $hotel['id'] ?>" class="bg-secondary hover:bg-primary text-white px-4 py-2 rounded-lg text-sm font-bold transition-colors shadow-sm">Book Now</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Featured Dayouts Section -->
    <?php if (!empty($featured_dayouts)): ?>
    <section class="max-w-[1400px] mx-auto px-4 lg:px-6 mt-12 mb-16">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-primary">Featured Dayouts</h2>
                <p class="text-text-secondary text-sm">Best single-day experiences for you and your family</p>
            </div>
            <div class="flex gap-2">
                <button onclick="scrollSlider('dayout-slider', -1)" class="w-10 h-10 rounded-full border border-neutral-200 flex items-center justify-center hover:bg-neutral-50 transition-colors shadow-sm">
                    <i class="fas fa-chevron-left text-primary"></i>
                </button>
                <button onclick="scrollSlider('dayout-slider', 1)" class="w-10 h-10 rounded-full border border-neutral-200 flex items-center justify-center hover:bg-neutral-50 transition-colors shadow-sm">
                    <i class="fas fa-chevron-right text-primary"></i>
                </button>
            </div>
        </div>
        <div id="dayout-slider" class="flex gap-4 overflow-x-auto snap-x snap-mandatory no-scrollbar scroll-smooth pb-4">
            <?php foreach ($featured_dayouts as $dayout): ?>
                <div class="min-w-[280px] md:min-w-[320px] bg-white rounded-xl shadow-sm border border-neutral-100 overflow-hidden snap-start hover:shadow-md transition-shadow group">
                    <div class="relative h-48 overflow-hidden">
                        <img src="<?= htmlspecialchars($dayout['first_room_image'] ?: 'assets/placeholder_dayout.jpg') ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <div class="absolute top-3 right-3 bg-[#febb02] text-primary text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider shadow-sm">Top Pick</div>
                    </div>
                    <div class="p-4">
                        <h3 class="font-bold text-primary truncate mb-1"><?= htmlspecialchars($dayout['property_name']) ?></h3>
                        <div class="flex items-center gap-1.5 text-text-secondary text-xs mb-4">
                            <i class="fas fa-location-dot text-secondary/70"></i>
                            <span class="truncate"><?= htmlspecialchars($dayout['district']) ?>, <?= htmlspecialchars($dayout['closest_main_town']) ?></span>
                        </div>
                        <div class="flex items-center justify-between pt-4 border-t border-neutral-50">
                            <div>
                                <p class="text-[10px] text-text-secondary uppercase font-bold tracking-tighter">Package price</p>
                                <p class="text-lg font-bold text-primary">LKR <?= number_format($dayout['price_lkr']) ?></p>
                            </div>
                            <a href="property_details.php?id=<?= $dayout['id'] ?>" class="bg-secondary hover:bg-primary text-white px-4 py-2 rounded-lg text-sm font-bold transition-colors shadow-sm">Book Now</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

   

    <script>
        function scrollSlider(id, direction) {
            const slider = document.getElementById(id);
            const scrollAmount = 340; // Card width + gap
            slider.scrollBy({
                left: direction * scrollAmount,
                behavior: 'smooth'
            });
        }

        // Auto-scroll sliders
        function autoScrollSlider(id) {
            const slider = document.getElementById(id);
            if (!slider) return;
            
            setInterval(() => {
                if (slider.scrollLeft + slider.offsetWidth >= slider.scrollWidth) {
                    slider.scrollTo({ left: 0, behavior: 'smooth' });
                } else {
                    slider.scrollBy({ left: 340, behavior: 'smooth' });
                }
            }, 5000);
        }

        autoScrollSlider('hotel-slider');
        autoScrollSlider('dayout-slider');
    </script>

    <!-- Visit Sri Lanka Section -->
    <section class="max-w-[1400px] mx-auto px-4 lg:px-6 mt-16">
        <div class="w-full bg-blue-700 rounded-[2rem] overflow-hidden flex flex-col md:flex-row shadow-2xl">
            <div class="flex-1 p-8 md:p-12 lg:p-16 flex flex-col justify-center text-white">
                <h2 class="text-3xl md:text-4xl lg:text-5xl font-['Outfit'] mb-2 tracking-tight">Visit Sri Lanka</h2>
                <h3 class="text-lg md:text-xl font-bold mb-8 text-white/90">Experience the Glory of this beautiful Island</h3>
                
                <p class="text-[14px] md:text-[15px] leading-relaxed text-white/90 mb-6 font-medium">
                    Visit Sri Lanka to witness the outstanding beauty and hospitality. Sri Lanka is known to be the "Paradise" of the Indian Ocean for its amazing beauty and incomparable richness in natural resources. The country is extremely famous for beautiful soothing tourist destinations and great hospitality of the Sri Lankans. Checkout our Sri Lankan Map which is completed with all known tourist destinations.
                </p>
                
                <p class="text-[14px] md:text-[15px] leading-relaxed text-white/90 mb-10 font-medium">
                    Tourist destinations in Sri Lanka provide a great and unforgettable holiday experience to all the people who visit Sri Lanka.
                </p>
                
             
            </div>
            <div class="flex-1 min-h-[400px] md:min-h-auto relative" id="sl-slideshow">
                <img src="https://images.unsplash.com/photo-1586500036706-41963de24d8b?auto=format&fit=crop&w=800&q=80" alt="Sri Lanka Palm Trees" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-1000 opacity-100">
                <img src="https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=800&q=80" alt="Sri Lanka Temples" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-1000 opacity-0">
                <img src="https://images.unsplash.com/photo-1552465011-b4e21bf6e79a?auto=format&fit=crop&w=800&q=80" alt="Sri Lanka Wildlife" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-1000 opacity-0">
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const slSlides = document.querySelectorAll('#sl-slideshow img');
            if(slSlides.length > 0) {
                let currentSlSlide = 0;
                setInterval(() => {
                    slSlides[currentSlSlide].classList.remove('opacity-100');
                    slSlides[currentSlSlide].classList.add('opacity-0');
                    currentSlSlide = (currentSlSlide + 1) % slSlides.length;
                    slSlides[currentSlSlide].classList.remove('opacity-0');
                    slSlides[currentSlSlide].classList.add('opacity-100');
                }, 4000);
            }
        });
    </script>

    <!-- Why Choose Us -->
    <section class="max-w-[1400px] mx-auto px-4 lg:px-6 mt-28 mb-16">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-black font-['Outfit'] text-neutral-800 tracking-tight">Why Book With Us?</h2>
            <p class="text-neutral-500 font-medium mt-3 max-w-xl mx-auto">Experience the best of Sri Lanka with our trusted and reliable travel platform.</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">
            <!-- Feature 1 -->
            <div class="bg-white rounded-3xl p-8 border border-neutral-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] transition-all duration-300 group">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-[#003580]/10 to-[#006ce4]/10 flex items-center justify-center text-primary mb-6 group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-shield-alt text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-neutral-800 mb-3">Secure Bookings</h3>
                <p class="text-[15px] text-neutral-500 leading-relaxed font-medium">Your data is safe with our robust 256-bit SSL encrypted payment gateway, ensuring total peace of mind.</p>
            </div>
            
            <!-- Feature 2 -->
            <div class="bg-white rounded-3xl p-8 border border-neutral-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] transition-all duration-300 group">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-[#10b981]/10 to-[#059669]/10 flex items-center justify-center text-[#10b981] mb-6 group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-headset text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-neutral-800 mb-3">24/7 Support</h3>
                <p class="text-[15px] text-neutral-500 leading-relaxed font-medium">Our dedicated local team is here to assist you anytime, anywhere during your stay in Sri Lanka.</p>
            </div>
            
            <!-- Feature 3 -->
            <div class="bg-white rounded-3xl p-8 border border-neutral-100 shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] transition-all duration-300 group">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-[#febb02]/10 to-[#e0a800]/10 flex items-center justify-center text-[#e0a800] mb-6 group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-thumbs-up text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-neutral-800 mb-3">Best Price Guarantee</h3>
                <p class="text-[15px] text-neutral-500 leading-relaxed font-medium">Found a lower price? We'll match it and give you an extra 5% off to guarantee the best value.</p>
            </div>
        </div>
    </section>

    <?php if (isset($_SESSION['user_id']) && !$user_has_properties): ?>
        <!-- Property Listing CTA for New/Propertyless Owners -->
        <section class="max-w-[1400px] mx-auto px-4 lg:px-6 mt-16">
            <div
                class="relative overflow-hidden bg-primary rounded-[2.5rem] p-8 md:p-16 flex flex-col md:flex-row items-center gap-12 group">
                <!-- Background Decoration -->
                <div
                    class="absolute -right-20 -top-20 w-96 h-96 bg-white/5 rounded-full blur-3xl transition-all group-hover:scale-110">
                </div>
                <div class="absolute -left-20 -bottom-20 w-96 h-96 bg-[#10b981]/10 rounded-full blur-3xl"></div>

                <div class="relative z-10 flex-1">
                    <div
                        class="inline-flex items-center gap-2 px-4 py-2 bg-[#10b981]/20 text-[#10b981] rounded-full text-xs font-black uppercase tracking-[0.2em] mb-6">
                        <i class="fas fa-gift"></i> Limited Offer
                    </div>
                    <h2 class="text-3xl md:text-5xl font-black text-white leading-tight mb-6">List your property & get a
                        <span class="text-[#10b981]">Free Management System</span>
                    </h2>
                    <p class="text-lg text-white/70 max-w-xl mb-8 leading-relaxed font-medium">Join thousands of property
                        owners in Sri Lanka. Manage bookings, tracks expenses, and grow your business with our all-in-one
                        platform.</p>

                    <div class="flex flex-wrap gap-4">
                        <a href="property_wizard.php"
                            class="bg-[#10b981] text-white px-8 py-4 rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-[#059669] transition-all shadow-xl shadow-[#10b981]/20 flex items-center gap-3">
                            <i class="fas fa-plus-circle"></i> Start Listing Now
                        </a>
                        <div
                            class="flex items-center gap-3 px-6 py-4 bg-white/5 rounded-2xl border border-white/10 text-white/80 font-bold text-sm">
                            <i class="fas fa-check text-[#10b981]"></i> No hidden fees
                        </div>
                    </div>
                </div>

                <div class="relative z-10 w-full md:w-1/3 flex justify-center">
                    <div class="relative">
                        <div class="w-64 h-64 bg-white/10 rounded-full flex items-center justify-center animate-pulse">
                            <i class="fas fa-hotel text-8xl text-white/20"></i>
                        </div>
                        <div
                            class="absolute -bottom-4 -right-4 bg-[#febb02] p-6 rounded-3xl shadow-2xl rotate-12 group-hover:rotate-0 transition-transform duration-500">
                            <i class="fas fa-chart-line text-4xl text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <!-- Publish Advertisements CTA -->
    <section class="max-w-[1400px] mx-auto px-4 lg:px-6 my-24">
        <div class="bg-[#0b5cce] rounded-[1.5rem] p-8 md:p-12 flex flex-col md:flex-row items-center justify-between gap-10 shadow-xl border border-blue-600/30">
            
            <div class="flex-1 max-w-xl text-white">
                <h2 class="text-3xl md:text-4xl font-black font-['Outfit'] leading-tight mb-4 tracking-tight">Publish your advertisements with us</h2>
                <p class="text-white/90 text-[15px] md:text-[17px] font-medium leading-relaxed mb-8 max-w-[480px]">
                    Reach thousands of travelers across Sri Lanka. Boost your visibility and grow your tourism business with our premium advertising spots.
                </p>
                <div class="flex flex-wrap items-center gap-4">
                    <a href="addnewadd.php" class="inline-flex items-center justify-center bg-[#ffc107] text-[#1a1a1a] px-6 py-3 rounded-xl font-bold text-[15px] hover:bg-[#e0a800] transition-colors shadow-sm no-underline">
                        Get Started Now
                    </a>
                    
                </div>
            </div>
            
            <div class="flex-1 w-full max-w-[600px] relative">
                <div class="aspect-[16/9] md:aspect-[5/3] w-full rounded-2xl overflow-hidden shadow-2xl relative border border-white/10 bg-white/5">
                    <img src="assets/ads_marketing_illustration.png" alt="Digital Marketing and Advertising" class="w-full h-full object-cover mix-blend-luminosity hover:mix-blend-normal transition-all duration-500">
                    <!-- Subtle overlay to ensure it matches the vibe -->
                    <div class="absolute inset-0 bg-blue-900/20 mix-blend-overlay"></div>
                </div>
            </div>
        </div>
    </section>

    <script>
        function toggleGuestDropdown() {
            const popup = document.getElementById('guestPopup');
            const overlay = document.getElementById('guestOverlay');
            const chevron = document.getElementById('guestChevron');
            const isHidden = popup.classList.contains('hidden');

            if (isHidden) {
                popup.classList.remove('hidden');
                overlay.classList.remove('hidden');
                chevron.classList.add('rotate-180');
                // Prevent scrolling on mobile when dropdown is open
                if (window.innerWidth < 768) {
                    document.body.style.overflow = 'hidden';
                }
            } else {
                popup.classList.add('hidden');
                overlay.classList.add('hidden');
                chevron.classList.remove('rotate-180');
                document.body.style.overflow = '';
            }
        }

        function updateGuestCount(type, change) {
            const countSpan = document.getElementById(type + 'Count');
            const hiddenInput = document.getElementById(type + 'Hidden');
            const displaySpan = document.getElementById('guestInputDisplay');

            let currentCount = parseInt(countSpan.innerText);
            let newCount = currentCount + change;

            // Validation
            if (type === 'adults' && newCount < 1) newCount = 1;
            if (type === 'children' && newCount < 0) newCount = 0;

            countSpan.innerText = newCount;
            hiddenInput.value = newCount;

            // Update display text
            const adults = document.getElementById('adultsCount').innerText;
            const children = document.getElementById('childrenCount').innerText;
            displaySpan.innerText = `${adults} adults · ${children} children`;
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function (event) {
            const container = document.getElementById('guestDropdownContainer');
            const popup = document.getElementById('guestPopup');
            const chevron = document.getElementById('guestChevron');

            if (!container.contains(event.target)) {
                popup.classList.add('hidden');
                chevron.classList.remove('rotate-180');
            }
        });

        // Initialize counts on load
        window.addEventListener('DOMContentLoaded', () => {
            const adults = document.getElementById('adultsHidden').value;
            const children = document.getElementById('childrenHidden').value;
            document.getElementById('adultsCount').innerText = adults;
            document.getElementById('childrenCount').innerText = children;
        });
    </script>
    <?php include 'footer.php'; ?>
    <!-- Guest Welcome Modal -->
    <?php if (!isset($_SESSION['user_id'])): ?>
        <div id="welcomeModal" class="fixed inset-0 z-[1000] flex items-center justify-center px-4">
            <!-- Backdrop -->
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeModal()"></div>
            
            <!-- Modal Content -->
            <div class="relative bg-white p-8 md:p-10 rounded-3xl shadow-2xl max-w-[420px] w-full text-center animate-fade-in border border-neutral-100">
                <!-- Close Button -->
                <button onclick="closeModal()" class="absolute top-4 right-4 text-neutral-300 hover:text-neutral-500 transition-colors">
                    <i class="fas fa-times text-lg"></i>
                </button>

                <!-- Logo Only (No round background, larger) -->
                <div class="mb-6 flex justify-center">
                    <img src="assets/logo.png" alt="Bookingjaunt" class="w-48 h-auto object-contain">
                </div>

                <h2 class="text-2xl font-black text-[#003580] leading-tight mb-3">
                    Register with Bookingjaunt
                </h2>

                <p class="text-neutral-500 text-sm mb-8 leading-relaxed font-medium">
                    Manage bookings, avoid overbooking, and reach more customers across Sri Lanka.
                </p>

                <div class="space-y-3">
                    <a href="register.php" class="block w-full bg-[#5f6de4] hover:bg-[#4a58d1] text-white py-3.5 rounded-2xl font-black text-base transition-all shadow-lg shadow-[#5f6de4]/20 hover:scale-[1.02] no-underline">
                        Register Now
                    </a>
                    <button onclick="closeModal()" class="block w-full text-neutral-400 hover:text-neutral-500 py-1 font-bold text-sm transition-colors">
                        Explore Later
                    </button>
                </div>

                <p class="mt-8 text-[10px] text-neutral-400 font-bold uppercase tracking-widest leading-none">
                    Built in Sri Lanka for Sri Lankan property owners
                </p>
            </div>
        </div>

        <script>
            function closeModal() {
                const modal = document.getElementById('welcomeModal');
                modal.style.opacity = '0';
                modal.style.transition = 'opacity 0.3s ease';
                setTimeout(() => {
                    modal.classList.add('hidden');
                }, 300);
            }
        </script>
    <?php endif; ?>
</body>

</html>
