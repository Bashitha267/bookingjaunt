<?php
require_once 'config.php';
session_start();

// Get data from URL or Form
$property_id = $_GET['property_id'] ?? ($_POST['property_id'] ?? 0);
$room_id = $_GET['room_id'] ?? ($_POST['room_id'] ?? 0);
$check_in = $_GET['checkin'] ?? ($_POST['checkin'] ?? date('Y-m-d'));
$check_out = $_GET['checkout'] ?? ($_POST['checkout'] ?? date('Y-m-d', strtotime('+1 day')));
$room_qty = $_GET['qty'] ?? ($_POST['qty'] ?? 1);

if (($_GET['action'] ?? '') === 'check_availability') {
    header('Content-Type: application/json');

    $room_id_check = (int) ($_GET['room_id'] ?? 0);
    $check_in_check = $_GET['checkin'] ?? '';
    $check_out_check = $_GET['checkout'] ?? '';
    $room_qty_check = max(1, (int) ($_GET['qty'] ?? 1));

    if (!$room_id_check || empty($check_in_check) || empty($check_out_check)) {
        echo json_encode(['available' => false, 'message' => 'Please select both check-in and check-out dates.']);
        exit();
    }

    if (strtotime($check_out_check) <= strtotime($check_in_check)) {
        echo json_encode(['available' => false, 'message' => 'Check-out must be after check-in.']);
        exit();
    }

    $room_stmt = $pdo->prepare("SELECT total_rooms FROM property_rooms WHERE id = ?");
    $room_stmt->execute([$room_id_check]);
    $total_rooms = (int) $room_stmt->fetchColumn();

    if ($total_rooms <= 0) {
        echo json_encode(['available' => false, 'message' => 'Room availability not found.']);
        exit();
    }

    $booked_stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id = ? AND status NOT IN ('cancelled') AND (check_in_date < ? AND check_out_date > ?)");
    $booked_stmt->execute([$room_id_check, $check_out_check, $check_in_check]);
    $booked_rooms = (int) $booked_stmt->fetchColumn();

    $available_rooms = $total_rooms - $booked_rooms;
    $is_available = $available_rooms >= $room_qty_check;

    echo json_encode([
        'available' => $is_available,
        'message' => $is_available ? 'Rooms are available for your dates.' : 'Sorry, this room is not available for the selected dates.'
    ]);
    exit();
}

if (!$property_id || !$room_id) {
    header("Location: index.php");
    exit();
}

// Fetch Property and Room Details
$stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
$stmt->execute([$property_id]);
$property = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM property_rooms WHERE id = ?");
$stmt->execute([$room_id]);
$room = $stmt->fetch();

if (!$property || !$room) {
    die("Property or Room not found.");
}

// Fetch User Details if logged in for auto-fill
$user_data = null;
if (isset($_SESSION['user_id'])) {
    $u_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $u_stmt->execute([$_SESSION['user_id']]);
    $user_data = $u_stmt->fetch();
}

$error = "";
$success = "";

