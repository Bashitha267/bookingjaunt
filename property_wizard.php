<?php
require_once 'config.php';
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: register.php");
    exit();
}

// Fetch Master Amenities
$stmt = $pdo->query("SELECT * FROM amenities_master ORDER BY category, amenity_name");
$all_amenities = $stmt->fetchAll(PDO::FETCH_ASSOC);

$grouped_amenities = [];
foreach ($all_amenities as $amenity) {
    $grouped_amenities[$amenity['category']][] = $amenity;
}

$type = $_GET['type'] ?? 'hotel';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Your Property - Bookingjaunt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #f8fafc; }
        .wizard-step { display: none; }
        .wizard-step.active { display: block; animation: fadeIn 0.5s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .step-circle { 
            width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; 
            background: white; border: 2px solid #e2e8f0; color: #64748b; font-weight: 700; transition: all 0.3s;
            position: relative; z-index: 10;
        }
        .step-item.active .step-circle { background: #006ce4; border-color: #006ce4; color: white; box-shadow: 0 0 0 4px rgba(0, 108, 228, 0.1); }
        .step-item.completed .step-circle { background: #10b981; border-color: #10b981; color: white; }
        
        .step-line { 
            position: absolute; top: 20px; left: 50%; width: 100%; height: 2px; background: #e2e8f0; z-index: 5;
        }
        .step-item:last-child .step-line { display: none; }
        .step-item.completed .step-line { background: #10b981; }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <!-- Header -->
    <nav class="bg-white border-b p-3 flex justify-between items-center sticky top-0 z-50 shadow-sm">
        <div class="flex items-center gap-4">
            <a href="index.php" class="text-xl font-bold text-[#006ce4]">Bookingjaunt</a>
            <span class="text-gray-300">|</span>
            <div id="header-progress" class="flex flex-col">
                <span class="text-[10px] font-bold text-[#006ce4] uppercase tracking-wider">Progress</span>
                <span class="text-xs font-bold text-gray-600" id="step-counter">Step 1 of 7</span>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <div class="text-right hidden sm:block">
                <p class="text-[10px] font-bold text-gray-400 uppercase" id="steps-remaining">6 steps remaining</p>
                <p class="text-xs font-bold text-gray-700"><?= $_SESSION['user_name'] ?></p>
            </div>
            <div class="w-8 h-8 bg-[#003580] text-white rounded-full flex items-center justify-center font-bold text-xs">
                <?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?>
            </div>
        </div>
    </nav>

    <!-- Stepper Container (Sticky) -->
    <div class="sticky top-[58px] z-40 bg-[#f8fafc]/90 backdrop-blur-md border-b py-6 mb-12">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex justify-between relative">
                <?php 
                $steps = ["Type", "Info", "Staff", "Amenities", "Rooms", "Rules", "Photos", "Finish"];
                foreach($steps as $i => $name): 
                    $num = $i + 1;
                ?>
                    <div class="step-item flex-1 flex flex-col items-center group relative" data-step="<?= $num ?>">
                        <div class="step-line"></div>
                        <div class="step-circle mb-2 text-sm"><?= $num ?></div>
                        <span class="text-[10px] font-bold text-gray-400 group-[.active]:text-[#006ce4] group-[.completed]:text-[#10b981] transition-all uppercase tracking-wider"><?= $name ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

        <!-- Wizard Form -->
        <main class="max-w-7xl mx-auto pb-24 px-6">
            <form id="property-form" class="bg-white rounded-3xl shadow-xl shadow-blue-900/5 p-10 border border-blue-50">
                
                <!-- Step 1: Business Type -->
                <div class="wizard-step active" data-step="1">
                    <div class="mb-8">
                        <span class="text-[#006ce4] font-bold text-xs tracking-widest uppercase mb-1 block">Step 01</span>
                        <h2 class="text-2xl font-bold text-gray-900">What are you listing?</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <label class="group relative border-2 rounded-2xl p-8 cursor-pointer hover:bg-blue-50/50 transition-all text-center has-[:checked]:border-[#006ce4] has-[:checked]:bg-blue-50 ring-offset-2 has-[:checked]:ring-2 ring-[#006ce4]">
                            <input type="radio" name="business_type" value="hotel" required class="hidden" <?= $type == 'hotel' ? 'checked' : '' ?>>
                            <div class="w-16 h-16 bg-blue-50 text-[#006ce4] rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:scale-105 transition-transform">
                                <i class="fas fa-hotel text-2xl"></i>
                            </div>
                            <div class="font-bold text-lg mb-1">Hotel</div>
                            <p class="text-xs text-gray-500 leading-relaxed">Full service accommodation & resorts.</p>
                        </label>
                        <label class="group relative border-2 rounded-2xl p-8 cursor-pointer hover:bg-blue-50/50 transition-all text-center has-[:checked]:border-[#006ce4] has-[:checked]:bg-blue-50 ring-offset-2 has-[:checked]:ring-2 ring-[#006ce4]">
                            <input type="radio" name="business_type" value="reception_hall" class="hidden" <?= $type == 'reception_hall' ? 'checked' : '' ?>>
                            <div class="w-16 h-16 bg-blue-50 text-[#006ce4] rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:scale-105 transition-transform">
                                <i class="fas fa-glass-cheers text-2xl"></i>
                            </div>
                            <div class="font-bold text-lg mb-1">Reception Hall</div>
                            <p class="text-xs text-gray-500 leading-relaxed">Host weddings, parties and grand events.</p>
                        </label>
                        <label class="group relative border-2 rounded-2xl p-8 cursor-pointer hover:bg-blue-50/50 transition-all text-center has-[:checked]:border-[#006ce4] has-[:checked]:bg-blue-50 ring-offset-2 has-[:checked]:ring-2 ring-[#006ce4]">
                            <input type="radio" name="business_type" value="hostel" class="hidden" <?= $type == 'hostel' ? 'checked' : '' ?>>
                            <div class="w-16 h-16 bg-blue-50 text-[#006ce4] rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:scale-105 transition-transform">
                                <i class="fas fa-bed text-2xl"></i>
                            </div>
                            <div class="font-bold text-lg mb-1">Hostel</div>
                            <p class="text-xs text-gray-500 leading-relaxed">Shared dormitory style accommodations.</p>
                        </label>
                        <label class="group relative border-2 rounded-2xl p-8 cursor-pointer hover:bg-blue-50/50 transition-all text-center has-[:checked]:border-[#006ce4] has-[:checked]:bg-blue-50 ring-offset-2 has-[:checked]:ring-2 ring-[#006ce4]">
                            <input type="radio" name="business_type" value="rest_hall" class="hidden" <?= $type == 'rest_hall' ? 'checked' : '' ?>>
                            <div class="w-16 h-16 bg-blue-50 text-[#006ce4] rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:scale-105 transition-transform">
                                <i class="fas fa-vihara text-2xl"></i>
                            </div>
                            <div class="font-bold text-lg mb-1">Pilgrim Hall</div>
                            <p class="text-xs text-gray-500 leading-relaxed">Vishrama Shalawa / Rest Halls for pilgrims.</p>
                        </label>
                    </div>
                </div>

                <!-- Step 2: Basic Info -->
                <div class="wizard-step" data-step="2">
                    <div class="mb-8">
                        <span class="text-[#006ce4] font-bold text-xs tracking-widest uppercase mb-1 block">Step 02</span>
                        <h2 class="text-2xl font-bold text-gray-900">General Information</h2>
                    </div>
                    
                    <div class="space-y-8">
                        <!-- Basic Property Details -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-bold text-gray-800 border-b pb-2">Basic Property Details</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-2 ml-1 uppercase tracking-wide">Property Name</label>
                                    <input type="text" name="property_name" required placeholder="e.g. Grand Plaza Hotel" class="w-full px-5 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-[#006ce4] transition-all">
                                </div>
                                <div id="hotel-category-container">
                                    <label class="block text-xs font-bold text-gray-600 mb-2 ml-1 uppercase tracking-wide">Hotel Category</label>
                                    <select name="hotel_category" class="w-full px-5 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-[#006ce4] transition-all">
                                        <option value="budget_friendly">Budget Friendly</option>
                                        <option value="luxury">Luxury</option>
                                        <option value="super_luxury">Super Luxury</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-2 ml-1 uppercase tracking-wide">Short Description</label>
                                <textarea name="description" required rows="2" placeholder="A brief overview of your property..." class="w-full px-5 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-[#006ce4] transition-all"></textarea>
                            </div>
                        </div>

                        <!-- Location Details -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-bold text-gray-800 border-b pb-2">Location Details</h3>
                            
                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-2 ml-1 uppercase tracking-wide">Street Address</label>
                                <input type="text" name="street_address" required placeholder="123 Main St" class="w-full px-5 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-[#006ce4] transition-all">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-2 ml-1 uppercase tracking-wide">Fixed Telephone Number <span class="text-xs font-normal text-gray-400 ml-1">(Optional)</span></label>
                                    <input type="text" name="fixed_telephone" placeholder="+94 ..." class="w-full px-5 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-[#006ce4] transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-2 ml-1 uppercase tracking-wide">Mobile Telephone Number</label>
                                    <input type="text" name="mobile_telephone" placeholder="+94 ..." class="w-full px-5 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-[#006ce4] transition-all">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-2 ml-1 uppercase tracking-wide">City</label>
                                    <input type="text" name="city" required placeholder="City Name" class="w-full px-5 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-[#006ce4] transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-2 ml-1 uppercase tracking-wide">District</label>
                                    <input type="text" name="district" required placeholder="District Name" class="w-full px-5 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-[#006ce4] transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-2 ml-1 uppercase tracking-wide">Province</label>
                                    <input type="text" name="province" required placeholder="Province Name" class="w-full px-5 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-[#006ce4] transition-all">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-2 ml-1 uppercase tracking-wide">Country</label>
                                    <input type="text" name="country" required value="Sri Lanka" class="w-full px-5 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-[#006ce4] transition-all">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-xs font-bold text-gray-600 mb-2 ml-1 uppercase tracking-wide">Google Map Location (Optional)</label>
                                    <div class="relative">
                                        <input type="text" name="google_map_location" placeholder="Paste link or coordinates" class="w-full px-5 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-[#006ce4] transition-all pl-12">
                                        <i class="fas fa-map-marker-alt absolute left-5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Logistics & Surroundings -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-bold text-gray-800 border-b pb-2">Surroundings & Logistics <span class="text-xs font-normal text-gray-400 ml-2">(Optional)</span></h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-2 ml-1 uppercase tracking-wide">Closest Police Station</label>
                                    <input type="text" name="closest_police_station" placeholder="Name of station" class="w-full px-5 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-[#006ce4] transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-2 ml-1 uppercase tracking-wide">Closest Hospital</label>
                                    <input type="text" name="closest_hospital" placeholder="Name of hospital" class="w-full px-5 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-[#006ce4] transition-all">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-2 ml-1 uppercase tracking-wide">Airport Distance (KM)</label>
                                    <input type="text" name="airport_distance" placeholder="Distance to Katunayake (CMB)" class="w-full px-5 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-[#006ce4] transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-2 ml-1 uppercase tracking-wide">Postal Code</label>
                                    <input type="text" name="postal_code" placeholder="e.g. 11500" class="w-full px-5 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-[#006ce4] transition-all">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-2 ml-1 uppercase tracking-wide">Property Logo <span class="text-xs font-normal text-gray-400 ml-1">(Optional)</span></label>
                                    <div class="upload-container relative group" id="logo-upload">
                                        <input type="file" accept="image/*" class="hidden file-input" data-type="logo">
                                        <input type="hidden" name="logo_image">
                                        <div class="w-full h-32 border-2 border-dashed border-gray-200 rounded-2xl flex flex-col items-center justify-center bg-gray-50 group-hover:bg-gray-100 transition-all cursor-pointer upload-trigger">
                                            <i class="fas fa-cloud-upload-alt text-gray-400 text-2xl mb-2"></i>
                                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Upload Logo</span>
                                        </div>
                                        <div class="preview-container hidden absolute inset-0 bg-white rounded-2xl border flex items-center justify-center p-2">
                                            <img src="" class="max-w-full max-h-full rounded-lg object-contain">
                                            <button type="button" class="absolute -top-2 -right-2 w-7 h-7 bg-red-500 text-white rounded-full flex items-center justify-center shadow-lg hover:bg-red-600 transition-all remove-image">
                                                <i class="fas fa-times text-xs"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-2 ml-1 uppercase tracking-wide">Cover Image <span class="text-xs font-normal text-gray-400 ml-1">(Optional)</span></label>
                                    <div class="upload-container relative group" id="cover-upload">
                                        <input type="file" accept="image/*" class="hidden file-input" data-type="cover">
                                        <input type="hidden" name="cover_image">
                                        <div class="w-full h-32 border-2 border-dashed border-gray-200 rounded-2xl flex flex-col items-center justify-center bg-gray-50 group-hover:bg-gray-100 transition-all cursor-pointer upload-trigger">
                                            <i class="fas fa-image text-gray-400 text-2xl mb-2"></i>
                                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Upload Cover</span>
                                        </div>
                                        <div class="preview-container hidden absolute inset-0 bg-white rounded-2xl border flex items-center justify-center p-2">
                                            <img src="" class="max-w-full max-h-full rounded-lg object-cover w-full h-full">
                                            <button type="button" class="absolute -top-2 -right-2 w-7 h-7 bg-red-500 text-white rounded-full flex items-center justify-center shadow-lg hover:bg-red-600 transition-all remove-image">
                                                <i class="fas fa-times text-xs"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Logistics & Surroundings -->
                        <div class="space-y-4">
                            <h3 class="text-lg font-bold text-gray-800 border-b pb-2">Surroundings & Logistics <span class="text-xs font-normal text-gray-400 ml-2">(Optional)</span></h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-2 ml-1 uppercase tracking-wide">Closest Police Station</label>
                                    <input type="text" name="closest_police_station" placeholder="Name of station" class="w-full px-5 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-[#006ce4] transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-2 ml-1 uppercase tracking-wide">Closest Hospital</label>
                                    <input type="text" name="closest_hospital" placeholder="Name of hospital" class="w-full px-5 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-[#006ce4] transition-all">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-2 ml-1 uppercase tracking-wide">Airport Distance (KM)</label>
                                    <input type="text" name="airport_distance" placeholder="Distance to Katunayake (CMB)" class="w-full px-5 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-[#006ce4] transition-all">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-600 mb-2 ml-1 uppercase tracking-wide">Postal Code</label>
                                    <input type="text" name="postal_code" placeholder="e.g. 11500" class="w-full px-5 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-[#006ce4] transition-all">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-600 mb-2 ml-1 uppercase tracking-wide">Closest Main Town</label>
                                <input type="text" name="closest_main_town" placeholder="Name of the nearest town" class="w-full px-5 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:bg-white focus:ring-2 focus:ring-[#006ce4] transition-all">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Staff Details -->
                <div class="wizard-step" data-step="3">
                    <div class="mb-8">
                        <span class="text-[#006ce4] font-bold text-xs tracking-widest uppercase mb-1 block">Step 03</span>
                        <h2 class="text-2xl font-bold text-gray-900">Staff Details</h2>
                    </div>

                    <!-- Manager Details -->
                    <div class="space-y-6 mb-10">
                        <h3 class="text-lg font-bold text-gray-800 border-b pb-2">Manager Details</h3>
                        <div class="bg-blue-50/50 p-5 rounded-xl mb-6 flex items-center justify-between border border-blue-100">
                            <span class="font-bold text-sm text-gray-700">Are you the manager of this property?</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="is_manager_checkbox" checked class="sr-only peer">
                                <div class="w-10 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#006ce4]"></div>
                            </label>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-start">
                            <div class="md:col-span-1">
                                <label class="block text-xs font-bold text-gray-600 mb-2 uppercase tracking-wide">Manager Photo <span class="text-xs font-normal text-gray-400 ml-1">(Optional)</span></label>
                                <div class="upload-container relative group" id="manager-photo-upload">
                                    <input type="file" accept="image/*" class="hidden file-input" data-type="manager">
                                    <input type="hidden" name="manager_photo">
                                    <div class="w-full h-32 border-2 border-dashed border-gray-200 rounded-2xl flex flex-col items-center justify-center bg-gray-50 group-hover:bg-gray-100 transition-all cursor-pointer upload-trigger">
                                        <i class="fas fa-user-circle text-gray-400 text-2xl mb-2"></i>
                                        <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Upload Photo</span>
                                    </div>
                                    <div class="preview-container hidden absolute inset-0 bg-white rounded-2xl border flex items-center justify-center p-2">
                                        <img src="" class="max-w-full max-h-full rounded-full object-cover w-24 h-24">
                                        <button type="button" class="absolute -top-2 -right-2 w-7 h-7 bg-red-500 text-white rounded-full flex items-center justify-center shadow-lg hover:bg-red-600 transition-all remove-image">
                                            <i class="fas fa-times text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="md:col-span-3">
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600 mb-2 uppercase tracking-wide">Manager Name</label>
                                        <input type="text" name="manager_name" placeholder="Full Name" class="w-full px-5 py-3 rounded-xl border bg-gray-50 text-sm outline-none transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600 mb-2 uppercase tracking-wide">Contact Number</label>
                                        <input type="text" name="manager_phone" placeholder="Phone Number" class="w-full px-5 py-3 rounded-xl border bg-gray-50 text-sm outline-none transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-gray-600 mb-2 uppercase tracking-wide">NIC Number</label>
                                        <input type="text" name="manager_nic" placeholder="National ID" class="w-full px-5 py-3 rounded-xl border bg-gray-50 text-sm outline-none transition-all">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Other Staff -->
                    <div class="space-y-6">
                        <h3 class="text-lg font-bold text-gray-800 border-b pb-2">Other Staff <span class="text-xs font-normal text-gray-400 ml-2">(Optional)</span></h3>
                        
                        <div class="mb-6">
                            <label class="block text-xs font-bold text-gray-600 mb-2 uppercase tracking-wide">How many staff members do you want to add?</label>
                            <input type="number" id="staff_count" min="0" max="20" value="0" class="w-32 px-5 py-3 rounded-xl border bg-gray-50 text-sm outline-none focus:ring-2 focus:ring-[#006ce4] transition-all">
                        </div>

                        <div id="staff_forms_container" class="space-y-8">
                            <!-- Dynamic staff forms will appear here -->
                        </div>
                    </div>
                </div>

                <!-- Step 4: Amenities -->
                <div class="wizard-step" data-step="4">
                    <div class="mb-8">
                        <span class="text-[#006ce4] font-bold text-xs tracking-widest uppercase mb-1 block">Step 04</span>
                        <h2 class="text-2xl font-bold text-gray-900">Amenities & Facilities</h2>
                        <p class="text-sm text-gray-500 mt-1">Select the amenities available at your property. These are managed by our admin.</p>
                    </div>

                    <div class="space-y-12">
                        <?php foreach($grouped_amenities as $category => $items): ?>
                            <div>
                                <h3 class="text-xs font-bold text-[#006ce4] uppercase tracking-wider mb-5 flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#006ce4]"></span>
                                    <?= $category ?>
                                </h3>
                                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                                    <?php foreach($items as $item): ?>
                                        <label class="amenity-card group relative cursor-pointer h-full">
                                            <input type="checkbox" name="amenities[]" value="<?= $item['id'] ?>" class="hidden peer">
                                            <div class="flex flex-col items-center justify-center p-5 rounded-2xl border-2 bg-gray-50/30 group-hover:bg-white peer-checked:border-[#006ce4] peer-checked:bg-[#006ce4]/5 transition-all shadow-sm group-hover:shadow-md h-full min-h-[110px] relative overflow-hidden">
                                                <!-- Selection Glow -->
                                                <div class="absolute inset-0 bg-[#006ce4]/10 opacity-0 peer-checked:opacity-100 transition-opacity"></div>
                                                
                                                <i class="fas <?= $item['icon'] ?? 'fa-check' ?> text-gray-400 group-hover:text-[#006ce4] peer-checked:text-[#006ce4] text-2xl mb-3 transition-all group-hover:scale-110 peer-checked:scale-110"></i>
                                                <span class="text-[10px] font-black text-gray-600 group-hover:text-gray-900 peer-checked:text-[#003580] text-center uppercase tracking-tighter leading-tight relative z-10"><?= $item['amenity_name'] ?></span>
                                                
                                                <!-- Checkmark Indicator -->
                                                <div class="absolute top-2 right-2 opacity-0 peer-checked:opacity-100 transition-opacity">
                                                    <i class="fas fa-check-circle text-[#006ce4] text-[10px]"></i>
                                                </div>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Special Amenities Row by Row -->
                        <div class="bg-blue-50/30 p-8 rounded-3xl border border-blue-100">
                            <h3 class="text-lg font-bold text-gray-800 mb-2">Special Amenities</h3>
                            <p class="text-xs text-gray-500 mb-6 uppercase tracking-wider">Does your hotel have something unique? Add them here row by row.</p>
                            
                            <div id="special-amenities-container" class="space-y-3">
                                <!-- Row 1 (Default) -->
                                <div class="special-amenity-row flex gap-3">
                                    <div class="relative flex-1">
                                        <i class="fas fa-magic absolute left-4 top-1/2 -translate-y-1/2 text-blue-300"></i>
                                        <input type="text" name="special_amenities[]" placeholder="e.g. Traditional Sri Lankan Welcome Drink" class="w-full pl-12 pr-5 py-3 rounded-xl border bg-white text-sm outline-none focus:ring-2 focus:ring-[#006ce4] transition-all">
                                    </div>
                                    <button type="button" class="remove-special-row w-12 h-12 rounded-xl border-2 border-dashed border-red-200 text-red-300 hover:border-red-500 hover:text-red-500 transition-all flex items-center justify-center">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="button" id="add-special-amenity" class="mt-4 flex items-center gap-2 text-xs font-bold text-[#006ce4] hover:text-[#003580] transition-all px-4 py-2 rounded-lg hover:bg-blue-50">
                                <i class="fas fa-plus-circle"></i> ADD ANOTHER SPECIAL AMENITY
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 5: Room Details -->
                <div class="wizard-step" data-step="5">
                    <div class="mb-8">
                        <span class="text-[#006ce4] font-bold text-xs tracking-widest uppercase mb-1 block">Step 05</span>
                        <h2 class="text-2xl font-bold text-gray-900">Room & Hall Details</h2>
                        <p class="text-sm text-gray-500 mt-1">Select a common type to add it quickly, or build your own.</p>
                    </div>

                    <!-- Quick Suggestions -->
                    <div class="mb-10">
                        <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] mb-4">Quick Suggestions</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3" id="room-suggestions">
                            <?php 
                            $propertyType = $type; 
                            $suggestions = [
                                'hotel' => [
                                    ['name' => 'Couple Room', 'icon' => 'fa-heart', 'adults' => 2, 'children' => 0],
                                    ['name' => 'Single Room', 'icon' => 'fa-user', 'adults' => 1, 'children' => 0],
                                    ['name' => 'Family Room', 'icon' => 'fa-users', 'adults' => 2, 'children' => 2],
                                    ['name' => 'Luxury Suite', 'icon' => 'fa-crown', 'adults' => 2, 'children' => 1],
                                    ['name' => 'Private Villa', 'icon' => 'fa-home', 'adults' => 4, 'children' => 2],
                                    ['name' => 'Entire House', 'icon' => 'fa-building', 'adults' => 6, 'children' => 4],
                                ],
                                'reception_hall' => [
                                    ['name' => 'Small Hall', 'icon' => 'fa-users', 'adults' => 100, 'children' => 0, 'is_hall' => 1],
                                    ['name' => 'Medium Hall', 'icon' => 'fa-glass-cheers', 'adults' => 300, 'children' => 0, 'is_hall' => 1],
                                    ['name' => 'Large Hall', 'icon' => 'fa-university', 'adults' => 500, 'children' => 0, 'is_hall' => 1],
                                    ['name' => 'Bridal Suite', 'icon' => 'fa-heart', 'adults' => 2, 'children' => 0, 'is_hall' => 0],
                                ],
                                'hostel' => [
                                    ['name' => 'Single Room', 'icon' => 'fa-user', 'adults' => 1, 'children' => 0],
                                    ['name' => 'Couple Room', 'icon' => 'fa-heart', 'adults' => 2, 'children' => 0],
                                    ['name' => 'Family Room', 'icon' => 'fa-users', 'adults' => 2, 'children' => 2],
                                ],
                                'rest_hall' => [
                                    ['name' => 'Single Room', 'icon' => 'fa-user', 'adults' => 1, 'children' => 0],
                                    ['name' => 'Couple Room', 'icon' => 'fa-heart', 'adults' => 2, 'children' => 0],
                                    ['name' => 'Family Room', 'icon' => 'fa-users', 'adults' => 2, 'children' => 2],
                                    ['name' => 'Large Hall', 'icon' => 'fa-vihara', 'adults' => 100, 'children' => 50, 'is_hall' => 1],
                                ]
                            ];
                            
                            $activeSuggestions = $suggestions[$propertyType] ?? $suggestions['hotel'];
                            
                            foreach($activeSuggestions as $s): ?>
                                <button type="button" 
                                    onclick="addRoomCard({room_name: '<?= $s['name'] ?>', max_adults: <?= $s['adults'] ?>, max_children: <?= $s['children'] ?>, is_hall: <?= $s['is_hall'] ?? 0 ?>})"
                                    class="flex flex-col items-center justify-center p-4 rounded-xl border-2 border-gray-100 bg-white hover:border-[#006ce4] hover:bg-blue-50/50 transition-all group">
                                    <i class="fas <?= $s['icon'] ?> text-gray-300 group-hover:text-[#006ce4] mb-2"></i>
                                    <span class="text-[9px] font-bold text-gray-500 group-hover:text-[#003580] uppercase tracking-tighter text-center leading-tight"><?= $s['name'] ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div id="room-inventory-container" class="space-y-6">
                        <!-- Room Cards will be injected here -->
                    </div>

                    <button type="button" id="add-room-btn" class="mt-8 w-full py-4 border-2 border-dashed border-blue-200 rounded-2xl text-[#006ce4] font-bold hover:bg-blue-50 hover:border-[#006ce4] transition-all flex items-center justify-center gap-2">
                        <i class="fas fa-plus-circle"></i> CAN'T FIND IT? ADD CUSTOM TYPE
                    </button>
                </div>

                <!-- Step 6: Policies & Rules -->
                <div class="wizard-step" data-step="6">
                    <div class="mb-8">
                        <span class="text-[#006ce4] font-bold text-xs tracking-widest uppercase mb-1 block">Step 06</span>
                        <h2 class="text-2xl font-bold text-gray-900">Policies & Rules</h2>
                        <p class="text-sm text-gray-500 mt-1">Set the guidelines for guests staying at your property.</p>
                    </div>

                    <div class="space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-gray-50/50 p-6 rounded-2xl border">
                                <label class="block text-xs font-bold text-[#006ce4] mb-3 uppercase tracking-wider">Check-in Time</label>
                                <div class="relative">
                                    <input type="time" name="check_in_time" value="14:00" class="w-full px-5 py-3 rounded-xl border bg-white text-sm outline-none focus:ring-2 focus:ring-[#006ce4] transition-all">
                                    <i class="fas fa-clock absolute right-5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                            <div class="bg-gray-50/50 p-6 rounded-2xl border">
                                <label class="block text-xs font-bold text-[#006ce4] mb-3 uppercase tracking-wider">Check-out Time</label>
                                <div class="relative">
                                    <input type="time" name="check_out_time" value="12:00" class="w-full px-5 py-3 rounded-xl border bg-white text-sm outline-none focus:ring-2 focus:ring-[#006ce4] transition-all">
                                    <i class="fas fa-clock absolute right-5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50/50 p-6 rounded-2xl border">
                            <label class="block text-xs font-bold text-[#006ce4] mb-3 uppercase tracking-wider">Cancellation Policy</label>
                            <textarea name="cancellation_policy" rows="3" placeholder="e.g. Free cancellation up to 24 hours before check-in. Non-refundable after that." class="w-full px-5 py-3 rounded-xl border bg-white text-sm outline-none focus:ring-2 focus:ring-[#006ce4] transition-all"></textarea>
                        </div>

                        <div class="bg-gray-50/50 p-6 rounded-2xl border">
                            <label class="block text-xs font-bold text-[#006ce4] mb-3 uppercase tracking-wider">Property Rules</label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <label class="flex items-center gap-3 p-3 bg-white rounded-xl border cursor-pointer hover:bg-gray-100 transition-all">
                                    <input type="checkbox" name="smoking_allowed" class="w-4 h-4 text-[#006ce4] border-gray-300 rounded">
                                    <span class="text-xs font-bold text-gray-600">Smoking Allowed</span>
                                </label>
                                <label class="flex items-center gap-3 p-3 bg-white rounded-xl border cursor-pointer hover:bg-gray-100 transition-all">
                                    <input type="checkbox" name="pets_allowed" class="w-4 h-4 text-[#006ce4] border-gray-300 rounded">
                                    <span class="text-xs font-bold text-gray-600">Pets Allowed</span>
                                </label>
                                <label class="flex items-center gap-3 p-3 bg-white rounded-xl border cursor-pointer hover:bg-gray-100 transition-all">
                                    <input type="checkbox" name="events_allowed" class="w-4 h-4 text-[#006ce4] border-gray-300 rounded">
                                    <span class="text-xs font-bold text-gray-600">Events Allowed</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 7: Property Photos -->
                <div class="wizard-step" data-step="7">
                    <div class="mb-10 text-center py-20">
                        <i class="fas fa-images text-6xl text-blue-200 mb-6"></i>
                        <h2 class="text-3xl font-extrabold text-gray-900">Property Photos</h2>
                        <p class="text-gray-500 mt-4">Add beautiful high-quality photos to attract more travelers.</p>
                    </div>
                </div>

                <!-- Step 8: Final Review -->
                <div class="wizard-step" data-step="8">
                    <div class="mb-10 text-center">
                        <div class="w-24 h-24 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-8">
                            <i class="fas fa-check text-4xl"></i>
                        </div>
                        <h2 class="text-3xl font-extrabold text-gray-900">Ready to go live!</h2>
                        <p class="text-gray-500 mt-4 max-w-md mx-auto">Please review your information. Once submitted, our team will verify your property within 24 hours.</p>
                    </div>
                    <button type="submit" class="w-full bg-[#10b981] text-white py-5 rounded-[2rem] font-bold text-xl shadow-xl shadow-green-900/10 hover:bg-[#059669] transition-all transform hover:-translate-y-1">Submit Property for Review</button>
                </div>

                <!-- Footer Navigation -->
                <div class="flex justify-between mt-12 pt-8 border-t border-gray-100">
                    <button type="button" id="prev-btn" onclick="changeStep(-1)" class="px-6 py-2.5 font-bold text-sm text-gray-400 hover:text-gray-900 transition-all flex items-center gap-2 invisible">
                        <i class="fas fa-arrow-left"></i> Previous
                    </button>
                    <button type="button" id="next-btn" onclick="changeStep(1)" class="px-10 py-3.5 bg-[#006ce4] text-white rounded-full font-bold text-base shadow-lg shadow-blue-900/10 hover:bg-[#0056b3] transition-all flex items-center gap-3">
                        Continue <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </form>
        </main>
    </div>

    <script>
        let currentStep = 1;
        const totalSteps = 8;

        function updateDisplay() {
            // Update Wizard Steps
            document.querySelectorAll('.wizard-step').forEach(el => el.classList.remove('active'));
            document.querySelector(`.wizard-step[data-step="${currentStep}"]`).classList.add('active');
            
            // Update Stepper UI
            document.querySelectorAll('.step-item').forEach(el => {
                const step = parseInt(el.dataset.step);
                el.classList.remove('active', 'completed');
                if (step === currentStep) el.classList.add('active');
                else if (step < currentStep) el.classList.add('completed');
            });

            // Update Navigation Buttons
            document.getElementById('prev-btn').style.visibility = currentStep === 1 ? 'hidden' : 'visible';
            document.getElementById('next-btn').style.display = currentStep === totalSteps ? 'none' : 'block';

            // Update Header Progress
            document.getElementById('step-counter').innerText = `Step ${currentStep} of ${totalSteps}`;
            const remaining = totalSteps - currentStep;
            document.getElementById('steps-remaining').innerText = remaining === 0 ? 'Last step!' : `${remaining} ${remaining === 1 ? 'step' : 'steps'} remaining`;

            // Save state
            localStorage.setItem('property_wizard_step', currentStep);
        }

        function changeStep(delta) {
            if (currentStep + delta > 0 && currentStep + delta <= totalSteps) {
                currentStep += delta;
                updateDisplay();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        }

        // Persistence Logic
        function saveFormData() {
            const formData = new FormData(document.getElementById('property-form'));
            const data = {};
            formData.forEach((value, key) => { data[key] = value; });
            localStorage.setItem('property_wizard_data', JSON.stringify(data));
        }

        function loadFormData() {
            const savedData = localStorage.getItem('property_wizard_data');
            if (savedData) {
                const data = JSON.parse(savedData);
                for (const key in data) {
                    const el = document.getElementsByName(key)[0];
                    if (el) {
                        if (el.type === 'radio') {
                            const radio = document.querySelector(`input[name="${key}"][value="${data[key]}"]`);
                            if (radio) radio.checked = true;
                        } else if (el.type === 'checkbox') {
                            el.checked = data[key] === 'on';
                        } else {
                            el.value = data[key];
                        }
                    }
                }

                // Reconstruct Room Cards
                const roomsData = {};
                Object.keys(data).forEach(key => {
                    if (key.startsWith('rooms[')) {
                        const match = key.match(/rooms\[(\d+)\]\[(\w+)\]/);
                        if (match) {
                            const index = match[1];
                            const field = match[2];
                            if (!roomsData[index]) roomsData[index] = {};
                            roomsData[index][field] = data[key];
                        }
                    }
                });

                const indices = Object.keys(roomsData).sort((a,b) => a-b);
                if (indices.length > 0) {
                    roomContainer.innerHTML = ''; // Clear default
                    indices.forEach(idx => {
                        addRoomCard({
                            room_name: roomsData[idx].name,
                            total_rooms: roomsData[idx].count,
                            max_adults: roomsData[idx].adults,
                            max_children: roomsData[idx].children,
                            room_image: roomsData[idx].image,
                            is_hall: roomsData[idx].is_hall
                        });
                    });
                }

                // Load staff count and triggers
                if (data.staff_count) {
                    document.getElementById('staff_count').value = data.staff_count;
                    generateStaffForms(data.staff_count);
                    // Fill staff details (handled by the general loop above, but ensure forms exist first)
                    for (const key in data) {
                        if (key.startsWith('staff_')) {
                            const staffEl = document.getElementsByName(key)[0];
                            if (staffEl) staffEl.value = data[key];
                        }
                    }
                }

                // Load images
                if (data.logo_image) showPreview('logo-upload', data.logo_image);
                if (data.cover_image) showPreview('cover-upload', data.cover_image);
                if (data.manager_photo) showPreview('manager-photo-upload', data.manager_photo);
            }
            
            const savedStep = localStorage.getItem('property_wizard_step');
            if (savedStep) {
                currentStep = parseInt(savedStep);
            }
        }

        document.getElementById('property-form').addEventListener('input', saveFormData);
        document.getElementById('property-form').addEventListener('change', saveFormData);

        document.getElementById('property-form').onsubmit = async (e) => {
            e.preventDefault();
            alert('Congratulations! Your property listing has been submitted successfully.');
            localStorage.removeItem('property_wizard_step');
            localStorage.removeItem('property_wizard_data');
            window.location.href = 'index.php';
        };

        // Load data before initial display
        loadFormData();
        updateDisplay();

        // Category Toggle
        const hotelCategoryContainer = document.getElementById('hotel-category-container');
        const businessTypeRadios = document.querySelectorAll('input[name="business_type"]');

        function updateCategoryVisibility(type) {
            if (type === 'hotel') {
                hotelCategoryContainer.style.display = 'block';
            } else {
                hotelCategoryContainer.style.display = 'none';
            }
        }

        businessTypeRadios.forEach(radio => {
            radio.addEventListener('change', (e) => {
                updateCategoryVisibility(e.target.value);
            });
        });

        // Initialize visibility
        updateCategoryVisibility(document.querySelector('input[name="business_type"]:checked')?.value || 'hotel');

        // Staff Dynamic Forms
        const staffCountInput = document.getElementById('staff_count');
        const staffFormsContainer = document.getElementById('staff_forms_container');

        function generateStaffForms(count) {
            staffFormsContainer.innerHTML = '';
            staffFormsContainer.classList.add('grid', 'grid-cols-1', 'xl:grid-cols-2', 'gap-8');
            for (let i = 1; i <= count; i++) {
                const staffForm = `
                    <div class="p-5 border rounded-2xl bg-gray-50/30 space-y-4 relative">
                        <div class="absolute -top-3 left-6 bg-white px-3 py-1 border rounded-lg text-[10px] font-bold text-[#006ce4] uppercase tracking-wider shadow-sm">
                            Staff Member ${i}
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 mb-1.5 uppercase">First Name</label>
                                <input type="text" name="staff_${i}_first_name" placeholder="Enter first name" class="w-full px-4 py-2.5 rounded-xl border bg-white text-sm outline-none focus:ring-2 focus:ring-[#006ce4] transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 mb-1.5 uppercase">Last Name</label>
                                <input type="text" name="staff_${i}_last_name" placeholder="Enter last name" class="w-full px-4 py-2.5 rounded-xl border bg-white text-sm outline-none focus:ring-2 focus:ring-[#006ce4] transition-all">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 mb-1.5 uppercase">Contact Number</label>
                                <input type="text" name="staff_${i}_phone" placeholder="Phone number" class="w-full px-4 py-2.5 rounded-xl border bg-white text-sm outline-none focus:ring-2 focus:ring-[#006ce4] transition-all">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-500 mb-1.5 uppercase">NIC Number</label>
                                <input type="text" name="staff_${i}_nic" placeholder="National ID" class="w-full px-4 py-2.5 rounded-xl border bg-white text-sm outline-none focus:ring-2 focus:ring-[#006ce4] transition-all">
                            </div>
                        </div>
                    </div>
                `;
                staffFormsContainer.insertAdjacentHTML('beforeend', staffForm);
            }
        }

        staffCountInput.addEventListener('input', (e) => {
            let count = parseInt(e.target.value);
            if (count > 20) { count = 20; e.target.value = 20; }
            if (count < 0) { count = 0; e.target.value = 0; }
            generateStaffForms(count);
            saveFormData(); // Save the structure change
        });

        // Initialize staff forms if data was loaded
        const savedData = localStorage.getItem('property_wizard_data');
        if (savedData) {
            const data = JSON.parse(savedData);
            const savedCount = Object.keys(data).filter(key => key.includes('_first_name')).length;
            if (savedCount > 0) {
                staffCountInput.value = savedCount;
                generateStaffForms(savedCount);
                // Re-load form data to fill the dynamic fields
                loadFormData();
            }

            // Load images
            if (data.logo_image) showPreview('logo-upload', data.logo_image);
            if (data.cover_image) showPreview('cover-upload', data.cover_image);
            if (data.manager_photo) showPreview('manager-photo-upload', data.manager_photo);
        }

        // Image Upload Logic
        document.querySelectorAll('.upload-trigger').forEach(trigger => {
            trigger.addEventListener('click', () => {
                trigger.parentElement.querySelector('.file-input').click();
            });
        });

        document.querySelectorAll('.file-input').forEach(input => {
            input.addEventListener('change', async (e) => {
                const file = e.target.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('file', file);
                formData.append('action', 'upload');

                const containerId = input.parentElement.id;
                const trigger = input.parentElement.querySelector('.upload-trigger');
                trigger.innerHTML = '<i class="fas fa-spinner fa-spin text-blue-500"></i>';

                try {
                    const response = await fetch('upload_handler.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (result.success) {
                        showPreview(containerId, result.filepath);
                        input.parentElement.querySelector('input[type="hidden"]').value = result.filepath;
                        saveFormData();
                    } else {
                        alert(result.message);
                    }
                } catch (error) {
                    console.error('Upload error:', error);
                } finally {
                    const type = input.dataset.type;
                    let icon = 'fa-cloud-upload-alt';
                    let text = 'Upload Logo';
                    if (type === 'cover') { icon = 'fa-image'; text = 'Upload Cover'; }
                    if (type === 'manager') { icon = 'fa-user-circle'; text = 'Upload Photo'; }
                    
                    trigger.innerHTML = `<i class="fas ${icon} text-gray-400 text-2xl mb-2"></i><span class="text-xs font-bold text-gray-500 uppercase tracking-wider">${text}</span>`;
                }
            });
        });

        document.querySelectorAll('.remove-image').forEach(button => {
            button.addEventListener('click', async (e) => {
                const container = button.closest('.upload-container');
                const hiddenInput = container.querySelector('input[type="hidden"]');
                const filepath = hiddenInput.value;

                if (!filepath) return;

                const formData = new FormData();
                formData.append('filepath', filepath);
                formData.append('action', 'delete');

                try {
                    const response = await fetch('upload_handler.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    if (result.success) {
                        hidePreview(container.id);
                        hiddenInput.value = '';
                        saveFormData();
                    }
                } catch (error) {
                    console.error('Delete error:', error);
                }
            });
        });

        function showPreview(containerId, filepath) {
            const container = document.getElementById(containerId);
            const preview = container.querySelector('.preview-container');
            const img = preview.querySelector('img');
            img.src = filepath;
            preview.classList.remove('hidden');
        }

        // Room Inventory Logic
        const roomContainer = document.getElementById('room-inventory-container');
        const addRoomBtn = document.getElementById('add-room-btn');
        let roomIndex = 0;

        function addRoomCard(data = {}) {
            const index = roomIndex++;
            const isHall = data.is_hall == 1;
            const propertyType = document.querySelector('input[name="business_type"]:checked')?.value || 'hotel';
            
            // Labels based on property type
            let typeLabel = "Room";
            let namePlaceholder = "e.g. Deluxe Double Room";
            if (propertyType === 'reception_hall' || propertyType === 'rest_hall') {
                typeLabel = "Hall / Space";
                namePlaceholder = "e.g. Grand Ballroom or Main Hall";
            }

            const card = document.createElement('div');
            card.className = 'room-card bg-gray-50/50 p-8 rounded-3xl border border-gray-100 relative group transition-all hover:bg-white hover:shadow-xl hover:shadow-blue-900/5';
            card.dataset.index = index;
            
            card.innerHTML = `
                <button type="button" class="remove-room absolute -top-3 -right-3 w-8 h-8 bg-red-500 text-white rounded-full flex items-center justify-center shadow-lg opacity-0 group-hover:opacity-100 transition-all hover:bg-red-600">
                    <i class="fas fa-times text-xs"></i>
                </button>
                
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                    <!-- Room Image -->
                    <div class="lg:col-span-1">
                        <label class="block text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-widest">${typeLabel} Photo</label>
                        <div class="upload-container relative h-40 group/img" id="room-upload-${index}">
                            <input type="file" accept="image/*" class="hidden room-file-input" data-index="${index}">
                            <input type="hidden" name="rooms[${index}][image]" class="room-image-path" value="${data.room_image || ''}">
                            <div class="w-full h-full border-2 border-dashed border-gray-200 rounded-2xl flex flex-col items-center justify-center bg-white group-hover/img:border-[#006ce4] transition-all cursor-pointer room-upload-trigger">
                                <i class="fas fa-camera text-gray-300 text-2xl mb-2"></i>
                                <span class="text-[10px] font-bold text-gray-400 uppercase">Upload</span>
                            </div>
                            <div class="preview-container ${data.room_image ? '' : 'hidden'} absolute inset-0 bg-white rounded-2xl border flex items-center justify-center p-2">
                                <img src="${data.room_image || ''}" class="max-w-full max-h-full rounded-xl object-cover">
                                <button type="button" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center shadow-lg remove-room-image">
                                    <i class="fas fa-times text-[10px]"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Room Details -->
                    <div class="lg:col-span-3">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div class="md:col-span-2">
                                <label class="block text-[10px] font-bold text-gray-600 mb-2 uppercase tracking-widest">${typeLabel} Type Name</label>
                                <input type="text" name="rooms[${index}][name]" value="${data.room_name || ''}" placeholder="${namePlaceholder}" class="w-full px-5 py-3 rounded-xl border bg-white text-sm outline-none focus:ring-2 focus:ring-[#006ce4] transition-all font-bold">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-600 mb-2 uppercase tracking-widest text-blue-600">Total Number of ${typeLabel}s</label>
                                <input type="number" name="rooms[${index}][count]" value="${data.total_rooms || 1}" min="1" class="w-full px-5 py-3 rounded-xl border bg-white text-sm outline-none focus:ring-2 focus:ring-[#006ce4] transition-all">
                                <input type="hidden" name="rooms[${index}][is_hall]" value="${data.is_hall || 0}">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-600 mb-2 uppercase tracking-widest">Adult Capacity</label>
                                <div class="relative">
                                    <input type="number" name="rooms[${index}][adults]" value="${data.max_adults || 2}" min="1" class="w-full px-5 py-3 rounded-xl border bg-white text-sm outline-none focus:ring-2 focus:ring-[#006ce4] transition-all">
                                    <i class="fas fa-user absolute right-4 top-1/2 -translate-y-1/2 text-gray-300"></i>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-600 mb-2 uppercase tracking-widest">Child Capacity</label>
                                <div class="relative">
                                    <input type="number" name="rooms[${index}][children]" value="${data.max_children || 0}" min="0" class="w-full px-5 py-3 rounded-xl border bg-white text-sm outline-none focus:ring-2 focus:ring-[#006ce4] transition-all">
                                    <i class="fas fa-child absolute right-4 top-1/2 -translate-y-1/2 text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            roomContainer.appendChild(card);
            
            // Bind Events for this card
            const uploadTrigger = card.querySelector('.room-upload-trigger');
            const fileInput = card.querySelector('.room-file-input');
            const removeBtn = card.querySelector('.remove-room');
            const removeImgBtn = card.querySelector('.remove-room-image');

            uploadTrigger.onclick = () => fileInput.click();

            fileInput.onchange = async (e) => {
                const file = e.target.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('file', file);
                formData.append('action', 'upload');

                uploadTrigger.innerHTML = '<i class="fas fa-spinner fa-spin text-blue-500"></i>';

                try {
                    const response = await fetch('upload_handler.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    if (result.success) {
                        card.querySelector('.preview-container img').src = result.filepath;
                        card.querySelector('.preview-container').classList.remove('hidden');
                        card.querySelector('.room-image-path').value = result.filepath;
                        saveFormData();
                    }
                } catch (error) { console.error(error); }
                finally {
                    uploadTrigger.innerHTML = '<i class="fas fa-camera text-gray-300 text-2xl mb-2"></i><span class="text-[10px] font-bold text-gray-400 uppercase">Upload</span>';
                }
            };

            removeBtn.onclick = () => {
                card.remove();
                saveFormData();
            };

            removeImgBtn.onclick = () => {
                card.querySelector('.preview-container').classList.add('hidden');
                card.querySelector('.room-image-path').value = '';
                saveFormData();
            };
        }

        addRoomBtn.addEventListener('click', () => addRoomCard());

        // Initialize with one room if empty
        if (roomContainer.children.length === 0) {
            addRoomCard();
        }

        function hidePreview(containerId) {
            const container = document.getElementById(containerId);
            const preview = container.querySelector('.preview-container');
            preview.classList.add('hidden');
        }

        // Special Amenities dynamic rows
        const specialContainer = document.getElementById('special-amenities-container');
        const addSpecialBtn = document.getElementById('add-special-amenity');

        addSpecialBtn.addEventListener('click', () => {
            const row = document.createElement('div');
            row.className = 'special-amenity-row flex gap-3';
            row.innerHTML = `
                <div class="relative flex-1">
                    <i class="fas fa-magic absolute left-4 top-1/2 -translate-y-1/2 text-blue-300"></i>
                    <input type="text" name="special_amenities[]" placeholder="e.g. Another unique feature" class="w-full pl-12 pr-5 py-3 rounded-xl border bg-white text-sm outline-none focus:ring-2 focus:ring-[#006ce4] transition-all">
                </div>
                <button type="button" class="remove-special-row w-12 h-12 rounded-xl border-2 border-dashed border-red-200 text-red-300 hover:border-red-500 hover:text-red-500 transition-all flex items-center justify-center">
                    <i class="fas fa-times"></i>
                </button>
            `;
            specialContainer.appendChild(row);
            
            // Re-bind removal events
            bindRemovalEvents();
        });

        function bindRemovalEvents() {
            document.querySelectorAll('.remove-special-row').forEach(btn => {
                btn.onclick = () => {
                    if (document.querySelectorAll('.special-amenity-row').length > 1) {
                        btn.closest('.special-amenity-row').remove();
                        saveFormData();
                    } else {
                        btn.closest('.special-amenity-row').querySelector('input').value = '';
                    }
                };
            });
        }
        
        bindRemovalEvents();
    </script>
</body>
</html>
