<?php
require_once '../../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$selected_property_id = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;

if ($_SESSION['role'] === 'admin') {
    $properties_stmt = $pdo->query("SELECT id, property_name FROM properties ORDER BY property_name");
    $properties = $properties_stmt->fetchAll();
} else {
    $properties_stmt = $pdo->prepare("SELECT id, property_name FROM properties WHERE owner_id = ? ORDER BY property_name");
    $properties_stmt->execute([$user_id]);
    $properties = $properties_stmt->fetchAll();
}

$property = null;
$property_id = 0;
if ($selected_property_id) {
    if ($_SESSION['role'] === 'admin') {
        $stmt = $pdo->prepare("SELECT id, property_name FROM properties WHERE id = ? LIMIT 1");
        $stmt->execute([$selected_property_id]);
        $property = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare("SELECT id, property_name FROM properties WHERE id = ? AND owner_id = ? LIMIT 1");
        $stmt->execute([$selected_property_id, $user_id]);
        $property = $stmt->fetch();
    }
}

if (!$property && !empty($properties)) {
    $first_property_id = (int)$properties[0]['id'];
    if ($_SESSION['role'] === 'admin') {
        $stmt = $pdo->prepare("SELECT id, property_name FROM properties WHERE id = ? LIMIT 1");
        $stmt->execute([$first_property_id]);
        $property = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare("SELECT id, property_name FROM properties WHERE id = ? AND owner_id = ? LIMIT 1");
        $stmt->execute([$first_property_id, $user_id]);
        $property = $stmt->fetch();
    }
}

$property_id = $property['id'] ?? 0;
if (!$property_id && $_SESSION['role'] !== 'admin') die("Property not found.");

$search = trim($_GET['search'] ?? '');
$type   = $_GET['type']   ?? '';
$rating = $_GET['rating'] ?? '';

$where  = ["r.property_id = ?", "r.status IN ('published', 'reported')"];
$params = [$property_id];

if ($search !== '') {
    $like = '%' . $search . '%';
    $where[] = "(CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR u.phone_number LIKE ? OR b.guest_name LIKE ? OR b.guest_phone LIKE ? OR r.comment LIKE ?)";
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}
if ($type   !== '') { $where[] = "r.feedback_type = ?"; $params[] = $type; }
if ($rating !== '') { $where[] = "r.rating = ?";        $params[] = (int)$rating; }

$where_sql = 'WHERE ' . implode(' AND ', $where);
$sql = "SELECT r.*, u.first_name, u.last_name, u.phone_number, b.guest_name, b.guest_phone
        FROM reviews r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN bookings b ON r.booking_id = b.id
        $where_sql ORDER BY r.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reviews = $stmt->fetchAll();

function h($v) { return htmlspecialchars((string)$v); }

