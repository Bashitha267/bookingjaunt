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

try {
    // --- Core Search Variables ---
    $q            = trim($_GET['q'] ?? '');
    $district     = trim($_GET['district'] ?? '');
    $vehicle_type = trim($_GET['vehicle_type'] ?? '');
    $driver_option = trim($_GET['driver_option'] ?? '');

    // Pagination
    $per_page = 20;
    $page     = max(1, (int)($_GET['page'] ?? 1));
    $offset   = ($page - 1) * $per_page;

    // --- Build WHERE clause ---
    $params = [];
    $where  = ["p.business_type = 'vehicle'"];

    if (!empty($q)) {
        $where[]  = "(p.property_name LIKE ? OR p.city LIKE ? OR p.district LIKE ? OR p.closest_main_town LIKE ?)";
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = "%$q%";
        $params[] = "%$q%";
    }

    if (!empty($district)) {
        $where[]  = "p.district = ?";
        $params[] = $district;
    }

    if (!empty($vehicle_type)) {
        $where[]  = "p.vehicle_category = ?";
        $params[] = $vehicle_type;
    }

    if (!empty($driver_option)) {
        if ($driver_option === 'with_driver') {
            $where[]  = "p.driver_option IN ('with_driver', 'both')";
        } elseif ($driver_option === 'without_driver') {
            $where[]  = "p.driver_option IN ('without_driver', 'both')";
        } else {
            $where[]  = "p.driver_option = ?";
            $params[] = $driver_option;
        }
    }

    $where_sql = implode(" AND ", $where);

    // --- Count total for pagination ---
    $count_sql    = "SELECT COUNT(DISTINCT p.id) FROM properties p WHERE $where_sql";
    $count_stmt   = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_results = (int) $count_stmt->fetchColumn();
    $total_pages   = max(1, (int) ceil($total_results / $per_page));

    // --- Main Query ---
    $query = "SELECT p.*, u.first_name, u.last_name, u.phone_number as owner_phone, u.whatsapp_number as owner_whatsapp,
              (pb.id IS NOT NULL) as is_featured
              FROM properties p
              LEFT JOIN users u ON p.owner_id = u.id
              LEFT JOIN property_boosts pb ON pb.property_id = p.id AND pb.status = 'active'
                   AND pb.start_date <= CURDATE() AND DATE_ADD(pb.start_date, INTERVAL pb.duration_days DAY) >= CURDATE()
              WHERE $where_sql
              ORDER BY is_featured DESC, p.created_at DESC
              LIMIT $per_page OFFSET $offset";

    $stmt       = $pdo->prepare($query);
    $stmt->execute($params);
    $vehicles   = $stmt->fetchAll();

    // --- Sidebar Ads ---
    $sidebar_ads_stmt = $pdo->query("
        SELECT a.*, p.package_type
        FROM advertisements a
        LEFT JOIN advertisement_packages p ON a.package_id = p.id
        WHERE a.status = 'active' AND (p.package_type = 'sidebar_ad' OR a.package_id IS NULL)
        ORDER BY RAND() LIMIT 4
    ");
    $sidebar_ads = $sidebar_ads_stmt->fetchAll();

    // --- Horizontal Strip Ads ---
    $strip_ads_stmt = $pdo->query("
        SELECT a.*, p.package_type
        FROM advertisements a
        LEFT JOIN advertisement_packages p ON a.package_id = p.id
        WHERE a.status = 'active' AND (p.package_type = 'horizontal_strip_ad' OR a.package_id IS NULL)
        ORDER BY RAND() LIMIT 4
    ");
    $strip_ads = $strip_ads_stmt->fetchAll();

    $ad_display_count = 1;

} catch (PDOException $e) {
    error_log("Rides query failed: " . $e->getMessage());
    $vehicles      = [];
    $sidebar_ads   = [];
    $strip_ads     = [];
    $total_results = 0;
    $total_pages   = 1;
}

$sri_lanka_districts = [
    'Ampara','Anuradhapura','Badulla','Batticaloa','Colombo',
    'Galle','Gampaha','Hambantota','Jaffna','Kalutara',
    'Kandy','Kegalle','Kilinochchi','Kurunegala','Mannar',
    'Matale','Matara','Monaragala','Mullaitivu','Nuwara Eliya',
    'Polonnaruwa','Puttalam','Ratnapura','Trincomalee','Vavuniya'
];

$vehicle_categories = [
    'car'      => ['label' => 'Car',      'icon' => 'fa-car'],
    'van'      => ['label' => 'Van',      'icon' => 'fa-shuttle-van'],
    'suv'      => ['label' => 'SUV',      'icon' => 'fa-truck-pickup'],
    'jeep'     => ['label' => 'Jeep',     'icon' => 'fa-truck-monster'],
    'bus'      => ['label' => 'Bus',      'icon' => 'fa-bus'],
    'tuk_tuk'  => ['label' => 'Tuk-Tuk', 'icon' => 'fa-motorcycle'],
    'motorbike'=> ['label' => 'Motorbike','icon' => 'fa-motorcycle'],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16x16.png">
    <link rel="manifest" href="/assets/site.webmanifest">
    <title>BookingJaunt - Find Your Perfect Ride in Sri Lanka</title>
    <meta name="description" content="Browse cars, vans, SUVs, jeeps, buses and tuk-tuks for hire across Sri Lanka. Find vehicles with or without a driver on BookingJaunt.">
    <meta name="keywords" content="Sri Lanka vehicle hire, car rental Sri Lanka, van hire Sri Lanka, driver Sri Lanka, tuk tuk hire, SUV rental Sri Lanka">
    <link rel="canonical" href="https://bookingjaunt.com/rides.php">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Sri Lanka Vehicle Hire - BookingJaunt">
    <meta property="og:description" content="Find cars, vans, SUVs and more across Sri Lanka. Book with or without a driver on BookingJaunt.">
    <meta property="og:url" content="https://bookingjaunt.com/rides.php">
    <meta property="og:image" content="https://bookingjaunt.com/assets/logo.png">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Sri Lanka Vehicle Hire - BookingJaunt">
    <meta name="twitter:description" content="Find cars, vans, SUVs and more across Sri Lanka. Book with or without a driver on BookingJaunt.">
    <meta name="twitter:image" content="https://bookingjaunt.com/assets/logo.png">
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
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap');

        * { font-family: 'Plus Jakarta Sans', sans-serif; }

        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .no-scrollbar::-webkit-scrollbar { display: none; }

        .vehicle-card { transition: all 0.3s ease; }
        .vehicle-card:hover { transform: translateY(-4px); }

        .category-pill {
            transition: all 0.2s ease;
        }
        .category-pill:hover {
            background-color: #003580;
            color: #ffffff;
            border-color: #003580;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,53,128,0.2);
        }
        .category-pill.active {
            background-color: #003580;
            color: #ffffff;
            border-color: #003580;
            box-shadow: 0 4px 12px rgba(0,53,128,0.25);
        }
        .category-pill.active i {
            color: #febb02;
        }

        .hero-gradient {
            background: linear-gradient(135deg, #003580 0%, #006ce4 60%, #0057b8 100%);
        }

        .search-bar-glass {
            background: rgba(255,255,255,0.12);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,0.25);
        }

        .filter-box {
            background: #ffffff;
            border: 1px solid #e7e7e7;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .filter-title {
            font-size: 16px;
            font-weight: 800;
            color: #1a1a1a;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .filter-group {
            margin-bottom: 18px;
            padding-bottom: 18px;
            border-bottom: 1px solid #f0f0f0;
        }
        .filter-group:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .filter-category-title {
            font-size: 11px;
            font-weight: 800;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 10px;
        }
        .filter-option {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            color: #4a4a4a;
            margin-bottom: 8px;
            cursor: pointer;
        }
        .filter-option input { accent-color: #003580; }

        .pagination-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            border: 1px solid #e7e7e7;
            color: #4a4a4a;
            background: #fff;
            transition: all 0.2s;
        }
        .pagination-btn:hover { background: #003580; color: #fff; border-color: #003580; }
        .pagination-btn.active { background: #003580; color: #fff; border-color: #003580; }
    </style>
</head>

<body class="bg-neutral-50 min-h-screen">

    <?php include 'navbar.php'; ?>

    <!-- =========================================================
         HERO SECTION
    ========================================================= -->
    <section class="hero-gradient py-10 md:py-16 px-4">
        <div class="max-w-[1100px] mx-auto text-center">
            <!-- Title -->
            <div class="inline-flex items-center gap-2 bg-white/15 text-white/90 text-xs font-black uppercase tracking-[0.2em] px-4 py-2 rounded-full mb-5 border border-white/20">
                <i class="fas fa-car"></i> Vehicle Hire Sri Lanka
            </div>
            <h1 class="text-3xl md:text-5xl font-black text-white leading-tight mb-3">
                Find Your Perfect Ride
            </h1>
            <p class="text-white/75 text-base md:text-lg font-medium mb-8">
                Cars, Vans, SUVs &amp; More Across Sri Lanka
            </p>

            <!-- Search Bar -->
            <form method="GET" action="rides.php" class="search-bar-glass rounded-2xl p-3 md:p-4 max-w-4xl mx-auto">
                <div class="flex flex-col md:flex-row gap-3">
                    <!-- Text Search -->
                    <div class="flex-1 flex items-center gap-2 bg-white rounded-xl px-4 py-3">
                        <i class="fas fa-search text-secondary/60 text-sm flex-shrink-0"></i>
                        <input
                            type="text"
                            name="q"
                            value="<?php echo htmlspecialchars($q); ?>"
                            placeholder="Search by name, town, district..."
                            class="w-full text-sm font-medium text-neutral-800 placeholder-neutral-400 outline-none bg-transparent"
                        >
                    </div>

                    <!-- District -->
                    <div class="flex items-center gap-2 bg-white rounded-xl px-4 py-3 min-w-[160px]">
                        <i class="fas fa-map-marker-alt text-secondary/60 text-sm flex-shrink-0"></i>
                        <select name="district" class="w-full text-sm font-medium text-neutral-700 outline-none bg-transparent cursor-pointer">
                            <option value="">All Districts</option>
                            <?php foreach ($sri_lanka_districts as $d): ?>
                                <option value="<?php echo htmlspecialchars($d); ?>" <?php echo ($district === $d) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($d); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Vehicle Type -->
                    <div class="flex items-center gap-2 bg-white rounded-xl px-4 py-3 min-w-[140px]">
                        <i class="fas fa-car text-secondary/60 text-sm flex-shrink-0"></i>
                        <select name="vehicle_type" class="w-full text-sm font-medium text-neutral-700 outline-none bg-transparent cursor-pointer">
                            <option value="">All Vehicles</option>
                            <?php foreach ($vehicle_categories as $val => $cat): ?>
                                <option value="<?php echo $val; ?>" <?php echo ($vehicle_type === $val) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Driver Option -->
                    <div class="flex items-center gap-2 bg-white rounded-xl px-4 py-3 min-w-[150px]">
                        <i class="fas fa-user-tie text-secondary/60 text-sm flex-shrink-0"></i>
                        <select name="driver_option" class="w-full text-sm font-medium text-neutral-700 outline-none bg-transparent cursor-pointer">
                            <option value="">With/Without Driver</option>
                            <option value="with_driver"    <?php echo ($driver_option === 'with_driver')    ? 'selected' : ''; ?>>With Driver</option>
                            <option value="without_driver" <?php echo ($driver_option === 'without_driver') ? 'selected' : ''; ?>>Without Driver</option>
                            <option value="both"           <?php echo ($driver_option === 'both')           ? 'selected' : ''; ?>>Both Options</option>
                        </select>
                    </div>

                    <!-- Search Button -->
                    <button type="submit"
                        class="bg-secondary hover:bg-primary text-white font-black text-sm px-7 py-3 rounded-xl transition-all duration-200 flex items-center gap-2 flex-shrink-0 shadow-lg shadow-secondary/30">
                        <i class="fas fa-search"></i>
                        <span>Search</span>
                    </button>
                </div>
            </form>

            <!-- Quick Stats -->
            <div class="flex items-center justify-center gap-6 mt-6 text-white/60 text-xs font-bold">
                <span class="flex items-center gap-1.5"><i class="fas fa-shield-alt text-gold"></i> Verified Vehicles</span>
                <span class="w-1 h-1 rounded-full bg-white/30"></span>
                <span class="flex items-center gap-1.5"><i class="fas fa-headset text-gold"></i> 24/7 Support</span>
                <span class="w-1 h-1 rounded-full bg-white/30"></span>
                <span class="flex items-center gap-1.5"><i class="fas fa-tag text-gold"></i> Best Prices</span>
            </div>
        </div>
    </section>

    <!-- =========================================================
         VEHICLE CATEGORY PILLS
    ========================================================= -->
    <section class="max-w-[1400px] mx-auto px-4 lg:px-6 mt-8 mb-4">
        <div class="flex items-center gap-3 overflow-x-auto no-scrollbar pb-2">
            <!-- All -->
            <a href="rides.php?q=<?php echo urlencode($q); ?>&district=<?php echo urlencode($district); ?>&driver_option=<?php echo urlencode($driver_option); ?>"
               class="category-pill flex items-center gap-2 px-5 py-2.5 rounded-full border border-neutral-200 bg-white text-neutral-700 text-sm font-bold whitespace-nowrap flex-shrink-0 <?php echo empty($vehicle_type) ? 'active' : ''; ?>">
                <i class="fas fa-th-large <?php echo empty($vehicle_type) ? 'text-gold' : 'text-neutral-400'; ?>"></i>
                All Vehicles
            </a>
            <?php foreach ($vehicle_categories as $val => $cat):
                $isActive = ($vehicle_type === $val);
            ?>
            <a href="rides.php?vehicle_type=<?php echo urlencode($val); ?>&q=<?php echo urlencode($q); ?>&district=<?php echo urlencode($district); ?>&driver_option=<?php echo urlencode($driver_option); ?>"
               class="category-pill flex items-center gap-2 px-5 py-2.5 rounded-full border border-neutral-200 bg-white text-neutral-700 text-sm font-bold whitespace-nowrap flex-shrink-0 <?php echo $isActive ? 'active' : ''; ?>">
                <i class="fas <?php echo $cat['icon']; ?> <?php echo $isActive ? 'text-gold' : 'text-neutral-400'; ?>"></i>
                <?php echo htmlspecialchars($cat['label']); ?>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- =========================================================
         STRIP ADS (Horizontal, Desktop)
    ========================================================= -->
    <?php if (!empty($strip_ads)): ?>
        <div class="max-w-[1400px] mx-auto px-4 lg:px-6 mb-6 hidden md:block">
            <div class="grid grid-cols-2 gap-6">
                <?php foreach ($strip_ads as $ad): ?>
                    <div class="bg-gray-100 p-2 rounded-[2rem] border border-gray-200 overflow-hidden relative">
                        <a href="<?php echo htmlspecialchars($ad['link_url'] ?: '#'); ?>" target="_blank"
                           class="block w-full h-[140px] group overflow-hidden rounded-[1.8rem] relative">
                            <?php if (!empty($ad['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($ad['image_path']); ?>" alt="Advertisement" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                            <?php else: ?>
                                <div class="w-full h-full bg-neutral-200 border border-neutral-300 flex items-center justify-center text-neutral-400 transition-all group-hover:bg-neutral-300">
                                    <i class="fas fa-ad text-2xl mr-3 opacity-20"></i>
                                    <span class="text-[11px] font-black uppercase tracking-widest">Advertisement <?php echo $ad_display_count++; ?></span>
                                </div>
                            <?php endif; ?>
                        </a>
                        <div class="absolute bottom-4 left-6 bg-white/80 backdrop-blur-sm px-4 py-1.5 rounded-full shadow-sm pointer-events-none">
                            <span class="text-[12px] font-bold text-primary"><?php echo htmlspecialchars(!empty($ad['ad_title']) ? $ad['ad_title'] : ($ad['owner_name'] ?? 'Sponsored')); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- =========================================================
         MAIN CONTENT: SIDEBAR + RESULTS
    ========================================================= -->
    <main class="max-w-[1400px] mx-auto flex flex-col md:grid md:grid-cols-[240px_1fr] lg:grid-cols-[260px_1fr] gap-6 px-4 lg:px-6 mt-4 mb-16">

        <!-- =====================================================
             SIDEBAR
        ====================================================== -->
        <aside class="hidden md:block">
            <form action="rides.php" method="GET" id="filterForm">
                <input type="hidden" name="q" value="<?php echo htmlspecialchars($q); ?>">

                <div class="filter-box">
                    <div class="filter-title">Filter By</div>

                    <!-- District Filter -->
                    <div class="filter-group">
                        <h4 class="filter-category-title">District</h4>
                        <select name="district" onchange="this.form.submit()"
                            class="w-full border border-neutral-200 rounded-lg px-3 py-2 text-sm font-semibold text-neutral-700 outline-none focus:border-secondary cursor-pointer">
                            <option value="">All Districts</option>
                            <?php foreach ($sri_lanka_districts as $d): ?>
                                <option value="<?php echo htmlspecialchars($d); ?>" <?php echo ($district === $d) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($d); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Vehicle Type Filter -->
                    <div class="filter-group">
                        <h4 class="filter-category-title">Vehicle Type</h4>
                        <?php foreach ($vehicle_categories as $val => $cat): ?>
                            <label class="filter-option">
                                <input type="radio" name="vehicle_type" value="<?php echo $val; ?>" onchange="this.form.submit()"
                                    <?php echo ($vehicle_type === $val) ? 'checked' : ''; ?>>
                                <i class="fas <?php echo $cat['icon']; ?> text-neutral-400 text-xs"></i>
                                <?php echo htmlspecialchars($cat['label']); ?>
                            </label>
                        <?php endforeach; ?>
                        <?php if (!empty($vehicle_type)): ?>
                            <button type="button"
                                onclick="document.getElementsByName('vehicle_type').forEach(r => r.checked = false); this.form.submit();"
                                class="text-[10px] text-red-500 mt-2 font-bold uppercase tracking-widest">
                                Clear Type
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Driver Option Filter -->
                    <div class="filter-group">
                        <h4 class="filter-category-title">Driver Option</h4>
                        <label class="filter-option">
                            <input type="radio" name="driver_option" value="with_driver" onchange="this.form.submit()" <?php echo ($driver_option === 'with_driver') ? 'checked' : ''; ?>>
                            <i class="fas fa-user-tie text-neutral-400 text-xs"></i> With Driver
                        </label>
                        <label class="filter-option">
                            <input type="radio" name="driver_option" value="without_driver" onchange="this.form.submit()" <?php echo ($driver_option === 'without_driver') ? 'checked' : ''; ?>>
                            <i class="fas fa-steering-wheel text-neutral-400 text-xs"></i> Without Driver
                        </label>
                        <label class="filter-option">
                            <input type="radio" name="driver_option" value="both" onchange="this.form.submit()" <?php echo ($driver_option === 'both') ? 'checked' : ''; ?>>
                            <i class="fas fa-users text-neutral-400 text-xs"></i> Both Options
                        </label>
                        <?php if (!empty($driver_option)): ?>
                            <button type="button"
                                onclick="document.getElementsByName('driver_option').forEach(r => r.checked = false); this.form.submit();"
                                class="text-[10px] text-red-500 mt-2 font-bold uppercase tracking-widest">
                                Clear Driver Filter
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

            <!-- Sidebar Ads -->
            <?php if (!empty($sidebar_ads)):
                $sidebar_ad_count = 1;
            ?>
                <div class="mt-6 space-y-5">
                    <h4 class="text-[11px] font-black text-neutral-400 uppercase tracking-[0.2em] mb-4 pl-1">Sponsored</h4>
                    <?php foreach ($sidebar_ads as $ad): ?>
                        <div class="bg-white rounded-2xl border border-neutral-100 shadow-sm overflow-hidden group hover:shadow-md transition-all duration-300">
                            <a href="<?php echo htmlspecialchars($ad['link_url'] ?: '#'); ?>" target="_blank" class="block relative h-44">
                                <?php if (!empty($ad['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($ad['image_path']); ?>" alt="Advertisement" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                <?php else: ?>
                                    <div class="w-full h-full bg-gradient-to-br from-neutral-50 to-neutral-100 flex flex-col items-center justify-center text-neutral-400 transition-all group-hover:from-neutral-100 group-hover:to-neutral-200">
                                        <i class="fas fa-ad text-4xl mb-2 opacity-10"></i>
                                        <span class="text-[11px] font-black uppercase tracking-widest opacity-40">Advertisement <?php echo $sidebar_ad_count++; ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="absolute bottom-0 left-0 right-0 p-4 bg-white/60 backdrop-blur-md border-t border-white/40">
                                    <p class="text-[13px] text-primary font-black truncate w-full">
                                        <?php echo htmlspecialchars(!empty($ad['ad_title']) ? $ad['ad_title'] : ($ad['owner_name'] ?? 'Sponsored')); ?>
                                    </p>
                                </div>
                                <div class="absolute inset-0 bg-primary/5 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </aside>

        <!-- =====================================================
             RESULTS SECTION
        ====================================================== -->
        <section>
            <!-- Results Header -->
            <div class="flex items-center justify-between mb-5 px-0">
                <div>
                    <h2 class="text-xl font-black text-neutral-800">
                        <?php echo number_format($total_results); ?> Vehicle<?php echo $total_results !== 1 ? 's' : ''; ?> Found
                    </h2>
                    <?php if (!empty($q) || !empty($district) || !empty($vehicle_type) || !empty($driver_option)): ?>
                        <p class="text-sm text-neutral-500 mt-0.5 font-medium">
                            Showing results
                            <?php if (!empty($q)): ?> for "<span class="font-bold text-primary"><?php echo htmlspecialchars($q); ?></span>"<?php endif; ?>
                            <?php if (!empty($district)): ?> in <span class="font-bold text-primary"><?php echo htmlspecialchars($district); ?></span><?php endif; ?>
                            <?php if (!empty($vehicle_type)): ?> &bull; <span class="font-bold text-primary"><?php echo htmlspecialchars(ucfirst(str_replace('_', '-', $vehicle_type))); ?></span><?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
                <!-- Active Filters / Clear -->
                <?php if (!empty($q) || !empty($district) || !empty($vehicle_type) || !empty($driver_option)): ?>
                    <a href="rides.php" class="text-xs font-bold text-red-500 hover:text-red-700 flex items-center gap-1.5 transition-colors">
                        <i class="fas fa-times-circle"></i> Clear Filters
                    </a>
                <?php endif; ?>
            </div>

            <!-- ================================================
                 VEHICLE GRID
            ================================================= -->
            <?php if (empty($vehicles)): ?>
                <!-- Empty State -->
                <div class="bg-white border border-neutral-200 rounded-3xl p-16 text-center shadow-sm">
                    <div class="w-20 h-20 bg-neutral-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-car text-3xl text-neutral-300"></i>
                    </div>
                    <h3 class="text-xl font-black text-neutral-800 mb-2">No Vehicles Found</h3>
                    <p class="text-neutral-500 font-medium text-sm max-w-sm mx-auto mb-6">
                        We couldn't find any vehicles matching your search. Try adjusting your filters or search terms.
                    </p>
                    <a href="rides.php"
                       class="inline-flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-xl font-bold text-sm hover:bg-secondary transition-all">
                        <i class="fas fa-redo"></i> View All Vehicles
                    </a>
                </div>

            <?php else: ?>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <?php foreach ($vehicles as $v):
                        // Image
                        $img = !empty($v['cover_image']) ? $v['cover_image'] : 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?auto=format&fit=crop&w=600&q=80';

                        // WhatsApp number: prefer driver_whatsapp, then owner_whatsapp, then owner_phone
                        $wa_number = !empty($v['driver_whatsapp']) ? $v['driver_whatsapp'] : (!empty($v['owner_whatsapp']) ? $v['owner_whatsapp'] : $v['owner_phone'] ?? '');
                        $wa_clean  = preg_replace('/[^0-9+]/', '', $wa_number);
                        if (!empty($wa_clean) && !str_starts_with($wa_clean, '+')) {
                            $wa_clean = '+94' . ltrim($wa_clean, '0');
                        }

                        // Pricing
                        $price_display = '';
                        $price_note    = '';
                        if (($v['pricing_type'] ?? 'day_wise') === 'day_wise') {
                            $price_val = $v['price_per_day'] ?? 0;
                            if ($currency === 'USD') {
                                $price_display = 'USD ' . number_format(ceil($price_val / $exchange_rate));
                            } else {
                                $price_display = 'LKR ' . number_format($price_val);
                            }
                            $price_label = '/ day';
                            if (!empty($v['included_km_per_day']) && $v['included_km_per_day'] > 0) {
                                $price_note = 'Includes ' . $v['included_km_per_day'] . ' km/day';
                            }
                        } else {
                            $price_val = $v['price_per_km'] ?? 0;
                            if ($currency === 'USD') {
                                $price_display = 'USD ' . number_format($price_val / $exchange_rate, 2);
                            } else {
                                $price_display = 'LKR ' . number_format($price_val);
                            }
                            $price_label = '/ km';
                        }

                        // Category label
                        $cat_label = $vehicle_categories[$v['vehicle_category'] ?? '']['label'] ?? ucfirst($v['vehicle_category'] ?? 'Vehicle');
                        $cat_icon  = $vehicle_categories[$v['vehicle_category'] ?? '']['icon'] ?? 'fa-car';

                        // Brand/Model/Year subtitle
                        $bmy_parts = array_filter([$v['brand'] ?? '', $v['model'] ?? '', $v['manufactured_year'] ? (string)$v['manufactured_year'] : '']);
                        $bmy = implode(' • ', $bmy_parts);

                        // Driver availability
                        $driver_available = in_array($v['driver_option'] ?? '', ['with_driver', 'both']);
                    ?>
                    <!-- Vehicle Card -->
                    <div class="vehicle-card bg-white border border-neutral-200 rounded-2xl overflow-hidden shadow-sm hover:shadow-md cursor-pointer group"
                         onclick="window.location.href='vehicle_details.php?id=<?php echo $v['id']; ?>'">

                        <!-- Image -->
                            <div class="relative h-[240px] overflow-hidden mx-3 mt-3 rounded-xl">
                            <img src="<?php echo htmlspecialchars($img); ?>"
                                 alt="<?php echo htmlspecialchars($v['property_name']); ?>"
                                   class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700 rounded-xl"
                                 onerror="this.src='https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?auto=format&fit=crop&w=600&q=80'">

                            <!-- Overlay gradient -->
                               <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent pointer-events-none rounded-xl"></div>

                            <!-- Featured Badge -->
                            <?php if (!empty($v['is_featured'])): ?>
                                <div class="absolute top-3 left-3 bg-gold text-primary font-black text-[10px] uppercase tracking-widest px-2.5 py-1 rounded-lg shadow-sm">
                                    <i class="fas fa-star mr-1"></i>Featured
                                </div>
                            <?php endif; ?>

                            <!-- AC Badge -->
                            <?php if (!empty($v['has_ac'])): ?>
                                <div class="absolute top-3 right-3 bg-secondary text-white font-black text-[10px] uppercase tracking-widest px-2.5 py-1 rounded-lg shadow-sm">
                                    <i class="fas fa-snowflake mr-1"></i>AC
                                </div>
                            <?php endif; ?>

                            <!-- Category Badge -->
                            <div class="absolute bottom-3 left-3 flex items-center gap-1.5 bg-black/50 backdrop-blur-sm text-white text-[11px] font-bold px-2.5 py-1 rounded-lg">
                                <i class="fas <?php echo $cat_icon; ?>"></i>
                                <?php echo htmlspecialchars($cat_label); ?>
                            </div>

                            <!-- WhatsApp Button -->
                            <?php if (!empty($wa_clean)): ?>
                                <a href="https://wa.me/<?php echo ltrim($wa_clean, '+'); ?>"
                                   target="_blank"
                                   onclick="event.stopPropagation();"
                                   class="absolute bottom-3 right-3 flex items-center gap-1.5 bg-[#25D366] text-white text-[11px] font-bold px-2.5 py-1.5 rounded-lg shadow-md hover:bg-[#128C7E] transition-colors">
                                    <i class="fab fa-whatsapp text-sm"></i>
                                    WhatsApp
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- Card Body -->
                        <div class="p-4">
                            <!-- Name -->
                            <h3 class="font-bold text-neutral-800 text-lg leading-tight mb-0.5 line-clamp-1">
                                <?php echo htmlspecialchars($v['property_name']); ?>
                            </h3>

                            <!-- Brand / Model / Year -->
                            <?php if (!empty($bmy)): ?>
                                <p class="text-secondary text-[12px] font-bold mb-2"><?php echo htmlspecialchars($bmy); ?></p>
                            <?php endif; ?>

                            <!-- Location -->
                            <div class="flex items-center gap-1.5 text-neutral-500 text-[12px] font-medium mb-1">
                                <i class="fas fa-map-marker-alt text-primary text-[11px]"></i>
                                <span>
                                    <?php
                                    $loc_parts = array_filter([$v['district'] ?? '', $v['closest_main_town'] ?? '']);
                                    echo htmlspecialchars(implode(' • ', $loc_parts));
                                    ?>
                                </span>
                            </div>

                            <!-- Pickup Location -->
                            <?php if (!empty($v['exact_pickup_location'])): ?>
                                <div class="flex items-center gap-1.5 text-neutral-500 text-[12px] font-medium mb-2">
                                    <i class="fas fa-map-pin text-secondary text-[11px]"></i>
                                    <span class="line-clamp-1"><?php echo htmlspecialchars($v['exact_pickup_location']); ?></span>
                                </div>
                            <?php endif; ?>

                            <?php
                            $delivery_towns_label = '';
                            if (!empty($v['delivery_towns_json'])) {
                                $decoded = json_decode($v['delivery_towns_json'], true);
                                if (is_array($decoded)) {
                                    $delivery_towns_label = implode(', ', array_filter($decoded));
                                } else {
                                    $delivery_towns_label = trim($v['delivery_towns_json']);
                                }
                            }
                            ?>
                            <?php if (!empty($delivery_towns_label)): ?>
                                <div class="flex items-center gap-1.5 text-neutral-500 text-[12px] font-medium mb-2">
                                    <i class="fas fa-route text-primary text-[11px]"></i>
                                    <span class="line-clamp-1">Delivery towns: <?php echo htmlspecialchars($delivery_towns_label); ?></span>
                                </div>
                            <?php endif; ?>

                            <!-- Features Row -->
                            <div class="flex flex-wrap items-center gap-2 mt-3 mb-3">
                                <!-- Seats -->
                                <?php if (!empty($v['seat_count'])): ?>
                                    <div class="flex items-center gap-1 bg-neutral-100 text-neutral-600 text-[11px] font-bold px-2.5 py-1 rounded-lg border border-neutral-200">
                                        <i class="fas fa-users text-neutral-400"></i>
                                        <?php echo (int)$v['seat_count']; ?> Seats
                                    </div>
                                <?php endif; ?>

                                <!-- Luggage -->
                                <?php if (!empty($v['luggage_count'])): ?>
                                    <div class="flex items-center gap-1 bg-neutral-100 text-neutral-600 text-[11px] font-bold px-2.5 py-1 rounded-lg border border-neutral-200">
                                        <i class="fas fa-suitcase text-neutral-400"></i>
                                        <?php echo (int)$v['luggage_count']; ?> Luggage
                                    </div>
                                <?php endif; ?>

                                <!-- AC Badge in features -->
                                <?php if (!empty($v['has_ac'])): ?>
                                    <div class="flex items-center gap-1 bg-blue-50 text-blue-600 text-[11px] font-bold px-2.5 py-1 rounded-lg border border-blue-100">
                                        <i class="fas fa-snowflake"></i> AC
                                    </div>
                                <?php endif; ?>

                                <!-- GPS -->
                                <?php if (!empty($v['has_gps'])): ?>
                                    <div class="flex items-center gap-1 bg-neutral-100 text-neutral-600 text-[11px] font-bold px-2.5 py-1 rounded-lg border border-neutral-200">
                                        <i class="fas fa-satellite-dish text-neutral-400"></i> GPS
                                    </div>
                                <?php endif; ?>

                                <!-- Driver Available -->
                                <?php if ($driver_available): ?>
                                    <div class="flex items-center gap-1 bg-green-50 text-green-700 text-[11px] font-bold px-2.5 py-1 rounded-lg border border-green-100">
                                        <i class="fas fa-user-tie"></i> Driver Available
                                    </div>
                                <?php endif; ?>

                                <!-- Fuel Type -->
                                <?php if (!empty($v['fuel_type'])): ?>
                                    <div class="flex items-center gap-1 bg-neutral-100 text-neutral-600 text-[11px] font-bold px-2.5 py-1 rounded-lg border border-neutral-200">
                                        <i class="fas fa-gas-pump text-neutral-400"></i>
                                        <?php echo htmlspecialchars(ucfirst($v['fuel_type'])); ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Transmission -->
                                <?php if (!empty($v['transmission_type'])): ?>
                                    <div class="flex items-center gap-1 bg-neutral-100 text-neutral-600 text-[11px] font-bold px-2.5 py-1 rounded-lg border border-neutral-200">
                                        <i class="fas fa-cogs text-neutral-400"></i>
                                        <?php echo htmlspecialchars(ucfirst($v['transmission_type'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Divider -->
                            <div class="border-t border-neutral-100 my-3"></div>

                            <!-- Pricing + Book Now -->
                            <div class="flex items-end justify-between gap-3">
                                <div>
                                    <div class="text-[11px] text-neutral-400 font-medium mb-0.5">Starting from</div>
                                    <div class="text-2xl font-black text-primary leading-none">
                                        <?php echo htmlspecialchars($price_display); ?>
                                        <span class="text-sm font-bold text-neutral-400"><?php echo $price_label; ?></span>
                                    </div>
                                    <?php if (!empty($price_note)): ?>
                                        <div class="text-[11px] text-palm font-bold mt-0.5 flex items-center gap-1">
                                            <i class="fas fa-check-circle"></i>
                                            <?php echo htmlspecialchars($price_note); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <a href="vehicle_details.php?id=<?php echo $v['id']; ?>"
                                   onclick="event.stopPropagation();"
                                   class="flex-shrink-0 bg-secondary hover:bg-primary text-white text-sm font-black px-5 py-2.5 rounded-xl transition-all duration-200 shadow-sm hover:shadow-md">
                                    Book Now
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- ============================================
                     PAGINATION
                ============================================= -->
                <?php if ($total_pages > 1):
                    // Build base query string without 'page'
                    $qp = $_GET;
                    unset($qp['page']);
                    $base_qs = http_build_query($qp);
                    $base_url = 'rides.php?' . ($base_qs ? $base_qs . '&' : '');
                ?>
                <div class="flex items-center justify-center gap-2 mt-10">
                    <!-- Prev -->
                    <?php if ($page > 1): ?>
                        <a href="<?php echo $base_url; ?>page=<?php echo $page - 1; ?>" class="pagination-btn">
                            <i class="fas fa-chevron-left text-xs"></i>
                        </a>
                    <?php else: ?>
                        <span class="pagination-btn opacity-40 cursor-not-allowed">
                            <i class="fas fa-chevron-left text-xs"></i>
                        </span>
                    <?php endif; ?>

                    <!-- Page Numbers -->
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page   = min($total_pages, $page + 2);
                    if ($start_page > 1): ?>
                        <a href="<?php echo $base_url; ?>page=1" class="pagination-btn">1</a>
                        <?php if ($start_page > 2): ?>
                            <span class="text-neutral-400 font-bold px-1">…</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="<?php echo $base_url; ?>page=<?php echo $i; ?>"
                           class="pagination-btn <?php echo ($i === $page) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span class="text-neutral-400 font-bold px-1">…</span>
                        <?php endif; ?>
                        <a href="<?php echo $base_url; ?>page=<?php echo $total_pages; ?>" class="pagination-btn">
                            <?php echo $total_pages; ?>
                        </a>
                    <?php endif; ?>

                    <!-- Next -->
                    <?php if ($page < $total_pages): ?>
                        <a href="<?php echo $base_url; ?>page=<?php echo $page + 1; ?>" class="pagination-btn">
                            <i class="fas fa-chevron-right text-xs"></i>
                        </a>
                    <?php else: ?>
                        <span class="pagination-btn opacity-40 cursor-not-allowed">
                            <i class="fas fa-chevron-right text-xs"></i>
                        </span>
                    <?php endif; ?>
                </div>

                <p class="text-center text-xs text-neutral-400 font-medium mt-3">
                    Page <?php echo $page; ?> of <?php echo $total_pages; ?> &bull; <?php echo number_format($total_results); ?> total vehicles
                </p>
                <?php endif; ?>

            <?php endif; ?>

        </section><!-- /results -->
    </main>

    <!-- =========================================================
         WHY BOOK WITH US (Trust Bar)
    ========================================================= -->
    <section class="max-w-[1400px] mx-auto px-4 lg:px-6 py-10 mb-8">
        <div class="bg-white border border-neutral-100 rounded-3xl p-8 md:p-10 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="flex gap-4 items-start">
                <div class="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary flex-shrink-0">
                    <i class="fas fa-shield-alt text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-neutral-800 mb-1">Verified Vehicles</h3>
                    <p class="text-sm text-text-secondary">All listed vehicles are verified with valid documents and insurance for your peace of mind.</p>
                </div>
            </div>
            <div class="flex gap-4 items-start">
                <div class="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary flex-shrink-0">
                    <i class="fas fa-headset text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-neutral-800 mb-1">24/7 Support</h3>
                    <p class="text-sm text-text-secondary">Our dedicated team is here to help you anytime, anywhere in Sri Lanka.</p>
                </div>
            </div>
            <div class="flex gap-4 items-start">
                <div class="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary flex-shrink-0">
                    <i class="fas fa-thumbs-up text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-neutral-800 mb-1">Best Price Guarantee</h3>
                    <p class="text-sm text-text-secondary">Find a lower price? We'll match it and give you an extra 5% off.</p>
                </div>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>

</body>
</html>
