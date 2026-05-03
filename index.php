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

    <!-- Hero Section -->
    <section class="relative h-[400px] md:h-[550px] flex flex-col justify-center px-[10%] text-white bg-cover bg-center"
        style="background-image: linear-gradient(rgba(0, 53, 128, 0.7), rgba(0, 108, 228, 0.4)), url('assets/hero_bg.png');">
        <!-- Pattern Overlay -->
        <div class="absolute inset-0 opacity-10 pointer-events-none"
            style="background-image: url('https://www.transparenttextures.com/patterns/cubes.png');"></div>

        <div
            class="absolute top-10 right-[10%] hidden md:flex items-center gap-4 text-3xl font-bold z-10 text-white/90">
            <i class="fas fa-phone-alt"></i> +94 1000000
        </div>

        <div class="relative z-10 max-w-2xl">
            <img src="assets/t_logo.png" alt="Bookingjaunt" class="w-52 h-auto mb-1 drop-shadow-2xl object-cover">
            <h1 class="text-5xl md:text-6xl font-bold mb-4 drop-shadow-xl tracking-tight">Discover Sri Lanka</h1>
            <p class="text-xl md:text-2xl font-medium drop-shadow-md text-white/90">Find your next paradise stay from
                luxury hotels to tropical villas.</p>
        </div>

        <!-- Search Bar -->
        <form action="index.php" method="GET"
            class="absolute -bottom-[190px] md:-bottom-[40px] left-1/2 -translate-x-1/2 w-[94%] md:w-[80%] max-w-[1100px] bg-[#10b981] p-1 md:p-1.5 rounded-xl md:rounded-2xl flex flex-col md:flex-row shadow-[0_10px_30px_rgba(0,0,0,0.2)] md:shadow-[0_20px_50px_rgba(0,0,0,0.3)] border border-white/20 z-20">

            <input type="hidden" name="type" value="<?php echo htmlspecialchars($type); ?>">

            <!-- Row 1: Location -->
            <div
                class="flex-[1.5] bg-white m-0.5 p-3 md:p-4 rounded-t-lg md:rounded-xl flex items-center gap-3 text-neutral-800">
                <i class="fas fa-search text-neutral-600 md:text-[#10b981] md:text-xl"></i>
                <input type="text" name="q" value="<?php echo htmlspecialchars($q); ?>"
                    placeholder="Around current location"
                    class="border-none outline-none w-full text-[15px] font-bold md:font-medium placeholder:text-neutral-800 md:placeholder:text-neutral-400">
            </div>

            <!-- Row 2: Dates -->
            <div class="flex-1 flex m-0.5 gap-1 md:gap-0 bg-transparent md:bg-white md:rounded-xl">
                <!-- Dates Container (Combined for Desktop) -->
                <div
                    class="flex-1 bg-white p-2 px-3 md:p-0 md:rounded-xl flex flex-row items-center gap-2 md:gap-0 overflow-hidden">
                    <div class="md:flex-1 h-full flex flex-col md:flex-row md:items-center relative">
                        <i class="far fa-calendar-alt text-[#10b981] hidden md:inline-block text-xl ml-4 mr-2"></i>
                        <div class="flex flex-col flex-1">
                            <span
                                class="text-[10px] text-neutral-400 font-bold uppercase tracking-tighter md:hidden">Check-in</span>
                            <input type="date" name="checkin" value="<?php echo htmlspecialchars($checkin); ?>"
                                class="border-none outline-none text-[13px] md:text-[14px] font-bold md:font-medium text-neutral-800 bg-transparent w-full">
                        </div>
                    </div>
                    <div class="hidden md:block w-[1px] h-8 bg-neutral-100"></div>
                    <div class="md:flex-1 h-full flex flex-col md:flex-row md:items-center">
                        <div class="flex flex-col flex-1 md:pl-3">
                            <span
                                class="text-[10px] text-neutral-400 font-bold uppercase tracking-tighter md:hidden">Check-out</span>
                            <input type="date" name="checkout" value="<?php echo htmlspecialchars($checkout); ?>"
                                class="border-none outline-none text-[13px] md:text-[14px] font-bold md:font-medium text-neutral-800 bg-transparent w-full">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 3: Guests Dropdown -->
            <div class="flex-1 flex m-0.5 relative" id="guestDropdownContainer">
                <div class="flex-1 bg-white p-3 md:p-4 md:rounded-xl flex items-center gap-3 text-neutral-800 cursor-pointer select-none"
                    onclick="toggleGuestDropdown()">
                    <i class="fas fa-user-friends text-[#10b981] text-xl"></i>
                    <div class="flex flex-col flex-1 overflow-hidden">
                        <span class="text-[14px] font-bold md:font-medium text-neutral-800 truncate"
                            id="guestInputDisplay">
                            <?php echo $adults; ?> adults · <?php echo $children; ?> children
                        </span>
                    </div>
                    <i class="fas fa-chevron-down text-neutral-400 text-xs transition-transform duration-300"
                        id="guestChevron"></i>
                </div>

                <!-- Dropdown Popup -->
                <div id="guestPopup"
                    class="fixed inset-x-4 bottom-10 md:absolute md:top-[calc(100%+8px)] md:left-0 md:right-auto md:bottom-auto md:w-[360px] bg-white rounded-2xl shadow-[0_10px_50px_rgba(0,0,0,0.3)] border border-neutral-100 p-6 z-[100] hidden animate-in fade-in slide-in-from-bottom-4 md:slide-in-from-top-2 duration-300">
                    <!-- Adults Row -->
                    <div class="flex items-center justify-between mb-6">
                        <span class="font-bold text-neutral-800 text-lg">Adults</span>
                        <div class="flex items-center border border-neutral-300 rounded-lg overflow-hidden h-12 w-44">
                            <button type="button" onclick="updateGuestCount('adults', -1)"
                                class="flex-1 h-full flex items-center justify-center text-secondary hover:bg-neutral-50 transition-colors">
                                <i class="fas fa-minus text-sm"></i>
                            </button>
                            <div
                                class="w-14 h-full flex items-center justify-center font-bold text-neutral-800 text-lg border-x border-neutral-100">
                                <span id="adultsCount">2</span>
                                <input type="hidden" name="adults" id="adultsHidden" value="<?php echo $adults; ?>">
                            </div>
                            <button type="button" onclick="updateGuestCount('adults', 1)"
                                class="flex-1 h-full flex items-center justify-center text-secondary hover:bg-neutral-50 transition-colors">
                                <i class="fas fa-plus text-sm"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Children Row -->
                    <div class="flex items-center justify-between mb-8">
                        <span class="font-bold text-neutral-800 text-lg">Children</span>
                        <div class="flex items-center border border-neutral-300 rounded-lg overflow-hidden h-12 w-44">
                            <button type="button" onclick="updateGuestCount('children', -1)"
                                class="flex-1 h-full flex items-center justify-center text-secondary hover:bg-neutral-50 transition-colors">
                                <i class="fas fa-minus text-sm"></i>
                            </button>
                            <div
                                class="w-14 h-full flex items-center justify-center font-bold text-neutral-800 text-lg border-x border-neutral-100">
                                <span id="childrenCount">0</span>
                                <input type="hidden" name="children" id="childrenHidden"
                                    value="<?php echo $children; ?>">
                            </div>
                            <button type="button" onclick="updateGuestCount('children', 1)"
                                class="flex-1 h-full flex items-center justify-center text-secondary hover:bg-neutral-50 transition-colors">
                                <i class="fas fa-plus text-sm"></i>
                            </button>
                        </div>
                    </div>

                    <button type="button" onclick="toggleGuestDropdown()"
                        class="w-full py-3.5 bg-white border border-secondary text-secondary rounded-xl font-bold hover:bg-secondary/5 transition-colors text-lg">Done</button>
                </div>

                <!-- Mobile Overlay -->
                <div id="guestOverlay" class="fixed inset-0 bg-black/40 z-[90] hidden md:hidden"
                    onclick="toggleGuestDropdown()"></div>
            </div>

            <!-- Search Button -->
            <div class="flex flex-col md:flex-row gap-1 m-0.5 mt-1 md:mt-0">
                <button type="submit"
                    class="bg-[#003580] hover:bg-[#002b66] text-white px-8 py-3.5 md:py-4 rounded-b-lg md:rounded-xl font-bold text-[18px] md:text-[16px] transition-all shadow-md active:scale-95">Search</button>
                <?php if (!empty($q) || !empty($checkin) || !empty($checkout)): ?>
                    <a href="index.php"
                        class="bg-white/20 hover:bg-white/30 text-white px-4 py-3.5 md:py-4 rounded-xl flex items-center justify-center transition-all">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <!-- Trending Destinations -->
    <section class="max-w-[1400px] mx-auto px-4 lg:px-6 mt-52 md:mt-16">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl md:text-3xl font-bold text-neutral-800 tracking-tight">Trending destinations</h2>
                <p class="text-text-secondary font-medium">Most popular choices for travelers from Sri Lanka</p>
            </div>
            <div class="hidden md:flex gap-2">
                <button
                    class="w-10 h-10 rounded-full border border-neutral-200 flex items-center justify-center hover:bg-neutral-50 transition-colors shadow-sm">
                    <i class="fas fa-chevron-left text-sm text-neutral-400"></i>
                </button>
                <button
                    class="w-10 h-10 rounded-full border border-neutral-200 flex items-center justify-center hover:bg-neutral-50 transition-colors shadow-sm">
                    <i class="fas fa-chevron-right text-sm text-neutral-400"></i>
                </button>
            </div>
        </div>

        <div class="flex overflow-x-auto no-scrollbar gap-4 pb-4 snap-x">
            <div class="min-w-[200px] md:min-w-[220px] snap-start group cursor-pointer">
                <div class="relative h-[150px] rounded-xl overflow-hidden mb-3">
                    <img src="assets/destinations/negombo.png" alt="Negombo"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                </div>
                <h3 class="font-bold text-neutral-800 text-lg group-hover:text-primary transition-colors">Negombo</h3>
            </div>
            <div class="min-w-[200px] md:min-w-[220px] snap-start group cursor-pointer">
                <div class="relative h-[150px] rounded-xl overflow-hidden mb-3">
                    <img src="assets/destinations/colombo.png" alt="Colombo"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                </div>
                <h3 class="font-bold text-neutral-800 text-lg group-hover:text-primary transition-colors">Colombo</h3>
            </div>
            <div class="min-w-[200px] md:min-w-[220px] snap-start group cursor-pointer">
                <div class="relative h-[150px] rounded-xl overflow-hidden mb-3">
                    <img src="assets/destinations/kandy.png" alt="Kandy"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                </div>
                <h3 class="font-bold text-neutral-800 text-lg group-hover:text-primary transition-colors">Kandy</h3>
            </div>
            <div class="min-w-[200px] md:min-w-[220px] snap-start group cursor-pointer">
                <div class="relative h-[150px] rounded-xl overflow-hidden mb-3">
                    <img src="assets/destinations/nuwara_eliya.png" alt="Nuwara Eliya"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                </div>
                <h3 class="font-bold text-neutral-800 text-lg group-hover:text-primary transition-colors">Nuwara Eliya
                </h3>
            </div>
            <div class="min-w-[200px] md:min-w-[220px] snap-start group cursor-pointer">
                <div class="relative h-[150px] rounded-xl overflow-hidden mb-3">
                    <img src="assets/destinations/galle.png" alt="Galle"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                </div>
                <h3 class="font-bold text-neutral-800 text-lg group-hover:text-primary transition-colors">Galle</h3>
            </div>
            <div class="min-w-[200px] md:min-w-[220px] snap-start group cursor-pointer">
                <div class="relative h-[150px] rounded-xl overflow-hidden mb-3">
                    <img src="assets/destinations/anuradhapura.png" alt="Anuradhapura"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
                </div>
                <h3 class="font-bold text-neutral-800 text-lg group-hover:text-primary transition-colors">Anuradhapura
                </h3>
            </div>
        </div>
    </section>

    <!-- Browse by property type -->
    <section class="max-w-[1400px] mx-auto px-4 lg:px-6 mt-16">
        <h2 class="text-2xl md:text-3xl font-bold text-neutral-800 tracking-tight mb-6">Browse by property type</h2>
        <div class="flex overflow-x-auto no-scrollbar gap-4 pb-4 snap-x">
            <?php
            $type_meta = [
                'hotel' => ['label' => 'Hotels', 'img' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=400&q=80'],
                'apartment' => ['label' => 'Apartments', 'img' => 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=400&q=80'],
                'resort' => ['label' => 'Resorts', 'img' => 'https://images.unsplash.com/photo-1540541338287-41700207dee6?auto=format&fit=crop&w=400&q=80'],
                'villa' => ['label' => 'Villas', 'img' => 'https://images.unsplash.com/photo-1580587771525-78b9bed3b928?auto=format&fit=crop&w=400&q=80'],
                'hostel' => ['label' => 'Hostels', 'img' => 'https://images.unsplash.com/photo-1555854877-bab0e564b8d5?auto=format&fit=crop&w=400&q=80'],
                'reception_hall' => ['label' => 'Reception Halls', 'img' => 'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?auto=format&fit=crop&w=400&q=80'],
                'rest_hall' => ['label' => 'Rest Halls', 'img' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=400&q=80'],
            ];

            foreach ($db_property_types as $p_type):
                $meta = $type_meta[strtolower($p_type)] ?? ['label' => ucfirst(str_replace('_', ' ', $p_type)), 'img' => 'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=400&q=80'];
                ?>
                <a href="index.php?type=<?php echo urlencode($p_type); ?>"
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

    <!-- Main Content -->
    <main
        class="max-w-[1400px] mx-auto flex flex-col md:grid md:grid-cols-[260px_1fr] lg:grid-cols-[280px_1fr] gap-6 px-0 md:px-4 lg:px-6 md:mt-16 mt-12">

        <!-- Mobile Filters & Quick Buttons (Hidden on Desktop) -->
        <div class="md:hidden px-4 mb-2">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-[18px] text-neutral-800 leading-tight">Quick and easy planner</h3>
                    <p class="text-[13px] text-text-secondary mt-0.5">Pick a vibe and explore top destinations</p>
                </div>
                <!-- Filter Toggle Button -->
                <button
                    class="bg-neutral-100 p-3 rounded-full border border-neutral-200 text-neutral-700 hover:bg-neutral-200 transition-colors shadow-sm flex-shrink-0 ml-2"
                    onclick="alert('Filter menu would slide up here!')">
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
                            <button
                                class="flex items-center justify-center gap-2 px-5 py-2.5 bg-white border border-primary/20 rounded-full whitespace-nowrap text-[13px] font-bold text-primary hover:bg-primary/5 hover:border-primary transition-all shadow-[0_2px_8px_rgba(0,0,0,0.04)] shrink-0 snap-start">
                                <?php if (!empty($am['icon'])): ?>
                                    <i class="fas <?php echo htmlspecialchars($am['icon']); ?> text-primary"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($am['amenity_name']); ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif;
            } catch (PDOException $e) { /* ignore */
            }
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
                    <label class="filter-option"><input type="checkbox"> <i class="fas fa-umbrella-beach"></i>
                        Beach</label>
                    <label class="filter-option"><input type="checkbox"> <i class="fas fa-hippo"></i> Safari</label>
                    <label class="filter-option"><input type="checkbox"> <i class="fas fa-wifi"></i> Free WiFi</label>
                    <label class="filter-option"><input type="checkbox"> <i class="fas fa-swimming-pool"></i>
                        Pool</label>
                    <label class="filter-option"><input type="checkbox"> <i class="fas fa-utensils"></i> Breakfast
                        included</label>
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

            <!-- Ads Box (Desktop Sidebar) -->
            <?php if (!empty($ads)):
                $ad_display_count = 1;
                ?>
                <div class="mt-6 space-y-4">
                    <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 pl-1">Sponsored</h4>
                    <?php foreach ($ads as $ad): ?>
                        <div class="bg-gray-100 p-2 rounded-2xl border border-gray-200">
                            <a href="<?php echo htmlspecialchars($ad['link_url'] ?: '#'); ?>" target="_blank"
                                class="block group relative overflow-hidden rounded-xl">
                                <div
                                    class="w-full h-40 bg-neutral-200 border border-neutral-300 flex flex-col items-center justify-center text-neutral-400 rounded-xl transition-all group-hover:bg-neutral-300">
                                    <i class="fas fa-ad text-3xl mb-1 opacity-20"></i>
                                    <span class="text-[10px] font-black uppercase tracking-widest">Advertisement
                                        <?php echo $ad_display_count++; ?></span>
                                </div>
                                <div class="absolute bottom-0 left-0 right-0 p-2 bg-gradient-to-t from-black/60 to-transparent">
                                    <div class="flex justify-between items-end">
                                        <p class="text-[9px] text-white/80 font-bold">
                                            <?php echo htmlspecialchars($ad['owner_name']); ?>
                                        </p>
                                        <p class="text-[10px] text-white font-black">LKR
                                            <?php echo number_format($ad['price']); ?>
                                        </p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </aside>

        <!-- Results -->
        <section class="results">

            <!-- Horizontal Ads (Desktop Strip) -->
            <?php if (!empty($ads)): ?>
                <div class="hidden md:block mb-8">
                    <div class="grid grid-cols-2 gap-6">
                        <?php foreach ($ads as $ad): ?>
                            <div class="bg-gray-100 p-2 rounded-[2rem] border border-gray-200 overflow-hidden">
                                <a href="<?php echo htmlspecialchars($ad['link_url'] ?: '#'); ?>" target="_blank"
                                    class="block w-full h-[120px] group overflow-hidden rounded-[1.8rem]">
                                    <div
                                        class="w-full h-full bg-neutral-200 border border-neutral-300 flex items-center justify-center text-neutral-400 transition-all group-hover:bg-neutral-300">
                                        <i class="fas fa-ad text-2xl mr-3 opacity-20"></i>
                                        <span class="text-[11px] font-black uppercase tracking-widest">Advertisement
                                            <?php echo $ad_display_count++; ?></span>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Mobile Ads (Horizontal Scroll) -->
            <?php if (!empty($ads)):
                $ad_mobile_count = 1;
                ?>
                <div class="md:hidden px-4 mb-6">
                    <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Promotions</h4>
                    <div class="flex gap-4 overflow-x-auto no-scrollbar">
                        <?php foreach ($ads as $ad): ?>
                            <div class="min-w-[300px] bg-gray-100 p-2 rounded-2xl border border-gray-200 shrink-0">
                                <a href="<?php echo htmlspecialchars($ad['link_url'] ?: '#'); ?>" target="_blank"
                                    class="block relative h-32 overflow-hidden rounded-xl group">
                                    <div
                                        class="w-full h-full bg-neutral-200 border border-neutral-300 flex items-center justify-center text-neutral-400 transition-all group-active:bg-neutral-300">
                                        <i class="fas fa-ad text-2xl mr-3 opacity-20"></i>
                                        <span class="text-[11px] font-black uppercase tracking-widest">Advertisement
                                            <?php echo $ad_mobile_count++; ?></span>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php
            if (empty($properties)): ?>
                <div class="no-results"
                    style="padding: 40px; text-align: center; background: #fff; border-radius: 8px; border: 1px solid #ddd;">
                    <i class="fas fa-search" style="font-size: 48px; color: #ccc; margin-bottom: 20px;"></i>
                    <h3>No properties found</h3>
                    <p>Try adjusting your filters or search criteria.</p>
                </div>
            <?php else: ?>
                <!-- Mobile Horizontal Scroll Container -->
                <div
                    class="flex flex-row md:flex-col overflow-x-auto md:overflow-visible gap-4 md:gap-0 pb-4 md:pb-0 px-4 md:px-0 snap-x snap-mandatory no-scrollbar w-full">
                    <?php foreach ($properties as $property):
                        $stars = 0;
                        if ($property['hotel_category'] == 'budget_friendly')
                            $stars = 3;
                        if ($property['hotel_category'] == 'luxury')
                            $stars = 4;
                        if ($property['hotel_category'] == 'super_luxury')
                            $stars = 5;

                        // Format location
                        $location = $property['city'];
                        if ($property['district'])
                            $location .= ", " . $property['district'];

                        $image = !empty($property['cover_image']) ? $property['cover_image'] : null;
                        ?>
                        <!-- Property Listing Card -->
                        <div class="bg-white border border-border rounded-lg md:rounded-xl overflow-hidden flex flex-col md:flex-row mb-0 md:mb-4 transition-all hover:shadow-[0_4px_20px_rgb(0,0,0,0.08)] group w-[72vw] min-w-[240px] max-w-[280px] md:max-w-none md:w-full snap-start shrink-0 md:shrink cursor-pointer"
                            onclick="window.location.href='hotel_info.php?id=<?php echo $property['id']; ?>&checkin=<?php echo $checkin; ?>&checkout=<?php echo $checkout; ?>&adults=<?php echo $adults; ?>&children=<?php echo $children; ?>'">
                            <!-- Image Wrapper -->
                            <div class="relative w-full md:w-[240px] md:h-auto h-[150px] p-0 md:p-4 flex-shrink-0">
                                <?php if ($image): ?>
                                    <img src="<?php echo htmlspecialchars($image); ?>"
                                        alt="<?php echo htmlspecialchars($property['property_name']); ?>"
                                        class="w-full h-full object-cover rounded-none md:rounded group-hover:scale-[1.02] transition-transform duration-500">
                                <?php else: ?>
                                    <div class="w-full h-full bg-gray-100 rounded-none md:rounded flex items-center justify-center">
                                        <i class="fas fa-image text-3xl text-gray-300"></i>
                                    </div>
                                <?php endif; ?>
                                <?php if ($property['hotel_category'] == 'super_luxury'): ?>
                                    <div
                                        class="absolute top-6 left-6 bg-secondary text-white px-2 py-1 rounded text-[10px] font-bold uppercase tracking-widest shadow-md">
                                        Featured</div>
                                <?php endif; ?>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 p-3 md:p-4 md:pl-0 flex flex-col text-left">
                                <div class="flex justify-between items-start mb-1">
                                    <div class="flex-1">
                                        <div
                                            class="flex flex-col md:flex-row md:items-center justify-start gap-1 md:gap-2 mb-1">
                                            <div class="flex justify-start gap-0.5 text-gold text-[10px] order-1 md:order-2">
                                                <?php for ($i = 0; $i < $stars; $i++): ?>
                                                    <i class="fas fa-star"></i>
                                                <?php endfor; ?>
                                            </div>
                                            <h3
                                                class="text-[16px] md:text-[20px] font-bold text-secondary leading-tight hover:underline cursor-pointer transition-colors order-2 md:order-1">
                                                <?php echo htmlspecialchars($property['property_name']); ?>
                                            </h3>
                                        </div>
                                        <div
                                            class="flex flex-wrap items-center justify-start gap-1.5 text-[11px] md:text-[12px] text-secondary mb-2">
                                            <span
                                                class="underline decoration-dotted font-bold cursor-pointer"><?php echo htmlspecialchars($location); ?></span>
                                            <span class="font-bold text-neutral-300">•</span>
                                            <span
                                                class="font-medium text-primary"><?php echo htmlspecialchars($property['closest_main_town'] ?? $property['city']); ?></span>
                                        </div>
                                        <div class="flex justify-start mb-1">
                                            <div
                                                class="inline-block text-palm text-[10px] md:text-[11px] font-bold px-2 py-0.5 rounded bg-palm/10">
                                                Free cancellation</div>
                                        </div>
                                    </div>

                                    <!-- Rating (Hidden on Mobile) -->
                                    <div class="hidden md:flex items-center gap-2 ml-4">
                                        <div class="text-right flex flex-col justify-center">
                                            <div class="text-[14px] font-bold text-neutral-800 leading-none">New</div>
                                            <div class="text-[10px] text-text-secondary uppercase tracking-tighter">Review
                                                Pending</div>
                                        </div>
                                        <div
                                            class="bg-ocean text-white w-8 h-8 rounded-md flex items-center justify-center font-bold text-sm shadow-sm">
                                            -</div>
                                    </div>
                                </div>

                                <!-- Body & Footer Split -->
                                <div class="flex flex-col md:flex-row mt-2 flex-1">
                                    <!-- Room & Amenities Details -->
                                    <div class="flex-1 border-r border-transparent md:border-border/50 md:pr-4">
                                        <div class="hidden md:block">
                                            <h4 class="text-[13px] font-bold text-neutral-800 mb-0.5">
                                                <?php echo htmlspecialchars($property['room_name'] ?? 'Standard Room'); ?>
                                            </h4>
                                            <p class="text-[12px] text-text-secondary mb-2">
                                                <?php echo htmlspecialchars($property['adults'] ?? 2); ?> Adults,
                                                <?php echo htmlspecialchars($property['children'] ?? 0); ?> Children
                                            </p>
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
                                                    <div
                                                        class="flex items-center gap-1 text-[9px] md:text-[10px] font-bold text-neutral-600 bg-neutral-100 px-1.5 py-0.5 rounded border border-neutral-200">
                                                        <?php if (!empty($amenity['icon'])): ?>
                                                            <i
                                                                class="fas <?php echo htmlspecialchars($amenity['icon']); ?> text-neutral-800"></i>
                                                        <?php endif; ?>
                                                        <span><?php echo htmlspecialchars($amenity['amenity_name']); ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div
                                            class="flex items-start justify-start gap-1.5 text-palm text-[10px] md:text-[12px] font-bold mt-1">
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
                                        <div class="text-[9px] md:text-[11px] text-text-secondary mt-1 md:mt-0">Starting from
                                        </div>
                                        <div
                                            class="text-[18px] md:text-[22px] font-black text-primary tracking-tight leading-none mb-1">
                                            <?php echo $currency_symbol; ?>         <?php echo number_format($display_price); ?>
                                        </div>
                                        <div
                                            class="text-[8px] md:text-[10px] text-text-secondary uppercase font-bold tracking-wider mb-2">
                                            taxes & fees included</div>
                                        <button
                                            onclick="event.stopPropagation(); window.location.href='hotel_info.php?id=<?php echo $property['id']; ?>&checkin=<?php echo $checkin; ?>&checkout=<?php echo $checkout; ?>&adults=<?php echo $adults; ?>&children=<?php echo $children; ?>'"
                                            class="hidden md:block bg-secondary hover:bg-primary text-white px-4 py-1.5 md:py-2 rounded font-bold text-[13px] md:text-[14px] w-full md:w-auto transition-colors">Check
                                            Availability <i
                                                class="fas fa-chevron-right ml-1 text-[9px] md:text-[10px]"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Additional Advertisement Row -->
            <?php if (!empty($ads)): ?>
                <div class="mt-12 mb-8">
                    <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-4">Featured Partners</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach ($ads as $ad): ?>
                            <div
                                class="bg-gradient-to-br from-primary/5 to-secondary/5 p-4 rounded-[2rem] border border-primary/10 overflow-hidden group hover:shadow-lg transition-all">
                                <a href="<?php echo htmlspecialchars($ad['link_url'] ?: '#'); ?>" target="_blank"
                                    class="flex items-center gap-6">
                                    <div
                                        class="w-24 h-24 bg-white rounded-2xl shadow-sm flex items-center justify-center flex-shrink-0 group-hover:scale-105 transition-transform">
                                        <i class="fas fa-ad text-3xl text-primary opacity-20"></i>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex justify-between items-start">
                                            <h5 class="font-bold text-neutral-800 group-hover:text-primary transition-colors">
                                                <?php echo htmlspecialchars($ad['owner_name']); ?>
                                            </h5>
                                            <span class="text-xs font-black text-primary">LKR
                                                <?php echo number_format($ad['price']); ?></span>
                                        </div>
                                        <p class="text-sm text-text-secondary mt-1">Exclusive deal for Bookingjaunt members.
                                            Book now and save big on your next trip.</p>
                                        <div class="mt-2 text-xs font-bold text-secondary flex items-center gap-1">
                                            Visit Partner <i class="fas fa-external-link-alt text-[10px]"></i>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Pagination -->
            <div class="hidden md:flex" style="justify-content:center; gap:5px; margin-top:20px;">
                <button style="padding:8px 12px; border:1px solid #ddd; background:#fff; border-radius:4px;"><i
                        class="fas fa-chevron-left"></i></button>
                <button
                    style="padding:8px 12px; border:1px solid #ddd; background:var(--primary); color:#fff; border-radius:4px;">1</button>
                <button style="padding:8px 12px; border:1px solid #ddd; background:#fff; border-radius:4px;">2</button>
                <button style="padding:8px 12px; border:1px solid #ddd; background:#fff; border-radius:4px;">3</button>
                <span>...</span>
                <button style="padding:8px 12px; border:1px solid #ddd; background:#fff; border-radius:4px;">24</button>
                <button style="padding:8px 12px; border:1px solid #ddd; background:#fff; border-radius:4px;"><i
                        class="fas fa-chevron-right"></i></button>
            </div>

            <!-- Mobile Authentication Promo (Hidden on Desktop) -->
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div
                    class="md:hidden mx-4 mt-6 mb-2 bg-white border border-border shadow-[0_4px_15px_rgba(0,0,0,0.05)] rounded-xl p-4 flex flex-col gap-3">
                    <div class="flex items-start gap-3">
                        <div class="bg-primary/10 p-2.5 rounded-full text-primary">
                            <i class="fas fa-gift text-xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-[15px] text-neutral-800 leading-tight">Sign in, save money</h3>
                            <p class="text-[12px] text-text-secondary leading-tight mt-1">Save 10% or more at participating
                                properties with a free Bookingjaunt account.</p>
                        </div>
                    </div>
                    <div class="flex gap-2 mt-2">
                        <a href="login.php"
                            class="flex-1 bg-secondary text-white text-center py-2.5 rounded font-bold text-[13px] hover:bg-primary transition-colors shadow-sm">Sign
                            in</a>
                        <a href="register.php"
                            class="flex-1 text-primary border border-primary text-center py-2.5 rounded font-bold text-[13px] hover:bg-neutral-50 transition-colors">Register</a>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Why Choose Us -->
    <section class="max-w-[1400px] mx-auto px-4 lg:px-6 mt-20 mb-10">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="flex gap-4 items-start">
                <div
                    class="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary flex-shrink-0">
                    <i class="fas fa-shield-alt text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-neutral-800 mb-1">Secure Bookings</h3>
                    <p class="text-sm text-text-secondary">Your data is safe with our 256-bit SSL encrypted payment
                        gateway.</p>
                </div>
            </div>
            <div class="flex gap-4 items-start">
                <div
                    class="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary flex-shrink-0">
                    <i class="fas fa-headset text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-neutral-800 mb-1">24/7 Support</h3>
                    <p class="text-sm text-text-secondary">Our dedicated team is here to help you anytime, anywhere in
                        Sri Lanka.</p>
                </div>
            </div>
            <div class="flex gap-4 items-start">
                <div
                    class="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary flex-shrink-0">
                    <i class="fas fa-thumbs-up text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-neutral-800 mb-1">Best Price Guarantee</h3>
                    <p class="text-sm text-text-secondary">Find a lower price? We'll match it and give you an extra 5%
                        off.</p>
                </div>
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

    <section
        class="bg-[#00224f] text-white py-12 px-4 md:px-[10%] mt-12 flex flex-col md:flex-row justify-center md:justify-between items-center text-center md:text-left gap-6">
        <div>
            <div class="text-[20px] md:text-[24px] font-bold leading-tight">Save time, save money!</div>
            <div class="text-[13px] md:text-[14px] font-normal text-white/80 mt-1 md:mt-0">Sign up and we'll send the
                best deals to you</div>
        </div>
        <div class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
            <input type="text" placeholder="Your email address"
                class="p-3 rounded text-neutral-800 w-full sm:w-[300px] outline-none focus:ring-2 focus:ring-secondary">
            <button
                class="bg-secondary hover:bg-primary text-white font-bold px-6 py-3 rounded transition-colors w-full sm:w-auto">Subscribe</button>
        </div>
    </section>

    <!-- Footer Main -->
    <footer
        class="bg-white py-10 px-4 md:px-[10%] grid grid-cols-2 md:grid-cols-5 gap-8 md:gap-4 border-b border-border">
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
            <div class="text-[28px] md:text-[32px] font-bold text-neutral-200 mt-6 md:mt-8 tracking-tighter">+94 1000000
            </div>
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

    <div
        class="bg-white pt-8 pb-12 px-4 md:px-[10%] flex flex-col items-center gap-6 text-[12px] text-text-secondary text-center">
        <a href="index.php">
            <img src="assets/logo.png" alt="Bookingjaunt"
                class="h-10 w-auto opacity-90 hover:opacity-100 transition-opacity">
        </a>
        <div class="flex flex-wrap justify-center gap-4">
            <span class="cursor-pointer hover:underline text-primary">Privacy & Cookies</span>
            <span class="cursor-pointer hover:underline text-primary">Manage Cookie Settings</span>
            <span class="cursor-pointer hover:underline text-primary">MSA Statement</span>
        </div>
        <div>© 2026 Bookingjaunt.com All rights reserved.</div>
    </div>

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
</body>

</html>