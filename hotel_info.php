<?php
require_once 'config.php';
session_start();

$property_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Deal of the Day override
$deal_room_id = isset($_GET['deal_room']) ? (int)$_GET['deal_room'] : 0;
$deal_price_override = isset($_GET['deal_price']) ? (float)$_GET['deal_price'] : 0;
// Validate deal is still active
if ($deal_room_id && $deal_price_override > 0) {
    $deal_check = $pdo->prepare("SELECT id, deal_price, room_id FROM deals_of_the_day WHERE room_id = ? AND property_id = ? AND is_active = 1 AND valid_from <= CURDATE() AND valid_until >= CURDATE() LIMIT 1");
    $deal_check->execute([$deal_room_id, $property_id]);
    $valid_deal = $deal_check->fetch();
    if (!$valid_deal) {
        $deal_room_id = 0;
        $deal_price_override = 0;
    }
}

if ($property_id <= 0) {
    header("Location: index.php");
    exit();
}

// Fetch Property Details
$stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
$stmt->execute([$property_id]);
$property = $stmt->fetch();

if (!$property) {
    header("Location: index.php");
    exit();
}

// Fetch Amenities
$amenities_stmt = $pdo->prepare("SELECT am.amenity_name, am.icon FROM property_amenities pa JOIN amenities_master am ON pa.amenity_id = am.id WHERE pa.property_id = ?");
$amenities_stmt->execute([$property_id]);
$amenities = $amenities_stmt->fetchAll();

// Handle Availability Dates
$dates_specified = !empty($_GET['checkin']) && !empty($_GET['checkout']);
$check_in = $dates_specified ? $_GET['checkin'] : '';
$check_out = $dates_specified ? $_GET['checkout'] : '';
$availability_check_in = $dates_specified ? $check_in : date('Y-m-d');
$availability_check_out = $dates_specified ? $check_out : date('Y-m-d', strtotime('+1 day'));

// Calculate Nights
$date1 = new DateTime($availability_check_in);
$date2 = new DateTime($availability_check_out);
$nights = $date1->diff($date2)->days;
if ($nights <= 0) $nights = 1;

