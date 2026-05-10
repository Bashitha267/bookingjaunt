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
$exchange_rate = 300;

// Fetch hero images from database
try {
    $stmt_hero = $pdo->query("SELECT image FROM hero_images ORDER BY id ASC");
    $hero_images = $stmt_hero->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $hero_images = [];
}

// Fallback if no hero images in table
if (empty($hero_images)) {
    $hero_images = [
        'assets/slideshow/beach.png',
        'assets/slideshow/tea.png',
        'assets/slideshow/sigiriya.png'
    ];
}

try {
    $q = $_GET['q'] ?? '';
    $adults = (int) ($_GET['adults'] ?? 1);
    $children = (int) ($_GET['children'] ?? 0);
    $selected_budget = $_GET['budget'] ?? '';
    
    $params = ["dayouts"];
    $where = ["p.business_type = ?"];
    
    if (!empty($q)) {
        $where[] = "(p.property_name LIKE ? OR p.city LIKE ? OR p.district LIKE ? OR p.closest_main_town LIKE ?)";
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }
    
    // Capacity filter
    $where[] = "(r.adults >= ?)";
    $params[] = $adults;

    if (!empty($selected_budget)) {
        if ($selected_budget == '0-5000') {
            $where[] = "r.price_lkr <= 5000";
        } elseif ($selected_budget == '5000-10000') {
            $where[] = "r.price_lkr > 5000 AND r.price_lkr <= 10000";
        } elseif ($selected_budget == '10000-20000') {
            $where[] = "r.price_lkr > 10000 AND r.price_lkr <= 20000";
        } elseif ($selected_budget == '20000+') {
            $where[] = "r.price_lkr > 20000";
        }
    }

    $where_sql = implode(" AND ", $where);

    $dayout_query = "SELECT p.*, r.room_name as pkg_name, r.price_lkr, r.room_image as pkg_image, r.adults,
              (pb.id IS NOT NULL) as is_featured
              FROM properties p 
              JOIN property_rooms r ON r.property_id = p.id
              LEFT JOIN property_boosts pb ON pb.property_id = p.id AND pb.status = 'active' 
                   AND pb.start_date <= CURDATE() AND DATE_ADD(pb.start_date, INTERVAL pb.duration_days DAY) >= CURDATE()
              WHERE $where_sql
              AND r.price_lkr = (
                  SELECT MIN(price_lkr) FROM property_rooms r2 
                  WHERE r2.property_id = p.id 
                  AND r2.adults >= ?
              )
              GROUP BY p.id
              ORDER BY is_featured DESC, p.created_at DESC";
    $params[] = $adults; // For subquery

    $stmt_dayouts = $pdo->prepare($dayout_query);
    $stmt_dayouts->execute($params);
    $dayouts = $stmt_dayouts->fetchAll();
    
    // Fetch Featured Dayouts (Boosted)
    $featured_query = "SELECT p.*, r.room_name as pkg_name, r.price_lkr, r.room_image as pkg_image, r.adults, r.description,
                       (SELECT MAX(price_lkr) FROM property_rooms pr3 WHERE pr3.property_id = p.id) as max_price,
                       AVG(rev.rating) as avg_rating, COUNT(rev.id) as review_count
                       FROM properties p
                       JOIN property_boosts pb ON pb.property_id = p.id
                       JOIN property_rooms r ON r.property_id = p.id
                       LEFT JOIN reviews rev ON rev.property_id = p.id
                       WHERE pb.status = 'active' 
                       AND p.business_type = 'dayouts'
                       AND pb.start_date <= CURDATE() 
                       AND DATE_ADD(pb.start_date, INTERVAL pb.duration_days DAY) >= CURDATE()
                       AND r.price_lkr = (
                           SELECT MIN(price_lkr) FROM property_rooms r2 
                           WHERE r2.property_id = p.id 
                       )
                       GROUP BY p.id
                       ORDER BY RAND() LIMIT 6";
    $stmt_featured = $pdo->query($featured_query);
    $featured_dayouts = $stmt_featured->fetchAll();

    // Fetch Ads for Sidebar
    $sidebar_ads_stmt = $pdo->query("
        SELECT a.*, p.package_type 
        FROM advertisements a 
        LEFT JOIN advertisement_packages p ON a.package_id = p.id 
        WHERE a.status = 'active' AND (p.package_type = 'sidebar_ad' OR a.package_id IS NULL)
        ORDER BY RAND() LIMIT 2
    ");
    $sidebar_ads = $sidebar_ads_stmt->fetchAll();

} catch (PDOException $e) {
    $dayouts = [];
    $featured_dayouts = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sri Lanka Dayouts - Bookingjaunt</title>
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
    <style>
        body {
            background-color: #eef6ff !important;
        }
        .filter-box {
            background: white;
            border-radius: 20px;
            padding: 20px;
            border: 1px solid #e7e7e7;
            position: sticky;
            top: 20px;
        }
        .filter-title {
            font-size: 14px;
            font-weight: 800;
            color: #1a1a1a;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .filter-group {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        .filter-group:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .filter-category-title {
            font-size: 11px;
            font-weight: 700;
            color: #4a4a4a;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .filter-option {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 12px;
            font-weight: 500;
            color: #4a4a4a;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .filter-option:hover {
            color: #006ce4;
        }
        .filter-option input {
            cursor: pointer;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <!-- Hero Section -->
    <section style="background: linear-gradient(135deg, #003580 0%, #006ce4 100%); padding: 120px 16px 60px; text-align: center; color: white; position: relative;">
        <div style="max-width: 1000px; margin: 0 auto;">
            <h1 style="font-size: clamp(24px, 5vw, 42px); font-weight: 800; margin-bottom: 12px; font-family: 'Outfit', sans-serif;">Find Your Perfect Dayout</h1>
            <p style="font-size: clamp(14px, 2vw, 17px); opacity: 0.9; margin-bottom: 40px; font-weight: 500;">Handpicked one-day experiences across Sri Lanka</p>

            <style>
                .bj-search { width: 100%; max-width: 900px; margin: 0 auto; display: flex; flex-direction: column; gap: 8px; padding: 14px; background: #fff; border-radius: 24px; box-shadow: 0 24px 80px rgba(0,0,0,0.3); text-align: left; box-sizing: border-box; }
                .bj-field { display: flex; align-items: center; gap: 10px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 14px; padding: 10px 16px; min-height: 58px; box-sizing: border-box; }
                .bj-field input { border: none; outline: none; background: transparent; width: 100%; min-width: 0; font-size: 13px; font-weight: 600; color: #1a1a1a; cursor: pointer; font-family: inherit; padding: 0; box-sizing: border-box; }
                .bj-field-label { font-size: 10px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 2px; }
                .bj-guests-field { cursor: pointer; position: relative; user-select: none; }
                .bj-search-btn { width: 100%; height: 56px; background: #006ce4; color: #fff; border: none; border-radius: 14px; font-weight: 800; font-size: 16px; letter-spacing: 0.1em; text-transform: uppercase; cursor: pointer; font-family: inherit; flex-shrink: 0; }

                @media (min-width: 768px) {
                    .bj-search { flex-direction: row; align-items: center; padding: 10px; border-radius: 20px; gap: 6px; }
                    .bj-field-location { flex: 2 1 200px; min-width: 0; }
                    .bj-field-date { flex: 1 1 150px; min-width: 0; }
                    .bj-guests-field { flex: 1 1 180px; min-width: 0; }
                    .bj-search-btn { width: auto; padding: 0 32px; flex: 0 0 auto; border-radius: 14px; height: 56px; }
                }
            </style>

            <form action="srilanka_dayouts.php" method="GET" class="bj-search">
                <input type="hidden" name="type" value="dayouts">

                <!-- Location -->
                <div class="bj-field bj-field-location">
                    <i class="fas fa-map-marker-alt" style="color: #006ce4; font-size: 16px; flex-shrink: 0;"></i>
                    <input type="text" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>" placeholder="Where do you want to go?">
                </div>

                <!-- Date Selection -->
                <div class="bj-field bj-field-date">
                    <i class="far fa-calendar-alt" style="color: #006ce4; font-size: 16px; flex-shrink: 0;"></i>
                    <div style="flex: 1; min-width: 0;">
                        <div class="bj-field-label">Preferred Date</div>
                        <input type="date" name="date" value="<?php echo htmlspecialchars($_GET['date'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Guests -->
                <div class="bj-field bj-guests-field" id="guestDropdownContainer" onclick="toggleGuestDropdown()">
                    <i class="far fa-user" style="color: #006ce4; font-size: 16px; flex-shrink: 0;"></i>
                    <div style="flex: 1; min-width: 0;">
                        <div class="bj-field-label">People</div>
                        <div id="guestInputDisplay" style="font-size: 13px; font-weight: 600; color: #1a1a1a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?php echo (int)($_GET['adults'] ?? 1); ?> adults · <?php echo (int)($_GET['children'] ?? 0); ?> children
                        </div>
                    </div>
                    <i class="fas fa-chevron-down" id="guestChevron" style="color: #9ca3af; font-size: 10px; transition: transform 0.3s; flex-shrink: 0;"></i>

                    <!-- Guest Dropdown Panel -->
                    <div id="guestPopup" style="display: none; position: absolute; top: calc(100% + 8px); left: 0; min-width: 280px; background: #fff; border-radius: 20px; border: 1px solid #e5e7eb; box-shadow: 0 20px 60px rgba(0,0,0,0.2); padding: 24px; z-index: 9999; text-align: left; color: #1a1a1a;">
                        <p style="font-size: 11px; font-weight: 800; color: #006ce4; text-transform: uppercase; letter-spacing: 0.15em; margin: 0 0 20px;">Guest Selection</p>

                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                            <div>
                                <p style="font-size: 14px; font-weight: 700; margin: 0;">Adults</p>
                                <p style="font-size: 11px; color: #9ca3af; margin: 0;">Ages 13+</p>
                            </div>
                            <div style="display: flex; align-items: center; background: #f3f4f6; border-radius: 12px; padding: 4px;">
                                <button type="button" onclick="event.stopPropagation(); updateGuestCount('adults', -1)" style="width: 32px; height: 32px; border: none; background: transparent; cursor: pointer; color: #006ce4;"><i class="fas fa-minus text-[10px]"></i></button>
                                <span id="adultsCount" style="width: 30px; text-align: center; font-weight: 800;"><?php echo (int)($_GET['adults'] ?? 1); ?></span>
                                <input type="hidden" name="adults" id="adultsHidden" value="<?php echo (int)($_GET['adults'] ?? 1); ?>">
                                <button type="button" onclick="event.stopPropagation(); updateGuestCount('adults', 1)" style="width: 32px; height: 32px; border: none; background: transparent; cursor: pointer; color: #006ce4;"><i class="fas fa-plus text-[10px]"></i></button>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
                            <div>
                                <p style="font-size: 14px; font-weight: 700; margin: 0;">Children</p>
                                <p style="font-size: 11px; color: #9ca3af; margin: 0;">Ages 0-12</p>
                            </div>
                            <div style="display: flex; align-items: center; background: #f3f4f6; border-radius: 12px; padding: 4px;">
                                <button type="button" onclick="event.stopPropagation(); updateGuestCount('children', -1)" style="width: 32px; height: 32px; border: none; background: transparent; cursor: pointer; color: #006ce4;"><i class="fas fa-minus text-[10px]"></i></button>
                                <span id="childrenCount" style="width: 30px; text-align: center; font-weight: 800;"><?php echo (int)($_GET['children'] ?? 0); ?></span>
                                <input type="hidden" name="children" id="childrenHidden" value="<?php echo (int)($_GET['children'] ?? 0); ?>">
                                <button type="button" onclick="event.stopPropagation(); updateGuestCount('children', 1)" style="width: 32px; height: 32px; border: none; background: transparent; cursor: pointer; color: #006ce4;"><i class="fas fa-plus text-[10px]"></i></button>
                            </div>
                        </div>

                        <button type="button" onclick="event.stopPropagation(); toggleGuestDropdown()" style="width: 100%; padding: 12px; background: #006ce4; color: #fff; border: none; border-radius: 12px; font-size: 13px; font-weight: 800; text-transform: uppercase; cursor: pointer;">Done</button>
                    </div>
                </div>

                <button type="submit" class="bj-search-btn" onmouseover="this.style.background='#003580'" onmouseout="this.style.background='#006ce4'">Search</button>
            </form>
        </div>
    </section>




    <!-- Main Content with Sidebar -->
    <main class="max-w-[1400px] mx-auto flex flex-col md:grid md:grid-cols-[280px_1fr] gap-10 px-4 lg:px-6 mb-24 mt-12">
        
        <!-- Sidebar Filter -->
        <aside class="hidden md:block">
            <form action="srilanka_dayouts.php" method="GET" id="dayoutFilterForm">
                <input type="hidden" name="q" value="<?php echo htmlspecialchars($q); ?>">
                <input type="hidden" name="adults" value="<?php echo htmlspecialchars($adults); ?>">
                <input type="hidden" name="children" value="<?php echo htmlspecialchars($children); ?>">

                <div class="filter-box">
                    <div class="filter-title">Filter Dayouts:</div>

                    <div class="filter-group">
                        <h4 class="filter-category-title">Package Budget</h4>
                        <label class="filter-option"><input type="radio" name="budget" value="0-5000" onchange="this.form.submit()" <?php echo $selected_budget == '0-5000' ? 'checked' : ''; ?>> LKR 0 - 5,000</label>
                        <label class="filter-option"><input type="radio" name="budget" value="5000-10000" onchange="this.form.submit()" <?php echo $selected_budget == '5000-10000' ? 'checked' : ''; ?>> LKR 5,000 - 10,000</label>
                        <label class="filter-option"><input type="radio" name="budget" value="10000-20000" onchange="this.form.submit()" <?php echo $selected_budget == '10000-20000' ? 'checked' : ''; ?>> LKR 10,000 - 20,000</label>
                        <label class="filter-option"><input type="radio" name="budget" value="20000+" onchange="this.form.submit()" <?php echo $selected_budget == '20000+' ? 'checked' : ''; ?>> LKR 20,000+</label>
                        <?php if ($selected_budget): ?>
                            <button type="button" onclick="document.getElementsByName('budget').forEach(r => r.checked = false); this.form.submit();" class="text-[10px] text-red-500 mt-2 font-bold uppercase tracking-widest">Clear Budget</button>
                        <?php endif; ?>
                    </div>

                    <div class="filter-group">
                        <h4 class="filter-category-title">Popular Perks</h4>
                        <label class="filter-option"><input type="checkbox" onchange="this.form.submit()"> Pool Access</label>
                        <label class="filter-option"><input type="checkbox" onchange="this.form.submit()"> Welcome Drink</label>
                        <label class="filter-option"><input type="checkbox" onchange="this.form.submit()"> Buffet Lunch</label>
                    </div>
                </div>
            </form>

            <!-- Ads Box (Desktop Sidebar) -->
            <?php if (!empty($sidebar_ads)):
                $ad_display_count = 1;
                ?>
                <div class="mt-8 space-y-5">
                    <h4 class="text-[11px] font-black text-neutral-400 uppercase tracking-[0.2em] mb-4 pl-1">Sponsored</h4>
                    <?php foreach ($sidebar_ads as $ad): ?>
                        <div class="bg-white rounded-2xl border border-neutral-100 shadow-sm overflow-hidden group hover:shadow-md transition-all duration-300">
                            <a href="<?php echo htmlspecialchars($ad['link_url'] ?: '#'); ?>" target="_blank" class="block relative h-44">
                                <?php if (!empty($ad['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($ad['image_path']); ?>" alt="Advertisement" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                <?php else: ?>
                                    <div class="w-full h-full bg-gradient-to-br from-neutral-50 to-neutral-100 flex flex-col items-center justify-center text-neutral-400 transition-all group-hover:from-neutral-100 group-hover:to-neutral-200">
                                        <i class="fas fa-ad text-4xl mb-2 opacity-10"></i>
                                        <span class="text-[11px] font-black uppercase tracking-widest opacity-40">Advertisement <?php echo $ad_display_count++; ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Glassy Bottom Overlay -->
                                <div class="absolute bottom-0 left-0 right-0 p-4 bg-white/60 backdrop-blur-md border-t border-white/40">
                                    <div class="flex justify-between items-center">
                                        <p class="text-[13px] text-primary font-black truncate w-full">
                                            <?php echo htmlspecialchars(!empty($ad['ad_title']) ? $ad['ad_title'] : $ad['owner_name']); ?>
                                        </p>
                                    </div>
                                </div>

                                <!-- Subtle Hover Glow -->
                                <div class="absolute inset-0 bg-primary/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </aside>

        <!-- Dayout Packages Grid -->
        <div>
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-xl md:text-2xl font-black text-neutral-800 tracking-tight">Available Dayouts</h2>
                    <p class="text-neutral-500 font-bold mt-1 uppercase text-[9px] tracking-widest">Discover the best one-day escapes</p>
                </div>
            </div>

        <?php if(empty($dayouts)): ?>
            <div class="bg-neutral-50 rounded-[2rem] border border-neutral-100 py-20 text-center">
                <i class="fas fa-search text-neutral-200 text-6xl mb-4"></i>
                <h3 class="text-xl font-bold text-neutral-400">No dayout packages available right now.</h3>
            </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-2 gap-6">
            <?php foreach($dayouts as $dayout): 
                $img = !empty($dayout['cover_image']) ? $dayout['cover_image'] : (!empty($dayout['pkg_image']) ? $dayout['pkg_image'] : 'https://images.unsplash.com/photo-1540541338287-41700207dee6?auto=format&fit=crop&w=400&q=80');
                $display_price = ($currency == 'USD') ? ceil($dayout['price_lkr'] / $exchange_rate) : $dayout['price_lkr'];
            ?>
            <div class="group bg-white rounded-3xl border border-neutral-100 overflow-hidden hover:shadow-xl hover:shadow-blue-900/5 transition-all duration-500 flex flex-col h-full cursor-pointer" onclick="window.location.href='hotel_info.php?id=<?php echo $dayout['id']; ?>'">
                <!-- Image Section -->
                <div class="relative h-[180px] md:h-[220px] overflow-hidden">
                    <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($dayout['property_name']); ?>"
                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-1000">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    
                    <!-- Top Badges -->
                    <div class="absolute top-4 left-4 flex flex-col gap-2">
                        <div class="bg-white/95 backdrop-blur-md text-[#003580] px-3 py-1.5 rounded-xl text-[11px] font-black shadow-md">
                            <?php echo $currency; ?> <?php echo number_format($display_price); ?>
                        </div>
                        <?php if (!empty($dayout['is_featured'])): ?>
                        <div class="bg-secondary text-white px-2.5 py-1 rounded-xl text-[9px] font-bold uppercase shadow-md w-max tracking-widest">
                            Featured
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Content Section -->
                <div class="p-5 md:p-6 flex flex-col flex-1">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex-1">
                            <h3 class="font-black text-lg md:text-xl text-neutral-800 leading-tight mb-1 group-hover:text-[#003580] transition-colors">
                                <?php echo htmlspecialchars($dayout['property_name']); ?>
                            </h3>
                            <p class="text-neutral-400 font-bold text-[11px] flex items-center">
                                <i class="fas fa-map-marker-alt mr-1.5 text-[#003580]"></i>
                                <?php echo htmlspecialchars($dayout['city']); ?>, Sri Lanka
                            </p>
                        </div>
                    </div>

                    <!-- Amenities (Simplified 3) -->
                    <div class="flex gap-2.5 mb-6">
                        <div class="flex items-center gap-1.5 bg-neutral-50 px-2.5 py-1.5 rounded-lg border border-neutral-100">
                            <i class="fas fa-swimming-pool text-[#003580] text-[10px]"></i>
                            <span class="text-[9px] font-bold text-neutral-600 uppercase">Pool</span>
                        </div>
                        <div class="flex items-center gap-1.5 bg-neutral-50 px-2.5 py-1.5 rounded-lg border border-neutral-100">
                            <i class="fas fa-utensils text-[#003580] text-[10px]"></i>
                            <span class="text-[9px] font-bold text-neutral-600 uppercase">Meal</span>
                        </div>
                        <div class="flex items-center gap-1.5 bg-neutral-50 px-2.5 py-1.5 rounded-lg border border-neutral-100">
                            <i class="fas fa-wifi text-[#003580] text-[10px]"></i>
                            <span class="text-[9px] font-bold text-neutral-600 uppercase">Wifi</span>
                        </div>
                    </div>

                    <!-- Footer Section -->
                    <div class="mt-auto pt-5 border-t border-neutral-50 flex items-center justify-between">
                        <div>
                            <p class="text-[9px] text-neutral-400 font-bold uppercase tracking-widest mb-0.5">Starting from</p>
                            <p class="text-lg font-black text-[#003580]"><?php echo $currency; ?> <?php echo number_format($display_price); ?></p>
                        </div>
                        <button class="bg-[#003580] hover:bg-[#006ce4] text-white px-5 py-2.5 rounded-xl font-black text-[13px] transition-all shadow-md shadow-[#003580]/10 hover:scale-[1.02] active:scale-95">
                            Book
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Why Choose Our Dayouts -->
    <section class="max-w-[1400px] mx-auto px-4 lg:px-6 mt-16 mb-16">
        <h2 class="text-2xl md:text-3xl font-bold text-neutral-800 tracking-tight mb-8 text-center">Why Plan a Dayout With Us?</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white rounded-xl border border-neutral-100 p-6 text-center shadow-sm hover:shadow-md transition-shadow">
                <div class="w-14 h-14 bg-primary/10 text-primary rounded-full flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fas fa-clock"></i>
                </div>
                <h3 class="font-bold text-lg text-neutral-800 mb-2">Hassle-Free Planning</h3>
                <p class="text-text-secondary text-sm">Curated itineraries so you spend less time planning and more time enjoying.</p>
            </div>
            <div class="bg-white rounded-xl border border-neutral-100 p-6 text-center shadow-sm hover:shadow-md transition-shadow">
                <div class="w-14 h-14 bg-primary/10 text-primary rounded-full flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fas fa-tag"></i>
                </div>
                <h3 class="font-bold text-lg text-neutral-800 mb-2">Best Price Guarantee</h3>
                <p class="text-text-secondary text-sm">Get the best rates on day packages, transport & experiences.</p>
            </div>
            <div class="bg-white rounded-xl border border-neutral-100 p-6 text-center shadow-sm hover:shadow-md transition-shadow">
                <div class="w-14 h-14 bg-primary/10 text-primary rounded-full flex items-center justify-center text-2xl mx-auto mb-4">
                    <i class="fas fa-headset"></i>
                </div>
                <h3 class="font-bold text-lg text-neutral-800 mb-2">24/7 Support</h3>
                <p class="text-text-secondary text-sm">Our team is always ready to help you during your day out.</p>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>

    <script>
        function toggleGuestDropdown() {
            const popup = document.getElementById('guestPopup');
            const chevron = document.getElementById('guestChevron');
            const isHidden = popup.style.display === 'none';

            if (isHidden) {
                popup.style.display = 'block';
                if (chevron) chevron.style.transform = 'rotate(180deg)';
                if (window.innerWidth < 768) {
                    document.body.style.overflow = 'hidden';
                }
            } else {
                popup.style.display = 'none';
                if (chevron) chevron.style.transform = 'rotate(0deg)';
                document.body.style.overflow = '';
            }
        }

        function updateGuestCount(type, change) {
            const countSpan = document.getElementById(type + 'Count');
            const hiddenInput = document.getElementById(type + 'Hidden');
            const displaySpan = document.getElementById('guestInputDisplay');

            let currentCount = parseInt(countSpan.innerText);
            let newCount = currentCount + change;

            if (type === 'adults' && newCount < 1) newCount = 1;
            if (type === 'children' && newCount < 0) newCount = 0;

            countSpan.innerText = newCount;
            if (hiddenInput) hiddenInput.value = newCount;

            const adults = document.getElementById('adultsCount').innerText;
            const children = document.getElementById('childrenCount').innerText;
            if (displaySpan) {
                displaySpan.innerText = `${adults} adults · ${children} children`;
            }
        }

        document.addEventListener('click', function (event) {
            const container = document.getElementById('guestDropdownContainer');
            const popup = document.getElementById('guestPopup');
            const chevron = document.getElementById('guestChevron');

            if (container && !container.contains(event.target)) {
                popup.style.display = 'none';
                if (chevron) chevron.style.transform = 'rotate(0deg)';
                document.body.style.overflow = '';
            }
        });

        window.addEventListener('DOMContentLoaded', () => {
            const adultsInput = document.getElementById('adultsHidden');
            const childrenInput = document.getElementById('childrenHidden');
            if (adultsInput) {
                document.getElementById('adultsCount').innerText = adultsInput.value;
            }
            if (childrenInput) {
                document.getElementById('childrenCount').innerText = childrenInput.value;
            }
        });
    </script>

</body>

</html>
