<?php
// Included by admin, manager, staff wrappers

$property_id = (int)($_GET['id'] ?? 0);

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    $user_id = $_SESSION['user_id'];
    
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE properties SET approval_status = 'approved', approved_by = ?, approval_timestamp = NOW() WHERE id = ?");
        $stmt->execute([$user_id, $property_id]);
        header("Location: pending_properties.php?success=approved");
        exit();
    } elseif ($action === 'reject') {
        // We will just mark it as rejected. The user can see it in their dashboard maybe.
        $stmt = $pdo->prepare("UPDATE properties SET approval_status = 'rejected', approved_by = ?, approval_timestamp = NOW() WHERE id = ?");
        $stmt->execute([$user_id, $property_id]);
        header("Location: pending_properties.php?success=rejected");
        exit();
    }
}

// Fetch property data
$stmt = $pdo->prepare("SELECT p.*, u.first_name, u.last_name, u.email as owner_email, u.phone_number as owner_phone FROM properties p JOIN users u ON p.owner_id = u.id WHERE p.id = ?");
$stmt->execute([$property_id]);
$property = $stmt->fetch();

if (!$property) {
    die("Property not found.");
}

// Function to render a detail row safely
function renderDetailRow($label, $value) {
    $val = trim((string)$value);
    if ($val === '') {
        $val = '<span class="text-gray-500 italic text-xs uppercase">Blank (Not Provided)</span>';
    } else {
        $val = htmlspecialchars($val);
    }
    echo '<div class="border-b border-white/10 pb-3 last:border-0 last:pb-0">';
    echo '<p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">'.htmlspecialchars($label).'</p>';
    echo '<p class="text-sm font-medium text-white">'.$val.'</p>';
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Approval Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #0f172a; }
        .glass-header { background: rgba(15, 23, 42, 0.35); backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px); border-bottom: 1px solid rgba(255,255,255,0.1); }
        .glass-card { background: rgba(15, 23, 42, 0.35); backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px); border: 1px solid rgba(255,255,255,0.1); border-radius: 24px; box-shadow: 0 4px 30px rgba(0,0,0,0.1); }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: rgba(255,255,255,0.02); }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.1); border-radius: 10px; }
    </style>
