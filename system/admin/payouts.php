<?php
require_once '../../config.php';
session_start();

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// Handle Approve / Reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $payment_id = $_POST['payment_id'] ?? 0;
    
    if ($_POST['action'] === 'approve') {
        $stmt = $pdo->prepare("UPDATE hotel_service_payments SET status = 'approved' WHERE id = ?");
        $stmt->execute([$payment_id]);
    } elseif ($_POST['action'] === 'reject') {
        $stmt = $pdo->prepare("UPDATE hotel_service_payments SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$payment_id]);
    }
    
    header("Location: payouts.php?tab=approvals");
    exit();
}

$active_tab = $_GET['tab'] ?? 'approvals';

// 1. Approvals Data
$approvals_stmt = $pdo->query("SELECT p.*, pr.property_name FROM hotel_service_payments p JOIN properties pr ON p.property_id = pr.id WHERE p.status = 'pending' ORDER BY p.created_at DESC");
$approvals = $approvals_stmt->fetchAll();

// 2. Remaining Dues Data
$remaining_stmt = $pdo->query("
    SELECT 
        pr.id, 
        pr.property_name,
        COALESCE((SELECT SUM(total_price) * 0.2 FROM bookings b WHERE b.property_id = pr.id AND b.booking_type = 'online'), 0) as total_commission,
        COALESCE((SELECT SUM(amount) FROM hotel_service_payments hp WHERE hp.property_id = pr.id AND hp.status = 'approved'), 0) as total_paid
    FROM properties pr
    HAVING total_commission > 0
    ORDER BY (total_commission - total_paid) DESC
");
$remainings = $remaining_stmt->fetchAll();

// 3. History Data with Search, Filter & Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$status_filter = $_GET['status_filter'] ?? '';

$where_clauses = ["1=1"];
$params = [];

if ($search !== '') {
    $where_clauses[] = "pr.property_name LIKE ?";
    $params[] = "%$search%";
}
if ($status_filter !== '') {
    $where_clauses[] = "p.status = ?";
    $params[] = $status_filter;
}

$where_sql = implode(' AND ', $where_clauses);

$total_history_stmt = $pdo->prepare("SELECT COUNT(*) FROM hotel_service_payments p JOIN properties pr ON p.property_id = pr.id WHERE $where_sql");
$total_history_stmt->execute($params);
$total_history = $total_history_stmt->fetchColumn();
$total_pages = ceil($total_history / $limit);

$history_stmt = $pdo->prepare("SELECT p.*, pr.property_name FROM hotel_service_payments p JOIN properties pr ON p.property_id = pr.id WHERE $where_sql ORDER BY p.created_at DESC LIMIT $limit OFFSET $offset");
$history_stmt->execute($params);
$histories = $history_stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payouts & Commissions - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f8fafc;
        }

        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }

        .tab-content.active {
            display: block;
        }

        .tab-btn.active {
            border-bottom: 2px solid #006ce4;
            color: #006ce4;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(5px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>

<body class="flex min-h-screen overflow-hidden">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <main class="flex-1 lg:ml-64 overflow-y-auto h-screen bg-[#f8fafc]">
        
        <!-- Header -->
        <header class="bg-white/80 backdrop-blur-md border-b border-gray-200 sticky top-0 z-40 px-4 lg:px-8 py-6 flex flex-col gap-3">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-[#003580] hover:bg-gray-100 transition-all">
                    <i class="fas fa-bars-staggered"></i>
                </button>
                <div>
                    <h1 class="text-2xl font-black text-[#003580]">Payouts & Commissions</h1>
                    <p class="text-xs text-gray-500 font-bold uppercase tracking-widest hidden sm:block">Manage system payments from hotels</p>
                </div>
            </div>
        </header>

        <div class="p-4 lg:p-8 space-y-6">
            <!-- Tabs Navigation -->
            <div class="bg-white rounded-2xl border border-gray-100 p-2 inline-flex flex-wrap gap-2 shadow-sm">
                <button class="tab-btn px-6 py-3 rounded-xl text-sm font-bold transition-all <?php echo $active_tab === 'approvals' ? 'bg-[#006ce4] text-white' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900'; ?>" onclick="switchTab('approvals')">
                    Pending Approvals
                    <?php if (count($approvals) > 0): ?>
                        <span class="ml-2 bg-red-500 text-white text-[10px] px-2 py-0.5 rounded-full"><?php echo count($approvals); ?></span>
                    <?php endif; ?>
                </button>
                <button class="tab-btn px-6 py-3 rounded-xl text-sm font-bold transition-all <?php echo $active_tab === 'remaining' ? 'bg-[#006ce4] text-white' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900'; ?>" onclick="switchTab('remaining')">
                    Remaining Dues
                </button>
                <button class="tab-btn px-6 py-3 rounded-xl text-sm font-bold transition-all <?php echo $active_tab === 'history' ? 'bg-[#006ce4] text-white' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-900'; ?>" onclick="switchTab('history')">
                    Payment History
                </button>
            </div>

            <!-- Approvals Tab -->
            <div id="tab-approvals" class="tab-content <?php echo $active_tab === 'approvals' ? 'active' : ''; ?>">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="font-bold text-[#003580]">Pending Payment Requests</h3>
                        <p class="text-xs text-gray-500 mt-1">Review and approve proofs of payment sent by properties.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                                <tr>
                                    <th class="px-6 py-4">ID</th>
                                    <th class="px-6 py-4">Property</th>
                                    <th class="px-6 py-4">Amount</th>
                                    <th class="px-6 py-4">Date Sent</th>
                                    <th class="px-6 py-4">Proof</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if (count($approvals) === 0): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500 font-medium">No pending payment requests.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($approvals as $payment): ?>
                                    <tr class="hover:bg-gray-50 transition-colors group">
                                        <td class="px-6 py-4 text-sm font-bold text-gray-900">#<?php echo $payment['id']; ?></td>
                                        <td class="px-6 py-4 text-sm font-bold text-gray-800"><?php echo htmlspecialchars($payment['property_name']); ?></td>
                                        <td class="px-6 py-4 text-sm font-bold text-[#006ce4]">LKR <?php echo number_format($payment['amount'], 2); ?></td>
                                        <td class="px-6 py-4 text-xs text-gray-500"><?php echo date('M d, Y h:i A', strtotime($payment['created_at'])); ?></td>
                                        <td class="px-6 py-4">
                                            <?php if ($payment['proof_image']): ?>
                                                <a href="../../<?php echo htmlspecialchars($payment['proof_image']); ?>" target="_blank" class="inline-flex items-center gap-2 text-xs font-bold text-blue-600 bg-blue-50 px-3 py-1.5 rounded-lg hover:bg-blue-100 transition-colors">
                                                    <i class="fas fa-image"></i> View Proof
                                                </a>
                                            <?php else: ?>
                                                <span class="text-xs text-gray-400 italic">No image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center hover:bg-emerald-500 hover:text-white transition-all" title="Approve">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to reject this payment request?');">
                                                    <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center hover:bg-red-500 hover:text-white transition-all" title="Reject">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Remaining Dues Tab -->
            <div id="tab-remaining" class="tab-content <?php echo $active_tab === 'remaining' ? 'active' : ''; ?>">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-gradient-to-br from-[#003580] to-[#006ce4] rounded-2xl p-6 text-white shadow-sm">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-blue-200">Total System Commission</p>
                        <h3 class="text-2xl font-black mt-2">
                            LKR <?php 
                            $total_comm = array_sum(array_column($remainings, 'total_commission')); 
                            echo number_format($total_comm, 2); 
                            ?>
                        </h3>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-emerald-500">Total Paid by Hotels</p>
                        <h3 class="text-2xl font-black text-gray-900 mt-2">
                            LKR <?php 
                            $total_paid = array_sum(array_column($remainings, 'total_paid')); 
                            echo number_format($total_paid, 2); 
                            ?>
                        </h3>
                    </div>
                    <div class="bg-white rounded-2xl border border-gray-100 p-6 shadow-sm">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-red-500">Total Remaining Dues</p>
                        <h3 class="text-2xl font-black text-gray-900 mt-2">
                            LKR <?php echo number_format($total_comm - $total_paid, 2); ?>
                        </h3>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="font-bold text-[#003580]">Properties Dues</h3>
                        <p class="text-xs text-gray-500 mt-1">List of properties and their outstanding commission balances (20% of online bookings).</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                                <tr>
                                    <th class="px-6 py-4">Property</th>
                                    <th class="px-6 py-4">Total Commission</th>
                                    <th class="px-6 py-4">Total Paid</th>
                                    <th class="px-6 py-4 text-right">Remaining Due</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if (count($remainings) === 0): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500 font-medium">No properties have commission data.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($remainings as $rem): 
                                        $due = $rem['total_commission'] - $rem['total_paid'];
                                    ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 text-sm font-bold text-gray-800"><?php echo htmlspecialchars($rem['property_name']); ?></td>
                                        <td class="px-6 py-4 text-sm font-medium text-gray-600">LKR <?php echo number_format($rem['total_commission'], 2); ?></td>
                                        <td class="px-6 py-4 text-sm font-medium text-emerald-600">LKR <?php echo number_format($rem['total_paid'], 2); ?></td>
                                        <td class="px-6 py-4 text-sm font-bold text-right <?php echo $due > 0 ? 'text-red-500' : 'text-gray-400'; ?>">
                                            LKR <?php echo number_format($due, 2); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- History Tab -->
            <div id="tab-history" class="tab-content <?php echo $active_tab === 'history' ? 'active' : ''; ?>">
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h3 class="font-bold text-[#003580]">Payment History</h3>
                            <p class="text-xs text-gray-500 mt-1">All processed payment requests.</p>
                        </div>
                        <form method="GET" class="flex flex-col sm:flex-row items-center gap-3">
                            <input type="hidden" name="tab" value="history">
                            <div class="relative w-full sm:w-auto">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search property..." class="w-full sm:w-48 pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-[#006ce4] outline-none transition-all">
                            </div>
                            <select name="status_filter" class="w-full sm:w-auto px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-[#006ce4] outline-none transition-all text-gray-600">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                            <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-[#006ce4] text-white rounded-xl text-xs font-bold transition-all hover:bg-[#005bb5]">Filter</button>
                        </form>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                                <tr>
                                    <th class="px-6 py-4">ID</th>
                                    <th class="px-6 py-4">Property</th>
                                    <th class="px-6 py-4">Amount</th>
                                    <th class="px-6 py-4">Date</th>
                                    <th class="px-6 py-4">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if (count($histories) === 0): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500 font-medium">No payment records found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($histories as $hist): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 text-xs font-medium text-gray-500">#<?php echo $hist['id']; ?></td>
                                        <td class="px-6 py-4 text-sm font-bold text-gray-800"><?php echo htmlspecialchars($hist['property_name']); ?></td>
                                        <td class="px-6 py-4 text-sm font-bold text-gray-900">LKR <?php echo number_format($hist['amount'], 2); ?></td>
                                        <td class="px-6 py-4 text-xs text-gray-500"><?php echo date('M d, Y', strtotime($hist['created_at'])); ?></td>
                                        <td class="px-6 py-4">
                                            <?php if($hist['status'] === 'approved'): ?>
                                                <span class="text-[10px] font-bold px-3 py-1 rounded-full bg-emerald-50 text-emerald-600 uppercase">Approved</span>
                                            <?php elseif($hist['status'] === 'rejected'): ?>
                                                <span class="text-[10px] font-bold px-3 py-1 rounded-full bg-red-50 text-red-600 uppercase">Rejected</span>
                                            <?php else: ?>
                                                <span class="text-[10px] font-bold px-3 py-1 rounded-full bg-amber-50 text-amber-600 uppercase">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                    <div class="p-6 border-t border-gray-100 flex items-center justify-between">
                        <p class="text-xs text-gray-500 font-medium">
                            Showing <span class="font-bold"><?php echo $offset + 1; ?></span> to <span class="font-bold"><?php echo min($offset + $limit, $total_history); ?></span> of <span class="font-bold"><?php echo $total_history; ?></span> entries
                        </p>
                        <div class="flex gap-2">
                            <?php if ($page > 1): ?>
                                <a href="?tab=history&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status_filter=<?php echo urlencode($status_filter); ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 transition-colors"><i class="fas fa-chevron-left text-xs"></i></a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?tab=history&page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status_filter=<?php echo urlencode($status_filter); ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border <?php echo $i === $page ? 'bg-[#006ce4] border-[#006ce4] text-white font-bold' : 'border-gray-200 text-gray-500 hover:bg-gray-50'; ?> transition-colors text-xs"><?php echo $i; ?></a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?tab=history&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status_filter=<?php echo urlencode($status_filter); ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 transition-colors"><i class="fas fa-chevron-right text-xs"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        function switchTab(tabId) {
            // Update URL without reload to persist tab state
            const url = new URL(window.location);
            url.searchParams.set('tab', tabId);
            window.history.pushState({}, '', url);

            // Hide all contents
            document.querySelectorAll('.tab-content').forEach(el => {
                el.classList.remove('active');
            });
            // Reset all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('bg-[#006ce4]', 'text-white');
                btn.classList.add('text-gray-500');
            });

            // Show selected content
            document.getElementById('tab-' + tabId).classList.add('active');
            
            // Highlight active button
            const activeBtns = document.querySelectorAll(`button[onclick="switchTab('${tabId}')"]`);
            activeBtns.forEach(btn => {
                btn.classList.remove('text-gray-500');
                btn.classList.add('bg-[#006ce4]', 'text-white');
            });
        }
    </script>
</body>
</html>
