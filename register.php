<?php
require_once 'config.php';
session_start();

// Handle AJAX requests for Email Check and Registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
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
            $stmt = $pdo->prepare("INSERT INTO properties (owner_id, business_type, hotel_category, property_name, description, street_address, city, district, province, country, google_map_location, fixed_telephone, mobile_telephone, closest_police_station, closest_hospital, airport_distance, closest_main_town, postal_code, logo_image, cover_image, manager_name, manager_phone, manager_nic, manager_photo, contact_number, business_email, check_in_time, check_out_time, cancellation_policy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $owner_id,
                $data['business_type'],
                $data['hotel_category'] ?? null,
                $data['property_name'],
                $data['description'] ?? '',
                $data['street_address'],
                $data['city'],
                $data['district'] ?? '',
                $data['province'] ?? '',
                $data['country'],
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
                $data['cancellation_policy'] ?? ''
            ]);

            $property_id = $pdo->lastInsertId();

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
                $stmt = $pdo->prepare("INSERT INTO property_rooms (property_id, room_name, adults, children, room_image, price_lkr, price_usd) VALUES (?, ?, ?, ?, ?, ?, ?)");
                foreach ($data['rooms'] as $room) {
                    $stmt->execute([
                        $property_id,
                        $room['name'],
                        $room['adults'] ?? 0,
                        $room['children'] ?? 0,
                        $room['image'] ?? '',
                        $room['price_lkr'] ?? 0,
                        $room['price_usd'] ?? 0
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

            $pdo->commit();
            echo json_encode(['success' => true, 'redirect' => 'index.php']);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
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