</head>
<body class="flex min-h-screen overflow-hidden bg-slate-900">
    <?php include $sidebar_path; ?>
    <main class="flex-1 lg:ml-64 overflow-y-auto h-screen relative z-10 custom-scrollbar">
        <header class="glass-header sticky top-0 z-40 px-4 lg:px-8 py-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="pending_properties.php" class="w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center text-white hover:bg-white/20 transition-all">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-black text-white">Review Registration</h1>
                    <p class="text-xs text-indigo-300 font-bold uppercase tracking-widest">Property ID: #<?php echo $property['id']; ?></p>
                </div>
            </div>
            
            <?php if ($property['approval_status'] === 'pending'): ?>
            <form method="POST" class="flex items-center gap-3" onsubmit="return confirm('Are you sure you want to perform this action?');">
                <button type="submit" name="action" value="reject" class="bg-red-500/20 hover:bg-red-500 text-red-400 hover:text-white border border-red-500/30 px-6 py-3 rounded-xl font-bold text-xs uppercase tracking-widest transition-all">
                    Reject
                </button>
                <button type="submit" name="action" value="approve" class="bg-emerald-600 hover:bg-emerald-500 text-white px-8 py-3 rounded-xl font-bold text-xs uppercase tracking-widest transition-all shadow-lg shadow-emerald-900/50">
                    <i class="fas fa-check mr-2"></i> Approve Property
                </button>
            </form>
            <?php else: ?>
                <div class="bg-white/10 px-6 py-3 rounded-xl border border-white/20">
                    <span class="text-xs font-bold text-white uppercase tracking-widest">Status: <?php echo htmlspecialchars($property['approval_status']); ?></span>
                </div>
            <?php endif; ?>
        </header>

        <div class="p-4 lg:p-8">
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                
                <!-- Main Details -->
                <div class="xl:col-span-2 space-y-6">
                    <div class="glass-card p-6 md:p-8">
                        <h2 class="text-lg font-black text-white mb-6 flex items-center gap-3">
                            <i class="fas fa-info-circle text-indigo-400"></i> Basic Information
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php 
                            renderDetailRow('Property Name', $property['property_name']);
                            renderDetailRow('Business Type', $property['business_type']);
                            renderDetailRow('Hotel Category', $property['hotel_category']);
                            renderDetailRow('Description', $property['description']);
                            renderDetailRow('Street Address', $property['street_address']);
                            renderDetailRow('City', $property['city']);
                            renderDetailRow('District', $property['district']);
                            renderDetailRow('Province', $property['province']);
                            renderDetailRow('Country', $property['country']);
                            renderDetailRow('Postal Code', $property['postal_code']);
                            renderDetailRow('Closest Main Town', $property['closest_main_town']);
                            renderDetailRow('Google Map Location', $property['google_map_location']);
                            ?>
                        </div>
                    </div>

                    <div class="glass-card p-6 md:p-8">
                        <h2 class="text-lg font-black text-white mb-6 flex items-center gap-3">
                            <i class="fas fa-address-book text-emerald-400"></i> Contact & Management
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php 
                            renderDetailRow('Fixed Telephone', $property['fixed_telephone']);
                            renderDetailRow('Mobile Telephone', $property['mobile_telephone']);
                            renderDetailRow('Contact Number', $property['contact_number']);
                            renderDetailRow('WhatsApp Number', $property['whatsapp_number']);
                            renderDetailRow('Business Email', $property['business_email']);
                            renderDetailRow('Manager Name', $property['manager_name']);
                            renderDetailRow('Manager Email', $property['manager_email']);
                            renderDetailRow('Manager Phone', $property['manager_phone']);
                            renderDetailRow('Manager NIC', $property['manager_nic']);
                            ?>
                        </div>
                    </div>

                    <div class="glass-card p-6 md:p-8">
                        <h2 class="text-lg font-black text-white mb-6 flex items-center gap-3">
                            <i class="fas fa-cogs text-orange-400"></i> Operations & Logistics
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php 
                            renderDetailRow('Closest Police Station', $property['closest_police_station']);
                            renderDetailRow('Closest Hospital', $property['closest_hospital']);
                            renderDetailRow('Airport Distance', $property['airport_distance']);
                            renderDetailRow('Check-In Time', $property['check_in_time']);
                            renderDetailRow('Check-Out Time', $property['check_out_time']);
                            renderDetailRow('Cancellation Policy', $property['cancellation_policy']);
                            renderDetailRow('Smoking Allowed', $property['smoking_allowed'] ? 'Yes' : 'No');
                            renderDetailRow('Pets Allowed', $property['pets_allowed'] ? 'Yes' : 'No');
                            renderDetailRow('Events Allowed', $property['events_allowed'] ? 'Yes' : 'No');
                            ?>
                        </div>
                    </div>
                    
                    <?php if ($property['business_type'] === 'vehicle'): ?>
                    <div class="glass-card p-6 md:p-8 border-l-4 border-indigo-500">
                        <h2 class="text-lg font-black text-white mb-6 flex items-center gap-3">
                            <i class="fas fa-car text-indigo-400"></i> Vehicle Details
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php 
                            renderDetailRow('Vehicle Category', $property['vehicle_category']);
                            renderDetailRow('Brand', $property['brand']);
                            renderDetailRow('Model', $property['model']);
                            renderDetailRow('Manufactured Year', $property['manufactured_year']);
                            renderDetailRow('Registration Number', $property['registration_number']);
                            renderDetailRow('Chassis Number', $property['chassis_number']);
                            renderDetailRow('Engine Number', $property['engine_number']);
                            renderDetailRow('Vehicle Color', $property['vehicle_color']);
                            renderDetailRow('Fuel Type', $property['fuel_type']);
                            renderDetailRow('Transmission Type', $property['transmission_type']);
                            renderDetailRow('Seat Count', $property['seat_count']);
                            renderDetailRow('Pricing Type', $property['pricing_type']);
                            renderDetailRow('Driver Option', $property['driver_option']);
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar Details -->
                <div class="space-y-6">
                    <div class="glass-card p-6">
                        <h2 class="text-sm font-black text-white mb-4 uppercase tracking-widest text-center">Owner Account</h2>
                        <div class="text-center">
                            <div class="w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full mx-auto flex items-center justify-center text-xl font-bold text-white mb-3 shadow-lg">
                                <?php echo substr($property['first_name'], 0, 1) . substr($property['last_name'], 0, 1); ?>
                            </div>
                            <p class="text-lg font-bold text-white"><?php echo htmlspecialchars($property['first_name'] . ' ' . $property['last_name']); ?></p>
                            <p class="text-xs text-indigo-300 mb-2"><?php echo htmlspecialchars($property['owner_email']); ?></p>
                            <p class="text-xs font-bold text-gray-300"><?php echo htmlspecialchars($property['owner_phone']); ?></p>
                        </div>
                    </div>

                    <div class="glass-card p-6">
                        <h2 class="text-sm font-black text-white mb-4 uppercase tracking-widest border-b border-white/10 pb-3">Financials</h2>
                        <div class="space-y-4">
                            <?php 
                            renderDetailRow('Bank Name', $property['bank_name']);
                            renderDetailRow('Branch', $property['bank_branch']);
                            renderDetailRow('Account Name', $property['bank_account_name']);
                            renderDetailRow('Account Number', $property['bank_account_number']);
                            renderDetailRow('Commission Rate', $property['commission_rate'] . '%');
                            renderDetailRow('Currency', $property['currency']);
                            ?>
                        </div>
                    </div>

                    <div class="glass-card p-6">
                        <h2 class="text-sm font-black text-white mb-4 uppercase tracking-widest border-b border-white/10 pb-3">Media Preview</h2>
                        <?php if (!empty($property['cover_image'])): ?>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Cover Image</p>
                            <img src="../../<?php echo htmlspecialchars($property['cover_image']); ?>" alt="Cover" class="w-full h-32 object-cover rounded-xl mb-4" onerror="this.style.display='none'">
                        <?php endif; ?>
                        
                        <?php if (!empty($property['logo_image'])): ?>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Logo</p>
                            <img src="../../<?php echo htmlspecialchars($property['logo_image']); ?>" alt="Logo" class="w-20 h-20 object-contain bg-white rounded-xl mx-auto" onerror="this.style.display='none'">
                        <?php endif; ?>
                        
                        <?php if (empty($property['cover_image']) && empty($property['logo_image'])): ?>
                            <p class="text-xs text-gray-500 italic text-center py-4">No media uploaded.</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </main>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            if(sidebar) sidebar.classList.toggle('-translate-x-full');
        }
    </script>
</body>
</html>
