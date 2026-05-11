<?php
require_once '../../config.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$selected_property_id = isset($_GET['property_id']) ? (int)$_GET['property_id'] : 0;

// Fetch properties for selector
if ($_SESSION['role'] === 'admin') {
    $properties_stmt = $pdo->query("SELECT id, property_name FROM properties ORDER BY property_name");
    $properties = $properties_stmt->fetchAll();
} else {
    $properties_stmt = $pdo->prepare("SELECT id, property_name FROM properties WHERE owner_id = ? ORDER BY property_name");
    $properties_stmt->execute([$user_id]);
    $properties = $properties_stmt->fetchAll();
}

// Resolve selected property
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

// --- Post Handlers ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_payment') {
    $amount = (float)$_POST['amount'];
    $payment_date = $_POST['payment_date'];
    
    $proof_path = '';
    if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../../uploads/payments/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
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

// Fetch History
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
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
        }
    </style>
</head>

<body class="flex min-h-screen overflow-hidden">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 lg:ml-64 overflow-y-auto h-screen bg-[#f8fafc]">
        
        <!-- Top Nav -->
        <header class="bg-white/80 backdrop-blur-md border-b border-gray-200 sticky top-0 z-40 px-4 lg:px-8 py-4 flex justify-between items-center">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-[#003580] hover:bg-gray-100 transition-all">
                    <i class="fas fa-bars-staggered"></i>
                </button>
                <form method="GET" class="hidden md:block">
                    <label class="sr-only" for="propertySelect">Property</label>
                    <select id="propertySelect" name="property_id" onchange="this.form.submit()" class="px-4 py-2.5 bg-gray-50 border border-gray-100 rounded-xl text-xs font-bold text-gray-600 uppercase tracking-widest focus:ring-2 focus:ring-[#003580] outline-none">
                        <?php if (!empty($properties)): ?>
                            <?php foreach ($properties as $prop): ?>
                                <option value="<?php echo (int)$prop['id']; ?>" <?php echo (int)$prop['id'] === (int)$property_id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($prop['property_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <option value="">No properties</option>
                        <?php endif; ?>
                    </select>
                </form>
            </div>
            
            <div class="flex items-center gap-2 lg:gap-4">
                <a href="../../index.php" target="_blank" class="hidden xl:flex items-center gap-2 px-5 py-2.5 border border-gray-200 text-gray-600 rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-gray-50 transition-all">
                    <i class="fas fa-external-link-alt"></i>
                    Visit Site
                </a>
                <button class="w-10 h-10 text-gray-400 hover:text-[#003580] transition-colors relative">
                    <i class="far fa-bell text-lg"></i>
                </button>
            </div>
        </header>

        <div class="p-4 lg:p-8">
            
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'payment_submitted'): ?>
            <div class="bg-green-50 text-green-600 p-4 rounded-xl mb-6 font-bold text-sm border border-green-200 flex items-center gap-3">
                <i class="fas fa-check-circle text-lg"></i>
                Payment proof submitted successfully! It is now pending admin approval.
            </div>
            <?php endif; ?>

            <div class="mb-8 flex justify-between items-end">
                <div>
                    <h2 class="text-2xl font-black text-[#003580]">System Payments</h2>
                    <p class="text-sm text-gray-400 font-medium">Manage and pay commission to the website</p>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex flex-col">
                    <div class="flex justify-between items-start mb-6">
                        <div class="w-12 h-12 bg-gray-50 text-gray-500 rounded-2xl flex items-center justify-center text-xl">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                    </div>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-widest mb-1">Total Commission Billed</p>
                    <h3 class="text-2xl font-black text-[#003580]">LKR <?php echo number_format($service_fee_total); ?></h3>
                </div>

                <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm flex flex-col">
                    <div class="flex justify-between items-start mb-6">
                        <div class="w-12 h-12 bg-green-50 text-green-500 rounded-2xl flex items-center justify-center text-xl">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <p class="text-gray-400 text-[10px] font-bold uppercase tracking-widest mb-1">Total Paid to System</p>
                    <h3 class="text-2xl font-black text-[#003580]">LKR <?php echo number_format($service_fee_paid); ?></h3>
                </div>

                <div class="bg-gradient-to-br from-[#003580] to-blue-600 p-6 rounded-3xl shadow-lg flex flex-col text-white relative overflow-hidden">
                    <div class="absolute -right-4 -bottom-4 text-white/10 text-8xl">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="flex justify-between items-start mb-6 relative z-10">
                        <div class="w-12 h-12 bg-white/20 text-white rounded-2xl flex items-center justify-center text-xl backdrop-blur-sm">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <button onclick="openPaymentModal()" class="bg-white text-[#003580] px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-gray-50 transition-all shadow-md">
                            Pay Now
                        </button>
                    </div>
                    <p class="text-blue-200 text-[10px] font-bold uppercase tracking-widest mb-1 relative z-10">Balance Due</p>
                    <h3 class="text-3xl font-black text-white relative z-10">LKR <?php echo number_format($service_fee_due); ?></h3>
                </div>
            </div>

            <!-- Payment History Table -->
            <div class="bg-white rounded-[2rem] border border-gray-100 shadow-sm overflow-hidden mb-8">
                <div class="p-6 border-b border-gray-50 flex justify-between items-center">
                    <h2 class="text-lg font-black text-[#003580]">Payment History</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                            <tr>
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4">Amount (LKR)</th>
                                <th class="px-6 py-4">Proof</th>
                                <th class="px-6 py-4">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm">
                            <?php if (empty($payment_history)): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-400 text-xs font-bold uppercase tracking-widest">No payments found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($payment_history as $payment): ?>
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-6 py-4 font-bold text-gray-600">
                                            <?php echo date('M d, Y', strtotime($payment['payment_date'])); ?>
                                            <div class="text-[10px] font-normal text-gray-400">Added: <?php echo date('M d, Y', strtotime($payment['created_at'])); ?></div>
                                        </td>
                                        <td class="px-6 py-4 font-black text-[#003580]">LKR <?php echo number_format($payment['amount']); ?></td>
                                        <td class="px-6 py-4">
                                            <?php if ($payment['proof_image']): ?>
                                                <a href="../../<?php echo htmlspecialchars($payment['proof_image']); ?>" target="_blank" class="text-blue-500 hover:text-blue-700 font-bold text-[10px] uppercase tracking-widest flex items-center gap-1">
                                                    <i class="fas fa-image"></i> View Proof
                                                </a>
                                            <?php else: ?>
                                                <span class="text-gray-400 text-[10px] uppercase tracking-widest">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-3 py-1 rounded-full text-[9px] font-bold uppercase tracking-wider
                                                <?php 
                                                    if($payment['status'] == 'approved') echo 'bg-green-50 text-green-600';
                                                    elseif($payment['status'] == 'pending') echo 'bg-orange-50 text-orange-600';
                                                    elseif($payment['status'] == 'rejected') echo 'bg-red-50 text-red-600';
                                                ?>">
                                                <?php echo $payment['status']; ?>
                                            </span>
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
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-black/40 backdrop-blur-sm" onclick="closePaymentModal()"></div>
            <div class="inline-block w-full max-w-lg my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-[2.5rem] border border-gray-100">
                <div class="p-8 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
                    <div>
                        <h3 class="text-xl font-black text-[#003580]">Submit Payment</h3>
                        <p class="text-xs text-gray-400 font-bold uppercase tracking-widest mt-1">Upload proof of transfer</p>
                    </div>
                    <button onclick="closePaymentModal()" class="text-gray-400 hover:text-red-500 transition-colors w-10 h-10 flex items-center justify-center rounded-full hover:bg-red-50">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form action="payments.php" method="POST" enctype="multipart/form-data" class="p-8">
                    <input type="hidden" name="action" value="submit_payment">
                    <input type="hidden" name="property_id" value="<?php echo $property_id; ?>">
                    
                    <div class="space-y-5">
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1 ml-1">Payment Amount (LKR)</label>
                            <input type="number" name="amount" min="1" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm font-bold focus:ring-2 focus:ring-[#003580] outline-none">
                        </div>
                        
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1 ml-1">Payment Date</label>
                            <input type="date" name="payment_date" required max="<?php echo date('Y-m-d'); ?>" value="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm font-bold focus:ring-2 focus:ring-[#003580] outline-none">
                        </div>
                        
                        <div>
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1 ml-1">Upload Receipt / Proof Image</label>
                            <input type="file" name="proof_image" accept="image/*,.pdf" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-xs focus:ring-2 focus:ring-[#003580] outline-none">
                        </div>
                    </div>
                    
                    <div class="mt-8 bg-blue-50 p-4 rounded-xl text-xs text-blue-800 flex items-start gap-3">
                        <i class="fas fa-info-circle mt-0.5"></i>
                        <p>After submission, an admin will review your receipt and approve the payment to update your balance.</p>
                    </div>
                    
                    <div class="mt-8 flex gap-4">
                        <button type="button" onclick="closePaymentModal()" class="flex-1 px-6 py-4 bg-gray-100 text-gray-500 rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-gray-200 transition-all">Cancel</button>
                        <button type="submit" class="flex-[2] px-6 py-4 bg-[#003580] text-white rounded-2xl font-bold text-xs uppercase tracking-widest hover:bg-[#002560] transition-all shadow-lg shadow-blue-900/20">Submit Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openPaymentModal() {
            document.getElementById('paymentModal').classList.remove('hidden');
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.add('hidden');
        }
    </script>
</body>
</html>
