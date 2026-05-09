<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: register.php");
    exit();
}

$success_msg = '';
$error_msg = '';

// Handle Ad Submission (Simulating Payment)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pay') {
    $user_id = $_SESSION['user_id'] ?? null;
    $owner_name = $_SESSION['user_name'] ?? 'User';
    $ad_title = $_POST['ad_title'] ?? '';
    $package_id = $_POST['package_id'] ?? null;
    $link_url = $_POST['link_url'] ?? '';
    
    // Handle image upload
    $image_path = '';
    if (isset($_FILES['ad_image']) && $_FILES['ad_image']['error'] == 0) {
        $upload_dir = 'uploads/ads/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = time() . '_' . basename($_FILES['ad_image']['name']);
        $target_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['ad_image']['tmp_name'], $target_path)) {
            $image_path = $target_path;
        } else {
            $error_msg = "Failed to upload image.";
        }
    } else {
        $error_msg = "Please upload an advertisement image.";
    }
    
    if (empty($error_msg) && $package_id) {
        try {
            // Fetch package details for snapshot
            $pkg_stmt = $pdo->prepare("SELECT * FROM advertisement_packages WHERE id = ?");
            $pkg_stmt->execute([$package_id]);
            $package = $pkg_stmt->fetch();
            
            if ($package) {
                $stmt = $pdo->prepare("INSERT INTO advertisements (user_id, ad_title, owner_name, package_id, package_name, package_type, package_price, package_duration_days, package_is_active, price, image_path, link_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                
                $stmt->execute([
                    $user_id,
                    $ad_title,
                    $owner_name,
                    $package_id,
                    $package['package_name'],
                    $package['package_type'],
                    $package['price'],
                    $package['duration_days'],
                    $package['is_active'],
                    $package['price'], // Setting price to package price for record
                    $image_path,
                    $link_url
                ]);
                $success_msg = "Thank you for publishing your ad! Your payment was successful and the ad is now active.";
            } else {
                $error_msg = "Invalid package selected.";
            }
        } catch (PDOException $e) {
            $error_msg = "Database error: " . $e->getMessage();
        }
    } elseif(empty($error_msg)) {
         $error_msg = "Please select a package.";
    }
}

// Fetch available packages
$packages = [];
try {
    $stmt = $pdo->query("SELECT * FROM advertisement_packages WHERE is_active = 1 ORDER BY price ASC");
    $packages = $stmt->fetchAll();
} catch (PDOException $e) {
    // If table doesn't exist yet, gracefully handle
}

