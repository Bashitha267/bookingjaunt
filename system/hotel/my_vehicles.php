<?php
require_once '../../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch first property for sidebar context
$prop_stmt = $pdo->prepare("SELECT * FROM properties WHERE owner_id = ? LIMIT 1");
$prop_stmt->execute([$user_id]);
$property = $prop_stmt->fetch();

// Fetch all vehicle listings for this owner
$stmt = $pdo->prepare("
    SELECT p.*,
        (SELECT COUNT(*) FROM bookings b WHERE b.property_id = p.id AND b.booking_category = 'vehicle') as booking_count,
        (SELECT pb.id FROM property_boosts pb WHERE pb.property_id = p.id AND pb.status = 'active' AND DATE_ADD(pb.start_date, INTERVAL pb.duration_days DAY) >= CURDATE() LIMIT 1) as is_boosted
    FROM properties p
    WHERE p.owner_id = ? AND p.business_type = 'vehicle'
    ORDER BY p.created_at DESC
");
$stmt->execute([$user_id]);
$vehicles = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Vehicles - Bookingjaunt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="flex min-h-screen overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <main class="main-content overflow-y-auto" style="position:relative; z-index:1;">

        <!-- Header -->
        <header class="glass-header sticky top-0 z-40 px-2 lg:px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden w-10 h-10 rounded-xl btn-glass flex items-center justify-center">
                    <i class="fas fa-bars-staggered"></i>
                </button>
                <h1 class="text-lg font-black" style="color:white;"><i class="fas fa-car mr-2" style="color:#fbbf24;"></i>My Vehicles</h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="../../index.php" target="_blank" class="btn-glass hidden xl:flex"><i class="fas fa-external-link-alt"></i> Visit Site</a>
                <a href="../../property_wizard.php?type=vehicle" class="btn-primary">
                    <i class="fas fa-plus"></i>
                    <span class="hidden sm:inline">Add Vehicle</span>
                </a>
            </div>
        </header>

        <div class="py-4 px-2 lg:py-8 lg:px-4">

            <!-- Page Intro -->
            <div class="mb-8 flex flex-col sm:flex-row sm:justify-between sm:items-end gap-4 anim-up">
                <div>
                    <h2 class="text-2xl font-black" style="color:white;">Vehicle Fleet</h2>
                    <p class="text-sm font-medium mt-1" style="color:rgba(255,255,255,0.5);">Manage your registered vehicles and track bookings</p>
                </div>
                <div class="flex items-center gap-3 text-sm font-bold" style="color:rgba(255,255,255,0.6);">
                    <span><i class="fas fa-car mr-1" style="color:#fbbf24;"></i><?php echo count($vehicles); ?> Vehicle<?php echo count($vehicles) !== 1 ? 's' : ''; ?></span>
                </div>
            </div>

            <!-- Vehicle Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8">

                <?php if (empty($vehicles)): ?>
                    <!-- Empty State -->
                    <div class="col-span-full glass-card p-16 text-center anim-up">
                        <div class="w-24 h-24 rounded-[2rem] flex items-center justify-center mx-auto mb-6" style="background:rgba(245,158,11,0.12); border:1px solid rgba(245,158,11,0.3);">
                            <i class="fas fa-car text-5xl" style="color:rgba(251,191,36,0.5);"></i>
                        </div>
                        <h3 class="text-xl font-black mb-3" style="color:white;">No vehicles listed yet</h3>
                        <p class="text-sm mb-8 max-w-sm mx-auto" style="color:rgba(255,255,255,0.5);">Start by adding your first vehicle to the BookingJaunt platform and reach thousands of travelers.</p>
                        <a href="../../property_wizard.php?type=vehicle" class="btn-primary" style="display:inline-flex;">
                            <i class="fas fa-plus"></i> Add Your First Vehicle
                        </a>
                    </div>

                <?php else: ?>
                    <?php foreach ($vehicles as $v): ?>
                    <div class="glass-card overflow-hidden group anim-up" style="border-radius:2rem; padding:0;">

                        <!-- Cover Image -->
                        <div class="h-52 relative overflow-hidden">
                            <img src="../../<?php echo $v['cover_image'] ?: 'assets/placeholder.png'; ?>"
                                 class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                                 alt="<?php echo htmlspecialchars($v['property_name']); ?>">

                            <!-- Gradient Overlay -->
                            <div class="absolute inset-0 flex flex-col justify-end p-5" style="background:linear-gradient(to top, rgba(0,0,0,0.90) 0%, rgba(0,0,0,0.3) 50%, transparent 100%);">
                                <div>
                                    <!-- Vehicle Category Badge -->
                                    <span class="text-[9px] font-black uppercase tracking-widest mb-2 inline-block px-3 py-1 rounded-full" style="background:#f59e0b; color:#000;">
                                        <i class="fas fa-car mr-1"></i><?php echo htmlspecialchars($v['vehicle_category'] ?? 'Vehicle'); ?>
                                    </span>
                                    <h3 class="text-lg font-black leading-tight" style="color:white;"><?php echo htmlspecialchars($v['property_name']); ?></h3>
                                    <p class="text-[10px] font-bold uppercase tracking-widest mt-1" style="color:rgba(255,255,255,0.55);">
                                        <i class="fas fa-map-marker-alt mr-1"></i><?php echo htmlspecialchars($v['district'] ?? $v['city'] ?? 'N/A'); ?>
                                    </p>
                                </div>
                            </div>

                            <!-- Status Badges (top row) -->
                            <div class="absolute top-4 left-4 flex items-center gap-2">
                                <!-- Live Pulse -->
                                <div class="flex items-center gap-2 px-3 py-1.5 rounded-full" style="background:rgba(0,0,0,0.55); backdrop-filter:blur(8px);">
                                    <div class="w-2 h-2 rounded-full bg-green-400" style="animation:pulse 2s infinite;"></div>
                                    <span class="text-[9px] font-bold uppercase tracking-wider" style="color:#86efac;">Live</span>
                                </div>
                                <?php if ($v['is_boosted']): ?>
                                <!-- Boosted Badge -->
                                <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-full" style="background:rgba(245,158,11,0.85); backdrop-filter:blur(8px);">
                                    <i class="fas fa-bolt text-[9px]" style="color:#000;"></i>
                                    <span class="text-[9px] font-black uppercase tracking-wider" style="color:#000;">Boosted</span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-6">

                            <!-- Quick Stats -->
                            <div class="grid grid-cols-3 gap-3 mb-5">
                                <div class="text-center p-3 rounded-xl" style="background:rgba(255,255,255,0.07); border:1px solid rgba(255,255,255,0.1);">
                                    <div class="font-black text-sm" style="color:white;"><?php echo $v['booking_count']; ?></div>
                                    <div class="text-[8px] font-bold uppercase tracking-wider mt-1" style="color:rgba(255,255,255,0.4);">Bookings</div>
                                </div>
                                <div class="text-center p-3 rounded-xl" style="background:rgba(255,255,255,0.07); border:1px solid rgba(255,255,255,0.1);">
                                    <div class="font-black text-xs" style="color:white;">
                                        <?php echo $v['price_per_night'] ? 'LKR ' . number_format($v['price_per_night']) : 'N/A'; ?>
                                    </div>
                                    <div class="text-[8px] font-bold uppercase tracking-wider mt-1" style="color:rgba(255,255,255,0.4);">Per Day</div>
                                </div>
                                <div class="text-center p-3 rounded-xl" style="background:rgba(255,255,255,0.07); border:1px solid rgba(255,255,255,0.1);">
                                    <div class="font-black text-xs" style="color:<?php echo $v['is_boosted'] ? '#fbbf24' : 'rgba(255,255,255,0.4)'; ?>;">
                                        <?php echo $v['is_boosted'] ? 'Yes' : 'No'; ?>
                                    </div>
                                    <div class="text-[8px] font-bold uppercase tracking-wider mt-1" style="color:rgba(255,255,255,0.4);">Boosted</div>
                                </div>
                            </div>

                            <!-- Info Tags -->
                            <div class="flex flex-wrap gap-2 mb-5">
                                <?php if (!empty($v['district'])): ?>
                                <span class="px-3 py-1 rounded-full text-[9px] font-bold uppercase tracking-wider" style="background:rgba(255,255,255,0.08); color:rgba(255,255,255,0.6);">
                                    <i class="fas fa-map-marker-alt mr-1"></i><?php echo htmlspecialchars($v['district']); ?>
                                </span>
                                <?php endif; ?>
                                <?php if (!empty($v['vehicle_category'])): ?>
                                <span class="px-3 py-1 rounded-full text-[9px] font-bold uppercase tracking-wider" style="background:rgba(245,158,11,0.12); color:#fcd34d; border:1px solid rgba(245,158,11,0.25);">
                                    <i class="fas fa-car mr-1"></i><?php echo htmlspecialchars($v['vehicle_category']); ?>
                                </span>
                                <?php endif; ?>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex gap-3">
                                <a href="../../vehicle_details.php?id=<?php echo $v['id']; ?>" class="btn-primary flex-1 justify-center py-3.5" target="_blank">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="../../property_wizard.php?edit=<?php echo $v['id']; ?>" class="btn-glass px-4 py-3.5 justify-center" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if (!$v['is_boosted']): ?>
                                <a href="dashboard.php?view=dashboard&property_id=<?php echo $v['id']; ?>" class="px-4 py-3.5 rounded-xl transition-all flex items-center justify-center" style="background:rgba(245,158,11,0.12); border:1px solid rgba(245,158,11,0.3); color:#fbbf24;" title="Boost">
                                    <i class="fas fa-bolt"></i>
                                </a>
                                <?php else: ?>
                                <div class="px-4 py-3.5 rounded-xl flex items-center justify-center" style="background:rgba(245,158,11,0.18); border:1px solid rgba(245,158,11,0.4); color:#fbbf24;" title="Already Boosted">
                                    <i class="fas fa-bolt"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <!-- Add New Vehicle Card -->
                    <a href="../../property_wizard.php?type=vehicle" class="glass-card flex flex-col items-center justify-center p-12 text-center group cursor-pointer anim-up-3" style="border-radius:2rem; border-style:dashed; min-height:320px; text-decoration:none; transition: all 0.3s;">
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4 transition-all group-hover:scale-110" style="background:rgba(245,158,11,0.12); border:1px solid rgba(245,158,11,0.3);">
                            <i class="fas fa-plus text-2xl" style="color:#fbbf24;"></i>
                        </div>
                        <h3 class="font-black text-lg mb-2" style="color:white;">Add New Vehicle</h3>
                        <p class="text-sm" style="color:rgba(255,255,255,0.4);">List another vehicle on BookingJaunt</p>
                    </a>

                <?php endif; ?>
            </div>
        </div>
    </main>

    <style>
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }
    </style>

    <script>
        async function requestDeletion(id) {
            if (confirm('Are you sure you want to request deletion of this vehicle? This requires admin approval.')) {
                const formData = new FormData();
                formData.append('action', 'request_delete');
                formData.append('property_id', id);
                try {
                    const response = await fetch('../../register.php', { method: 'POST', body: formData });
                    const result = await response.json();
                    alert(result.success ? 'Deletion request sent to admin successfully.' : 'Error: ' + result.message);
                } catch (error) {
                    alert('An error occurred while submitting the request.');
                }
            }
        }
    </script>
</body>
</html>