// Fetch Rooms with availability check
$rooms_stmt = $pdo->prepare("
    SELECT pr.*, 
           (pr.total_rooms - COALESCE(b_count.booked_rooms, 0)) as available_count
    FROM property_rooms pr
    LEFT JOIN (
        SELECT room_id, COUNT(*) as booked_rooms 
        FROM bookings 
        WHERE status NOT IN ('cancelled', 'checked_out')
        AND (
            (check_in_date < ? AND check_out_date > ?)
        )
        GROUP BY room_id
    ) b_count ON pr.id = b_count.room_id
    WHERE pr.property_id = ?
    HAVING available_count > 0
");
$rooms_stmt->execute([$availability_check_out, $availability_check_in, $property_id]);
$rooms = $rooms_stmt->fetchAll();

// Deal of the Day override: check if there are active deals for the rooms
foreach ($rooms as &$room) {
    $deal_stmt = $pdo->prepare("SELECT deal_price, original_price, deal_label FROM deals_of_the_day WHERE room_id = ? AND property_id = ? AND is_active = 1 AND valid_from <= CURDATE() AND valid_until >= CURDATE() LIMIT 1");
    $deal_stmt->execute([$room['id'], $property_id]);
    $deal = $deal_stmt->fetch();
    if ($deal) {
        $room['original_price_lkr'] = $room['price_lkr'];
        $room['price_lkr'] = $deal['deal_price'];
        $room['deal_label'] = $deal['deal_label'];
        $room['is_deal'] = true;
    } else {
        $room['is_deal'] = false;
    }
}
unset($room);

// Fetch Media
$media_stmt = $pdo->prepare("SELECT media_path, is_featured FROM property_media WHERE property_id = ? AND media_type = 'image' ORDER BY is_featured DESC, sort_order ASC");
$media_stmt->execute([$property_id]);
$media_rows = $media_stmt->fetchAll(PDO::FETCH_ASSOC);

$images = [];
if (!empty($property['cover_image'])) {
    $images[] = $property['cover_image'];
}
if (!empty($media_rows)) {
    foreach ($media_rows as $row) {
        if (!in_array($row['media_path'], $images)) {
            $images[] = $row['media_path'];
        }
    }
}

$totalImages = count($images);
$currency = $_SESSION['currency'] ?? 'LKR';
$exchange_rate = 300;

// Stars logic
$stars = 0;
if ($property['hotel_category'] == 'budget_friendly') $stars = 3;
if ($property['hotel_category'] == 'luxury') $stars = 4;
if ($property['hotel_category'] == 'super_luxury') $stars = 5;

// Fetch real reviews
$reviews_stmt = $pdo->prepare("
    SELECT r.*, CONCAT(u.first_name, ' ', u.last_name) as user_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.property_id = ? 
    ORDER BY r.created_at DESC
");
$reviews_stmt->execute([$property_id]);
$reviews = $reviews_stmt->fetchAll();

// Calculate average rating
$avg_rating_stmt = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as review_count FROM reviews WHERE property_id = ?");
$avg_rating_stmt->execute([$property_id]);
$rating_stats = $avg_rating_stmt->fetch();
$avg_rating = round($rating_stats['avg_rating'], 1) ?: 'New';
$review_count = $rating_stats['review_count'];

// Fetch nearest tourist destinations (popular destinations in the same district or matching the city)
$nearest_destinations = [];
if (!empty($property['district']) || !empty($property['city'])) {
    try {
        $dest_stmt = $pdo->prepare("
            SELECT * FROM popular_destinations 
            WHERE is_active = 1 
              AND (
                LOWER(district_name) = LOWER(:district) 
                OR LOWER(destination_name) = LOWER(:district)
                OR LOWER(district_name) = LOWER(:city) 
                OR LOWER(destination_name) = LOWER(:city)
              )
            ORDER BY sort_order, id
        ");
        $dest_stmt->execute([
            'district' => $property['district'] ?? '',
            'city' => $property['city'] ?? ''
        ]);
        $nearest_destinations = $dest_stmt->fetchAll();
    } catch (PDOException $e) {
        $nearest_destinations = [];
    }
}

// Fetch Hotels Near Me (same city first, then district)
$hotels_near = [];
$current_city = $property['city'] ?? '';
$current_district = $property['district'] ?? '';
if (!empty($current_city) || !empty($current_district)) {
    try {
        $hotels_near_stmt = $pdo->prepare("
            SELECT p.*, MIN(pr.price_lkr) as price_lkr
            FROM properties p
            LEFT JOIN property_rooms pr ON pr.property_id = p.id
            WHERE p.business_type != 'vehicle'
              AND p.approval_status = 'approved'
              AND p.id != ?
              AND (
                  (? != '' AND LOWER(p.city) = LOWER(?))
                  OR (? != '' AND LOWER(p.district) = LOWER(?))
              )
            GROUP BY p.id
            ORDER BY 
              CASE WHEN (? != '' AND LOWER(p.city) = LOWER(?)) THEN 1 ELSE 2 END ASC,
              p.id DESC
            LIMIT 10
        ");
        $hotels_near_stmt->execute([
            $property_id,
            $current_city, $current_city,
            $current_district, $current_district,
            $current_city, $current_city
        ]);
        $hotels_near = $hotels_near_stmt->fetchAll();

        // Check for active deals
        $deal_stmt = $pdo->prepare("SELECT deal_price FROM deals_of_the_day WHERE property_id = ? AND is_active = 1 AND valid_from <= CURDATE() AND valid_until >= CURDATE() LIMIT 1");
        foreach ($hotels_near as &$h) {
            $deal_stmt->execute([$h['id']]);
            $deal = $deal_stmt->fetch();
            if ($deal) {
                $h['original_price_lkr'] = $h['price_lkr'];
                $h['price_lkr'] = $deal['deal_price'];
                $h['is_deal'] = true;
            } else {
                $h['is_deal'] = false;
            }
        }
        unset($h);
    } catch (PDOException $e) {
        $hotels_near = [];
    }
}

// Fetch Rides Near Me (same city first, then district)
$rides_near = [];
if (!empty($current_city) || !empty($current_district)) {
    try {
        $rides_near_stmt = $pdo->prepare("
            SELECT *
            FROM properties
            WHERE business_type = 'vehicle'
              AND approval_status = 'approved'
              AND (
                  (? != '' AND LOWER(city) = LOWER(?))
                  OR (? != '' AND LOWER(district) = LOWER(?))
              )
            ORDER BY 
              CASE WHEN (? != '' AND LOWER(city) = LOWER(?)) THEN 1 ELSE 2 END ASC,
              id DESC
            LIMIT 10
        ");
        $rides_near_stmt->execute([
            $current_city, $current_city,
            $current_district, $current_district,
            $current_city, $current_city
        ]);
        $rides_near = $rides_near_stmt->fetchAll();
    } catch (PDOException $e) {
        $rides_near = [];
    }
}
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
    <title><?php echo htmlspecialchars($property['property_name']); ?> - Bookingjaunt</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'], display: ['Outfit', 'sans-serif'] },
                    colors: {
                        brand: { 50: '#f0f6ff', 100: '#e0edff', 600: '#006ce4', 700: '#0057b8', 900: '#003580' },
                        palm: '#008009', gold: '#febb02'
                    }
                }
            }
        }
    </script>
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        body { font-family: 'Inter', sans-serif; color: #1a1a1a; background-color: #dbeafe; }
        .font-display { font-family: 'Outfit', sans-serif; }
        .lightbox-modal { display: none; position: fixed; z-index: 9999; padding-top: 50px; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); backdrop-filter: blur(10px); }
        .lightbox-content { margin: auto; display: block; max-width: 90%; max-height: 80vh; border-radius: 8px; }
        .close-lightbox { position: absolute; top: 20px; right: 35px; color: #fff; font-size: 50px; cursor: pointer; }
    </style>
    <script>
        function openLightbox(src) { document.getElementById("myLightbox").style.display = "block"; document.getElementById("imgLightbox").src = src; document.body.style.overflow = "hidden"; }
        function closeLightbox() { document.getElementById("myLightbox").style.display = "none"; document.body.style.overflow = "auto"; }
        function openGallery() { document.getElementById("galleryModal").classList.remove("hidden"); document.body.style.overflow = "hidden"; }
        function closeGallery() { document.getElementById("galleryModal").classList.add("hidden"); document.body.style.overflow = "auto"; }

        function validateBooking(event, checkin, checkout, specified) {
            if (!specified) {
                event.preventDefault();
                alert("Please select your check-in and check-out dates, then click Check Availability.");
                document.getElementById('availability').scrollIntoView({ behavior: 'smooth' });
                const inputs = document.querySelectorAll('#availability input[type="date"]');
                inputs.forEach(i => {
                    i.classList.add('ring-4', 'ring-brand-600/30');
                    setTimeout(() => i.classList.remove('ring-4', 'ring-brand-600/30'), 3000);
                });
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <main class="max-w-[1150px] mx-auto px-4 md:px-4 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between gap-6 mb-6">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <span class="bg-neutral-600 text-white text-[10px] font-bold px-2 py-0.5 rounded">HOTEL</span>
                    <div class="flex gap-0.5 text-gold text-[11px]">
                        <?php for ($i = 0; $i < $stars; $i++): ?><i class="fas fa-star"></i><?php endfor; ?>
                    </div>
                </div>
                <h1 class="text-2xl md:text-3xl font-extrabold font-display"><?php echo htmlspecialchars($property['property_name']); ?></h1>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-[13px] text-neutral-600 mt-2">
                    <div class="flex items-center gap-1.5">
                        <i class="fas fa-map-marker-alt text-brand-600"></i>
                        <span><?php echo htmlspecialchars($property['street_address'] . ', ' . $property['city'] . ', ' . $property['district']); ?></span>
                    </div>
                    <?php if (!empty($property['google_map_location'])): ?>
                        <span class="text-neutral-300 hidden sm:inline">|</span>
                        <a href="<?php echo htmlspecialchars($property['google_map_location']); ?>" target="_blank" class="inline-flex items-center gap-1 text-brand-600 hover:text-brand-700 font-bold hover:underline transition-all bg-brand-50 px-2 py-0.5 rounded-md text-xs border border-brand-100/50">
                            <i class="fas fa-location-arrow text-[10px]"></i> View on Map
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="hidden md:flex items-center gap-3">
                <?php if (!empty($property['google_map_location'])): ?>
                    <a href="<?php echo htmlspecialchars($property['google_map_location']); ?>" target="_blank" class="bg-white hover:bg-blue-50 text-brand-600 border border-blue-200 px-6 py-2.5 rounded-lg font-bold transition-all flex items-center gap-2 shadow-sm">
                        <i class="fas fa-map-marked-alt"></i> View Map
                    </a>
                <?php endif; ?>
                <button onclick="document.getElementById('availability').scrollIntoView({ behavior: 'smooth' })" class="bg-brand-600 text-white px-8 py-2.5 rounded-lg font-bold hover:bg-brand-700 transition-colors">Reserve</button>
            </div>
        </div>

        <!-- Gallery -->
        <?php if (!empty($images)): ?>
        <?php $totalImages = count($images); ?>

        <!-- Mobile: Horizontal Scroll -->
        <div class="md:hidden flex overflow-x-auto snap-x snap-mandatory no-scrollbar h-[300px] mb-6 rounded-xl">
            <?php foreach ($images as $img): ?>
                <img src="<?php echo htmlspecialchars($img); ?>" class="w-full shrink-0 snap-center object-cover" onclick="openLightbox(this.src)">
            <?php endforeach; ?>
        </div>

        <!-- Desktop: Premium Mosaic Gallery -->
        <div class="hidden md:grid grid-cols-4 grid-rows-2 gap-2 h-[480px] mb-10 rounded-xl overflow-hidden shadow-sm relative">
            <!-- Large Main Image -->
            <div class="col-span-2 row-span-2 relative group overflow-hidden border border-blue-200/20">
                <img src="<?php echo htmlspecialchars($images[0]); ?>" class="w-full h-full object-cover cursor-pointer hover:scale-105 transition-transform duration-700" onclick="openLightbox(this.src)">
            </div>

            <!-- Small Images (up to 4 more) -->
            <?php 
            $smallImages = array_slice($images, 1, 4);
            foreach ($smallImages as $index => $img): 
                $isLast = ($index === 3 && $totalImages > 5);
            ?>
                <div class="relative group overflow-hidden border border-blue-200/20 <?php echo $isLast ? 'bg-neutral-900' : ''; ?>">
                    <img src="<?php echo htmlspecialchars($img); ?>" 
                         class="w-full h-full object-cover cursor-pointer hover:scale-105 transition-transform duration-700 <?php echo $isLast ? 'opacity-50' : ''; ?>" 
                         onclick="openLightbox(this.src)">
                    
                    <?php if ($isLast): ?>
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-white bg-black/40 cursor-pointer" onclick="openGallery()">
                            <i class="fas fa-images text-2xl mb-2"></i>
                            <span class="font-bold text-[13px] tracking-wide">+<?php echo $totalImages - 5; ?> PHOTOS</span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <!-- Fallback placeholders if fewer than 5 images -->
            <?php for($i = count($smallImages); $i < 4; $i++): ?>
                <div class="bg-[#dbeafe]"></div>
            <?php endfor; ?>

            <?php if ($totalImages > 5): ?>
                <button onclick="openGallery()" class="absolute bottom-4 right-4 bg-white/95 backdrop-blur-md text-brand-900 border border-blue-200 rounded-full px-5 py-2 text-[12px] font-bold shadow-xl hover:bg-white transition-all flex items-center gap-2 z-20">
                    <i class="fas fa-th-large text-brand-600"></i>
                    <span>See all <?php echo $totalImages; ?> photos</span>
                </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Content -->
        <div class="grid grid-cols-1 lg:grid-cols-[1fr_350px] gap-10">
            <div>
                <p class="text-[15px] text-neutral-700 leading-relaxed mb-10"><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
                <div class="mb-10">
                    <h3 class="text-xl font-bold font-display mb-6">Most popular facilities</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-5">
                        <?php foreach ($amenities as $am): ?>
                            <div class="flex items-center gap-3 text-[14px] text-palm font-semibold">
                                <i class="fas <?php echo !empty($am['icon']) ? htmlspecialchars($am['icon']) : 'fa-check'; ?> text-brand-600"></i>
                                <span><?php echo htmlspecialchars($am['amenity_name']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php 
                // Parse tourist attractions robustly (JSON array or newline string)
                $attractions = [];
                if (!empty($property['tourist_attractions'])) {
                    $decoded = json_decode($property['tourist_attractions'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $attractions = $decoded;
                    } else {
                        $raw_lines = explode("\n", $property['tourist_attractions']);
                        foreach ($raw_lines as $line) {
                            $cleaned = trim(str_replace(['-', '•', '*', '[', ']', '"', "'"], '', $line));
                            if (!empty($cleaned)) {
                                $attractions[] = $cleaned;
                            }
                        }
                    }
                }

                // Pre-process Payment, Food, and Security features
                $accepted_methods = [];
                if (!empty($property['pay_cash'])) $accepted_methods[] = ['label' => 'Cash Accepted', 'icon' => 'fa-money-bill-wave', 'color' => 'emerald'];
                if (!empty($property['pay_cc'])) $accepted_methods[] = ['label' => 'Credit Card', 'icon' => 'fa-credit-card', 'color' => 'blue'];
                if (!empty($property['pay_debit'])) $accepted_methods[] = ['label' => 'Debit Card', 'icon' => 'fa-wallet', 'color' => 'indigo'];
                if (!empty($property['pay_online'])) $accepted_methods[] = ['label' => 'Online Payment (UPI/Wallets/Gateway)', 'icon' => 'fa-qrcode', 'color' => 'purple'];
                if (!empty($property['pay_bank'])) $accepted_methods[] = ['label' => 'Bank Transfer', 'icon' => 'fa-university', 'color' => 'teal'];
                if (!empty($property['pay_installments'])) $accepted_methods[] = ['label' => 'Installment Plans', 'icon' => 'fa-percentage', 'color' => 'amber'];

                if (!empty($property['custom_payments_json'])) {
                    $decoded = json_decode($property['custom_payments_json'], true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $opt) {
                            if (!empty(trim($opt))) {
                                $accepted_methods[] = ['label' => trim($opt), 'icon' => 'fa-check-circle', 'color' => 'brand'];
                            }
                        }
                    }
                }

                $food_features = [];
                if (!empty($property['food_breakfast_included'])) {
                    $desc = !empty($property['food_breakfast_type']) ? 'Type: ' . htmlspecialchars($property['food_breakfast_type']) : 'Included';
                    $food_features[] = ['label' => 'Breakfast Included', 'desc' => $desc, 'icon' => 'fa-mug-hot', 'color' => 'amber'];
                }
                if (!empty($property['food_restaurant_available'])) {
                    $desc = !empty($property['food_restaurant_count']) ? htmlspecialchars($property['food_restaurant_count']) . ' on-site restaurant(s)' : 'Available';
                    $food_features[] = ['label' => 'On-site Restaurant', 'desc' => $desc, 'icon' => 'fa-utensils', 'color' => 'rose'];
                }
                if (!empty($property['food_room_service'])) {
                    $desc = !empty($property['food_room_service_247']) ? 'Available 24/7' : 'Available';
                    $food_features[] = ['label' => 'Room Service', 'desc' => $desc, 'icon' => 'fa-concierge-bell', 'color' => 'emerald'];
                }
                if (!empty($property['food_vegetarian'])) {
                    $food_features[] = ['label' => 'Vegetarian Options', 'desc' => 'Available', 'icon' => 'fa-leaf', 'color' => 'green'];
                }
                if (!empty($property['food_vegan'])) {
                    $food_features[] = ['label' => 'Vegan Options', 'desc' => 'Available', 'icon' => 'fa-seedling', 'color' => 'green'];
                }
                if (!empty($property['food_halal'])) {
                    $food_features[] = ['label' => 'Halal Food', 'desc' => 'Available', 'icon' => 'fa-certificate', 'color' => 'teal'];
                }
                if (!empty($property['food_buffet'])) {
                    $food_features[] = ['label' => 'Buffet Available', 'desc' => 'Yes', 'icon' => 'fa-hamburger', 'color' => 'orange'];
                }
                if (!empty($property['food_delivery_allowed'])) {
                    $food_features[] = ['label' => 'Food Delivery Allowed', 'desc' => 'From external apps', 'icon' => 'fa-truck', 'color' => 'blue'];
                }
                if (!empty($property['food_dietary_options'])) {
                    $food_features[] = ['label' => 'Special Dietary Support', 'desc' => 'Available', 'icon' => 'fa-carrot', 'color' => 'indigo'];
                }
                if (!empty($property['food_kitchen_in_room'])) {
                    $food_features[] = ['label' => 'In-room Kitchen', 'desc' => 'Yes', 'icon' => 'fa-sink', 'color' => 'cyan'];
                }
                if (!empty($property['food_minibar'])) {
                    $food_features[] = ['label' => 'Mini Bar', 'desc' => 'In-room amenities', 'icon' => 'fa-wine-glass', 'color' => 'violet'];
                }

                if (!empty($property['custom_food_json'])) {
                    $decoded = json_decode($property['custom_food_json'], true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $opt) {
                            if (!empty(trim($opt))) {
                                $food_features[] = ['label' => trim($opt), 'desc' => 'Available Option', 'icon' => 'fa-plus-circle', 'color' => 'brand'];
                            }
                        }
                    }
                }

                $security_features = [];
                if (!empty($property['sec_staff_247'])) {
                    $security_features[] = ['label' => '24/7 Security Staff', 'desc' => 'On-site presence', 'icon' => 'fa-user-shield', 'color' => 'blue'];
                }
                if (!empty($property['sec_cctv'])) {
                    $desc = !empty($property['sec_cctv_coverage']) ? 'Coverage: ' . htmlspecialchars($property['sec_cctv_coverage']) : 'Monitored area';
                    $security_features[] = ['label' => 'CCTV Surveillance', 'desc' => $desc, 'icon' => 'fa-video', 'color' => 'indigo'];
                }
                if (!empty($property['sec_smoke_detectors'])) {
                    $security_features[] = ['label' => 'Smoke Detectors', 'desc' => 'Installed', 'icon' => 'fa-wind', 'color' => 'cyan'];
                }
                if (!empty($property['sec_fire_extinguishers'])) {
                    $security_features[] = ['label' => 'Fire Extinguishers', 'desc' => 'Equipped', 'icon' => 'fa-fire-extinguisher', 'color' => 'rose'];
                }
                if (!empty($property['sec_fire_alarm'])) {
                    $security_features[] = ['label' => 'Fire Alarm System', 'desc' => 'Operational', 'icon' => 'fa-bell', 'color' => 'red'];
                }
                if (!empty($property['sec_emergency_exit_plan'])) {
                    $security_features[] = ['label' => 'Emergency Exit Plan', 'desc' => 'Posted in rooms', 'icon' => 'fa-door-open', 'color' => 'emerald'];
                }
                if (!empty($property['sec_key_card_access'])) {
                    $security_features[] = ['label' => 'Key Card Access', 'desc' => 'Secure entry', 'icon' => 'fa-id-card', 'color' => 'purple'];
                }
                if (!empty($property['sec_digital_lock'])) {
                    $security_features[] = ['label' => 'Digital Smart Lock', 'desc' => 'Keyless entry', 'icon' => 'fa-key', 'color' => 'violet'];
                }
                if (!empty($property['sec_biometric_access'])) {
                    $security_features[] = ['label' => 'Biometric Access', 'desc' => 'Scanner', 'icon' => 'fa-fingerprint', 'color' => 'fuchsia'];
                }
                if (!empty($property['sec_safe_box'])) {
                    $security_features[] = ['label' => 'In-room Safe Box', 'desc' => 'Secure storage', 'icon' => 'fa-vault', 'color' => 'amber'];
                }
                if (!empty($property['sec_luggage_storage'])) {
                    $security_features[] = ['label' => 'Secure Luggage Storage', 'desc' => 'Available', 'icon' => 'fa-suitcase', 'color' => 'teal'];
                }
                if (!empty($property['sec_female_floor'])) {
                    $security_features[] = ['label' => 'Female-only Floor', 'desc' => 'Enhanced privacy', 'icon' => 'fa-venus', 'color' => 'pink'];
                }
                if (!empty($property['sec_panic_button'])) {
                    $security_features[] = ['label' => 'Panic Buttons', 'desc' => 'Installed', 'icon' => 'fa-exclamation-triangle', 'color' => 'orange'];
                }
                if (!empty($property['sec_first_aid'])) {
                    $security_features[] = ['label' => 'First Aid Kit', 'desc' => 'On-site availability', 'icon' => 'fa-first-aid', 'color' => 'red'];
                }
                if (!empty($property['sec_medical_support'])) {
                    $security_features[] = ['label' => 'On-call Medical Support', 'desc' => 'Available', 'icon' => 'fa-user-md', 'color' => 'emerald'];
                }
                if (!empty($property['sec_patrol_frequency'])) {
                    $security_features[] = ['label' => 'Security Patrols', 'desc' => htmlspecialchars($property['sec_patrol_frequency']), 'icon' => 'fa-walking', 'color' => 'slate'];
                }
                if (!empty($property['sec_parking_security'])) {
                    $security_features[] = ['label' => 'Secure Parking', 'desc' => htmlspecialchars($property['sec_parking_security']), 'icon' => 'fa-car', 'color' => 'sky'];
                }
                if (!empty($property['sec_hospital_distance'])) {
                    $security_features[] = ['label' => 'Nearest Hospital', 'desc' => htmlspecialchars($property['sec_hospital_distance']), 'icon' => 'fa-hospital-symbol', 'color' => 'rose'];
                }
                if (!empty($property['sec_emergency_evac_instructions'])) {
                    $security_features[] = ['label' => 'Evacuation Instructions', 'desc' => htmlspecialchars($property['sec_emergency_evac_instructions']), 'icon' => 'fa-info-circle', 'color' => 'zinc'];
                }

                if (!empty($property['custom_security_json'])) {
                    $decoded = json_decode($property['custom_security_json'], true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $opt) {
                            if (!empty(trim($opt))) {
                                $security_features[] = ['label' => trim($opt), 'desc' => 'Active Feature', 'icon' => 'fa-shield-halved', 'color' => 'brand'];
                            }
                        }
                    }
                }

                $has_more_info = !empty($property['closest_police_station']) || 
                                 !empty($property['closest_hospital']) || 
                                 !empty($property['closest_fuel_station']) || 
                                 !empty($property['google_map_location']) || 
                                 !empty($attractions) || 
                                 !empty($nearest_destinations) || 
                                 !empty($food_features) || 
                                 !empty($accepted_methods) || 
                                 !empty($security_features) || 
                                 !empty($property['food_notes']) || 
                                 !empty($property['payment_notes']) || 
                                 !empty($property['sec_notes']);
                if ($has_more_info):
                ?>
                <!-- View More Info Collapsible Accordion Button -->
                <div class="mb-10 border-t border-blue-200 pt-8 mt-8">
                    <button type="button" onclick="toggleMoreInfo()" class="w-full bg-brand-50 hover:bg-brand-100 text-brand-600 font-bold py-3.5 px-6 rounded-xl flex items-center justify-between transition-all border border-brand-100/50 shadow-sm outline-none focus:outline-none" id="toggle-more-info-btn">
                        <span class="flex items-center gap-2 text-sm uppercase tracking-wider font-display">
                            <i class="fas fa-info-circle"></i> View More Info (Foods, Security, Payments, Attractions, Map)
                        </span>
                        <i class="fas fa-chevron-down transition-transform duration-300" id="more-info-chevron"></i>
                    </button>

                    <!-- Collapsible Container -->
                    <div id="more-info-container" class="hidden overflow-hidden transition-all duration-500 max-h-0 opacity-0">
                        <div class="pt-6 space-y-6">
                            <?php if (!empty($property['closest_police_station']) || !empty($property['closest_hospital']) || !empty($property['closest_fuel_station'])): ?>
                            <!-- Logistics & Proximity -->
                            <div class="mb-4">
                                <h3 class="text-xl font-bold font-display mb-6">Location & Proximity</h3>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <?php if (!empty($property['closest_police_station'])): ?>
                                        <div class="flex items-center gap-4 text-[14px] text-neutral-600">
                                            <div class="w-12 h-12 shrink-0 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center text-lg"><i class="fas fa-shield-alt"></i></div>
                                            <div>
                                                <p class="font-bold text-neutral-800 text-xs uppercase tracking-wide">Closest Police Station</p>
                                                <p class="font-medium text-neutral-600"><?php echo htmlspecialchars($property['closest_police_station']); ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($property['closest_hospital'])): ?>
                                        <div class="flex items-center gap-4 text-[14px] text-neutral-600">
                                            <div class="w-12 h-12 shrink-0 rounded-full bg-red-50 text-red-600 flex items-center justify-center text-lg"><i class="fas fa-hospital"></i></div>
                                            <div>
                                                <p class="font-bold text-neutral-800 text-xs uppercase tracking-wide">Closest Hospital</p>
                                                <p class="font-medium text-neutral-600"><?php echo htmlspecialchars($property['closest_hospital']); ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($property['closest_fuel_station'])): ?>
                                        <div class="flex items-center gap-4 text-[14px] text-neutral-600">
                                            <div class="w-12 h-12 shrink-0 rounded-full bg-orange-50 text-orange-600 flex items-center justify-center text-lg"><i class="fas fa-gas-pump"></i></div>
                                            <div>
                                                <p class="font-bold text-neutral-800 text-xs uppercase tracking-wide">Closest Fuel Station</p>
                                                <p class="font-medium text-neutral-600"><?php echo htmlspecialchars($property['closest_fuel_station']); ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($property['google_map_location'])): ?>
                            <!-- Google Maps Card -->
                            <div class="mb-4">
                                <div class="bg-brand-50/60 border border-brand-100/50 rounded-2xl p-6 flex flex-col sm:flex-row items-center justify-between gap-6 shadow-sm">
                                    <div class="flex items-center gap-4 text-left">
                                        <div class="w-12 h-12 shrink-0 rounded-full bg-white text-brand-600 flex items-center justify-center text-xl shadow-sm border border-brand-100/30">
                                            <i class="fas fa-map-marked-alt"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-neutral-800 text-sm">Open in Google Maps</h4>
                                            <p class="text-xs text-neutral-500 mt-1">Get instant directions and check coordinates on Google Maps.</p>
                                        </div>
                                    </div>
                                    <a href="<?php echo htmlspecialchars($property['google_map_location']); ?>" target="_blank" class="w-full sm:w-auto bg-brand-600 hover:bg-brand-700 text-white font-bold text-xs uppercase tracking-wider px-6 py-3 rounded-xl shadow-md hover:shadow-lg transition-all text-center flex items-center justify-center gap-2">
                                        <i class="fas fa-location-arrow"></i> Directions
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($attractions) || !empty($nearest_destinations)): ?>
                            <!-- Tourist Attractions & Nearest Destinations -->
                            <div class="mb-4">
                                <h3 class="text-xl font-bold font-display mb-6">Explore the Area</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <?php if (!empty($attractions)): ?>
                                    <!-- Property Nearby Attractions -->
                                    <div>
                                        <h4 class="text-sm font-bold text-neutral-800 uppercase tracking-wider mb-4 flex items-center gap-2">
                                            <i class="fas fa-map-marked-alt text-brand-600"></i>
                                            <span>Nearby Attractions</span>
                                        </h4>
                                        <ul class="space-y-3">
                                            <?php foreach ($attractions as $attraction): ?>
                                                <li class="flex items-start gap-3 text-[14px] text-neutral-700 bg-white/90 p-3 rounded-lg border border-blue-100">
                                                    <i class="fas fa-location-arrow text-brand-600 mt-1"></i>
                                                    <span class="font-medium"><?php echo htmlspecialchars($attraction); ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($nearest_destinations)): ?>
                                    <!-- Popular District Destinations -->
                                    <div class="<?php echo empty($attractions) ? 'col-span-2' : ''; ?>">
                                        <h4 class="text-sm font-bold text-neutral-800 uppercase tracking-wider mb-4 flex items-center gap-2">
                                            <i class="fas fa-compass text-brand-600"></i>
                                            <span>Nearest Tourist Destinations</span>
                                        </h4>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <?php foreach ($nearest_destinations as $destination): ?>
                                                <?php 
                                                $query_name = $destination['destination_name'] ?: $destination['district_name'];
                                                $media_type = $destination['media_type'] ?? 'image';
                                                ?>
                                                <a href="hotels.php?q=<?php echo urlencode($query_name); ?>" class="group cursor-pointer block border border-blue-200/40 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all duration-300">
                                                    <div class="relative h-[120px] overflow-hidden">
                                                        <?php if ($media_type === 'video'): ?>
                                                            <video class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" autoplay muted loop playsinline>
                                                                <source src="<?php echo htmlspecialchars($destination['media_path']); ?>">
                                                            </video>
                                                        <?php else: ?>
                                                            <img src="<?php echo htmlspecialchars($destination['media_path']); ?>" alt="<?php echo htmlspecialchars($destination['destination_name']); ?>"
                                                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                                        <?php endif; ?>
                                                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"></div>
                                                        <div class="absolute bottom-2 left-3 text-white right-2">
                                                            <h5 class="font-bold text-sm tracking-tight leading-tight"><?php echo htmlspecialchars($destination['destination_name']); ?></h5>
                                                            <p class="text-[10px] opacity-85 truncate mt-0.5"><?php echo htmlspecialchars($destination['description']); ?></p>
                                                        </div>
                                                    </div>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>


                <!-- Food & Dining -->
                <div class="mb-10 border-t border-blue-200 pt-8 mt-8">
                    <h3 class="text-xl font-bold font-display mb-6">Food & Dining</h3>
                    <?php if (!empty($food_features)): ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                            <?php foreach ($food_features as $ff): ?>
                                <div class="flex items-start gap-3 p-4 rounded-xl border border-brand-100/80 bg-brand-50/50 hover:scale-[1.02] transition-transform duration-300">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 bg-white shadow-sm text-brand-600">
                                        <i class="fas <?php echo $ff['icon']; ?> text-base"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-[13px] text-neutral-800 leading-tight"><?php echo htmlspecialchars($ff['label']); ?></h4>
                                        <p class="text-[11px] text-neutral-500 mt-0.5 leading-snug"><?php echo $ff['desc']; ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-neutral-500 italic">No specific food & dining amenities declared by the property.</p>
                    <?php endif; ?>
                    
                    <?php if (!empty($property['food_notes'])): ?>
                        <div class="mt-4 bg-white/90 p-4 rounded-xl border border-blue-100">
                            <h4 class="text-xs font-bold text-neutral-700 uppercase tracking-wide mb-1 flex items-center gap-1.5">
                                <i class="fas fa-info-circle text-neutral-500"></i> Food & Dining Notes
                            </h4>
                            <p class="text-[13px] text-neutral-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($property['food_notes'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Payment Options & Policies -->
                <div class="mb-10 border-t border-blue-200 pt-8 mt-8">
                    <h3 class="text-xl font-bold font-display mb-6">Payment Options & Policies</h3>
                    
                    <!-- Policy Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                        <!-- Refund Card -->
                        <div class="flex items-start gap-3 p-4 rounded-xl border border-brand-100/80 bg-brand-50/50 hover:scale-[1.02] transition-transform duration-300">
                            <?php if ($property['refund_supported'] === 1): ?>
                                <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 bg-white shadow-sm text-emerald-600">
                                    <i class="fas fa-check text-base"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-[13px] text-neutral-800 leading-tight">Refund Supported</h4>
                                    <p class="text-[11px] text-neutral-500 mt-0.5 leading-snug">Eligible for cancellation refund</p>
                                </div>
                            <?php elseif ($property['refund_supported'] === 0): ?>
                                <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 bg-white shadow-sm text-rose-600">
                                    <i class="fas fa-times text-base"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-[13px] text-neutral-800 leading-tight">Non-refundable</h4>
                                    <p class="text-[11px] text-neutral-500 mt-0.5 leading-snug">Payments are non-refundable</p>
                                </div>
                            <?php else: ?>
                                <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 bg-white shadow-sm text-neutral-400">
                                    <i class="fas fa-minus text-base"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-[13px] text-neutral-800 leading-tight">Refund Unspecified</h4>
                                    <p class="text-[11px] text-neutral-500 mt-0.5 leading-snug">Contact property for policies</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Advance Payment Card -->
                        <div class="flex items-start gap-3 p-4 rounded-xl border border-brand-100/80 bg-brand-50/50 hover:scale-[1.02] transition-transform duration-300">
                            <?php if ($property['advance_payment_required'] === 1): ?>
                                <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 bg-white shadow-sm text-amber-600">
                                    <i class="fas fa-exclamation text-base"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-[13px] text-neutral-800 leading-tight">Advance Payment Required</h4>
                                    <p class="text-[11px] text-neutral-500 mt-0.5 leading-snug">Deposit required to secure booking</p>
                                </div>
                            <?php elseif ($property['advance_payment_required'] === 0): ?>
                                <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 bg-white shadow-sm text-emerald-600">
                                    <i class="fas fa-check text-base"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-[13px] text-neutral-800 leading-tight">No Advance Payment Needed</h4>
                                    <p class="text-[11px] text-neutral-500 mt-0.5 leading-snug">No immediate deposit required</p>
                                </div>
                            <?php else: ?>
                                <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 bg-white shadow-sm text-neutral-400">
                                    <i class="fas fa-minus text-base"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-[13px] text-neutral-800 leading-tight">Advance Payment Unspecified</h4>
                                    <p class="text-[11px] text-neutral-500 mt-0.5 leading-snug">Contact property for booking deposit</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Accepted Methods -->
                    <div class="mb-6">
                        <h4 class="text-xs font-bold text-neutral-500 uppercase tracking-wider mb-4">Accepted Payment Methods</h4>
                        <?php if (!empty($accepted_methods)): ?>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                <?php foreach ($accepted_methods as $am): ?>
                                    <div class="flex items-start gap-3 p-4 rounded-xl border border-brand-100/80 bg-brand-50/50 hover:scale-[1.02] transition-transform duration-300">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 bg-white shadow-sm text-brand-600">
                                            <i class="fas <?php echo $am['icon']; ?> text-base"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-[13px] text-neutral-800 leading-tight"><?php echo htmlspecialchars($am['label']); ?></h4>
                                            <p class="text-[11px] text-neutral-500 mt-0.5 leading-snug">Accepted Method</p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-sm text-neutral-500 italic">Standard property payment conditions apply.</p>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($property['payment_notes'])): ?>
                        <div class="mt-4 bg-white/90 p-4 rounded-xl border border-blue-100">
                            <h4 class="text-xs font-bold text-neutral-700 uppercase tracking-wide mb-1 flex items-center gap-1.5">
                                <i class="fas fa-info-circle text-neutral-500"></i> Payment Instructions
                            </h4>
                            <p class="text-[13px] text-neutral-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($property['payment_notes'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Safety & Security -->
                <div class="mb-10 border-t border-blue-200 pt-8 mt-8">
                    <h3 class="text-xl font-bold font-display mb-6">Safety & Security Features</h3>
                    <?php if (!empty($security_features)): ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                            <?php foreach ($security_features as $sf): ?>
                                <div class="flex items-start gap-3 p-4 rounded-xl border border-brand-100/80 bg-brand-50/50 hover:scale-[1.02] transition-transform duration-300">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 bg-white shadow-sm text-brand-600">
                                        <i class="fas <?php echo $sf['icon']; ?> text-base"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-[13px] text-neutral-800 leading-tight"><?php echo htmlspecialchars($sf['label']); ?></h4>
                                        <p class="text-[11px] text-neutral-500 mt-0.5 leading-snug"><?php echo $sf['desc']; ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-neutral-500 italic">Standard hotel safety & security features apply.</p>
                    <?php endif; ?>
                    
                    <?php if (!empty($property['sec_notes'])): ?>
                        <div class="mt-4 bg-white/90 p-4 rounded-xl border border-blue-100">
                            <h4 class="text-xs font-bold text-neutral-700 uppercase tracking-wide mb-1 flex items-center gap-1.5">
                                <i class="fas fa-info-circle text-neutral-500"></i> Safety Notes
                            </h4>
                            <p class="text-[13px] text-neutral-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($property['sec_notes'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                        </div> <!-- closes pt-6 space-y-6 -->
                    </div> <!-- closes #more-info-container -->
                </div> <!-- closes accordion container -->

                <script>
                function toggleMoreInfo() {
                    const container = document.getElementById('more-info-container');
                    const chevron = document.getElementById('more-info-chevron');
                    
                    if (container.classList.contains('hidden')) {
                        container.classList.remove('hidden');
                        container.style.maxHeight = '0px';
                        container.style.opacity = '0';
                        
                        // Force layout reflow
                        container.offsetHeight;
                        
                        container.style.maxHeight = container.scrollHeight + 100 + 'px';
                        container.style.opacity = '1';
                        chevron.classList.add('rotate-180');
                    } else {
                        container.style.maxHeight = '0px';
                        container.style.opacity = '0';
                        chevron.classList.remove('rotate-180');
                        
                        setTimeout(() => {
                            container.classList.add('hidden');
                        }, 500);
                    }
                }
                </script>
                <?php endif; ?>

            </div>
            <div class="hidden lg:block">
                <div class="bg-brand-50 p-6 rounded-xl border border-brand-100 sticky top-6">
                    <h4 class="font-bold mb-4">Property highlights</h4>
                    <button onclick="document.getElementById('availability').scrollIntoView({ behavior: 'smooth' })" class="w-full bg-brand-600 text-white py-3 rounded-lg font-bold">Reserve your stay</button>
                </div>
            </div>
        </div>

        <!-- Availability -->
        <div class="mt-12" id="availability">
            <h3 class="text-2xl font-bold font-display mb-6">Availability</h3>
            
            <!-- Date Selection Form -->
            <form method="GET" action="#availability" class="bg-white border border-blue-200/70 rounded-xl p-4 mb-8 flex flex-col md:flex-row items-end gap-4 shadow-sm">
                <input type="hidden" name="id" value="<?php echo $property_id; ?>">
                <div class="flex-1 w-full">
                    <label class="block text-[11px] font-bold text-neutral-500 uppercase mb-1.5 ml-1">Check-in Date</label>
                    <div class="relative">
                        <i class="far fa-calendar absolute left-4 top-1/2 -translate-y-1/2 text-neutral-400"></i>
                           <input type="date" name="checkin" value="<?php echo htmlspecialchars($check_in); ?>" min="<?php echo date('Y-m-d'); ?>" 
                               class="w-full pl-10 pr-4 py-2.5 bg-white border border-blue-200 rounded-lg outline-none focus:ring-2 focus:ring-brand-600/20 font-medium text-sm">
                    </div>
                </div>
                <div class="flex-1 w-full">
                    <label class="block text-[11px] font-bold text-neutral-500 uppercase mb-1.5 ml-1">Check-out Date</label>
                    <div class="relative">
                        <i class="far fa-calendar absolute left-4 top-1/2 -translate-y-1/2 text-neutral-400"></i>
                           <input type="date" name="checkout" value="<?php echo htmlspecialchars($check_out); ?>" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                               class="w-full pl-10 pr-4 py-2.5 bg-white border border-blue-200 rounded-lg outline-none focus:ring-2 focus:ring-brand-600/20 font-medium text-sm">
                    </div>
                </div>
                <button type="submit" class="w-full md:w-auto bg-brand-600 text-white px-8 py-2.5 rounded-lg font-bold hover:bg-brand-700 transition-colors">
                    Check Availability
                </button>
            </form>
            <div class="hidden md:block border border-blue-200/70 rounded-xl overflow-hidden shadow-sm">
                <table class="w-full text-left border-collapse bg-white">
                    <thead class="bg-brand-900 text-white text-[11px] uppercase">
                        <tr>
                            <th class="p-4 w-[35%]"><?php echo $property['business_type'] == 'dayouts' ? 'Package Details' : 'Room Type'; ?></th>
                            <th class="p-4 text-center">Sleeps</th>
                            <th class="p-4">Price</th>
                            <th class="p-4">Choices</th>
                            <th class="p-4">Select</th>
                            <th class="p-4"></th>
                        </tr>
                    </thead>
                    <tbody class="text-[13px]">
                        <?php foreach ($rooms as $room): ?>
                            <tr class="border-t border-blue-100 hover:bg-blue-50/30 transition-colors">
                                <td class="p-5 align-top">
                                    <div class="flex gap-4">
                                        <?php if (!empty($room['room_image'])): ?>
                                            <div class="w-24 h-24 shrink-0 rounded-lg overflow-hidden border border-blue-100">
                                                <img src="<?php echo htmlspecialchars($room['room_image']); ?>" class="w-full h-full object-cover cursor-pointer hover:scale-110 transition-transform duration-500" onclick="openLightbox(this.src)">
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="font-bold text-brand-600 text-[16px] mb-1 hover:underline cursor-pointer flex items-center gap-2 flex-wrap">
                                                <span><?php echo htmlspecialchars($room['room_name']); ?></span>
                                                <?php if (isset($room['is_deal']) && $room['is_deal']): ?>
                                                    <span class="bg-amber-400 text-amber-950 text-[10px] font-black px-2 py-0.5 rounded-lg shadow-sm flex items-center gap-1">
                                                        <i class="fas fa-bolt text-[9px]"></i> <?php echo htmlspecialchars($room['deal_label'] ?: 'Special Deal'); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($property['business_type'] == 'dayouts'): ?>
                                                <div class="text-neutral-500 text-[12px] mt-2 mb-1">
                                                    <?php echo nl2br(htmlspecialchars($room['description'] ?? '')); ?>
                                                </div>
                                                <?php if (!empty($room['things_included'])): ?>
                                                <div class="text-neutral-600 font-medium text-[11px] mt-2 bg-white/90 p-2 border border-blue-100 rounded">
                                                    <div class="font-bold mb-1 text-[10px] uppercase tracking-wider text-neutral-800">Includes:</div>
                                                    <?php echo nl2br(htmlspecialchars($room['things_included'])); ?>
                                                </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div class="text-neutral-500 text-[12px] flex items-center gap-2 mt-1">
                                                    <i class="fas fa-bed text-neutral-400"></i>
                                                    <span>1 extra-large double bed</span>
                                                </div>
                                                <div class="text-neutral-400 text-[11px] mt-2 flex items-center gap-3">
                                                    <span><i class="fas fa-expand mr-1"></i> 35 m²</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-5 text-center align-top">
                                    <div class="flex justify-center gap-0.5 text-neutral-700">
                                        <?php for($i=0;$i<$room['adults'];$i++): ?>
                                            <i class="fas fa-user text-[13px]"></i>
                                        <?php endfor; ?>
                                        <?php if($room['children'] > 0): ?>
                                            <?php for($i=0;$i<$room['children'];$i++): ?>
                                                <i class="fas fa-child text-[11px]"></i>
                                            <?php endfor; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="p-5 align-top">
                                    <?php 
                                    $display_price = ($currency == 'USD') ? ($room['price_lkr'] / $exchange_rate) : $room['price_lkr'];
                                    $total_display_price = $display_price * $nights;
                                    ?>
                                    <?php if (isset($room['is_deal']) && $room['is_deal']): 
                                        $orig_display = ($currency == 'USD') ? ($room['original_price_lkr'] / $exchange_rate) : $room['original_price_lkr'];
                                        $disc_pct = round((($room['original_price_lkr'] - $room['price_lkr']) / $room['original_price_lkr']) * 100);
                                    ?>
                                        <div class="flex items-center gap-1.5 mb-1.5">
                                            <span class="text-xs text-red-500 line-through font-semibold"><?php echo $currency; ?> <?php echo number_format($orig_display * $nights, ($currency == 'USD' ? 2 : 0)); ?></span>
                                            <span class="bg-red-500 text-white text-[9px] font-black px-1.5 py-0.5 rounded shadow-sm">-<?php echo $disc_pct; ?>%</span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex flex-wrap items-baseline gap-x-2">
                                        <span class="font-black text-xl text-neutral-900"><?php echo $currency; ?> <?php echo number_format($total_display_price, ($currency == 'USD' ? 2 : 0)); ?></span>
                                        <span class="text-[11px] text-neutral-500 font-bold">for <?php echo $nights; ?> night<?php echo $nights > 1 ? 's' : ''; ?></span>
                                    </div>
                                    <div class="text-[10px] text-neutral-400 font-medium mt-1 italic">(<?php echo $currency; ?> <?php echo number_format($display_price, ($currency == 'USD' ? 2 : 0)); ?> / night)</div>
                                    <div class="text-[10px] text-neutral-500 uppercase font-bold mt-2 tracking-tight">taxes & fees included</div>
                                </td>
                                <td class="p-5 align-top">
                                    <div class="space-y-2">
                                        <div class="text-palm font-bold text-[12px] flex items-center gap-2">
                                            <i class="fas fa-check text-[10px]"></i> <span>Free cancellation</span>
                                        </div>
                                        <div class="text-palm font-bold text-[12px] flex items-center gap-2">
                                            <i class="fas fa-check text-[10px]"></i> <span>No prepayment needed</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-5 align-top">
                                    <form action="newbooking.php" method="GET">
                                        <input type="hidden" name="property_id" value="<?php echo $property_id; ?>">
                                        <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                        <input type="hidden" name="checkin" value="<?php echo $check_in; ?>">
                                        <input type="hidden" name="checkout" value="<?php echo $check_out; ?>">
                                        
                                        <select name="qty" class="w-full p-2 border border-blue-200 rounded-lg outline-none focus:ring-2 focus:ring-brand-600/20 bg-white text-sm font-medium mb-3">
                                            <?php for($i = 1; $i <= $room['available_count']; $i++): ?>
                                                <option value="<?php echo $i; ?>"><?php echo $i; ?> <?php echo $property['business_type'] == 'dayouts' ? 'package' : 'room'; ?><?php echo $i > 1 ? 's' : ''; ?> (<?php echo $currency; ?> <?php echo number_format($total_display_price * $i, ($currency == 'USD' ? 2 : 0)); ?>)</option>
                                            <?php endfor; ?>
                                        </select>
                                        
                                        <button type="submit" 
                                                onclick="return validateBooking(event, '<?php echo $check_in; ?>', '<?php echo $check_out; ?>', <?php echo $dates_specified ? 'true' : 'false'; ?>)"
                                                class="w-full bg-brand-600 text-white py-2.5 rounded-lg font-bold hover:bg-brand-700 transition-all shadow-md shadow-brand-600/10 mb-2">
                                            Reserve
                                        </button>
                                        <p class="text-[10px] text-neutral-500 font-medium text-center">Confirmation is instant</p>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Room Cards -->
            <div class="md:hidden space-y-5">
                                <?php foreach ($rooms as $room): ?>
                                    <div class="bg-white border border-blue-200/70 rounded-2xl overflow-hidden shadow-sm">
                        <?php if (!empty($room['room_image'])): ?>
                            <div class="h-48 overflow-hidden">
                                <img src="<?php echo htmlspecialchars($room['room_image']); ?>" class="w-full h-full object-cover" onclick="openLightbox(this.src)">
                            </div>
                        <?php endif; ?>
                        <div class="p-5">
                            <div class="flex justify-between items-start mb-3">
                                <h4 class="font-extrabold text-[18px] text-neutral-900 leading-tight font-display flex items-center gap-2 flex-wrap">
                                    <span><?php echo htmlspecialchars($room['room_name']); ?></span>
                                    <?php if (isset($room['is_deal']) && $room['is_deal']): ?>
                                        <span class="bg-amber-400 text-amber-950 text-[10px] font-black px-2 py-0.5 rounded-lg shadow-sm flex items-center gap-1">
                                            <i class="fas fa-bolt text-[9px]"></i> <?php echo htmlspecialchars($room['deal_label'] ?: 'Special Deal'); ?>
                                        </span>
                                    <?php endif; ?>
                                </h4>
                                <div class="flex gap-0.5 text-neutral-600">
                                    <?php for($i=0;$i<$room['adults'];$i++): ?><i class="fas fa-user text-[11px]"></i><?php endfor; ?>
                                </div>
                            </div>
                            <?php if ($property['business_type'] == 'dayouts'): ?>
                                <p class="text-[13px] text-neutral-500 mb-2 font-medium"><?php echo nl2br(htmlspecialchars($room['description'] ?? '')); ?></p>
                                <?php if (!empty($room['things_included'])): ?>
                                    <div class="text-[12px] text-neutral-600 mb-4 bg-white/90 p-2 border border-blue-100 rounded">
                                        <strong class="block text-[10px] uppercase tracking-wider text-neutral-800 mb-1">Includes:</strong>
                                        <?php echo nl2br(htmlspecialchars($room['things_included'])); ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="text-[13px] text-neutral-500 mb-4 font-medium">1 extra-large double bed • 35 m²</p>
                            <?php endif; ?>
                            <div class="bg-brand-50 p-4 rounded-xl mb-5 space-y-2">
                                <div class="text-[12px] text-palm font-bold flex items-center gap-2">
                                    <i class="fas fa-check text-[10px]"></i> Free cancellation
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <div>
                                    <?php 
                                    $display_price = ($currency == 'USD') ? ($room['price_lkr'] / $exchange_rate) : $room['price_lkr'];
                                    $total_display_price = $display_price * $nights;
                                    ?>
                                    <?php if (isset($room['is_deal']) && $room['is_deal']): 
                                        $orig_display = ($currency == 'USD') ? ($room['original_price_lkr'] / $exchange_rate) : $room['original_price_lkr'];
                                        $disc_pct = round((($room['original_price_lkr'] - $room['price_lkr']) / $room['original_price_lkr']) * 100);
                                    ?>
                                        <div class="flex items-center gap-1.5 mb-0.5">
                                            <span class="text-xs text-red-500 line-through font-semibold"><?php echo $currency; ?> <?php echo number_format($orig_display * $nights, ($currency == 'USD' ? 2 : 0)); ?></span>
                                            <span class="bg-red-500 text-white text-[9px] font-black px-1.5 py-0.5 rounded shadow-sm">-<?php echo $disc_pct; ?>%</span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="flex items-baseline gap-1.5">
                                        <span class="font-black text-xl"><?php echo $currency; ?> <?php echo number_format($total_display_price, ($currency == 'USD' ? 2 : 0)); ?></span>
                                        <span class="text-[10px] text-neutral-500 font-bold uppercase">/ <?php echo $nights; ?> nights</span>
                                    </div>
                                    <div class="text-[10px] text-neutral-400 font-medium italic"><?php echo $currency; ?> <?php echo number_format($display_price, ($currency == 'USD' ? 2 : 0)); ?> per night</div>
                                </div>
                                <a href="newbooking.php?property_id=<?php echo $property_id; ?>&room_id=<?php echo $room['id']; ?>&checkin=<?php echo $check_in; ?>&checkout=<?php echo $check_out; ?>&qty=1" 
                                   onclick="return validateBooking(event, '<?php echo $check_in; ?>', '<?php echo $check_out; ?>', <?php echo $dates_specified ? 'true' : 'false'; ?>)"
                                   class="bg-brand-600 text-white px-6 py-2.5 rounded-xl font-bold text-[14px] no-underline">
                                    Select
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Guest Reviews Section -->
        <div class="mt-20 pt-12 border-t border-blue-200">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-10">
                <div>
                    <h3 class="text-2xl font-bold font-display text-primary">Guest reviews</h3>
                    <div class="flex items-center gap-3 mt-2">
                        <div class="bg-primary text-white font-black px-3 py-1.5 rounded-lg text-lg"><?php echo $avg_rating; ?></div>
                        <div>
                            <p class="font-bold text-neutral-800 leading-none">Overall Score</p>
                            <p class="text-[12px] text-neutral-500 mt-1"><?php echo $review_count; ?> real reviews from our guests</p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (empty($reviews)): ?>
                <div class="bg-white/90 rounded-2xl p-10 text-center border border-blue-100">
                    <p class="text-neutral-500 font-medium">No reviews yet for this property. Be the first to share your experience after your stay!</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($reviews as $review): ?>
                        <div class="bg-white p-6 rounded-2xl border border-blue-200/50 shadow-sm flex flex-col justify-between hover:shadow-md transition-shadow">
                            <div>
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-neutral-100 rounded-full flex items-center justify-center font-bold text-primary">
                                            <?php echo strtoupper(substr($review['user_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <p class="font-bold text-sm text-neutral-800 leading-tight"><?php echo htmlspecialchars($review['user_name']); ?></p>
                                            <p class="text-[11px] text-neutral-500">Guest</p>
                                        </div>
                                    </div>
                                    <div class="flex text-gold text-[10px]">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <i class="<?php echo $i <= $review['rating'] ? 'fas' : 'far'; ?> fa-star"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p class="text-[14px] text-neutral-700 leading-relaxed italic">"<?php echo nl2br(htmlspecialchars($review['comment'])); ?>"</p>
                            </div>
                            <p class="text-[10px] text-neutral-400 font-bold mt-4 uppercase tracking-tighter"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <!-- Hotels Near Me Section -->
        <?php if (!empty($hotels_near)): ?>
        <div class="mt-20 pt-12 border-t border-blue-200 relative">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <span class="inline-flex items-center gap-2 px-3 py-1 bg-brand-50 text-brand-600 border border-brand-100 rounded-full text-[10px] font-black uppercase tracking-wider mb-2">
                        <i class="fas fa-hotel"></i> Stays Near Me
                    </span>
                    <h3 class="text-2xl font-bold font-display text-primary">Hotels Near Me</h3>
                    <p class="text-xs text-neutral-500 mt-1 font-medium">Explore alternative stays nearby in <?php echo htmlspecialchars($property['city']); ?></p>
                </div>
                <div class="flex gap-2">
                    <button id="hotels-near-prev" class="w-10 h-10 rounded-full border border-blue-200 flex items-center justify-center hover:bg-blue-50 hover:border-blue-300 transition-all shadow-sm bg-white" aria-label="Previous stays">
                        <i class="fas fa-chevron-left text-neutral-600 text-xs"></i>
                    </button>
                    <button id="hotels-near-next" class="w-10 h-10 rounded-full border border-blue-200 flex items-center justify-center hover:bg-blue-50 hover:border-blue-300 transition-all shadow-sm bg-white" aria-label="Next stays">
                        <i class="fas fa-chevron-right text-neutral-600 text-xs"></i>
                    </button>
                </div>
            </div>

            <div class="relative overflow-hidden">
                <div id="hotels-near-slider" class="flex overflow-x-auto no-scrollbar gap-5 pb-4 snap-x snap-mandatory scroll-smooth w-full">
                    <?php foreach ($hotels_near as $hn):
                        $hn_cover = $hn['cover_image'] ?: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=800&q=80';
                        $hn_price = ($currency === 'USD') ? ($hn['price_lkr'] / $exchange_rate) : $hn['price_lkr'];
                    ?>
                    <a href="hotel_info.php?id=<?php echo $hn['id']; ?>" class="flex-none w-[280px] sm:w-[300px] snap-start group" style="text-decoration:none;">
                        <div class="relative rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-all duration-400 border border-blue-200/70 bg-white">
                            <div class="relative h-44 overflow-hidden">
                                <img src="<?php echo htmlspecialchars($hn_cover); ?>" alt="<?php echo htmlspecialchars($hn['property_name']); ?>"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                                <?php if (isset($hn['is_deal']) && $hn['is_deal']): ?>
                                <div class="absolute top-3 left-3">
                                    <span class="bg-red-500 text-white text-[10px] font-black px-2 py-0.5 rounded shadow">DEAL</span>
                                </div>
                                <?php endif; ?>
                                <div class="absolute bottom-3 left-3 right-3">
                                    <h4 class="text-white font-bold text-sm leading-tight truncate"><?php echo htmlspecialchars($hn['property_name']); ?></h4>
                                    <p class="text-white/80 text-xs mt-0.5"><i class="fas fa-map-marker-alt mr-1"></i><?php echo htmlspecialchars($hn['city']); ?>, <?php echo htmlspecialchars($hn['district']); ?></p>
                                </div>
                            </div>
                            <div class="p-4 flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] text-neutral-400 font-bold uppercase tracking-wider">Starting from</p>
                                    <p class="text-base font-black text-neutral-900"><?php echo $currency; ?> <?php echo number_format($hn_price, ($currency === 'USD' ? 2 : 0)); ?><span class="text-xs text-neutral-500 font-medium">/night</span></p>
                                </div>
                                <div class="bg-brand-600 text-white text-xs font-bold px-3 py-2 rounded-xl group-hover:bg-brand-700 transition-colors">Book</div>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Rides Near Me Section -->
        <?php if (!empty($rides_near)): ?>
        <div class="mt-20 pt-12 border-t border-blue-200 relative">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <span class="inline-flex items-center gap-2 px-3 py-1 bg-brand-50 text-brand-600 border border-brand-100 rounded-full text-[10px] font-black uppercase tracking-wider mb-2">
                        <i class="fas fa-car"></i> Rides Near Me
                    </span>
                    <h3 class="text-2xl font-bold font-display text-primary">Rides Near Me</h3>
                    <p class="text-xs text-neutral-500 mt-1 font-medium">Find convenient transport options nearby in <?php echo htmlspecialchars($property['city']); ?></p>
                </div>
                <div class="flex gap-2">
                    <button id="rides-near-prev" class="w-10 h-10 rounded-full border border-blue-200 flex items-center justify-center hover:bg-blue-50 hover:border-blue-300 transition-all shadow-sm bg-white" aria-label="Previous rides">
                        <i class="fas fa-chevron-left text-neutral-600 text-xs"></i>
                    </button>
                    <button id="rides-near-next" class="w-10 h-10 rounded-full border border-blue-200 flex items-center justify-center hover:bg-blue-50 hover:border-blue-300 transition-all shadow-sm bg-white" aria-label="Next rides">
                        <i class="fas fa-chevron-right text-neutral-600 text-xs"></i>
                    </button>
                </div>
            </div>

            <div class="relative overflow-hidden">
                <div id="rides-near-slider" class="flex overflow-x-auto no-scrollbar gap-5 pb-4 snap-x snap-mandatory scroll-smooth w-full">
                    <?php foreach ($rides_near as $rn):
                        $rn_cover = $rn['cover_image'] ?: 'assets/placeholder-vehicle.png';
                        
                        $pricing_type = $rn['pricing_type'] ?? 'day_wise';
                        if ($pricing_type === 'day_wise') {
                            $rn_price_val = $rn['price_per_day'];
                            $rn_price_label = '/day';
                        } else {
                            $rn_price_val = $rn['price_per_km'];
                            $rn_price_label = '/km';
                        }
                        $rn_price = ($currency === 'USD') ? ($rn_price_val / $exchange_rate) : $rn_price_val;
                    ?>
                    <a href="vehicle_details.php?id=<?php echo $rn['id']; ?>" class="flex-none w-[280px] sm:w-[300px] snap-start group" style="text-decoration:none;">
                        <div class="relative rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-all duration-400 border border-blue-200/70 bg-white">
                            <div class="relative h-44 overflow-hidden">
                                <img src="<?php echo htmlspecialchars($rn_cover); ?>" alt="<?php echo htmlspecialchars($rn['property_name']); ?>"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                                     onerror="this.src='assets/placeholder-vehicle.png'">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                                <div class="absolute top-3 left-3 bg-black/55 text-white text-[10px] font-black px-2 py-0.5 rounded shadow">
                                    <i class="fas fa-car mr-1"></i><?php echo htmlspecialchars(ucfirst($rn['vehicle_category'] ?? 'Vehicle')); ?>
                                </div>
                                <div class="absolute bottom-3 left-3 right-3">
                                    <h4 class="text-white font-bold text-sm leading-tight truncate"><?php echo htmlspecialchars($rn['property_name']); ?></h4>
                                    <p class="text-white/80 text-xs mt-0.5"><i class="fas fa-map-marker-alt mr-1"></i><?php echo htmlspecialchars($rn['city']); ?>, <?php echo htmlspecialchars($rn['district']); ?></p>
                                </div>
                            </div>
                            <div class="p-4 flex items-center justify-between">
                                <div>
                                    <p class="text-[10px] text-neutral-400 font-bold uppercase tracking-wider">Starting from</p>
                                    <p class="text-base font-black text-neutral-900"><?php echo $currency; ?> <?php echo number_format($rn_price, ($currency === 'USD' ? 2 : 0)); ?><span class="text-xs text-neutral-500 font-medium"><?php echo $rn_price_label; ?></span></p>
                                </div>
                                <div class="bg-brand-600 text-white text-xs font-bold px-3 py-2 rounded-xl group-hover:bg-brand-700 transition-colors">Select</div>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Sliders JavaScript -->
        <script>
        document.addEventListener("DOMContentLoaded", function () {
            const initSlider = (sliderId, prevId, nextId) => {
                const slider = document.getElementById(sliderId);
                const prev = document.getElementById(prevId);
                const next = document.getElementById(nextId);
                if (!slider) return;

                let autoScrollInterval;
                const startAutoScroll = () => {
                    autoScrollInterval = setInterval(() => {
                        const card = slider.querySelector('.snap-start');
                        const cardWidth = card ? card.offsetWidth : 300;
                        slider.scrollBy({ left: cardWidth + 20, behavior: 'smooth' });
                        // Reset to start if end reached
                        if (slider.scrollLeft + slider.clientWidth >= slider.scrollWidth - 10) {
                            setTimeout(() => { slider.scrollTo({ left: 0, behavior: 'smooth' }); }, 1000);
                        }
                    }, 4000);
                };

                const stopAutoScroll = () => clearInterval(autoScrollInterval);

                startAutoScroll();
                slider.addEventListener('mouseenter', stopAutoScroll);
                slider.addEventListener('mouseleave', startAutoScroll);

                if (prev) {
                    prev.addEventListener('click', () => {
                        const card = slider.querySelector('.snap-start');
                        const cardWidth = card ? card.offsetWidth : 300;
                        slider.scrollBy({ left: -(cardWidth + 20), behavior: 'smooth' });
                    });
                }
                if (next) {
                    next.addEventListener('click', () => {
                        const card = slider.querySelector('.snap-start');
                        const cardWidth = card ? card.offsetWidth : 300;
                        slider.scrollBy({ left: cardWidth + 20, behavior: 'smooth' });
                    });
                }
            };

            initSlider('hotels-near-slider', 'hotels-near-prev', 'hotels-near-next');
            initSlider('rides-near-slider', 'rides-near-prev', 'rides-near-next');
        });
        </script>
    </main>

    <footer class="bg-white/90 border-t border-blue-200 py-12 px-6 mt-16">
        <div class="max-w-[1150px] mx-auto text-center text-sm text-neutral-500">
            © 2026 Bookingjaunt.com All rights reserved.
        </div>
    </footer>

    <div id="myLightbox" class="lightbox-modal" onclick="closeLightbox()"><span class="close-lightbox">&times;</span><img class="lightbox-content" id="imgLightbox"></div>
    <div id="galleryModal" class="fixed inset-0 z-[9998] bg-black/95 hidden overflow-y-auto" onclick="if(event.target===this)closeGallery()">
        <div class="max-w-5xl mx-auto p-6 text-white">
            <div class="flex justify-between mb-6"><h3 class="text-xl font-bold">All Photos</h3><button onclick="closeGallery()" class="text-3xl">&times;</button></div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <?php foreach ($images as $img): ?><img src="<?php echo htmlspecialchars($img); ?>" class="rounded-lg object-cover w-full h-48 cursor-pointer" onclick="openLightbox(this.src)"><?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
