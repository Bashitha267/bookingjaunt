<?php
require_once '../../config.php';
require_once '../auth_guard.php';
requireRole(['manager']);

$search = trim($_GET['search'] ?? '');
$type = trim($_GET['type'] ?? '');
$params = [];
$where = [];

if ($search !== '') {
    $like = '%' . $search . '%';
    $where[] = "(p.property_name LIKE ? OR u.email LIKE ? OR p.contact_number LIKE ? OR p.mobile_telephone LIKE ? OR p.fixed_telephone LIKE ?)";
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}

$business_types = ['hotel', 'reception_hall', 'hostel', 'rest_hall', 'villa', 'dayouts', 'safari', 'resort', 'apartment', 'vehicle'];
if ($type !== '' && in_array($type, $business_types, true)) {
    $where[] = "p.business_type = ?";
    $params[] = $type;
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT p.id, p.property_name, p.contact_number, p.mobile_telephone, p.fixed_telephone, p.business_type, u.email AS owner_email
        FROM properties p
        JOIN users u ON p.owner_id = u.id
        $where_sql
        ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$properties = $stmt->fetchAll();

function format_contact_number($row) {
    $contact = trim((string)($row['contact_number'] ?? ''));
    $mobile = trim((string)($row['mobile_telephone'] ?? ''));
    $fixed = trim((string)($row['fixed_telephone'] ?? ''));
    if ($contact !== '') return $contact;
    if ($mobile !== '') return $mobile;
    if ($fixed !== '') return $fixed;
    return 'N/A';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Properties - Manager Console</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="flex min-h-screen overflow-hidden">
    
    <?php include 'sidebar.php'; ?>

    <main class="flex-1 lg:ml-64 overflow-y-auto h-screen relative z-10">
        <header class="glass-header sticky top-0 z-40 px-4 lg:px-8 py-6 flex items-center gap-4">
            <button onclick="toggleSidebar()" class="lg:hidden w-10 h-10 bg-white/10 rounded-xl flex items-center justify-center text-white hover:bg-white/20 transition-all">
                <i class="fas fa-bars-staggered"></i>
            </button>
            <div class="flex flex-col gap-1">
                <h1 class="text-2xl font-black text-white">Property Directory</h1>
                <p class="text-xs text-sky-300 font-bold uppercase tracking-widest hidden sm:block">View and Manage All Properties</p>
            </div>
        </header>

        <div class="p-4 lg:p-8 space-y-6">
            <div class="glass-card p-6">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <div class="md:col-span-2">
                        <label class="text-[10px] font-bold uppercase tracking-widest text-sky-300">Search</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name, owner email, contact number" class="mt-2 w-full px-4 py-2 custom-input">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-widest text-sky-300">Type</label>
                        <select name="type" class="mt-2 w-full px-4 py-2 custom-input">
                            <option value="" class="text-black">All Types</option>
                            <?php foreach ($business_types as $item): ?>
                                <option value="<?php echo $item; ?>" class="text-black" <?php echo $type === $item ? 'selected' : ''; ?>><?php echo ucwords(str_replace('_', ' ', $item)); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex gap-3">
                        <button class="px-5 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white text-xs font-bold uppercase tracking-widest transition-all">Search</button>
                        <a href="properties.php" class="px-5 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold uppercase tracking-widest transition-all">Reset</a>
                    </div>
                </form>
            </div>

            <div class="glass-card overflow-hidden">
                <div class="px-6 py-4 border-b border-white/10 flex items-center justify-between">
                    <h2 class="font-bold text-white">Registered Properties</h2>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-sky-300">Total <?php echo count($properties); ?></span>
                </div>

                <?php if (empty($properties)): ?>
                    <div class="p-10 text-center">
                        <div class="w-16 h-16 bg-white/10 text-sky-300 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-hotel text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-white">No properties found</h3>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr>
                                    <th class="px-6 py-4">Property Name</th>
                                    <th class="px-6 py-4">Owner Email</th>
                                    <th class="px-6 py-4">Contact</th>
                                    <th class="px-6 py-4">Type</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($properties as $property): ?>
                                    <tr>
                                        <td class="px-6 py-4 text-sm font-semibold text-white">
                                            <?php echo htmlspecialchars($property['property_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-300">
                                            <?php echo htmlspecialchars($property['owner_email']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-300">
                                            <?php echo htmlspecialchars(format_contact_number($property)); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-sky-300 font-bold uppercase text-[10px] tracking-wider">
                                            <?php echo htmlspecialchars(str_replace('_', ' ', $property['business_type'])); ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="property_details.php?id=<?php echo (int)$property['id']; ?>" class="px-3 py-2 rounded-lg text-[10px] font-bold uppercase tracking-widest bg-white/10 hover:bg-white/20 text-white transition-all">
                                                Manage
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