// Handle Booking Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_booking'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $guest_name = $first_name . ' ' . $last_name;
    $guest_phone = trim($_POST['guest_phone'] ?? '');
    $guest_email = trim($_POST['guest_email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($guest_email) || empty($guest_phone)) {
        $error = "Please fill in all required guest details.";
    } elseif (!isset($_SESSION['user_id']) && empty($password)) {
        $error = "Please create a password for your account.";
    } else {
        $adults = $_POST['adults'] ?? 1;
        $children = $_POST['children'] ?? 0;
        $country = $_POST['country'] ?? 'Sri Lanka';
        $booking_type = $_POST['booking_type'] ?? 'online';
        $amount_paid = $_POST['amount_paid'] ?? 0;
        $payment_desc = $_POST['payment_description'] ?? '';
        $user_id = $_SESSION['user_id'] ?? null;

        // If not logged in, try to register/login
        if (!$user_id && !empty($guest_email)) {
            // Check if user exists
            $user_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $user_stmt->execute([$guest_email]);
            $existing_user = $user_stmt->fetch();

            if ($existing_user) {
                $user_id = $existing_user['id'];
            } elseif (!empty($password)) {
                // Register new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                try {
                    $reg_stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, 'user')");
                    $reg_stmt->execute([$first_name, $last_name, $guest_email, $hashed_password]);
                    $user_id = $pdo->lastInsertId();
                    
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                    $_SESSION['role'] = 'user';
                } catch (PDOException $e) {
                    $user_stmt->execute([$guest_email]);
                    $existing_user = $user_stmt->fetch();
                    if ($existing_user) $user_id = $existing_user['id'];
                }
            }
        }
        
        $days = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
        if ($days <= 0) $days = 1;
        $price_per_room = $room['price_lkr'];
        $total_price = $price_per_room * $room_qty * $days;
        
        $paying_now = ($_POST['pay_now'] ?? 'yes') == 'yes';
        $payment_status = $paying_now ? (($amount_paid >= $total_price) ? 'complete' : 'pending') : 'pending';
        if (!$paying_now) $amount_paid = 0;

        try {
            $stmt = $pdo->prepare("INSERT INTO bookings 
                (property_id, room_id, user_id, booking_type, guest_name, guest_phone, guest_email, check_in_date, check_out_date, adults, children, country, status, price_per_room, total_price, amount_paid, payment_description, payment_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?)");
            $stmt->execute([
                $property_id, $room_id, $user_id, $booking_type, $guest_name, $guest_phone, $guest_email,
                $check_in, $check_out, $adults, $children, $country,
                $price_per_room, $total_price, $amount_paid, $payment_desc, $payment_status
            ]);
            $success = "Booking confirmed successfully! Your booking ID is #" . $pdo->lastInsertId();
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
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
    <title>Complete Your Booking - Bookingjaunt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include 'navbar.php'; ?>

    <main class="max-w-6xl mx-auto px-4 py-8 md:py-12">
        <!-- Stepper -->
        <div class="flex items-center justify-center mb-10 md:mb-16">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold">1</div>
                    <span class="text-sm font-bold text-blue-600">Selection</span>
                </div>
                <div class="w-12 h-[1px] bg-gray-200"></div>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold border-4 border-blue-100">2</div>
                    <span class="text-sm font-bold text-blue-600">Your Details</span>
                </div>
                <div class="w-12 h-[1px] bg-gray-200"></div>
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-xs font-bold">3</div>
                    <span class="text-sm font-bold text-gray-400">Final Step</span>
                </div>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="max-w-3xl mx-auto bg-green-50 border border-green-200 p-8 rounded-3xl text-center">
                <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-check text-3xl"></i>
                </div>
                <h2 class="text-2xl font-black text-gray-900 mb-2">Booking Confirmed!</h2>
                <p class="text-gray-600 mb-8"><?php echo $success; ?></p>
                <a href="index.php" class="inline-block bg-[#003580] text-white px-10 py-4 rounded-xl font-bold uppercase tracking-widest text-sm shadow-xl shadow-blue-900/20">Go to Home</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                
                <!-- Left: Booking Form -->
                <div class="lg:col-span-8 space-y-6">
                    <?php if ($error): ?>
                        <div class="bg-red-50 border border-red-100 text-red-600 p-4 rounded-2xl flex items-center gap-3 mb-4">
                            <i class="fas fa-exclamation-circle"></i>
                            <p class="text-sm font-bold"><?php echo $error; ?></p>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="bookingForm">
                        <input type="hidden" name="property_id" value="<?php echo $property_id; ?>">
                        <input type="hidden" name="room_id" value="<?php echo $room_id; ?>">
                        <input type="hidden" name="qty" value="<?php echo $room_qty; ?>">
                        <input type="hidden" name="booking_type" value="<?php echo isset($_SESSION['user_id']) && $_SESSION['role'] != 'user' ? 'inplace' : 'online'; ?>">

                        <!-- 1. Details Section -->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 md:p-8 mb-6">
                            <div class="flex items-center gap-3 mb-8">
                                <i class="fas fa-user text-blue-600"></i>
                                <h2 class="text-xl font-black text-gray-900 tracking-tight">Enter your details</h2>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-2">First Name</label>
                                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($user_data['first_name'] ?? ''); ?>" required placeholder="e.g. John" 
                                           class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all text-sm font-medium">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-2">Last Name</label>
                                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($user_data['last_name'] ?? ''); ?>" required placeholder="e.g. Doe" 
                                           class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all text-sm font-medium">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-2">Email Address</label>
                                    <input type="email" name="guest_email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required placeholder="john.doe@example.com" 
                                           class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all text-sm font-medium">
                                    <p class="text-[10px] text-gray-400 mt-2 font-medium">Confirmation email will be sent here.</p>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-2">Phone Number</label>
                                    <input type="tel" name="guest_phone" value="<?php echo htmlspecialchars($user_data['phone_number'] ?? ''); ?>" required placeholder="e.g. +94 77 123 4567" 
                                           class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all text-sm font-medium">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-2">Check-in Date</label>
                                    <input type="date" name="checkin" id="checkinInputVisible" value="<?php echo htmlspecialchars($check_in); ?>" min="<?php echo date('Y-m-d'); ?>" required
                                           class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all text-sm font-medium">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-2">Check-out Date</label>
                                    <input type="date" name="checkout" id="checkoutInputVisible" value="<?php echo htmlspecialchars($check_out); ?>" min="<?php echo date('Y-m-d'); ?>" required
                                           class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all text-sm font-medium">
                                </div>
                            </div>

                            <?php if (!isset($_SESSION['user_id'])): ?>
                            <div class="mb-6">
                                <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-2">Create Password (to manage booking later)</label>
                                <input type="password" name="password" required placeholder="••••••••" 
                                       class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all text-sm font-medium">
                            </div>
                            <?php endif; ?>

                            <div class="bg-blue-50 p-4 rounded-xl flex items-start gap-4">
                                <div class="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center shrink-0">
                                    <i class="fas fa-info text-[10px]"></i>
                                </div>
                                <p class="text-[11px] text-blue-800 font-bold leading-relaxed">Almost done! Just fill in the required fields to complete your booking.</p>
                            </div>
                        </div>

                        <!-- 2. Special Requests -->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 md:p-8 mb-6">
                            <div class="flex items-center gap-3 mb-4">
                                <i class="fas fa-bars-staggered text-blue-600"></i>
                                <h2 class="text-xl font-black text-gray-900 tracking-tight">Special Requests</h2>
                            </div>
                            <p class="text-[11px] text-gray-400 font-medium mb-6 leading-relaxed">Special requests cannot be guaranteed, but the property will do its best to meet your needs.</p>
                            <textarea name="special_requests" placeholder="e.g. High floor, quiet room, early check-in (if available)..." 
                                      class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all text-sm font-medium h-32 resize-none"></textarea>
                        </div>

                        <!-- 3. Payment Option -->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 md:p-8 mb-6">
                            <div class="flex items-center gap-3 mb-6">
                                <i class="fas fa-wallet text-blue-600"></i>
                                <h2 class="text-xl font-black text-gray-900 tracking-tight">Would you like to pay now?</h2>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <label class="relative flex items-center p-4 border border-gray-100 rounded-2xl cursor-pointer hover:bg-blue-50/30 transition-all group">
                                    <input type="radio" name="pay_now" value="yes" checked class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" onchange="togglePayment(true)">
                                    <div class="ml-4">
                                        <p class="text-sm font-black text-gray-900">Pay Now</p>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Secure online payment</p>
                                    </div>
                                </label>
                                <label class="relative flex items-center p-4 border border-gray-100 rounded-2xl cursor-pointer hover:bg-blue-50/30 transition-all group">
                                    <input type="radio" name="pay_now" value="no" class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" onchange="togglePayment(false)">
                                    <div class="ml-4">
                                        <p class="text-sm font-black text-gray-900">Pay at Property</p>
                                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Pay when you arrive</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- 4. Payment Method -->
                        <div id="paymentDetails" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 md:p-8">
                            <div class="flex justify-between items-center mb-8">
                                <div class="flex items-center gap-3">
                                    <i class="fas fa-credit-card text-blue-600"></i>
                                    <h2 class="text-xl font-black text-gray-900 tracking-tight">Payment Method</h2>
                                </div>
                                <div class="flex gap-2">
                                    <i class="fab fa-cc-visa text-gray-400 text-2xl"></i>
                                    <i class="fab fa-cc-mastercard text-gray-400 text-2xl"></i>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-2">Cardholder's Name</label>
                                    <input type="text" placeholder="Name as on card" 
                                           class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all text-sm font-medium">
                                </div>
                                <div class="relative">
                                    <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-2">Card Number</label>
                                    <input type="text" placeholder="0000 0000 0000 0000" 
                                           class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all text-sm font-medium pr-12">
                                    <i class="fas fa-lock absolute right-5 top-11 text-gray-300"></i>
                                </div>
                                <div class="grid grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-2">Expiry</label>
                                        <input type="text" placeholder="MM / YY" 
                                               class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all text-sm font-medium text-center">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-bold text-gray-400 uppercase tracking-widest mb-2">CVC</label>
                                        <input type="text" placeholder="123" 
                                               class="w-full px-5 py-4 bg-gray-50 border border-gray-100 rounded-xl outline-none focus:ring-2 focus:ring-blue-500/20 transition-all text-sm font-medium text-center">
                                    </div>
                                </div>
                            </div>

                            <div class="mt-8 flex items-center gap-3 text-[11px] text-green-600 font-bold">
                                <i class="fas fa-shield-alt text-lg"></i>
                                <span>Your data is protected by industry-standard TLS encryption.</span>
                            </div>
                        </div>

                        <!-- Combined Submit Logic -->
                        <input type="hidden" name="confirm_booking" value="1">
                        <input type="hidden" name="guest_name" id="combined_guest_name">
                        <input type="hidden" name="country" value="Sri Lanka">
                        <input type="hidden" name="adults" value="1">
                        <input type="hidden" name="children" value="0">
                    </form>
                </div>

                <!-- Right: Summary Sidebar -->
                <div class="lg:col-span-4 space-y-6 lg:sticky lg:top-24">
                    <div class="bg-white rounded-3xl border border-gray-100 shadow-xl overflow-hidden">
                        <div class="relative h-48 overflow-hidden">
                            <img src="<?php echo htmlspecialchars($room['room_image']); ?>" class="w-full h-full object-cover">
                            <div class="absolute top-4 right-4 bg-white/90 backdrop-blur-md px-2 py-1 rounded-lg flex items-center gap-1.5 shadow-sm">
                                <i class="fas fa-star text-[#febb02] text-[10px]"></i>
                                <span class="text-[11px] font-black text-gray-900">4.8</span>
                            </div>
                        </div>
                        
                        <div class="p-8">
                            <h2 class="text-xl font-black text-gray-900 mb-1"><?php echo htmlspecialchars($property['property_name']); ?></h2>
                            <p class="text-[11px] text-gray-400 font-bold uppercase tracking-widest flex items-center gap-2 mb-8">
                                <i class="fas fa-map-marker-alt text-blue-600"></i> <?php echo htmlspecialchars($property['city']); ?>
                            </p>

                            <div class="space-y-4 mb-8">
                                <div class="flex justify-between items-center text-[11px]">
                                    <span class="font-bold text-gray-400 uppercase tracking-widest">Check-in</span>
                                    <span id="summaryCheckIn" class="font-black text-gray-900"><?php echo date('D, M d, Y', strtotime($check_in)); ?></span>
                                </div>
                                <div class="flex justify-between items-center text-[11px]">
                                    <span class="font-bold text-gray-400 uppercase tracking-widest">Check-out</span>
                                    <span id="summaryCheckOut" class="font-black text-gray-900"><?php echo date('D, M d, Y', strtotime($check_out)); ?></span>
                                </div>
                                <div class="flex justify-between items-center text-[11px]">
                                    <span class="font-bold text-gray-400 uppercase tracking-widest">Stay duration</span>
                                    <span id="summaryNights" class="font-black text-gray-900">
                                        <?php 
                                            $days = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
                                            if ($days <= 0) $days = 1;
                                            echo $days; 
                                        ?> Nights
                                    </span>
                                </div>
                                <div class="flex justify-between items-center text-[11px]">
                                    <span class="font-bold text-gray-400 uppercase tracking-widest">Room type</span>
                                    <span class="font-black text-gray-900 italic"><?php echo htmlspecialchars($room['room_name']); ?></span>
                                </div>
                            </div>

                            <div class="pt-8 border-t border-gray-50">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-[11px] font-bold text-gray-400 uppercase tracking-widest">Total Price</span>
                                    <span class="text-[11px] font-bold text-gray-400 line-through">LKR <?php echo number_format($room['price_lkr'] * $room_qty * $days * 1.25); ?></span>
                                </div>
                                <div class="flex justify-between items-end">
                                    <div class="bg-green-100 text-green-700 px-3 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest">Limited Deal -20%</div>
                                    <div class="text-right">
                                        <p class="text-3xl font-black text-gray-900">LKR <?php echo number_format($room['price_lkr'] * $room_qty * $days); ?></p>
                                        <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-1">Includes taxes and charges</p>
                                    </div>
                                </div>
                            </div>

                            <button type="button" onclick="handleBookingSubmit()" 
                                    class="w-full bg-[#003580] text-white py-5 rounded-2xl font-black text-sm uppercase tracking-widest mt-10 hover:bg-[#002560] transition-all shadow-xl shadow-blue-900/20 active:scale-95">
                                Complete Booking
                            </button>

                            <p class="mt-6 text-center text-[10px] text-gray-400 leading-relaxed font-medium">
                                By clicking "Complete Booking", you agree to the <a href="#" class="text-blue-600 hover:underline">Terms & Conditions</a> and <a href="#" class="text-blue-600 hover:underline">Privacy Policy</a>.
                            </p>
                        </div>
                    </div>

                    <!-- Trust Box -->
                    <div class="bg-blue-50/50 border border-blue-100 rounded-2xl p-6 flex items-start gap-4">
                        <div class="w-10 h-10 bg-green-500 text-white rounded-full flex items-center justify-center shrink-0 shadow-lg shadow-green-500/20">
                            <i class="fas fa-check"></i>
                        </div>
                        <div>
                            <h4 class="text-sm font-black text-gray-900 mb-1">No hidden fees</h4>
                            <p class="text-[11px] text-gray-500 font-medium leading-relaxed">What you see is what you pay. No extra booking fees.</p>
                        </div>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </main>

    <footer class="py-12 border-t border-gray-100 text-center">
        <div class="flex justify-center gap-6 mb-6 text-gray-300">
            <i class="fab fa-cc-visa text-xl"></i>
            <i class="fas fa-shield-halved text-xl"></i>
            <i class="fas fa-globe text-xl"></i>
        </div>
        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">
            © <?php echo date('Y'); ?> StayEase – Your worldwide travel companion.
        </p>
    </footer>
    <div id="toast" class="fixed top-6 right-6 z-50 hidden">
        <div id="toastBody" class="px-5 py-4 rounded-xl shadow-lg text-sm font-bold"></div>
    </div>

    <script>
        const roomId = <?php echo (int) $room_id; ?>;
        const roomQty = <?php echo (int) $room_qty; ?>;

        function showToast(message, type) {
            const toast = document.getElementById('toast');
            const toastBody = document.getElementById('toastBody');
            toastBody.textContent = message;

            toastBody.className = 'px-5 py-4 rounded-xl shadow-lg text-sm font-bold ' +
                (type === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700');

            toast.classList.remove('hidden');
            clearTimeout(toast.hideTimer);
            toast.hideTimer = setTimeout(() => toast.classList.add('hidden'), 3000);
        }

        function updateSummaryDates() {
            const checkin = document.getElementById('checkinInputVisible').value;
            const checkout = document.getElementById('checkoutInputVisible').value;
            const checkinEl = document.getElementById('summaryCheckIn');
            const checkoutEl = document.getElementById('summaryCheckOut');
            const nightsEl = document.getElementById('summaryNights');

            if (checkin) {
                checkinEl.textContent = new Date(checkin + 'T00:00:00').toLocaleDateString('en-US', {
                    weekday: 'short', month: 'short', day: 'numeric', year: 'numeric'
                });
            }

            if (checkout) {
                checkoutEl.textContent = new Date(checkout + 'T00:00:00').toLocaleDateString('en-US', {
                    weekday: 'short', month: 'short', day: 'numeric', year: 'numeric'
                });
            }

            if (checkin && checkout) {
                const start = new Date(checkin + 'T00:00:00');
                const end = new Date(checkout + 'T00:00:00');
                const diffDays = Math.max(1, Math.round((end - start) / (1000 * 60 * 60 * 24)));
                nightsEl.textContent = diffDays + ' Nights';
            }
        }

        function handleBookingSubmit() {
            const checkin = document.getElementById('checkinInputVisible').value;
            const checkout = document.getElementById('checkoutInputVisible').value;

            if (!checkin || !checkout) {
                showToast('Please select both check-in and check-out dates.', 'error');
                return;
            }

            if (new Date(checkout) <= new Date(checkin)) {
                showToast('Check-out must be after check-in.', 'error');
                return;
            }

            fetch(`newbooking.php?action=check_availability&room_id=${roomId}&checkin=${encodeURIComponent(checkin)}&checkout=${encodeURIComponent(checkout)}&qty=${roomQty}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.available) {
                        showToast(data.message || 'Room not available for selected dates.', 'error');
                        return;
                    }

                    document.getElementById('combined_guest_name').value =
                        document.querySelector('[name=first_name]').value + ' ' +
                        document.querySelector('[name=last_name]').value;

                    showToast(data.message || 'Rooms are available.', 'success');
                    setTimeout(() => document.getElementById('bookingForm').submit(), 600);
                })
                .catch(() => showToast('Unable to check availability. Please try again.', 'error'));
        }

        function togglePayment(show) {
            const paymentDetails = document.getElementById('paymentDetails');
            if (show) {
                paymentDetails.style.display = 'block';
                // Add required to payment fields if needed
            } else {
                paymentDetails.style.display = 'none';
            }
        }

        document.getElementById('checkinInputVisible').addEventListener('change', updateSummaryDates);
        document.getElementById('checkoutInputVisible').addEventListener('change', updateSummaryDates);
        updateSummaryDates();
    </script>
</body>
</html>
