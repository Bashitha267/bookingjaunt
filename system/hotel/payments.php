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
        $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ? LIMIT 1");
        $stmt->execute([$selected_property_id]);
        $property = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ? AND owner_id = ? LIMIT 1");
        $stmt->execute([$selected_property_id, $user_id]);
        $property = $stmt->fetch();
    }
}

if (!$property && !empty($properties)) {
    $first_property_id = (int)$properties[0]['id'];
    if ($_SESSION['role'] === 'admin') {
        $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ? LIMIT 1");
        $stmt->execute([$first_property_id]);
        $property = $stmt->fetch();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ? AND owner_id = ? LIMIT 1");
        $stmt->execute([$first_property_id, $user_id]);
        $property = $stmt->fetch();
    }
}

if (!$property && $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

$property_id = $property['id'] ?? 0;

// Handle Payment Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_payment') {
    $amount = (float)$_POST['amount'];
    $payment_date = $_POST['payment_date'];

    $proof_path = '';
    if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/payments/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = strtolower(pathinfo($_FILES['proof_image']['name'], PATHINFO_EXTENSION));
        $new_name = 'proof_' . date('Ymd_His') . '_' . substr(md5(uniqid()), 0, 8) . '.' . $ext;
        if (move_uploaded_file($_FILES['proof_image']['tmp_name'], $upload_dir . $new_name)) {
            $proof_path = 'uploads/payments/' . $new_name;
        }
    }

    $ins = $pdo->prepare("INSERT INTO hotel_service_payments (property_id, amount, payment_date, proof_image, status) VALUES (?, ?, ?, ?, 'pending')");
    $ins->execute([$property_id, $amount, $payment_date, $proof_path]);
    header("Location: payments.php?property_id=$property_id&msg=payment_submitted");
    exit();
}

// Fetch balances
$service_fee_total_stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price * 0.2), 0) FROM bookings WHERE property_id = ? AND booking_type = 'online'");
$service_fee_total_stmt->execute([$property_id]);
$service_fee_total = (float)$service_fee_total_stmt->fetchColumn();

$service_fee_paid_stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM hotel_service_payments WHERE property_id = ? AND status != 'rejected'");
$service_fee_paid_stmt->execute([$property_id]);
$service_fee_paid = (float)$service_fee_paid_stmt->fetchColumn();
$service_fee_due = max(0, $service_fee_total - $service_fee_paid);

