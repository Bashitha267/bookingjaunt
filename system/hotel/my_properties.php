<?php
require_once '../../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM properties WHERE owner_id = ?");
$stmt->execute([$user_id]);
$properties = $stmt->fetchAll();

$property = $properties[0] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Properties - Bookingjaunt</title>
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
                <h1 class="text-lg font-black" style="color:white;">My Properties</h1>
            </div>
            <div class="flex items-center gap-3">
                <a href="../../index.php" target="_blank" class="btn-glass hidden xl:flex"><i class="fas fa-external-link-alt"></i> Visit Site</a>
                <a href="../../property_wizard.php" class="btn-primary">
                    <i class="fas fa-plus"></i>
                    <span class="hidden sm:inline">Add Property</span>
                </a>
            </div>
        </header>

        <div class="py-4 px-2 lg:py-8 lg:px-4">

            <div class="mb-8 anim-up">
                <p class="text-sm font-medium" style="color:var(--text-secondary);">Manage and update your registered properties</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-8">

                <?php if (empty($properties)): ?>
                    <div class="col-span-full glass-card p-16 text-center anim-up">
                        <div class="w-20 h-20 rounded-[2rem] flex items-center justify-center mx-auto mb-6" style="background:rgba(255,255,255,0.08); border:1px solid var(--glass-border);">
                            <i class="fas fa-hotel text-4xl" style="color:var(--text-muted);"></i>
                        </div>
                        <h3 class="text-lg font-bold mb-2" style="color:white;">No properties found</h3>
                        <p class="text-sm mb-8" style="color:var(--text-secondary);">Start by adding your first property to the platform.</p>
                        <a href="../../property_wizard.php" class="btn-primary">
                            <i class="fas fa-plus"></i> Add Property
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($properties as $prop): ?>
                    <div class="glass-card overflow-hidden group" style="border-radius:2rem; padding:0;">
                        <!-- Cover Image -->
                        <div class="h-56 relative overflow-hidden">
                            <img src="../../<?php echo $prop['cover_image'] ?: 'assets/placeholder.png'; ?>"
                                 class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                                 alt="<?php echo htmlspecialchars($prop['property_name']); ?>">
                            <!-- Gradient Overlay -->
                            <div class="absolute inset-0 flex flex-col justify-end p-6" style="background:linear-gradient(to top, rgba(0,0,0,0.88) 0%, rgba(0,0,0,0.35) 50%, transparent 100%);">
                                <div>
                                    <span class="text-[9px] font-black uppercase tracking-widest mb-2 inline-block px-3 py-1 rounded-full" style="background:var(--accent); color:#003580;">
                                        <?php echo str_replace('_', ' ', $prop['business_type']); ?>
                                    </span>
                                    <h3 class="text-xl font-black" style="color:white;"><?php echo htmlspecialchars($prop['property_name']); ?></h3>
                                    <p class="text-[10px] font-bold uppercase tracking-widest mt-1" style="color:rgba(255,255,255,0.55);">
                                        <i class="fas fa-map-marker-alt mr-1"></i><?php echo htmlspecialchars($prop['city']); ?>
                                    </p>
                                </div>
                            </div>
                            <!-- Live Pulse -->
                            <div class="absolute top-4 right-4 flex items-center gap-2 px-3 py-1.5 rounded-full" style="background:rgba(0,0,0,0.5); backdrop-filter:blur(8px);">
                                <div class="w-2 h-2 rounded-full bg-green-400" style="animation:pulse 2s infinite;"></div>
                                <span class="text-[9px] font-bold uppercase tracking-wider" style="color:#86efac;">Live</span>
                            </div>
                        </div>

                        <!-- Body -->
                        <div class="p-6">
                            <!-- Quick Stats -->
                            <div class="grid grid-cols-3 gap-3 mb-6">
                                <?php
                                $bk_stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE property_id = ?");
                                $bk_stmt->execute([$prop['id']]);
                                $bk_count = $bk_stmt->fetchColumn();

                                $rm_stmt = $pdo->prepare("SELECT SUM(total_rooms) FROM property_rooms WHERE property_id = ?");
                                $rm_stmt->execute([$prop['id']]);
                                $rm_count = $rm_stmt->fetchColumn() ?: 0;

                                $rv_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_paid),0) FROM bookings WHERE property_id = ?");
                                $rv_stmt->execute([$prop['id']]);
                                $rv_total = $rv_stmt->fetchColumn();
                                ?>
                                <div class="text-center p-3 rounded-xl" style="background:rgba(255,255,255,0.07); border:1px solid var(--glass-border);">
                                    <div class="font-black" style="color:white;"><?php echo $bk_count; ?></div>
                                    <div class="text-[8px] font-bold uppercase tracking-wider mt-1" style="color:var(--text-muted);">Bookings</div>
                                </div>
                                <div class="text-center p-3 rounded-xl" style="background:rgba(255,255,255,0.07); border:1px solid var(--glass-border);">
                                    <div class="font-black" style="color:white;"><?php echo $rm_count; ?></div>
                                    <div class="text-[8px] font-bold uppercase tracking-wider mt-1" style="color:var(--text-muted);">Rooms</div>
                                </div>
                                <div class="text-center p-3 rounded-xl" style="background:rgba(255,255,255,0.07); border:1px solid var(--glass-border);">
                                    <div class="font-black text-xs" style="color:white;"><?php echo number_format($rv_total/1000, 0); ?>K</div>
                                    <div class="text-[8px] font-bold uppercase tracking-wider mt-1" style="color:var(--text-muted);">Revenue</div>
                                </div>
                            </div>

                            <!-- Info Pills -->
                            <div class="flex flex-wrap gap-2 mb-6">
                                <span class="px-3 py-1 rounded-full text-[9px] font-bold uppercase tracking-wider" style="background:rgba(255,255,255,0.08); color:var(--text-secondary);">
                                    <i class="fas fa-map-marker-alt mr-1"></i><?php echo htmlspecialchars($prop['city']); ?>
                                </span>
                                <span class="px-3 py-1 rounded-full text-[9px] font-bold uppercase tracking-wider" style="background:rgba(255,255,255,0.08); color:var(--text-secondary);">
                                    <i class="fas fa-star mr-1" style="color:#fbbf24;"></i>
                                    <?php
                                    $avg_s = $pdo->prepare("SELECT AVG(rating) FROM reviews WHERE property_id = ? AND status='published'");
                                    $avg_s->execute([$prop['id']]);
                                    echo number_format($avg_s->fetchColumn() ?: 0, 1);
                                    ?> Rating
                                </span>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-3">
                                <a href="../../property_wizard.php?edit=<?php echo $prop['id']; ?>" class="btn-primary flex-1 justify-center py-3.5">
                                    <i class="fas fa-edit"></i> Edit Property
                                </a>
                                <a href="dashboard.php?property_id=<?php echo $prop['id']; ?>" class="btn-glass px-4 py-3.5 justify-center">
                                    <i class="fas fa-chart-line"></i>
                                </a>
                                <button onclick="requestDeletion(<?php echo $prop['id']; ?>)" class="px-4 py-3.5 rounded-xl transition-all" style="background:rgba(248,113,113,0.1); border:1px solid rgba(248,113,113,0.25); color:#fca5a5;">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <!-- Add New Property Card -->
                    <a href="../../property_wizard.php" class="glass-card flex flex-col items-center justify-center p-12 text-center group cursor-pointer anim-up-3" style="border-radius:2rem; border-style:dashed; min-height:300px; text-decoration:none; transition: all 0.3s;">
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4 transition-all group-hover:scale-110" style="background:rgba(0,108,228,0.15); border:1px solid rgba(0,108,228,0.3);">
                            <i class="fas fa-plus text-2xl" style="color:#93c5fd;"></i>
                        </div>
                        <h3 class="font-black text-lg mb-2" style="color:white;">Add New Property</h3>
                        <p class="text-sm" style="color:var(--text-secondary);">List another hotel or property on BookingJaunt</p>
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
            if (confirm('Are you sure you want to request deletion of this property? This requires admin approval and will take effect once reviewed.')) {
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
