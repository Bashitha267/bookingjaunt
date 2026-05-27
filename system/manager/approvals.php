<?php
require_once '../../config.php';
require_once '../auth_guard.php';
requireRole(['manager']);

// Handle Approval/Rejection
if (isset($_POST['action'])) {
    $request_id = $_POST['request_id'];
    $status = $_POST['status']; // 'approved' or 'rejected'
    $notes = $_POST['admin_notes'] ?? '';

    try {
        $pdo->beginTransaction();

        // Fetch the request
        $stmt = $pdo->prepare("SELECT * FROM property_requests WHERE id = ?");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch();

        if ($request) {
            if ($status === 'approved') {
                if ($request['request_type'] === 'delete') {
                    // Delete Property
                    $stmt = $pdo->prepare("DELETE FROM properties WHERE id = ?");
                    $stmt->execute([$request['property_id']]);
                } elseif ($request['request_type'] === 'edit') {
                    $new_data = json_decode($request['new_data'], true);
                    if ($new_data) {
                        $property_id = $request['property_id'];
                        
                        // Update full property info
                        $stmt = $pdo->prepare("UPDATE properties SET 
                            property_name = ?, description = ?, street_address = ?, city = ?, district = ?, province = ?, country = ?, google_map_location = ?, fixed_telephone = ?, mobile_telephone = ?, closest_police_station = ?, closest_hospital = ?, airport_distance = ?, closest_main_town = ?, postal_code = ?, hotel_category = ?, manager_name = ?, manager_phone = ?, manager_nic = ?, contact_number = ?, business_email = ?, bank_name = ?, bank_branch = ?, bank_account_name = ?, bank_account_number = ?, commission_rate = ?, check_in_time = ?, check_out_time = ?, cancellation_policy = ?, smoking_allowed = ?, pets_allowed = ?, events_allowed = ?, rules_json = ?, popular_amenities_json = ?, custom_rules_json = ?, logo_image = ?, cover_image = ?, manager_photo = ?
                            WHERE id = ?");
                            
                        $stmt->execute([
                            $new_data['property_name'] ?? '', $new_data['description'] ?? '', $new_data['street_address'] ?? '', $new_data['city'] ?? '', $new_data['district'] ?? '', $new_data['province'] ?? '', $new_data['country'] ?? '', $new_data['google_map_location'] ?? '', $new_data['fixed_telephone'] ?? '', $new_data['mobile_telephone'] ?? '', $new_data['closest_police_station'] ?? '', $new_data['closest_hospital'] ?? '', $new_data['airport_distance'] ?? '', $new_data['closest_main_town'] ?? '', $new_data['postal_code'] ?? '', $new_data['hotel_category'] ?? null, $new_data['manager_name'] ?? '', $new_data['manager_phone'] ?? '', $new_data['manager_nic'] ?? '', $new_data['contact_number'] ?? '', $new_data['business_email'] ?? '', $new_data['bank_name'] ?? null, $new_data['bank_branch'] ?? null, $new_data['bank_account_name'] ?? null, $new_data['bank_account_number'] ?? null, $new_data['commission_rate'] ?? 80, $new_data['check_in_time'] ?? '14:00', $new_data['check_out_time'] ?? '12:00', $new_data['cancellation_policy'] ?? '', ($new_data['smoking_allowed'] ?? '0') == '1' ? 1 : 0, ($new_data['pets_allowed'] ?? '0') == '1' ? 1 : 0, ($new_data['events_allowed'] ?? '0') == '1' ? 1 : 0, json_encode($new_data['rules'] ?? []), json_encode($new_data['popular_amenities'] ?? []), json_encode($new_data['custom_rules'] ?? []), $new_data['logo_image'] ?? '', $new_data['cover_image'] ?? '', $new_data['manager_photo'] ?? '', $property_id
                        ]);

                        // Update Media (Photos/Videos)
                        $pdo->prepare("DELETE FROM property_media WHERE property_id = ?")->execute([$property_id]);
                        if (isset($new_data['property_photos']) && is_array($new_data['property_photos'])) {
                            $stmt = $pdo->prepare("INSERT INTO property_media (property_id, media_path, media_type) VALUES (?, ?, 'image')");
                            foreach ($new_data['property_photos'] as $photo) {
                                if (!empty($photo)) $stmt->execute([$property_id, $photo]);
                            }
                        }
                        if (isset($new_data['property_videos']) && is_array($new_data['property_videos'])) {
                            $stmt = $pdo->prepare("INSERT INTO property_media (property_id, media_path, media_type) VALUES (?, ?, 'video')");
                            foreach ($new_data['property_videos'] as $video) {
                                if (!empty($video)) $stmt->execute([$property_id, $video]);
                            }
                        }

                        // Update Rooms (Safely)
                        if (isset($new_data['rooms']) && is_array($new_data['rooms'])) {
                            $current_room_ids = [];
                            foreach ($new_data['rooms'] as $room) {
                                if (!empty($room['name'])) {
                                    $check = $pdo->prepare("SELECT id FROM property_rooms WHERE property_id = ? AND room_name = ?");
                                    $check->execute([$property_id, $room['name']]);
                                    $existing = $check->fetch();

                                    if ($existing) {
                                        $room_id = $existing['id'];
                                        $current_room_ids[] = $room_id;
                                        $stmt = $pdo->prepare("UPDATE property_rooms SET adults = ?, children = ?, price_lkr = ?, price_usd = ?, room_image = ?, total_rooms = ?, room_numbers = ? WHERE id = ?");
                                        $stmt->execute([$room['adults'] ?? 2, $room['children'] ?? 0, $room['price_lkr'] ?? 0, $room['price_usd'] ?? 0, $room['image'] ?? '', $room['count'] ?? 1, $room['room_numbers'] ?? '', $room_id]);
                                    } else {
                                        $stmt = $pdo->prepare("INSERT INTO property_rooms (property_id, room_name, adults, children, price_lkr, price_usd, room_image, total_rooms, room_numbers) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                        $stmt->execute([$property_id, $room['name'], $room['adults'] ?? 2, $room['children'] ?? 0, $room['price_lkr'] ?? 0, $room['price_usd'] ?? 0, $room['image'] ?? '', $room['count'] ?? 1, $room['room_numbers'] ?? '']);
                                        $current_room_ids[] = $pdo->lastInsertId();
                                    }
                                }
                            }
                            
                            if (!empty($current_room_ids)) {
                                $placeholders = implode(',', array_fill(0, count($current_room_ids), '?'));
                                $stmt = $pdo->prepare("DELETE FROM property_rooms WHERE property_id = ? AND id NOT IN ($placeholders)");
                                $params = array_merge([$property_id], $current_room_ids);
                                $stmt->execute($params);
                            }
                        }
                    }
                }
            }

            // Update request status
            $stmt = $pdo->prepare("UPDATE property_requests SET status = ?, admin_notes = ? WHERE id = ?");
            $stmt->execute([$status, $notes, $request_id]);
        }

        $pdo->commit();
        header("Location: approvals.php?success=1");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Fetch Pending Requests
$stmt = $pdo->query("SELECT r.*, p.property_name, u.first_name, u.last_name 
                     FROM property_requests r 
                     JOIN properties p ON r.property_id = p.id 
                     JOIN users u ON r.user_id = u.id 
                     WHERE r.status = 'pending' 
                     ORDER BY r.created_at DESC");
$requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approvals - Manager Console</title>
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
            <div class="flex items-center gap-4 flex-1">
                <div class="w-12 h-12 bg-orange-500/20 text-orange-400 rounded-xl flex items-center justify-center text-xl shadow-inner border border-orange-500/30">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div class="flex flex-col gap-1">
                    <h1 class="text-2xl font-black text-white">Pending Approvals</h1>
                    <p class="text-xs text-sky-300 font-bold uppercase tracking-widest hidden sm:block">Review property edit requests</p>
                </div>
            </div>
            <span class="text-[11px] font-bold uppercase tracking-widest text-white bg-orange-600 px-4 py-2 rounded-xl shadow-lg shadow-orange-900/50">
                Pending: <?php echo count($requests); ?>
            </span>
        </header>

        <div class="p-4 lg:p-8">
            <?php if (isset($_GET['success'])): ?>
                <div class="bg-emerald-500/20 border border-emerald-500/30 text-emerald-300 px-4 py-3 rounded-xl font-bold mb-6">
                    <i class="fas fa-check-circle mr-2"></i> Action completed successfully.
                </div>
            <?php endif; ?>

            <?php if (empty($requests)): ?>
                <div class="glass-card p-16 text-center">
                    <div class="w-20 h-20 bg-white/10 text-sky-400 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-check-double text-3xl"></i>
                    </div>
                    <h2 class="text-xl font-bold text-white mb-2">No Pending Requests</h2>
                    <p class="text-sky-300/70 text-sm">Everything is up to date. Good job!</p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($requests as $req): ?>
                        <div class="glass-card overflow-hidden">
                            <div class="p-6 md:p-8 flex flex-col md:flex-row justify-between gap-6">
                                <div class="flex gap-6">
                                    <div class="w-16 h-16 <?php echo $req['request_type'] === 'delete' ? 'bg-red-500/20 text-red-400 border border-red-500/30' : 'bg-orange-500/20 text-orange-400 border border-orange-500/30'; ?> rounded-2xl flex flex-col items-center justify-center flex-shrink-0">
                                        <i class="fas <?php echo $req['request_type'] === 'delete' ? 'fa-trash-alt' : 'fa-edit'; ?> text-xl mb-1"></i>
                                        <span class="text-[8px] font-black uppercase tracking-tighter"><?php echo $req['request_type']; ?></span>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-black text-white"><?php echo htmlspecialchars($req['property_name']); ?></h3>
                                        <p class="text-xs font-bold text-sky-300 uppercase tracking-widest mb-3">Requested by: <?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></p>
                                        
                                        <?php if ($req['request_type'] === 'edit'): 
                                            $old_data = json_decode($req['old_data'], true) ?: [];
                                            $new_data = json_decode($req['new_data'], true) ?: [];
                                            $changes = [];
                                            foreach ($new_data as $key => $value) {
                                                if (isset($old_data[$key])) {
                                                    if ($old_data[$key] != $value) {
                                                        $changes[$key] = ['old' => $old_data[$key], 'new' => $value];
                                                    }
                                                } else {
                                                    $changes[$key] = ['old' => '(Not Set)', 'new' => $value];
                                                }
                                            }
                                        ?>
                                            <div class="bg-white/5 rounded-2xl p-6 border border-white/10 mb-4 max-w-3xl">
                                                <div class="flex items-center justify-between mb-4">
                                                    <p class="text-[10px] font-black text-sky-300 uppercase tracking-widest">Change Comparison</p>
                                                    <span class="bg-sky-500/20 text-sky-300 border border-sky-500/30 text-[9px] font-black px-2 py-0.5 rounded-full uppercase"><?php echo count($changes); ?> Fields Changed</span>
                                                </div>
                                                
                                                <div class="space-y-4">
                                                    <?php foreach ($changes as $field => $data): 
                                                        if (is_array($data['new'])) continue;
                                                    ?>
                                                        <div class="border-b border-white/10 pb-3 last:border-0 last:pb-0">
                                                            <p class="text-[10px] font-bold text-gray-400 uppercase mb-1"><?php echo str_replace('_', ' ', $field); ?></p>
                                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                                                                <div class="relative pl-4 border-l-2 border-red-400/50">
                                                                    <span class="absolute -left-[7px] top-1/2 -translate-y-1/2 w-3 h-3 bg-red-500/20 text-red-400 rounded-full flex items-center justify-center text-[8px]"><i class="fas fa-minus"></i></span>
                                                                    <div class="text-gray-400 line-through opacity-60"><?php echo htmlspecialchars((string)$data['old']); ?></div>
                                                                </div>
                                                                <div class="relative pl-4 border-l-2 border-green-400/50">
                                                                    <span class="absolute -left-[7px] top-1/2 -translate-y-1/2 w-3 h-3 bg-green-500/20 text-green-400 rounded-full flex items-center justify-center text-[8px]"><i class="fas fa-plus"></i></span>
                                                                    <div class="text-white font-bold"><?php echo htmlspecialchars((string)$data['new']); ?></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="bg-red-500/20 rounded-2xl p-4 border border-red-500/30 mb-4 inline-block">
                                                <p class="text-xs font-bold text-red-300 flex items-center gap-2">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    The owner wants to permanently delete this property.
                                                </p>
                                            </div>
                                        <?php endif; ?>
                                        <p class="text-[10px] text-gray-500 font-bold italic uppercase tracking-tighter">Submitted on <?php echo date('M d, Y @ H:i', strtotime($req['created_at'])); ?></p>
                                    </div>
                                </div>
                                
                                <div class="flex flex-col gap-3 min-w-[200px]">
                                    <form method="POST" class="space-y-3">
                                        <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                        <textarea name="admin_notes" placeholder="Add optional notes for the owner..." class="w-full p-4 bg-black/20 border border-white/10 rounded-2xl text-xs text-white placeholder-gray-500 outline-none focus:border-sky-400 transition-all h-24 resize-none"></textarea>
                                        <div class="flex gap-2">
                                            <button type="submit" name="action" value="approve" onclick="return confirm('Approve this request?')" class="flex-1 bg-emerald-600 hover:bg-emerald-500 text-white py-3 rounded-xl font-bold text-xs uppercase tracking-widest transition-all shadow-lg">Approve</button>
                                            <button type="submit" name="action" value="reject" onclick="return confirm('Reject this request?')" class="flex-1 bg-red-500/20 hover:bg-red-500 text-red-400 hover:text-white border border-red-500/30 py-3 rounded-xl font-bold text-xs uppercase tracking-widest transition-all">Reject</button>
                                        </div>
                                        <input type="hidden" name="status" id="status_input_<?php echo $req['id']; ?>" value="">
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        document.querySelectorAll('button[name="action"]').forEach(btn => {
            btn.addEventListener('click', function() {
                const status = this.value === 'approve' ? 'approved' : 'rejected';
                this.closest('form').querySelector('input[name="status"]').value = status;
            });
        });
    </script>
</body>
</html>