// Fetch user's current active ads
$current_ads = [];
try {
    $user_id = $_SESSION['user_id'] ?? 0;
    $stmt = $pdo->prepare("SELECT a.*, p.package_name as current_pkg_name FROM advertisements a LEFT JOIN advertisement_packages p ON a.package_id = p.id WHERE a.user_id = ? ORDER BY a.created_at DESC");
    $stmt->execute([$user_id]);
    $current_ads = $stmt->fetchAll();
} catch (PDOException $e) {
    // Graceful fail
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Publish Advertisement - Bookingjaunt</title>
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
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-neutral-50 text-neutral-800 font-['Inter']">

    <?php include 'navbar.php'; ?>

    <div class="max-w-[1200px] mx-auto px-4 py-12">
        <div class="mb-10 text-center">
            <h1 class="text-3xl md:text-5xl font-black text-[#003580] tracking-tight font-['Outfit'] mb-4">Launch Your Campaign</h1>
            <p class="text-neutral-500 font-medium max-w-2xl mx-auto text-lg">Select a premium placement package, upload your creative, and reach thousands of daily travelers.</p>
        </div>

        <?php if ($success_msg): ?>
            <div class="bg-green-100 border border-green-200 text-green-800 px-6 py-4 rounded-2xl mb-8 flex items-center gap-4 shadow-sm animate-fade-in">
                <i class="fas fa-check-circle text-2xl text-green-600"></i>
                <div class="font-bold"><?php echo $success_msg; ?></div>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="bg-red-100 border border-red-200 text-red-800 px-6 py-4 rounded-2xl mb-8 flex items-center gap-4 shadow-sm animate-fade-in">
                <i class="fas fa-exclamation-circle text-2xl text-red-600"></i>
                <div class="font-bold"><?php echo $error_msg; ?></div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-[1fr_400px] gap-10">
            <!-- Ad Creation Form -->
            <div class="bg-white rounded-3xl p-8 border border-neutral-200 shadow-sm">
                <h2 class="text-2xl font-black text-neutral-800 mb-8 border-b border-neutral-100 pb-4">1. Advertisement Details</h2>
                
                <form action="addnewadd.php" method="POST" enctype="multipart/form-data" id="adForm">
                    <!-- Package Selection -->
                    <div class="mb-8">
                        <label class="block text-sm font-bold text-neutral-700 mb-4">Select Placement Package <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($packages as $pkg): ?>
                                <label class="relative border-2 border-neutral-200 rounded-2xl p-4 cursor-pointer hover:border-secondary transition-colors group">
                                    <input type="radio" name="package_id" value="<?php echo $pkg['id']; ?>" class="absolute opacity-0" required onchange="updateTotal(<?php echo $pkg['price']; ?>)">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <div class="font-black text-primary mb-1"><?php echo htmlspecialchars($pkg['package_name']); ?></div>
                                            <div class="text-[11px] font-bold text-neutral-400 uppercase tracking-wider mb-2">Duration: <?php echo $pkg['duration_days']; ?> Days</div>
                                        </div>
                                        <div class="w-5 h-5 rounded-full border-2 border-neutral-300 flex items-center justify-center group-hover:border-secondary transition-colors indicator">
                                            <div class="w-2.5 h-2.5 bg-secondary rounded-full opacity-0 transition-opacity"></div>
                                        </div>
                                    </div>
                                    <div class="text-xl font-black text-secondary">LKR <?php echo number_format($pkg['price']); ?></div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-bold text-neutral-700 mb-2">Advertisement Title <span class="text-red-500">*</span></label>
                            <input type="text" name="ad_title" required placeholder="E.g. Summer Dayout Promo" class="w-full bg-neutral-50 border border-neutral-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-secondary/20 focus:border-secondary transition-all">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-neutral-700 mb-2">Destination URL <span class="text-neutral-400 font-normal">(Optional)</span></label>
                            <input type="url" name="link_url" placeholder="https://yourwebsite.com" class="w-full bg-neutral-50 border border-neutral-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-secondary/20 focus:border-secondary transition-all">
                            <p class="text-xs text-neutral-400 mt-1 font-medium">Where should users go when they click your ad?</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-neutral-700 mb-2">Starting Date <span class="text-red-500">*</span></label>
                            <input type="date" name="start_date" required min="<?php echo date('Y-m-d'); ?>" class="w-full bg-neutral-50 border border-neutral-200 rounded-xl px-4 py-3 focus:outline-none focus:ring-2 focus:ring-secondary/20 focus:border-secondary transition-all">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-neutral-700 mb-2">Advertisement Creative <span class="text-red-500">*</span></label>
                            <div class="border-2 border-dashed border-neutral-300 rounded-xl p-8 text-center bg-neutral-50 hover:bg-neutral-100 transition-colors cursor-pointer relative" onclick="document.getElementById('ad_image').click()">
                                <i class="fas fa-cloud-upload-alt text-4xl text-neutral-400 mb-3"></i>
                                <div class="font-bold text-primary mb-1">Click to upload image</div>
                                <div class="text-xs text-neutral-500">JPG, PNG or WEBP (Max 2MB)</div>
                                <input type="file" name="ad_image" id="ad_image" accept="image/*" class="hidden" required onchange="previewImage(this)">
                            </div>
                            <div id="imagePreviewContainer" class="mt-4 hidden relative rounded-xl overflow-hidden border border-neutral-200">
                                <img id="imagePreview" class="w-full h-auto max-h-[300px] object-cover">
                                <button type="button" onclick="clearImage(event)" class="absolute top-2 right-2 bg-white text-red-500 w-8 h-8 rounded-full shadow-md flex items-center justify-center hover:bg-red-50">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Payment Summary -->
            <div class="relative">
                <div class="sticky top-28 bg-white rounded-3xl p-8 border border-neutral-200 shadow-xl shadow-[#003580]/5">
                    <h2 class="text-xl font-black text-neutral-800 mb-6 border-b border-neutral-100 pb-4">Payment Summary</h2>
                    
                    <div class="space-y-4 mb-8">
                        <div class="flex justify-between items-center">
                            <span class="text-neutral-500 font-bold text-sm">Package Cost</span>
                            <span class="font-black text-neutral-800" id="summaryCost">LKR 0.00</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-neutral-500 font-bold text-sm">Taxes (0%)</span>
                            <span class="font-black text-neutral-800">LKR 0.00</span>
                        </div>
                        <div class="pt-4 border-t border-neutral-100 flex justify-between items-center">
                            <span class="text-lg font-black text-primary">Total Amount</span>
                            <span class="text-2xl font-black text-[#008009]" id="summaryTotal">LKR 0.00</span>
                        </div>
                    </div>

                    <div class="bg-blue-50 text-blue-800 p-4 rounded-xl text-sm font-medium mb-6">
                        <i class="fas fa-info-circle mr-2"></i> This is a secure payment simulation for demonstration.
                    </div>

                    <button type="submit" form="adForm" name="action" value="pay" class="w-full bg-[#008009] hover:bg-[#006607] text-white py-4 rounded-xl font-black text-lg transition-all shadow-lg shadow-[#008009]/20 hover:scale-[1.02] flex items-center justify-center gap-3">
                        <i class="fas fa-lock text-sm"></i> Make Payment
                    </button>
                </div>
            </div>
        </div>

        <!-- Current Ads Section -->
        <div class="mt-20">
            <h2 class="text-2xl font-black text-[#003580] mb-8">Your Active Campaigns</h2>
            
            <?php if (empty($current_ads)): ?>
                <div class="bg-white rounded-2xl border border-neutral-200 p-12 text-center shadow-sm">
                    <i class="fas fa-ad text-neutral-200 text-6xl mb-4"></i>
                    <h3 class="text-xl font-bold text-neutral-500">No active campaigns yet</h3>
                    <p class="text-neutral-400 mt-2">Create your first ad campaign above to get started.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($current_ads as $ad): ?>
                        <div class="bg-white rounded-2xl border border-neutral-200 overflow-hidden shadow-sm group">
                            <div class="h-40 overflow-hidden relative">
                                <?php if ($ad['image_path']): ?>
                                    <img src="<?php echo htmlspecialchars($ad['image_path']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                <?php else: ?>
                                    <div class="w-full h-full bg-neutral-100 flex items-center justify-center">
                                        <i class="fas fa-image text-3xl text-neutral-300"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="absolute top-3 left-3 bg-[#008009] text-white text-[10px] font-bold px-2 py-1 rounded uppercase tracking-wider shadow-sm">Active</div>
                            </div>
                            <div class="p-5">
                                <div class="text-[11px] font-bold text-neutral-400 uppercase tracking-widest mb-1"><?php echo htmlspecialchars($ad['current_pkg_name'] ?? $ad['package_name'] ?? 'Custom Package'); ?></div>
                                <h4 class="text-lg font-black text-neutral-800 mb-1"><?php echo htmlspecialchars(!empty($ad['ad_title']) ? $ad['ad_title'] : $ad['owner_name']); ?></h4>
                                <?php if (!empty($ad['link_url'])): ?>
                                <a href="<?php echo htmlspecialchars($ad['link_url']); ?>" target="_blank" class="text-primary font-bold hover:underline truncate block mb-4 text-sm">
                                    <?php echo htmlspecialchars($ad['link_url']); ?>
                                </a>
                                <?php else: ?>
                                <div class="text-neutral-400 text-sm mb-4">No Destination URL</div>
                                <?php endif; ?>
                                <div class="flex items-center justify-between border-t border-neutral-50 pt-4">
                                    <div>
                                        <div class="text-[10px] font-bold text-neutral-400 uppercase">Status</div>
                                        <div class="font-bold text-[#008009] capitalize"><?php echo htmlspecialchars($ad['status']); ?></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-[10px] font-bold text-neutral-400 uppercase">Start Date</div>
                                        <div class="font-bold text-neutral-700"><?php echo date('M d, Y', strtotime($ad['created_at'])); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <style>
        /* Custom radio button styling */
        input[type="radio"]:checked + div > .indicator {
            border-color: var(--secondary);
        }
        input[type="radio"]:checked + div > .indicator > div {
            opacity: 1;
        }
        input[type="radio"]:checked ~ div {
            color: var(--secondary);
        }
        label:has(input[type="radio"]:checked) {
            border-color: var(--secondary);
            background-color: #f0f7ff;
        }
    </style>

    <script>
        function updateTotal(price) {
            const formatted = new Intl.NumberFormat('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(price);
            document.getElementById('summaryCost').innerText = 'LKR ' + formatted;
            document.getElementById('summaryTotal').innerText = 'LKR ' + formatted;
        }

        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').src = e.target.result;
                    document.getElementById('imagePreviewContainer').classList.remove('hidden');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function clearImage(e) {
            e.preventDefault();
            e.stopPropagation();
            document.getElementById('ad_image').value = '';
            document.getElementById('imagePreviewContainer').classList.add('hidden');
        }
    </script>
</body>
</html>