// Rating summary
$avg_stmt = $pdo->prepare("SELECT AVG(r.rating) AS avg_rating, COUNT(*) AS total FROM reviews r WHERE r.property_id = ? AND r.status IN ('published','reported')");
$avg_stmt->execute([$property_id]);
$avg_row = $avg_stmt->fetch();
$avg_rating = round($avg_row['avg_rating'] ?? 0, 1);
$total_reviews = $avg_row['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedbacks - Bookingjaunt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="flex min-h-screen overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <main class="main-content overflow-y-auto" style="position:relative; z-index:1;">

        <header class="glass-header sticky top-0 z-40 px-2 lg:px-4 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden w-10 h-10 rounded-xl btn-glass flex items-center justify-center"><i class="fas fa-bars-staggered"></i></button>
                <form method="GET" class="hidden sm:block">
                    <label class="sr-only" for="propertySelect">Property</label>
                    <select id="propertySelect" name="property_id" onchange="this.form.submit()" class="glass-input px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest">
                        <?php foreach ($properties as $prop): ?>
                            <option value="<?php echo (int)$prop['id']; ?>" <?php echo (int)$prop['id'] === (int)$property_id ? 'selected' : ''; ?>>
                                <?php echo h($prop['property_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <a href="../../index.php" target="_blank" class="btn-glass hidden xl:flex"><i class="fas fa-external-link-alt"></i> Visit Site</a>
        </header>

        <div class="py-4 px-2 lg:py-8 lg:px-4 space-y-6">

            <!-- Header Row -->
            <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 anim-up">
                <div>
                    <h1 class="text-2xl font-black" style="color:white;">Feedbacks</h1>
                    <p class="text-sm mt-1" style="color:var(--text-secondary);">Published or reported feedback for <?php echo h($property['property_name'] ?? 'your property'); ?></p>
                </div>
                <!-- Rating Summary Badge -->
                <div class="glass-card flex items-center gap-4 px-6 py-4" style="min-width:200px;">
                    <div class="text-center">
                        <div class="text-3xl font-black" style="color:#fbbf24;"><?php echo number_format($avg_rating, 1); ?></div>
                        <div class="text-[9px] font-bold uppercase tracking-widest mt-1" style="color:var(--text-muted);">Avg Rating</div>
                    </div>
                    <div class="w-px h-10" style="background:var(--glass-border);"></div>
                    <div>
                        <div style="color:white;">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <i class="fas fa-star text-xs" style="color:<?php echo $s <= round($avg_rating) ? '#fbbf24' : 'rgba(255,255,255,0.2)'; ?>;"></i>
                            <?php endfor; ?>
                        </div>
                        <div class="text-[10px] font-bold mt-1" style="color:var(--text-muted);"><?php echo $total_reviews; ?> reviews</div>
                    </div>
                </div>
            </div>

            <!-- Filter Panel -->
            <div class="glass-card p-6 anim-up-2">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                    <input type="hidden" name="property_id" value="<?php echo (int)$property_id; ?>">
                    <div class="lg:col-span-2">
                        <label class="text-[10px] font-bold uppercase tracking-widest" style="color:var(--text-muted);">Search</label>
                        <div class="relative mt-2">
                            <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-xs" style="color:var(--text-muted);"></i>
                            <input type="text" name="search" value="<?php echo h($search); ?>" placeholder="Guest name, phone or comment" class="glass-input w-full pl-10 pr-4 py-2 rounded-xl text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-widest" style="color:var(--text-muted);">Type</label>
                        <select name="type" class="glass-input mt-2 w-full px-4 py-2 rounded-xl text-sm">
                            <option value="">All</option>
                            <option value="positive" <?php echo $type === 'positive' ? 'selected' : ''; ?>>Positive</option>
                            <option value="negative" <?php echo $type === 'negative' ? 'selected' : ''; ?>>Negative</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-widest" style="color:var(--text-muted);">Rating</label>
                        <select name="rating" class="glass-input mt-2 w-full px-4 py-2 rounded-xl text-sm">
                            <option value="">All</option>
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo (string)$rating === (string)$i ? 'selected' : ''; ?>><?php echo $i; ?> Star</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button class="btn-primary flex-1 justify-center py-2"><i class="fas fa-filter"></i> Apply</button>
                        <a href="feedbacks.php?property_id=<?php echo (int)$property_id; ?>" class="btn-glass flex-1 justify-center py-2"><i class="fas fa-times"></i></a>
                    </div>
                </form>
            </div>

            <!-- Feedback Cards / Table -->
            <?php if (empty($reviews)): ?>
            <div class="glass-card p-16 text-center anim-up">
                <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background:rgba(96,165,250,0.1);">
                    <i class="fas fa-comment-dots text-2xl" style="color:#93c5fd;"></i>
                </div>
                <h3 class="text-lg font-bold" style="color:white;">No feedback yet</h3>
                <p class="text-xs mt-2" style="color:var(--text-muted);">Published feedback will appear here.</p>
            </div>
            <?php else: ?>
            <div class="space-y-4 anim-up">
                <?php foreach ($reviews as $review):
                    $is_negative  = ($review['feedback_type'] ?? '') === 'negative';
                    $guest_name   = trim(($review['first_name'] ?? '') . ' ' . ($review['last_name'] ?? ''));
                    $guest_name   = $guest_name !== '' ? $guest_name : ($review['guest_name'] ?? 'Guest');
                    $rating_val   = (int)($review['rating'] ?? 0);
                    $accent_color = $is_negative ? 'rgba(248,113,113,0.3)' : 'rgba(74,222,128,0.25)';
                    $accent_glow  = $is_negative ? 'rgba(248,113,113,0.08)' : 'rgba(74,222,128,0.05)';
                ?>
                <div class="glass-card p-6" style="border-color:<?php echo $accent_color; ?>; background:<?php echo $accent_glow; ?>;">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-4">
                        <!-- Avatar -->
                        <div class="w-11 h-11 rounded-xl flex items-center justify-center font-black text-sm flex-shrink-0" style="background:rgba(255,255,255,0.10); color:white; border:1px solid var(--glass-border);">
                            <?php echo strtoupper(substr($guest_name, 0, 1)); ?>
                        </div>
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-3 mb-2">
                                <span class="font-bold" style="color:white;"><?php echo h($guest_name); ?></span>
                                <span class="text-[10px]" style="color:var(--text-muted);"><?php echo h($review['phone_number'] ?? $review['guest_phone'] ?? ''); ?></span>
                                <!-- Stars -->
                                <div>
                                    <?php for ($s = 1; $s <= 5; $s++): ?>
                                        <i class="fas fa-star text-xs" style="color:<?php echo $s <= $rating_val ? '#fbbf24' : 'rgba(255,255,255,0.15)'; ?>;"></i>
                                    <?php endfor; ?>
                                </div>
                                <!-- Type Badge -->
                                <span class="badge <?php echo $is_negative ? 'badge-cancelled' : 'badge-confirmed'; ?>">
                                    <?php echo h($review['feedback_type'] ?? 'positive'); ?>
                                </span>
                                <!-- Status -->
                                <span class="badge <?php echo ($review['status'] ?? '') === 'reported' ? 'badge-pending' : 'badge-confirmed'; ?>">
                                    <?php echo h($review['status'] ?? 'published'); ?>
                                </span>
                                <!-- Date -->
                                <span class="text-[10px] ml-auto" style="color:var(--text-muted);"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                            </div>
                            <p class="text-sm leading-relaxed" style="color:var(--text-secondary);"><?php echo nl2br(h($review['comment'] ?? '')); ?></p>
                            <?php if (!empty($review['report_note'])): ?>
                                <p class="text-xs font-bold mt-2" style="color:#fca5a5;"><i class="fas fa-flag mr-1"></i>Report: <?php echo h($review['report_note']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
