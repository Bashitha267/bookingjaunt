<?php
require_once 'config.php';
session_start();

// Handle Form Submission via AJAX or Post
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] == 'register_user') {
        // User registration logic
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone_number'] ?? '';
        $whatsapp = $_POST['whatsapp_number'] ?? '';
        $nic = $_POST['nic_passport'] ?? '';
        $username = $_POST['username'] ?? '';
        $password = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
        $address = $_POST['address'] ?? '';
        $country = $_POST['country'] ?? '';
        $role = $_POST['role'] ?? 'user';

        try {
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone_number, whatsapp_number, nic_passport, username, password, address, country, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$first_name, $last_name, $email, $phone, $whatsapp, $nic, $username, $password, $address, $country, $role]);
            
            $user_id = $pdo->lastInsertId();
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = $role;

            echo json_encode(['success' => true, 'redirect' => ($role == 'owner' ? 'property_reg' : 'index.php')]);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Email or Username already exists.']);
            exit;
        }
    }

    if ($_POST['action'] == 'register_property') {
        // Complex property registration logic
        // This would involve multiple tables: properties, amenities, services, etc.
        // For brevity in this initial implementation, I'll handle the main data
        $owner_id = $_SESSION['user_id'] ?? 0;
        if (!$owner_id) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized. Please register as user first.']);
            exit;
        }

        $data = $_POST;
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO properties (owner_id, business_type, hotel_category, property_name, description, street_address, city, country, google_map_location, contact_number, whatsapp_number, business_email, manager_name, manager_email, manager_phone, payout_percentage, commission_percentage, allow_payout_requests, min_payout_amount, currency, vat_percentage, service_charge_percentage, check_in_time, check_out_time, id_required, cancellation_policy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $owner_id,
                $data['business_type'],
                $data['hotel_category'] ?? null,
                $data['property_name'],
                $data['description'],
                $data['street_address'],
                $data['city'],
                $data['country'],
                $data['google_map_location'],
                $data['contact_number'],
                $data['whatsapp_number'],
                $data['business_email'],
                $data['manager_name'] ?? null,
                $data['manager_email'] ?? null,
                $data['manager_phone'] ?? null,
                $data['payout_percentage'] ?? 80,
                20, // fixed commission
                isset($data['allow_payout_requests']) ? 1 : 0,
                $data['min_payout_amount'] ?? 0,
                $data['currency'] ?? 'USD',
                $data['vat_percentage'] ?? 0,
                $data['service_charge_percentage'] ?? 0,
                $data['check_in_time'],
                $data['check_out_time'],
                isset($data['id_required']) ? 1 : 0,
                $data['cancellation_policy']
            ]);

            $property_id = $pdo->lastInsertId();

            // Handle Amenities
            if (isset($data['amenities'])) {
                foreach ($data['amenities'] as $amenity) {
                    $stmt = $pdo->prepare("INSERT INTO property_amenities (property_id, amenity_name) VALUES (?, ?)");
                    $stmt->execute([$property_id, $amenity]);
                }
            }

            // Handle Bank Details
            if ($data['payment_method'] == 'bank_transfer') {
                $stmt = $pdo->prepare("INSERT INTO property_bank_details (property_id, bank_name, account_number, account_holder_name) VALUES (?, ?, ?, ?)");
                $stmt->execute([$property_id, $data['bank_name'], $data['account_number'], $data['account_holder_name']]);
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
    <title>Registration - Bookingjaunt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .step-content { display: none; }
        .step-content.active { display: block; }
        .progress-bar-step { transition: width 0.3s ease; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 min-h-screen">

    <div id="selection-screen" class="min-h-screen flex items-center justify-center p-6">
        <div class="max-w-4xl w-full grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Register as User -->
            <div onclick="showRegistration('user')" class="bg-white p-8 rounded-3xl shadow-sm border-2 border-transparent hover:border-indigo-500 cursor-pointer transition-all group">
                <div class="w-16 h-16 bg-indigo-50 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-indigo-500 transition-colors">
                    <i class="fas fa-user text-2xl text-indigo-500 group-hover:text-white"></i>
                </div>
                <h3 class="text-2xl font-bold mb-2">Register as User</h3>
                <p class="text-gray-500">I want to browse and book properties for my next trip.</p>
            </div>
            <!-- Register as Owner -->
            <div onclick="showRegistration('owner')" class="bg-white p-8 rounded-3xl shadow-sm border-2 border-transparent hover:border-purple-500 cursor-pointer transition-all group">
                <div class="w-16 h-16 bg-purple-50 rounded-2xl flex items-center justify-center mb-6 group-hover:bg-purple-500 transition-colors">
                    <i class="fas fa-hotel text-2xl text-purple-500 group-hover:text-white"></i>
                </div>
                <h3 class="text-2xl font-bold mb-2">Register Property</h3>
                <p class="text-gray-500">I want to list my hotel or reception hall and manage it.</p>
            </div>
        </div>
    </div>

    <!-- User Registration Form -->
    <div id="user-registration" class="hidden min-h-screen flex items-center justify-center p-6">
        <div class="bg-white max-w-5xl w-full rounded-3xl shadow-xl overflow-hidden flex flex-col md:flex-row">
            <div class="md:w-2/5 bg-indigo-600 p-12 text-white flex flex-col justify-between">
                <div>
                    <h2 class="text-3xl font-bold mb-4">Join Bookingjaunt</h2>
                    <p class="opacity-80">Create an account to start your journey with us.</p>
                </div>
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-check-circle"></i>
                        <span>Best prices guaranteed</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <i class="fas fa-check-circle"></i>
                        <span>24/7 Customer support</span>
                    </div>
                </div>
            </div>
            <div class="md:w-3/5 p-12">
                <h2 class="text-2xl font-bold mb-8">Personal Details</h2>
                <form id="user-form" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <input type="hidden" name="action" value="register_user">
                    <input type="hidden" name="role" id="user-role-input" value="user">
                    
                    <div>
                        <label class="block text-sm font-semibold mb-2">First Name</label>
                        <input type="text" name="first_name" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Last Name</label>
                        <input type="text" name="last_name" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Email Address</label>
                        <input type="email" name="email" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Contact Number</label>
                        <input type="text" name="phone_number" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">WhatsApp Number</label>
                        <input type="text" name="whatsapp_number" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">NIC / Passport Number</label>
                        <input type="text" name="nic_passport" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold mb-2">Address</label>
                        <textarea name="address" rows="2" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Country</label>
                        <input type="text" name="country" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Username</label>
                        <input type="text" name="username" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Password</label>
                        <input type="password" name="password" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-2">Confirm Password</label>
                        <input type="password" name="confirm_password" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    
                    <div class="md:col-span-2 mt-4">
                        <button type="submit" class="w-full bg-indigo-600 text-white py-4 rounded-xl font-bold hover:bg-indigo-700 transition-colors">Create Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Property Registration Wizard -->
    <div id="property-registration" class="hidden min-h-screen py-12 px-6">
        <div class="max-w-5xl mx-auto">
            <!-- Header & Progress -->
            <div class="mb-12">
                <h2 class="text-3xl font-bold mb-6">List Your Property</h2>
                <div class="flex items-center gap-2 mb-4 overflow-x-auto pb-4 no-scrollbar">
                    <template id="step-nav-template">
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <div class="step-indicator w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold bg-gray-200 text-gray-500">1</div>
                            <span class="text-sm font-medium text-gray-500 whitespace-nowrap">Step Name</span>
                            <div class="h-px w-8 bg-gray-200 mx-2 last:hidden"></div>
                        </div>
                    </template>
                    <div id="step-navigation" class="flex items-center gap-2"></div>
                </div>
                <div class="h-2 w-full bg-gray-200 rounded-full overflow-hidden">
                    <div id="main-progress" class="h-full bg-purple-600 transition-all" style="width: 12.5%"></div>
                </div>
            </div>

            <!-- Wizard Form -->
            <form id="property-form" class="bg-white rounded-3xl shadow-sm p-8 md:p-12">
                <input type="hidden" name="action" value="register_property">
                
                <!-- Step 1: Business Type -->
                <div class="step-content active" data-step="1">
                    <h3 class="text-2xl font-bold mb-8">Business Type</h3>
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-semibold mb-4">Business Category</label>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="border-2 rounded-2xl p-6 cursor-pointer flex flex-col items-center gap-4 transition-all hover:bg-gray-50 peer-checked:border-purple-500 has-[:checked]:border-purple-500 has-[:checked]:bg-purple-50">
                                    <input type="radio" name="business_type" value="hotel" required class="hidden" onchange="toggleHotelCategory(true)">
                                    <i class="fas fa-hotel text-3xl text-purple-600"></i>
                                    <span class="font-bold">Hotel</span>
                                </label>
                                <label class="border-2 rounded-2xl p-6 cursor-pointer flex flex-col items-center gap-4 transition-all hover:bg-gray-50 has-[:checked]:border-purple-500 has-[:checked]:bg-purple-50">
                                    <input type="radio" name="business_type" value="reception_hall" class="hidden" onchange="toggleHotelCategory(false)">
                                    <i class="fas fa-glass-cheers text-3xl text-purple-600"></i>
                                    <span class="font-bold">Reception Hall</span>
                                </label>
                            </div>
                        </div>
                        <div id="hotel-category-container" class="hidden">
                            <label class="block text-sm font-semibold mb-4">Hotel Category</label>
                            <select name="hotel_category" class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none">
                                <option value="budget_friendly">Budget Friendly</option>
                                <option value="luxury">Luxury</option>
                                <option value="super_luxury">Super Luxury</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Property Info -->
                <div class="step-content" data-step="2">
                    <h3 class="text-2xl font-bold mb-8">Property Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold mb-2">Property Name</label>
                            <input type="text" name="property_name" required class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold mb-2">Description</label>
                            <textarea name="description" rows="3" class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none"></textarea>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold mb-2">Street Address</label>
                            <input type="text" name="street_address" required class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2">City</label>
                            <input type="text" name="city" required class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2">Country</label>
                            <input type="text" name="country" required class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold mb-2">Google Map Location (URL/Iframe)</label>
                            <input type="text" name="google_map_location" class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2">Business Contact Number</label>
                            <input type="text" name="contact_number" required class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2">WhatsApp Number</label>
                            <input type="text" name="whatsapp_number" class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold mb-2">Business Email</label>
                            <input type="email" name="business_email" required class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none">
                        </div>
                    </div>
                </div>

                <!-- Step 3: Manager Setup -->
                <div class="step-content" data-step="3">
                    <h3 class="text-2xl font-bold mb-8">Manager Setup</h3>
                    <div class="space-y-6">
                        <div class="flex items-center justify-between p-6 bg-gray-50 rounded-2xl">
                            <div>
                                <h4 class="font-bold">Owner acts as manager</h4>
                                <p class="text-sm text-gray-500">Enable if the property owner will also manage the operations.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="owner_as_manager" value="1" checked class="sr-only peer" onchange="toggleManagerFields(this)">
                                <div class="w-14 h-7 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-purple-600"></div>
                            </label>
                        </div>
                        
                        <div id="manager-fields" class="hidden grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
                            <div>
                                <label class="block text-sm font-semibold mb-2">Manager Full Name</label>
                                <input type="text" name="manager_name" class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2">Manager Email</label>
                                <input type="email" name="manager_email" class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2">Manager Contact Number</label>
                                <input type="text" name="manager_phone" class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-2">Manager Password</label>
                                <input type="password" name="manager_password" class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Staff Setup -->
                <div class="step-content" data-step="4">
                    <div class="flex justify-between items-center mb-8">
                        <h3 class="text-2xl font-bold">Staff Setup</h3>
                        <button type="button" onclick="addStaffRow()" class="px-4 py-2 bg-purple-100 text-purple-700 rounded-lg font-bold hover:bg-purple-200">+ Add Staff</button>
                    </div>
                    <div id="staff-container" class="space-y-4">
                        <!-- Staff rows will be added here -->
                    </div>
                </div>

                <!-- Step 5: Business Rules -->
                <div class="step-content" data-step="5">
                    <h3 class="text-2xl font-bold mb-8">Business Rules</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="bg-indigo-50 p-6 rounded-2xl">
                            <label class="block text-sm font-bold mb-2 text-indigo-900">Owner Payout Percentage (%)</label>
                            <input type="number" name="payout_percentage" value="80" class="w-full bg-white px-4 py-3 rounded-xl border border-indigo-200 outline-none">
                            <p class="text-xs text-indigo-600 mt-2">The percentage of revenue the property owner receives.</p>
                        </div>
                        <div class="bg-gray-50 p-6 rounded-2xl">
                            <label class="block text-sm font-bold mb-2 text-gray-500">Platform Commission (%)</label>
                            <input type="number" value="20" disabled class="w-full bg-gray-100 px-4 py-3 rounded-xl border border-gray-200 outline-none text-gray-400">
                            <p class="text-xs text-gray-400 mt-2">Auto-calculated platform service fee.</p>
                        </div>
                        <div class="md:col-span-2 flex items-center justify-between p-6 border-2 border-gray-100 rounded-2xl">
                            <div>
                                <h4 class="font-bold">Allow Payout Requests</h4>
                                <p class="text-sm text-gray-500">Enable if the owner can request payouts manually.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="allow_payout_requests" value="1" checked class="sr-only peer">
                                <div class="w-14 h-7 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-purple-600 after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all"></div>
                            </label>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2">Minimum Payout Request Amount</label>
                            <input type="number" name="min_payout_amount" value="100" class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none">
                        </div>
                    </div>
                </div>

                <!-- Step 6: Amenities & Services -->
                <div class="step-content" data-step="6">
                    <h3 class="text-2xl font-bold mb-6">Amenities & Extra Services</h3>
                    <div class="mb-10">
                        <label class="block text-sm font-bold mb-4">Select Amenities</label>
                        <div class="flex flex-wrap gap-3">
                            <?php
                            $amenities = [
                                'Breakfast Provided', 'Lunch Available', 'Dinner Available', 'Swimming Pool', 
                                'Private Pool', 'Free WiFi', 'Parking', 'Air Conditioning', 'Laundry', 
                                'Airport Pickup', 'Safari', 'Hiking', 'Photoshoot / Shooting', 
                                'BBQ Facilities', '24-hour Reception', 'Security'
                            ];
                            foreach ($amenities as $item): ?>
                                <label class="cursor-pointer">
                                    <input type="checkbox" name="amenities[]" value="<?= $item ?>" class="hidden peer">
                                    <span class="px-4 py-2 rounded-full border border-gray-200 text-sm font-medium peer-checked:bg-purple-600 peer-checked:text-white peer-checked:border-purple-600 hover:bg-gray-50 transition-all block">
                                        <?= $item ?>
                                    </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-6 flex justify-between items-center">
                        <h4 class="font-bold">Extra Services</h4>
                        <button type="button" onclick="addServiceRow()" class="text-sm font-bold text-purple-600">+ Add Service</button>
                    </div>
                    <div id="services-container" class="space-y-4">
                        <!-- Services rows -->
                    </div>
                </div>

                <!-- Step 7: Payment & Policies -->
                <div class="step-content" data-step="7">
                    <h3 class="text-2xl font-bold mb-8">Payment & Policies</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div>
                            <label class="block text-sm font-semibold mb-2">Currency</label>
                            <select name="currency" class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none">
                                <option value="USD">USD</option>
                                <option value="LKR">LKR</option>
                                <option value="EUR">EUR</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2">VAT Percentage (%)</label>
                            <input type="number" name="vat_percentage" value="0" class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2">Service Charge (%)</label>
                            <input type="number" name="service_charge_percentage" value="0" class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none">
                        </div>
                    </div>

                    <div class="mb-8">
                        <label class="block text-sm font-bold mb-4">Accepted Payment Methods</label>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <label class="flex items-center gap-3 p-4 border rounded-xl cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" name="payment_methods[]" value="cash" class="w-5 h-5 text-purple-600 rounded">
                                <span class="font-medium">Cash</span>
                            </label>
                            <label class="flex items-center gap-3 p-4 border rounded-xl cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" name="payment_methods[]" value="card" class="w-5 h-5 text-purple-600 rounded">
                                <span class="font-medium">Card</span>
                            </label>
                            <label class="flex items-center gap-3 p-4 border rounded-xl cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" name="payment_methods[]" value="bank_transfer" onchange="toggleBankDetails(this)" class="w-5 h-5 text-purple-600 rounded">
                                <span class="font-medium">Bank Transfer</span>
                            </label>
                        </div>
                    </div>

                    <div id="bank-details-container" class="hidden grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 p-6 bg-gray-50 rounded-2xl">
                        <div>
                            <label class="block text-sm font-semibold mb-2 text-gray-600">Bank Name</label>
                            <input type="text" name="bank_name" class="w-full bg-white px-4 py-3 rounded-xl border border-gray-200 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2 text-gray-600">Account Number</label>
                            <input type="text" name="account_number" class="w-full bg-white px-4 py-3 rounded-xl border border-gray-200 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2 text-gray-600">Account Holder Name</label>
                            <input type="text" name="account_holder_name" class="w-full bg-white px-4 py-3 rounded-xl border border-gray-200 outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold mb-2">Check-in Time</label>
                            <input type="time" name="check_in_time" value="14:00" class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-2">Check-out Time</label>
                            <input type="time" name="check_out_time" value="12:00" class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none">
                        </div>
                        <div class="md:col-span-2 flex items-center gap-4 py-4">
                            <input type="checkbox" name="id_required" value="1" checked class="w-5 h-5 text-purple-600 rounded">
                            <span class="font-semibold">Identification Required at Check-in</span>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold mb-2">Cancellation Policy</label>
                            <textarea name="cancellation_policy" rows="3" placeholder="e.g., Free cancellation up to 24 hours before arrival." class="w-full px-4 py-3 rounded-xl border border-gray-200 outline-none"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Step 8: Review & Submit -->
                <div class="step-content" data-step="8">
                    <h3 class="text-2xl font-bold mb-8">Review & Submit</h3>
                    <div id="review-container" class="space-y-6">
                        <!-- Summary will be populated here -->
                        <div class="p-8 bg-purple-50 rounded-3xl text-center">
                            <div class="w-20 h-20 bg-purple-600 rounded-full flex items-center justify-center mx-auto mb-6">
                                <i class="fas fa-check text-3xl text-white"></i>
                            </div>
                            <h4 class="text-xl font-bold mb-2">Ready to Go!</h4>
                            <p class="text-gray-600 mb-8">Please review your information before final submission.</p>
                            <button type="submit" class="w-full bg-purple-600 text-white py-4 rounded-xl font-bold hover:bg-purple-700 transition-colors">Submit Registration</button>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="flex justify-between mt-12 pt-8 border-t border-gray-100">
                    <button type="button" id="prev-btn" onclick="changeStep(-1)" class="px-8 py-3 rounded-xl font-bold text-gray-500 hover:bg-gray-100 transition-all invisible">Back</button>
                    <button type="button" id="next-btn" onclick="changeStep(1)" class="px-8 py-3 bg-purple-600 text-white rounded-xl font-bold hover:bg-purple-700 transition-all">Continue</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 8;
        const steps = ['Business Type', 'Property Info', 'Manager Setup', 'Staff Setup', 'Business Rules', 'Amenities', 'Payment', 'Review'];
        let registrationType = 'user';

        function showRegistration(type) {
            registrationType = type;
            document.getElementById('selection-screen').classList.add('hidden');
            if (type === 'user') {
                document.getElementById('user-registration').classList.remove('hidden');
                document.getElementById('user-role-input').value = 'user';
            } else {
                // For owner, we first show the user registration part but with role 'owner'
                document.getElementById('user-registration').classList.remove('hidden');
                document.getElementById('user-role-input').value = 'owner';
                document.querySelector('#user-registration h2').innerText = 'Owner Registration';
                document.querySelector('#user-registration p').innerText = 'Register your owner account to start listing properties.';
            }
        }

        // Handle User Form Submission
        document.getElementById('user-form').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            const response = await fetch('register.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                if (result.redirect === 'property_reg') {
                    document.getElementById('user-registration').classList.add('hidden');
                    document.getElementById('property-registration').classList.remove('hidden');
                    initPropertyWizard();
                } else {
                    window.location.href = result.redirect;
                }
            } else {
                alert(result.message);
            }
        };

        // Property Wizard Logic
        function initPropertyWizard() {
            renderStepNavigation();
            updateStepDisplay();
        }

        function renderStepNavigation() {
            const nav = document.getElementById('step-navigation');
            nav.innerHTML = '';
            steps.forEach((step, index) => {
                const stepNum = index + 1;
                const activeClass = stepNum === currentStep ? 'bg-purple-600 text-white' : (stepNum < currentStep ? 'bg-purple-100 text-purple-600' : 'bg-gray-100 text-gray-400');
                const textClass = stepNum === currentStep ? 'text-purple-900 font-bold' : 'text-gray-400';
                
                nav.innerHTML += `
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all ${activeClass}">${stepNum < currentStep ? '<i class="fas fa-check"></i>' : stepNum}</div>
                        <span class="text-sm font-medium whitespace-nowrap transition-all ${textClass}">${step}</span>
                        ${index < steps.length - 1 ? '<div class="h-px w-6 bg-gray-200 mx-1"></div>' : ''}
                    </div>
                `;
            });
        }

        function updateStepDisplay() {
            document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
            document.querySelector(`.step-content[data-step="${currentStep}"]`).classList.add('active');
            
            document.getElementById('prev-btn').style.visibility = currentStep === 1 ? 'hidden' : 'visible';
            document.getElementById('next-btn').innerText = currentStep === totalSteps ? 'Finish Review' : 'Continue';
            if (currentStep === totalSteps) document.getElementById('next-btn').style.display = 'none';
            else document.getElementById('next-btn').style.display = 'block';

            const progress = (currentStep / totalSteps) * 100;
            document.getElementById('main-progress').style.width = `${progress}%`;
            
            renderStepNavigation();
            if (currentStep === 8) populateReview();
        }

        function changeStep(delta) {
            if (currentStep + delta > 0 && currentStep + delta <= totalSteps) {
                currentStep += delta;
                updateStepDisplay();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        function toggleHotelCategory(show) {
            document.getElementById('hotel-category-container').style.display = show ? 'block' : 'none';
        }

        function toggleManagerFields(checkbox) {
            document.getElementById('manager-fields').style.display = checkbox.checked ? 'none' : 'grid';
        }

        function toggleBankDetails(checkbox) {
            document.getElementById('bank-details-container').style.display = checkbox.checked ? 'grid' : 'none';
        }

        let staffCount = 0;
        function addStaffRow() {
            const container = document.getElementById('staff-container');
            const rowId = `staff-${Date.now()}`;
            const html = `
                <div id="${rowId}" class="p-6 border rounded-2xl grid grid-cols-1 md:grid-cols-4 gap-4 bg-gray-50 relative group">
                    <button type="button" onclick="this.parentElement.remove()" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full text-xs items-center justify-center hidden group-hover:flex"><i class="fas fa-times"></i></button>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Name</label>
                        <input type="text" name="staff_name[]" class="w-full px-3 py-2 rounded-lg border outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Role</label>
                        <input type="text" name="staff_role[]" class="w-full px-3 py-2 rounded-lg border outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Contact</label>
                        <input type="text" name="staff_phone[]" class="w-full px-3 py-2 rounded-lg border outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Email</label>
                        <input type="email" name="staff_email[]" class="w-full px-3 py-2 rounded-lg border outline-none">
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        function addServiceRow() {
            const container = document.getElementById('services-container');
            const html = `
                <div class="p-6 border rounded-2xl grid grid-cols-1 md:grid-cols-4 gap-4 bg-white">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-gray-500 mb-1">Service Name</label>
                        <input type="text" name="service_name[]" class="w-full px-3 py-2 rounded-lg border outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Price</label>
                        <input type="number" name="service_price[]" class="w-full px-3 py-2 rounded-lg border outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 mb-1">Type</label>
                        <select name="service_type[]" class="w-full px-3 py-2 rounded-lg border outline-none">
                            <option>Per Person</option>
                            <option>Per Room</option>
                            <option>Per Stay</option>
                        </select>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        function populateReview() {
            const container = document.getElementById('review-container');
            const formData = new FormData(document.getElementById('property-form'));
            
            let html = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8 text-left">
                    <div class="p-6 bg-gray-50 rounded-2xl">
                        <h4 class="font-bold text-gray-400 uppercase text-xs mb-4 tracking-widest">Property Overview</h4>
                        <div class="space-y-2">
                            <p><span class="text-gray-500">Name:</span> <span class="font-semibold">${formData.get('property_name')}</span></p>
                            <p><span class="text-gray-500">Type:</span> <span class="font-semibold capitalize">${formData.get('business_type')}</span></p>
                            <p><span class="text-gray-500">Location:</span> <span class="font-semibold">${formData.get('city')}, ${formData.get('country')}</span></p>
                        </div>
                    </div>
                    <div class="p-6 bg-gray-50 rounded-2xl">
                        <h4 class="font-bold text-gray-400 uppercase text-xs mb-4 tracking-widest">Business Rules</h4>
                        <div class="space-y-2">
                            <p><span class="text-gray-500">Payout:</span> <span class="font-semibold">${formData.get('payout_percentage')}%</span></p>
                            <p><span class="text-gray-500">Commission:</span> <span class="font-semibold">20%</span></p>
                            <p><span class="text-gray-500">Currency:</span> <span class="font-semibold">${formData.get('currency')}</span></p>
                        </div>
                    </div>
                </div>
                <div class="p-8 bg-purple-50 rounded-3xl text-center">
                    <div class="w-20 h-20 bg-purple-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-check text-3xl text-white"></i>
                    </div>
                    <h4 class="text-xl font-bold mb-2">Ready to Go!</h4>
                    <p class="text-gray-600 mb-8">Please review your information before final submission.</p>
                    <button type="submit" class="w-full bg-purple-600 text-white py-4 rounded-xl font-bold hover:bg-purple-700 transition-colors shadow-xl shadow-purple-100">Submit Registration</button>
                </div>
            `;
            container.innerHTML = html;
        }

        // Handle Property Form Submission
        document.getElementById('property-form').onsubmit = async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
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
        };
    </script>
</body>
</html>
