<?php
ob_start(); // Buffer any stray output (warnings, notices) so they don't corrupt JSON
require_once 'config.php';
session_start();

// Handle AJAX requests for Email Check and Registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    ob_end_clean(); // Discard any buffered output before sending JSON
    header('Content-Type: application/json');

    if ($_POST['action'] == 'check_email') {
        $email = $_POST['email'];
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            echo json_encode(['exists' => true, 'redirect' => 'login.php?email=' . urlencode($email)]);
        } else {
            echo json_encode(['exists' => false]);
        }
        exit;
    }

    if ($_POST['action'] == 'register_user') {
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
        $phone_number = $_POST['phone_number'] ?? '';
        $whatsapp_number = $_POST['whatsapp_number'] ?? '';
        $role = $_POST['role'] ?? 'user';

        try {
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, phone_number, whatsapp_number, role) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$first_name, $last_name, $email, $password, $phone_number, $whatsapp_number, $role]);

            $user_id = $pdo->lastInsertId();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $first_name . ' ' . $last_name;
            $_SESSION['role'] = $role;

            echo json_encode(['success' => true, 'redirect' => ($role == 'owner' ? 'list_your_property.php' : 'index.php')]);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Registration failed. Email might already exist.']);
            exit;
        }
    }

    // Property registration logic remains largely the same but refined
    if ($_POST['action'] == 'register_property') {
        $owner_id = $_SESSION['user_id'] ?? 0;
        if (!$owner_id) {
            echo json_encode(['success' => false, 'message' => 'Session expired. Please login.']);
            exit;
        }

        $data = $_POST;
        try {
            $pdo->beginTransaction();

            // 1. Insert Property
            $stmt = $pdo->prepare("INSERT INTO properties (
                owner_id, business_type, hotel_category, property_name, description, 
                street_address, city, district, province, country, google_map_location, 
                fixed_telephone, mobile_telephone, closest_police_station, closest_hospital, 
                airport_distance, closest_main_town, postal_code, logo_image, cover_image, 
                manager_name, manager_phone, manager_nic, manager_photo, contact_number, 
                business_email, check_in_time, check_out_time, cancellation_policy, 
                rules_json, popular_amenities_json, custom_rules_json,
                bank_name, bank_branch, bank_account_name, bank_account_number, commission_rate, tourist_attractions, closest_fuel_station
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $owner_id,
                $data['business_type'] ?? null,
                $data['hotel_category'] ?? null,
                $data['property_name'],
                $data['description'] ?? '',
                $data['street_address'] ?? '',
                $data['city'] ?? '',
                $data['district'] ?? '',
                $data['province'] ?? '',
                $data['country'] ?? 'Sri Lanka',
                $data['google_map_location'] ?? '',
                $data['fixed_telephone'] ?? '',
                $data['mobile_telephone'] ?? '',
                $data['closest_police_station'] ?? '',
                $data['closest_hospital'] ?? '',
                $data['airport_distance'] ?? '',
                $data['closest_main_town'] ?? '',
                $data['postal_code'] ?? '',
                $data['logo_image'] ?? '',
                $data['cover_image'] ?? '',
                $data['manager_name'] ?? '',
                $data['manager_phone'] ?? '',
                $data['manager_nic'] ?? '',
                $data['manager_photo'] ?? '',
                $data['contact_number'] ?? '',
                $data['business_email'] ?? '',
                $data['check_in_time'] ?? '14:00',
                $data['check_out_time'] ?? '12:00',
                $data['cancellation_policy'] ?? '',
                json_encode($data['rules'] ?? []),
                json_encode($data['popular_amenities'] ?? []),
                json_encode($data['custom_rules'] ?? []),
                $data['bank_name'] ?? null,
                $data['bank_branch'] ?? null,
                $data['bank_account_name'] ?? null,
                $data['bank_account_number'] ?? null,
                $data['commission_rate'] ?? 80,
                !empty($data['tourist_attractions']) ? json_encode(array_values(array_filter($data['tourist_attractions']))) : null,
                $data['closest_fuel_station'] ?? null
            ]);

            $property_id = $pdo->lastInsertId();

            // 1b. If vehicle, update vehicle-specific fields
            if (($data['business_type'] ?? '') === 'vehicle') {
                $vStmt = $pdo->prepare("UPDATE properties SET
                    vehicle_category = ?,
                    brand = ?,
                    model = ?,
                    manufactured_year = ?,
                    registration_number = ?,
                    chassis_number = ?,
                    engine_number = ?,
                    vehicle_color = ?,
                    fuel_type = ?,
                    transmission_type = ?,
                    vehicle_condition = ?,
                    seat_count = ?,
                    luggage_count = ?,
                    max_passengers = ?,
                    pricing_type = ?,
                    price_per_day = ?,
                    included_km_per_day = ?,
                    extra_km_price = ?,
                    price_per_km = ?,
                    hourly_price = ?,
                    weekly_price = ?,
                    monthly_price = ?,
                    discount_daily = ?,
                    discount_weekly = ?,
                    discount_monthly = ?,
                    discount_seasonal = ?,
                    coupon_support = ?,
                    driver_option = ?,
                    driver_name = ?,
                    driver_contact = ?,
                    driver_whatsapp = ?,
                    driver_nic = ?,
                    driver_license = ?,
                    driver_experience = ?,
                    driver_languages = ?,
                    has_ac = ?,
                    has_gps = ?,
                    has_bluetooth = ?,
                    has_wifi = ?,
                    has_music_system = ?,
                    has_charging_ports = ?,
                    has_baby_seat = ?,
                    has_sunroof = ?,
                    has_reverse_camera = ?,
                    has_airbags = ?,
                    delivery_available = ?,
                    pickup_available = ?,
                    delivery_fee = ?,
                    delivery_towns_json = ?,
                    airport_delivery = ?,
                    hotel_delivery = ?,
                    exact_pickup_location = ?,
                    whatsapp_number = ?
                    WHERE id = ?");
                $vStmt->execute([
                    $data['vehicle_category'] ?? null,
                    $data['brand'] ?? null,
                    $data['model'] ?? null,
                    !empty($data['manufactured_year']) ? (int)$data['manufactured_year'] : null,
                    $data['registration_number'] ?? null,
                    $data['chassis_number'] ?? null,
                    $data['engine_number'] ?? null,
                    $data['vehicle_color'] ?? null,
                    $data['fuel_type'] ?? null,
                    $data['transmission_type'] ?? null,
                    $data['vehicle_condition'] ?? 'good',
                    !empty($data['seat_count']) ? (int)$data['seat_count'] : 4,
                    !empty($data['luggage_count']) ? (int)$data['luggage_count'] : 2,
                    !empty($data['max_passengers']) ? (int)$data['max_passengers'] : 4,
                    $data['pricing_type'] ?? 'day_wise',
                    !empty($data['price_per_day']) ? (float)$data['price_per_day'] : 0,
                    !empty($data['included_km_per_day']) ? (int)$data['included_km_per_day'] : 0,
                    !empty($data['extra_km_price']) ? (float)$data['extra_km_price'] : 0,
                    !empty($data['price_per_km']) ? (float)$data['price_per_km'] : 0,
                    !empty($data['hourly_price']) ? (float)$data['hourly_price'] : 0,
                    !empty($data['weekly_price']) ? (float)$data['weekly_price'] : 0,
                    !empty($data['monthly_price']) ? (float)$data['monthly_price'] : 0,
                    !empty($data['discount_daily']) ? (float)$data['discount_daily'] : 0,
                    !empty($data['discount_weekly']) ? (float)$data['discount_weekly'] : 0,
                    !empty($data['discount_monthly']) ? (float)$data['discount_monthly'] : 0,
                    !empty($data['discount_seasonal']) ? (float)$data['discount_seasonal'] : 0,
                    isset($data['coupon_support']) ? 1 : 0,
                    $data['driver_option'] ?? 'both',
                    $data['driver_name'] ?? null,
                    $data['driver_contact'] ?? null,
                    $data['driver_whatsapp'] ?? null,
                    $data['driver_nic'] ?? null,
                    $data['driver_license'] ?? null,
                    $data['driver_experience'] ?? null,
                    $data['driver_languages'] ?? null,
                    isset($data['has_ac']) ? 1 : 0,
                    isset($data['has_gps']) ? 1 : 0,
                    isset($data['has_bluetooth']) ? 1 : 0,
                    isset($data['has_wifi']) ? 1 : 0,
                    isset($data['has_music_system']) ? 1 : 0,
                    isset($data['has_charging_ports']) ? 1 : 0,
                    isset($data['has_baby_seat']) ? 1 : 0,
                    isset($data['has_sunroof']) ? 1 : 0,
                    isset($data['has_reverse_camera']) ? 1 : 0,
                    isset($data['has_airbags']) ? 1 : 0,
                    isset($data['delivery_available']) ? 1 : 0,
                    isset($data['pickup_available']) ? 1 : 0,
                    !empty($data['delivery_fee']) ? (float)$data['delivery_fee'] : 0,
                    !empty($data['delivery_towns']) ? json_encode(array_filter(array_map('trim', explode(',', $data['delivery_towns'])))) : null,
                    isset($data['airport_delivery']) ? 1 : 0,
                    isset($data['hotel_delivery']) ? 1 : 0,
                    $data['exact_pickup_location'] ?? null,
                    $data['driver_whatsapp'] ?? ($data['mobile_telephone'] ?? null),
                    $property_id
                ]);
            }

            // 2. Insert Amenities
            if (isset($data['amenities']) && is_array($data['amenities'])) {
                $stmt = $pdo->prepare("INSERT INTO property_amenities (property_id, amenity_id) VALUES (?, ?)");
                foreach ($data['amenities'] as $amenity_id) {
                    $stmt->execute([$property_id, $amenity_id]);
                }
            }

            // 3. Insert Special Amenities
            if (isset($data['special_amenities']) && is_array($data['special_amenities'])) {
                $stmt = $pdo->prepare("INSERT INTO property_custom_amenities (property_id, amenity_name) VALUES (?, ?)");
                foreach ($data['special_amenities'] as $name) {
                    if (!empty($name))
                        $stmt->execute([$property_id, $name]);
                }
            }

            // 4. Insert Rooms
            if (isset($data['rooms']) && is_array($data['rooms'])) {
                $stmt = $pdo->prepare("INSERT INTO property_rooms (property_id, room_name, total_rooms, room_numbers, adults, children, room_image, price_lkr, price_usd, description, things_included) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($data['rooms'] as $room) {
                    $stmt->execute([
                        $property_id,
                        $room['name'],
                        $room['count'] ?? 1,
                        $room['room_numbers'] ?? '',
                        $room['adults'] ?? 0,
                        $room['children'] ?? 0,
                        $room['image'] ?? '',
                        $room['price_lkr'] ?? 0,
                        $room['price_usd'] ?? 0,
                        $room['description'] ?? null,
                        $room['things_included'] ?? null
                    ]);
                }
            }

            // 5. Insert Staff
            if (isset($data['staff_name']) && is_array($data['staff_name'])) {
                $stmt = $pdo->prepare("INSERT INTO property_staff_names (property_id, staff_name) VALUES (?, ?)");
                foreach ($data['staff_name'] as $name) {
                    if (!empty($name))
                        $stmt->execute([$property_id, $name]);
                }
            }

            // 6. Insert Cover Image as featured media (first slot)
            if (!empty($data['cover_image'])) {
                $stmt = $pdo->prepare("INSERT INTO property_media (property_id, media_path, media_type, is_featured, sort_order) VALUES (?, ?, 'image', 1, 0)");
                $stmt->execute([$property_id, $data['cover_image']]);
            }

            // 7. Insert Gallery Photos
            if (isset($data['property_photos']) && is_array($data['property_photos'])) {
                $stmt = $pdo->prepare("INSERT INTO property_media (property_id, media_path, media_type, is_featured, sort_order) VALUES (?, ?, 'image', 0, ?)");
                $sort = 1;
                foreach ($data['property_photos'] as $photo) {
                    if (!empty($photo) && $photo !== $data['cover_image']) {
                        $stmt->execute([$property_id, $photo, $sort++]);
                    }
                }
            }

            // 8. Insert Videos
            if (isset($data['property_videos']) && is_array($data['property_videos'])) {
                $stmt = $pdo->prepare("INSERT INTO property_media (property_id, media_path, media_type, is_featured, sort_order) VALUES (?, ?, 'video', 0, ?)");
                $sort = 1;
                foreach ($data['property_videos'] as $video) {
                    if (!empty($video))
                        $stmt->execute([$property_id, $video, $sort++]);
                }
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'redirect' => 'index.php']);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    // 2. Request Edit logic
    if ($_POST['action'] == 'request_edit') {
        try {
            $user_id = $_SESSION['user_id'] ?? 0;
            $property_id = $_POST['property_id'] ?? null;

            if (!$property_id) {
                echo json_encode(['success' => false, 'message' => 'Missing property ID.']);
                exit;
            }

            // Fetch current data for old_data
            $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
            $stmt->execute([$property_id]);
            $old_data = $stmt->fetch(PDO::FETCH_ASSOC);

            // Store only a safe subset of new_data to avoid massive payload issues
            $safe_new_data = array_filter($_POST, function($key) {
                return !in_array($key, ['_token']); // exclude any internal keys if needed
            }, ARRAY_FILTER_USE_KEY);
            $new_data = json_encode($safe_new_data);

            $stmt = $pdo->prepare("INSERT INTO property_requests (property_id, user_id, request_type, old_data, new_data) VALUES (?, ?, 'edit', ?, ?)");
            $stmt->execute([$property_id, $user_id, json_encode($old_data), $new_data]);

            echo json_encode(['success' => true, 'message' => 'Edit request submitted for admin approval.']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to submit edit request: ' . $e->getMessage()]);
            exit;
        }
    }

    // 3. Request Delete logic
    if ($_POST['action'] == 'request_delete') {
        try {
            $user_id = $_SESSION['user_id'] ?? 0;
            $property_id = $_POST['property_id'] ?? null;

            if (!$property_id) {
                echo json_encode(['success' => false, 'message' => 'Missing property ID.']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO property_requests (property_id, user_id, request_type) VALUES (?, ?, 'delete')");
            $stmt->execute([$property_id, $user_id]);

            echo json_encode(['success' => true, 'message' => 'Deletion request submitted for admin approval.']);
            exit;
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to submit delete request: ' . $e->getMessage()]);
            exit;
        }
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
    <title>Join Bookingjaunt - Excellence Redefined</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            position: relative;
        }

        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(rgba(0, 15, 40, 0.4), rgba(0, 15, 40, 0.8)), url('assets/login-bg.png') no-repeat center center;
            background-size: cover;
        }

        .blue-gradient {
            background: linear-gradient(135deg, #006ce4 0%, #003580 100%);
        }

        .blue-text {
            color: #006ce4;
        }

        .glass-container {
            background: rgba(0, 20, 50, 0.6);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .custom-input {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.75rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .custom-input:focus-within {
            background: rgba(255, 255, 255, 0.08);
            border-color: #006ce4;
            box-shadow: 0 0 0 4px rgba(0, 108, 228, 0.15);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.8s ease-out forwards;
        }

        .step-content {
            display: none;
        }

        .step-content.active {
            display: block;
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>

<body class="flex flex-col items-center justify-center p-6">

    <div class="glass-container max-w-md w-full p-8 md:p-10 rounded-[2rem] animate-fade-in my-10">
        <div class="flex flex-col items-center mb-6 text-center">
            <div class="w-28 h-20 rounded-2xl flex items-center justify-center mb-2">
                <img src="assets/white_logo.png" class="w-24 h-18 object-contain">
            </div>
            <h1 class="text-2xl font-extrabold text-white tracking-tighter mb-1"
                style="font-family: 'Playfair Display', serif;">Bookingjaunt</h1>
            <p class="text-gray-300 text-[9px] font-bold tracking-[0.2em] opacity-80 uppercase">Where every booking
                feels like a vacation</p>
        </div>

        <!-- Step 1: Email Entry -->
        <div id="email-step" class="step-content active">
            <h2 class="text-lg font-bold text-white mb-5 text-center">Start your journey</h2>
            <form id="email-form" class="space-y-6">
                <div class="space-y-1">
                    <label class="block text-[10px] font-bold text-blue-400 mb-1.5 uppercase tracking-widest ml-1">Email
                        Address</label>
                    <div class="flex items-center px-4 py-3 custom-input group">
                        <i
                            class="fas fa-envelope text-gray-500 mr-3 group-focus-within:text-blue-400 transition-colors"></i>
                        <input type="email" id="email-input" name="email" required placeholder="name@example.com"
                            class="w-full bg-transparent outline-none text-white text-[13px] placeholder-gray-500">
                    </div>
                </div>
                <button type="submit"
                    class="w-full blue-gradient text-white py-4 rounded-xl font-bold text-sm shadow-xl shadow-blue-900/40 hover:scale-[1.02] active:scale-95 transition-all uppercase tracking-widest mt-4">Continue</button>
            </form>
        </div>

        <!-- Step 2: Full Registration -->
        <div id="registration-step" class="step-content">
            <h2 class="text-lg font-bold text-white mb-6 text-center">Complete your profile</h2>
            <form id="registration-form" class="space-y-6">
                <input type="hidden" name="action" value="register_user">
                <input type="hidden" name="email" id="final-email">
                <input type="hidden" name="role" id="user-role" value="user">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label
                            class="block text-[10px] font-bold text-blue-400 mb-1.5 uppercase tracking-widest ml-1">First
                            Name</label>
                        <div class="flex items-center px-4 py-3 custom-input group">
                            <input type="text" name="first_name" required placeholder="John"
                                class="w-full bg-transparent outline-none text-white text-[13px] placeholder-gray-500">
                        </div>
                    </div>
                    <div class="space-y-1">
                        <label
                            class="block text-[10px] font-bold text-blue-400 mb-1.5 uppercase tracking-widest ml-1">Last
                            Name</label>
                        <div class="flex items-center px-4 py-3 custom-input group">
                            <input type="text" name="last_name" required placeholder="Doe"
                                class="w-full bg-transparent outline-none text-white text-[13px] placeholder-gray-500">
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label
                            class="block text-[10px] font-bold text-blue-400 mb-1.5 uppercase tracking-widest ml-1">Contact
                            Number</label>
                        <div class="flex items-center px-4 py-3 custom-input group">
                            <i class="fas fa-phone text-gray-500 mr-3 group-focus-within:text-blue-400 transition-colors text-xs"></i>
                            <input type="text" name="phone_number" required placeholder="+94..."
                                class="w-full bg-transparent outline-none text-white text-[13px] placeholder-gray-500">
                        </div>
                    </div>
                    <div class="space-y-1">
                        <label
                            class="block text-[10px] font-bold text-blue-400 mb-1.5 uppercase tracking-widest ml-1">WhatsApp
                            Number</label>
                        <div class="flex items-center px-4 py-3 custom-input group">
                            <i class="fab fa-whatsapp text-gray-500 mr-3 group-focus-within:text-blue-400 transition-colors text-xs"></i>
                            <input type="text" name="whatsapp_number" placeholder="+94..."
                                class="w-full bg-transparent outline-none text-white text-[13px] placeholder-gray-500">
                        </div>
                    </div>
                </div>

                <div class="space-y-1">
                    <label
                        class="block text-[10px] font-bold text-blue-400 mb-1.5 uppercase tracking-widest ml-1">Create
                        Password</label>
                    <div class="flex items-center px-4 py-3 custom-input group">
                        <i
                            class="fas fa-lock text-gray-500 mr-3 group-focus-within:text-blue-400 transition-colors"></i>
                        <input type="password" name="password" required placeholder="••••••••"
                            class="w-full bg-transparent outline-none text-white text-[13px] placeholder-gray-500">
                    </div>
                </div>

                <div class="bg-white/5 p-3 rounded-xl border border-white/10 flex items-center justify-between">
                    <div class="flex flex-col">
                        <span class="text-[11px] font-bold text-white">List your property?</span>
                        <span class="text-[9px] text-gray-400">Register as a property owner</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="is-owner" class="sr-only peer"
                            onchange="document.getElementById('user-role').value = this.checked ? 'owner' : 'user'">
                        <div
                            class="w-9 h-5 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#ff8c00]">
                        </div>
                    </label>
                </div>

                <button type="submit"
                    class="w-full blue-gradient text-white py-4 rounded-xl font-bold text-sm shadow-xl shadow-blue-900/40 hover:scale-[1.02] active:scale-95 transition-all uppercase tracking-widest">Create
                    Account</button>
            </form>
        </div>

        <div class="text-center mt-12 pt-8 border-t border-white/10">
            <p class="text-gray-400 text-sm font-medium">
                Already have an account? <a href="login.php"
                    class="text-white font-bold hover:text-[#006ce4] transition-colors ml-1">Sign In</a>
            </p>
        </div>
    </div>

    <div class="mb-10 text-center text-[10px] text-gray-400 font-bold uppercase tracking-[0.3em] opacity-60">
        © 2026 Experience Sri Lanka, effortlessly-by Bookingjaunt
    </div>

    <script>
        // Email check step
        document.getElementById('email-form').onsubmit = async (e) => {
            e.preventDefault();
            const email = document.getElementById('email-input').value;
            const btn = e.target.querySelector('button');
            const originalBtnHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Checking...';
            btn.disabled = true;

            try {
                const response = await fetch('register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=check_email&email=${encodeURIComponent(email)}`
                });
                const result = await response.json();

                if (result.exists) {
                    window.location.href = result.redirect;
                } else {
                    document.getElementById('email-step').classList.remove('active');
                    document.getElementById('registration-step').classList.add('active');
                    document.getElementById('final-email').value = email;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Something went wrong. Please try again.');
            } finally {
                btn.innerHTML = originalBtnHtml;
                btn.disabled = false;
            }
        };

        // Full registration step
        document.getElementById('registration-form').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const btn = e.target.querySelector('button');
            const originalBtnHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Creating...';
            btn.disabled = true;

            try {
                const response = await fetch('register.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    window.location.href = result.redirect;
                } else {
                    alert(result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Registration failed. Please try again.');
            } finally {
                btn.innerHTML = originalBtnHtml;
                btn.disabled = false;
            }
        };
    </script>

</body>

</html>