$history_stmt = $pdo->prepare("SELECT * FROM hotel_service_payments WHERE property_id = ? ORDER BY created_at DESC");
$history_stmt->execute([$property_id]);
$payment_history = $history_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay to System - <?php echo htmlspecialchars($property['property_name'] ?? 'Bookingjaunt'); ?></title>
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
                <form method="GET" class="hidden md:block">
                    <label class="sr-only" for="propertySelect">Property</label>
                    <select id="propertySelect" name="property_id" onchange="this.form.submit()" class="glass-input px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest">
                        <?php foreach ($properties as $prop): ?>
                            <option value="<?php echo (int)$prop['id']; ?>" <?php echo (int)$prop['id'] === (int)$property_id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($prop['property_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <div class="flex items-center gap-3">
                <a href="../../index.php" target="_blank" class="btn-glass hidden xl:flex"><i class="fas fa-external-link-alt"></i> Visit Site</a>
            </div>
        </header>

        <div class="py-4 px-2 lg:py-8 lg:px-4">

            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'payment_submitted'): ?>
            <div class="glass-card mb-6 p-4 flex items-center gap-3 anim-up" style="border-color:rgba(74,222,128,0.3); background:rgba(74,222,128,0.10);">
                <i class="fas fa-check-circle" style="color:#4ade80;"></i>
                <span style="color:#86efac; font-weight:700; font-size:0.875rem;">Payment proof submitted! It is now pending admin approval.</span>
            </div>
            <?php endif; ?>

            <div class="mb-8 flex justify-between items-end anim-up">
                <div>
                    <h2 class="text-2xl font-black" style="color:white;">System Payments</h2>
                    <p class="text-sm font-medium mt-1" style="color:var(--text-secondary);">Manage and pay commission to the website</p>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="glass-card p-6 flex flex-col anim-up">
                    <div class="stat-icon mb-4" style="background:rgba(148,163,184,0.15);"><i class="fas fa-file-invoice-dollar" style="color:#cbd5e1;"></i></div>
                    <p class="stat-label">Total Commission Billed</p>
                    <h3 class="stat-value" style="font-size:1.3rem;">LKR <?php echo number_format($service_fee_total); ?></h3>
                </div>

                <div class="glass-card p-6 flex flex-col anim-up-2">
                    <div class="stat-icon mb-4" style="background:rgba(74,222,128,0.15);"><i class="fas fa-check-circle" style="color:#86efac;"></i></div>
                    <p class="stat-label">Total Paid to System</p>
                    <h3 class="stat-value" style="font-size:1.3rem;">LKR <?php echo number_format($service_fee_paid); ?></h3>
                </div>

                <div class="glass-card p-6 flex flex-col relative overflow-hidden anim-up" style="background:rgba(0,108,228,0.20); border-color:rgba(96,165,250,0.35);">
                    <div class="absolute -right-4 -bottom-4 text-8xl" style="color:rgba(255,255,255,0.05);">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="flex justify-between items-start mb-4 relative z-10">
                        <div class="stat-icon" style="background:rgba(255,255,255,0.15);"><i class="fas fa-exclamation-circle" style="color:white;"></i></div>
                        <button onclick="openPaymentModal()" class="btn-glass" style="font-size:0.6rem; padding:6px 14px; border-color:rgba(255,255,255,0.3);">
                            Pay Now
                        </button>
                    </div>
                    <p class="stat-label relative z-10" style="color:rgba(147,197,253,0.7);">Balance Due</p>
                    <h3 class="stat-value relative z-10" style="font-size:1.6rem;">LKR <?php echo number_format($service_fee_due); ?></h3>
                </div>
            </div>

            <!-- Payment History -->
            <div class="glass-table anim-up-2">
                <div class="px-6 py-5 flex justify-between items-center" style="border-bottom:1px solid var(--glass-border);">
                    <h2 class="text-lg font-black" style="color:white;">Payment History</h2>
                    <span class="text-[10px] font-bold uppercase tracking-widest" style="color:var(--text-muted);"><?php echo count($payment_history); ?> records</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr>
                                <th class="glass-th">Date</th>
                                <th class="glass-th">Amount (LKR)</th>
                                <th class="glass-th">Proof</th>
                                <th class="glass-th">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($payment_history)): ?>
                                <tr>
                                    <td colspan="4" class="glass-td text-center py-16" style="color:var(--text-muted);">
                                        <i class="fas fa-receipt text-4xl mb-3 block opacity-30"></i>
                                        No payments found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($payment_history as $payment): ?>
                                    <tr class="glass-tr">
                                        <td class="glass-td">
                                            <div class="font-bold" style="color:white;"><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></div>
                                            <div class="text-[10px] mt-1" style="color:var(--text-muted);">Added: <?php echo date('M d, Y', strtotime($payment['created_at'])); ?></div>
                                        </td>
                                        <td class="glass-td font-black text-xl" style="color:white;">LKR <?php echo number_format($payment['amount']); ?></td>
                                        <td class="glass-td">
                                            <?php if ($payment['proof_image']): ?>
                                                <a href="../../<?php echo htmlspecialchars($payment['proof_image']); ?>" target="_blank" class="btn-glass" style="font-size:0.6rem; padding:5px 12px;">
                                                    <i class="fas fa-image"></i> View Proof
                                                </a>
                                            <?php else: ?>
                                                <span class="text-[10px] uppercase tracking-widest" style="color:var(--text-muted);">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="glass-td">
                                            <?php
                                                $badge_map = ['approved' => 'badge-confirmed', 'pending' => 'badge-pending', 'rejected' => 'badge-cancelled'];
                                                $badge_class = $badge_map[$payment['status']] ?? 'badge-checked_out';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>"><?php echo $payment['status']; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Payment Modal -->
    <div id="paymentModal" class="fixed inset-0 z-[60] hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-md" onclick="closePaymentModal()"></div>
            <div class="relative w-full max-w-lg my-8 rounded-[2rem] overflow-hidden" style="background:rgba(5,18,60,0.90); backdrop-filter:blur(24px); border:1px solid var(--glass-border);">
                <div class="p-8 flex justify-between items-center" style="border-bottom:1px solid var(--glass-border);">
                    <div>
                        <h3 class="text-xl font-black" style="color:white;">Submit Payment</h3>
                        <p class="text-xs font-bold uppercase tracking-widest mt-1" style="color:var(--text-muted);">Upload proof of transfer</p>
                    </div>
                    <button onclick="closePaymentModal()" class="w-10 h-10 rounded-full btn-glass flex items-center justify-center"><i class="fas fa-times"></i></button>
                </div>
                <form action="payments.php" method="POST" enctype="multipart/form-data" class="p-8 space-y-5">
                    <input type="hidden" name="action" value="submit_payment">
                    <input type="hidden" name="property_id" value="<?php echo $property_id; ?>">
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest mb-2" style="color:var(--text-secondary);">Payment Amount (LKR)</label>
                        <input type="number" name="amount" min="1" required class="glass-input w-full px-4 py-3 rounded-xl text-sm font-bold">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest mb-2" style="color:var(--text-secondary);">Payment Date</label>
                        <input type="date" name="payment_date" required max="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" class="glass-input w-full px-4 py-3 rounded-xl text-sm font-bold">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-widest mb-2" style="color:var(--text-secondary);">Upload Receipt / Proof Image</label>
                        <input type="file" name="proof_image" accept="image/*,.pdf" required class="w-full text-xs cursor-pointer" style="color:var(--text-secondary);">
                    </div>
                    <div class="p-4 rounded-xl flex items-start gap-3" style="background:rgba(96,165,250,0.1); border:1px solid rgba(96,165,250,0.25);">
                        <i class="fas fa-info-circle mt-0.5" style="color:#93c5fd;"></i>
                        <p class="text-xs" style="color:rgba(147,197,253,0.85);">After submission, an admin will review your receipt and approve the payment to update your balance.</p>
                    </div>
                    <div class="flex gap-4 pt-2">
                        <button type="button" onclick="closePaymentModal()" class="flex-1 btn-glass justify-center py-4">Cancel</button>
                        <button type="submit" class="flex-[2] btn-primary justify-center py-4">
                            <i class="fas fa-paper-plane"></i> Submit Payment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openPaymentModal() { document.getElementById('paymentModal').classList.remove('hidden'); }
        function closePaymentModal() { document.getElementById('paymentModal').classList.add('hidden'); }
    </script>
</body>
</html>
