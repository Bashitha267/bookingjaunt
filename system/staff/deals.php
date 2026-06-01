<?php
require_once '../../config.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../../login.php'); exit();
}
$current_page = basename($_SERVER['PHP_SELF']);

$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';
$allowed_sorts = ['valid_from','valid_until','deal_price','created_at','original_price'];
if (!in_array($sort, $allowed_sorts)) $sort = 'created_at';
$order = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

$where = '1=1';
$params = [];
if (!empty($search)) {
    $where .= ' AND (p.property_name LIKE ? OR d.room_name LIKE ? OR d.deal_label LIKE ?)';
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}

$deals_stmt = $pdo->prepare("
    SELECT d.*, p.property_name, p.city,
           CASE WHEN d.is_active = 1 AND d.valid_from <= CURDATE() AND d.valid_until >= CURDATE() THEN 'active' ELSE 'ended' END as status
    FROM deals_of_the_day d
    JOIN properties p ON p.id = d.property_id
    WHERE $where
    ORDER BY d.$sort $order
");
$deals_stmt->execute($params);
$deals = $deals_stmt->fetchAll();
$total = count($deals);
$active_count = count(array_filter($deals, fn($d) => $d['status'] === 'active'));
$ended_count = $total - $active_count;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deals of the Day — Staff · BookingJaunt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .main-content { margin-left: 256px; min-height: 100vh; }
        @media (max-width: 1023px) { .main-content { margin-left: 0; } }
        .glass-card { background: rgba(255,255,255,0.10) !important; backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.2) !important; border-radius: 1.5rem; color: #f8fafc; }
        .glass-table { background: rgba(255,255,255,0.08); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.15); border-radius: 1.5rem; overflow: hidden; }
        .glass-th { padding: 12px 20px; font-size: 0.625rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.7); border-bottom: 1px solid rgba(255,255,255,0.08); white-space: nowrap; }
        .glass-td { padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #ffffff; font-size: 0.875rem; }
        .glass-tr:last-child .glass-td { border-bottom: none; }
        .glass-tr:hover { background: rgba(255,255,255,0.05); }
        .glass-input { background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.15); color: #ffffff; border-radius: 0.75rem; padding: 10px 16px; outline: none; }
        .glass-input::placeholder { color: rgba(255,255,255,0.4); }
        .btn-glass { background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 8px 16px; border-radius: 0.75rem; font-size: 0.75rem; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .stat-label { font-size: 0.625rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: rgba(255,255,255,0.6); margin-bottom: 4px; }
        .stat-value { font-size: 1.875rem; font-weight: 800; color: white; }
        .stat-icon { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; margin-bottom: 16px; }
        select option { background: #0f172a; color: white; }
        .sort-link { color: rgba(255,255,255,0.6); text-decoration: none; font-size: 0.625rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; }
        .sort-link:hover { color: #febb02; }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="main-content">
    <header class="glass-header px-6 py-5 flex items-center justify-between">
        <div>
            <h1 class="text-lg font-black" style="color:white;">Deals of the Day</h1>
            <p class="text-xs font-bold uppercase tracking-widest" style="color:rgba(255,255,255,0.45);">Read-only view of all hotel deals</p>
        </div>
        <div class="text-xs font-bold" style="color:rgba(255,255,255,0.45);"><?php echo date('D, M d Y'); ?></div>
    </header>
    <main class="p-6 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="glass-card p-6">
                <div class="stat-icon" style="background:rgba(251,190,36,0.15);"><i class="fas fa-tags" style="color:#febb02;"></i></div>
                <p class="stat-label">Total Deals</p><h3 class="stat-value"><?php echo $total; ?></h3>
            </div>
            <div class="glass-card p-6">
                <div class="stat-icon" style="background:rgba(74,222,128,0.15);"><i class="fas fa-bolt" style="color:#4ade80;"></i></div>
                <p class="stat-label">Active Now</p><h3 class="stat-value" style="color:#4ade80;"><?php echo $active_count; ?></h3>
            </div>
            <div class="glass-card p-6">
                <div class="stat-icon" style="background:rgba(148,163,184,0.15);"><i class="fas fa-clock" style="color:#94a3b8;"></i></div>
                <p class="stat-label">Ended</p><h3 class="stat-value" style="color:#94a3b8;"><?php echo $ended_count; ?></h3>
            </div>
        </div>
        <form method="GET" class="glass-card p-5">
            <div class="flex flex-col md:flex-row gap-3">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2" style="color:rgba(255,255,255,0.4);font-size:0.8rem;"></i>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" class="glass-input w-full" style="padding-left:2.5rem;" placeholder="Search by property, room, or label...">
                </div>
                <div class="flex gap-2 items-center">
                    <select name="sort" class="glass-input" style="width:auto;padding:10px 12px;font-size:0.8rem;font-weight:600;">
                        <option value="created_at" <?php echo $sort==='created_at'?'selected':''; ?>>Sort: Date Added</option>
                        <option value="valid_from" <?php echo $sort==='valid_from'?'selected':''; ?>>Sort: Valid From</option>
                        <option value="valid_until" <?php echo $sort==='valid_until'?'selected':''; ?>>Sort: Valid Until</option>
                        <option value="deal_price" <?php echo $sort==='deal_price'?'selected':''; ?>>Sort: Deal Price</option>
                        <option value="original_price" <?php echo $sort==='original_price'?'selected':''; ?>>Sort: Original Price</option>
                    </select>
                    <select name="order" class="glass-input" style="width:auto;padding:10px 12px;font-size:0.8rem;font-weight:600;">
                        <option value="DESC" <?php echo $order==='DESC'?'selected':''; ?>>↓ Desc</option>
                        <option value="ASC" <?php echo $order==='ASC'?'selected':''; ?>>↑ Asc</option>
                    </select>
                    <button type="submit" class="btn-glass"><i class="fas fa-filter"></i> Filter</button>
                    <?php if ($search): ?><a href="deals.php" class="btn-glass" style="color:#fca5a5;"><i class="fas fa-times"></i></a><?php endif; ?>
                </div>
            </div>
        </form>
        <?php if (empty($deals)): ?>
            <div class="glass-card p-16 text-center"><h3 class="text-lg font-black mb-2" style="color:white;">No Deals Found</h3></div>
        <?php else: ?>
        <div class="glass-table" style="overflow-x:auto;">
            <div class="px-6 py-4" style="border-bottom:1px solid rgba(255,255,255,0.08);">
                <h3 class="font-black" style="color:white;">All Deals (<?php echo $total; ?> total)</h3>
            </div>
            <table class="w-full text-left" style="min-width:900px;">
                <thead><tr>
                    <th class="glass-th">Property</th><th class="glass-th">Room Type</th>
                    <th class="glass-th">Original</th><th class="glass-th">Deal Price</th>
                    <th class="glass-th">Discount</th><th class="glass-th">Label</th>
                    <th class="glass-th"><a href="?sort=valid_from&order=<?php echo $sort==='valid_from'&&$order==='ASC'?'DESC':'ASC'; ?>&search=<?php echo urlencode($search); ?>" class="sort-link">Valid From <?php echo $sort==='valid_from'?($order==='ASC'?'↑':'↓'):''; ?></a></th>
                    <th class="glass-th"><a href="?sort=valid_until&order=<?php echo $sort==='valid_until'&&$order==='ASC'?'DESC':'ASC'; ?>&search=<?php echo urlencode($search); ?>" class="sort-link">Valid Until <?php echo $sort==='valid_until'?($order==='ASC'?'↑':'↓'):''; ?></a></th>
                    <th class="glass-th">Status</th>
                </tr></thead>
                <tbody>
                    <?php foreach ($deals as $deal):
                        $disc = round((($deal['original_price'] - $deal['deal_price']) / $deal['original_price']) * 100);
                        $is_active = ($deal['status'] === 'active');
                    ?>
                    <tr class="glass-tr">
                        <td class="glass-td"><div class="font-bold"><?php echo htmlspecialchars($deal['property_name']); ?></div><div style="color:rgba(255,255,255,0.45);font-size:0.75rem;"><?php echo htmlspecialchars($deal['city']); ?></div></td>
                        <td class="glass-td"><?php echo htmlspecialchars($deal['room_name']); ?></td>
                        <td class="glass-td" style="color:#94a3b8;text-decoration:line-through;">LKR <?php echo number_format($deal['original_price']); ?></td>
                        <td class="glass-td"><span style="color:#4ade80;font-weight:800;">LKR <?php echo number_format($deal['deal_price']); ?></span></td>
                        <td class="glass-td"><span style="background:rgba(251,190,36,0.2);color:#febb02;padding:3px 10px;border-radius:999px;font-size:0.65rem;font-weight:800;">-<?php echo $disc; ?>%</span></td>
                        <td class="glass-td" style="color:#94a3b8;"><?php echo htmlspecialchars($deal['deal_label'] ?: '—'); ?></td>
                        <td class="glass-td" style="color:#94a3b8;"><?php echo date('M d, Y', strtotime($deal['valid_from'])); ?></td>
                        <td class="glass-td" style="color:#94a3b8;"><?php echo date('M d, Y', strtotime($deal['valid_until'])); ?></td>
                        <td class="glass-td">
                            <?php if ($is_active): ?><span style="background:rgba(74,222,128,0.2);color:#4ade80;padding:3px 12px;border-radius:999px;font-size:0.65rem;font-weight:800;text-transform:uppercase;">Active</span>
                            <?php else: ?><span style="background:rgba(148,163,184,0.2);color:#94a3b8;padding:3px 12px;border-radius:999px;font-size:0.65rem;font-weight:800;text-transform:uppercase;">Ended</span><?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
