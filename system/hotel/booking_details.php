<?php
require_once '../../config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

$booking_id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT b.*, pr.room_name, p.property_name, p.owner_id, p.street_address, p.city, p.district
    FROM bookings b 
    JOIN property_rooms pr ON b.room_id = pr.id 
    JOIN properties p ON b.property_id = p.id 
    WHERE b.id = ?
");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

if (!$booking || ($booking['owner_id'] != $user_id && $booking['user_id'] != $user_id && $_SESSION['role'] !== 'admin')) {
    die("Booking not found or access denied.");
}

$is_owner = ($booking['owner_id'] == $user_id || $_SESSION['role'] === 'admin');

if ($is_owner && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_payment'])) {
        $payment_amount = (float)($_POST['payment_amount'] ?? 0);
        if ($payment_amount > 0) {
            $pdo->prepare("INSERT INTO booking_payments (booking_id, amount) VALUES (?, ?)")
                ->execute([$booking_id, $payment_amount]);

            $pdo->prepare("UPDATE bookings SET amount_paid = amount_paid + ? WHERE id = ?")
                ->execute([$payment_amount, $booking_id]);

            $pdo->prepare("UPDATE bookings SET payment_status = CASE WHEN amount_paid >= total_price THEN 'complete' ELSE 'pending' END WHERE id = ?")
                ->execute([$booking_id]);
        }

        header("Location: booking_details.php?id=" . $booking_id);
        exit();
    }

    if (isset($_POST['add_expense'])) {
        $expense_desc = trim($_POST['expense_desc'] ?? '');
        $expense_amount = (float)($_POST['expense_amount'] ?? 0);

        if ($expense_desc !== '' && $expense_amount > 0) {
            $pdo->prepare("INSERT INTO booking_expenses (booking_id, description, amount) VALUES (?, ?, ?)")
                ->execute([$booking_id, $expense_desc, $expense_amount]);

            $pdo->prepare("UPDATE bookings SET total_price = total_price + ? WHERE id = ?")
                ->execute([$expense_amount, $booking_id]);

            $pdo->prepare("UPDATE bookings SET payment_status = CASE WHEN amount_paid >= total_price THEN 'complete' ELSE 'pending' END WHERE id = ?")
                ->execute([$booking_id]);
        }

        header("Location: booking_details.php?id=" . $booking_id);
        exit();
    }
}

$exp_stmt = $pdo->prepare("SELECT * FROM booking_expenses WHERE booking_id = ? ORDER BY created_at DESC");
$exp_stmt->execute([$booking_id]);
$expenses = $exp_stmt->fetchAll();

$nights = (strtotime($booking['check_out_date']) - strtotime($booking['check_in_date'])) / 86400;
if ($nights <= 0) $nights = 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $booking['id']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #ffffff; color: #111827; }
        @media print { .no-print { display: none !important; } .print-container { width: 100% !important; margin: 0 !important; border: none !important; } }
        .compact-table td, .compact-table th { padding: 6px 10px; }
        @media (max-width: 640px) { .compact-table td, .compact-table th { padding: 4px 6px; } }
    </style>
</head>
<body class="py-4 md:py-10">
    <div class="max-w-5xl mx-auto px-4 md:px-6">
        <!-- Compact Header -->
        <div class="flex justify-between items-center mb-3 md:mb-4 no-print">
            <a href="bookings.php" class="text-[10px] md:text-xs font-bold text-neutral-400 hover:text-neutral-600 uppercase tracking-widest flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> <span class="hidden xs:inline">Back to List</span><span class="xs:hidden">Back</span>
            </a>
            <div class="flex items-center gap-3">
                <button onclick="window.print()" class="text-[10px] md:text-xs font-bold text-neutral-700 uppercase tracking-widest hover:underline flex items-center gap-2">
                    <i class="fas fa-download"></i> <span class="hidden xs:inline">Download PDF</span><span class="xs-hidden">Download</span>
                </button>
                <button onclick="window.print()" class="text-[10px] md:text-xs font-bold text-neutral-700 uppercase tracking-widest hover:underline flex items-center gap-2">
                    <i class="fas fa-print"></i> <span class="hidden xs:inline">Print Invoice</span><span class="xs-hidden">Print</span>
                </button>
            </div>
        </div>

        <div class="bg-white border border-neutral-200 rounded-2xl md:rounded-3xl p-5 md:p-12 print-container shadow-sm">
            <!-- Property & Invoice Info -->
            <div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-6 md:mb-8 border-b border-neutral-100 pb-6 md:pb-8">
                <div>
                    <h1 class="text-lg md:text-2xl font-black text-neutral-900 leading-tight"><?php echo htmlspecialchars($booking['property_name']); ?></h1>
                    <p class="text-[11px] md:text-sm text-neutral-500 mt-1"><?php echo htmlspecialchars($booking['street_address'] . ', ' . $booking['city'] . ', ' . $booking['district']); ?></p>
                </div>
                <div class="sm:text-right w-full sm:w-auto">
                    <h2 class="text-xs md:text-base font-black uppercase tracking-widest text-neutral-400">Invoice #<?php echo $booking['id']; ?></h2>
                    <p class="text-[11px] md:text-sm text-neutral-500 mt-0.5">Issued on <?php echo date('M d, Y', strtotime($booking['created_at'])); ?></p>
                    <span class="inline-block mt-2 px-2.5 py-0.5 border border-neutral-200 text-[9px] md:text-[10px] font-black uppercase rounded-full tracking-wider"><?php echo str_replace('_', ' ', $booking['status']); ?></span>
                </div>
            </div>

            <!-- Billing & Stay Info -->
            <div class="grid grid-cols-2 gap-6 md:gap-12 mb-6 md:mb-8 text-[11px] md:text-sm">
                <div>
                    <h4 class="text-[9px] md:text-[11px] font-black text-neutral-400 uppercase tracking-widest mb-2 md:mb-3">Guest Information</h4>
                    <p class="text-sm md:text-base font-bold text-neutral-900"><?php echo htmlspecialchars($booking['guest_name']); ?></p>
                    <p class="text-neutral-600 mt-0.5"><?php echo htmlspecialchars($booking['guest_phone']); ?></p>
                    <p class="text-neutral-600 truncate"><?php echo htmlspecialchars($booking['guest_email'] ?: 'N/A'); ?></p>
                </div>
                <div class="text-right">
                    <h4 class="text-[9px] md:text-[11px] font-black text-neutral-400 uppercase tracking-widest mb-2 md:mb-3">Stay Details</h4>
                    <p class="text-sm md:text-base font-bold text-neutral-900"><?php echo date('M d', strtotime($booking['check_in_date'])); ?> — <?php echo date('M d, Y', strtotime($booking['check_out_date'])); ?></p>
                    <p class="text-neutral-600 mt-0.5"><?php echo htmlspecialchars($booking['room_name']); ?> (<?php echo $nights; ?> nights)</p>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left compact-table text-xs md:text-sm mb-6 md:mb-10">
                    <thead>
                        <tr class="border-b-2 border-neutral-900">
                            <th class="py-2 md:py-3 font-black uppercase text-[9px] md:text-[11px] text-neutral-400 tracking-widest">Description</th>
                            <th class="py-2 md:py-3 text-right font-black uppercase text-[9px] md:text-[11px] text-neutral-400 tracking-widest">Amount (LKR)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-50 text-neutral-800">
                        <tr>
                            <td class="py-3 md:py-4">
                                <span class="font-bold">Accommodation Fee</span>
                                <div class="text-neutral-400 text-[10px] md:text-xs">@ LKR <?php echo number_format($booking['price_per_room']); ?> / night</div>
                            </td>
                            <td class="py-3 md:py-4 text-right font-black"><?php echo number_format($booking['price_per_room'] * $nights); ?></td>
                        </tr>
                        <?php foreach ($expenses as $exp): ?>
                        <tr>
                            <td class="py-3 md:py-4 font-medium"><?php echo htmlspecialchars($exp['description']); ?></td>
                            <td class="py-3 md:py-4 text-right font-black text-neutral-900">+ <?php echo number_format($exp['amount']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="border-t border-neutral-900">
                        <tr>
                            <td class="pt-4 md:pt-6 text-right text-[9px] md:text-[11px] font-black uppercase text-neutral-400 tracking-widest">Grand Total</td>
                            <td class="pt-4 md:pt-6 text-right text-xl md:text-3xl font-black text-neutral-900 leading-none">LKR <?php echo number_format($booking['total_price']); ?></td>
                        </tr>
                        <tr>
                            <td class="pt-2 text-right text-[9px] md:text-[11px] font-black uppercase text-neutral-500 tracking-widest">Total Paid</td>
                            <td class="pt-2 text-right text-sm md:text-base font-bold text-neutral-900">LKR <?php echo number_format($booking['amount_paid']); ?></td>
                        </tr>
                        <tr>
                            <td class="pt-2 text-right text-[9px] md:text-[11px] font-black uppercase text-neutral-500 tracking-widest">Balance Due</td>
                            <td class="pt-2 text-right text-sm md:text-base font-black text-neutral-900 underline underline-offset-4 decoration-2">LKR <?php echo number_format($booking['total_price'] - $booking['amount_paid']); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Signatures -->
            <div class="mt-8 md:mt-12 flex justify-between gap-6 md:gap-12 text-[8px] md:text-[10px] text-neutral-400 font-black uppercase tracking-[0.2em]">
                <div class="text-center w-32 md:w-40 border-t border-neutral-200 pt-1 md:pt-2">Guest Signature</div>
                <div class="text-center w-32 md:w-40 border-t border-neutral-200 pt-1 md:pt-2">Manager Signature</div>
            </div>

            <div class="mt-6 md:mt-8 pt-4 border-t border-neutral-100 text-center">
                <p class="text-[8px] md:text-[10px] text-neutral-400 font-black uppercase tracking-[0.25em]">Thank you • System by Bookingjaunt</p>
            </div>
        </div>

        <!-- Hidden Management Controls for Guests -->
        <?php if ($is_owner): ?>
        <div class="mt-6 md:mt-8 grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6 no-print">
            <div class="bg-white border border-neutral-200 p-4 md:p-6 rounded-xl md:rounded-2xl shadow-sm">
                <h4 class="text-[9px] md:text-[11px] font-black uppercase tracking-widest mb-3 md:mb-4">Add Payment</h4>
                <form method="POST" class="flex gap-2 md:gap-3">
                    <input type="number" name="payment_amount" required class="flex-1 px-3 md:px-4 py-1.5 md:py-2 bg-neutral-50 border border-neutral-100 rounded-lg md:rounded-xl text-xs" placeholder="LKR 0.00">
                    <button type="submit" name="add_payment" class="bg-neutral-900 text-white px-3 md:px-5 py-1.5 md:py-2 rounded-lg md:rounded-xl text-[9px] md:text-[11px] font-black uppercase tracking-widest">Add</button>
                </form>
            </div>
            <div class="bg-white border border-neutral-200 p-4 md:p-6 rounded-xl md:rounded-2xl shadow-sm">
                <h4 class="text-[9px] md:text-[11px] font-black uppercase tracking-widest mb-3 md:mb-4">Add Extra Expense</h4>
                <form method="POST" class="space-y-2 md:space-y-3">
                    <input type="text" name="expense_desc" required class="w-full px-3 md:px-4 py-1.5 md:py-2 bg-neutral-50 border border-neutral-100 rounded-lg md:rounded-xl text-xs" placeholder="Description">
                    <div class="flex gap-2 md:gap-3">
                        <input type="number" name="expense_amount" required class="flex-1 px-3 md:px-4 py-1.5 md:py-2 bg-neutral-50 border border-neutral-100 rounded-lg md:rounded-xl text-xs" placeholder="Amount">
                        <button type="submit" name="add_expense" class="bg-neutral-900 text-white px-3 md:px-5 py-1.5 md:py-2 rounded-lg md:rounded-xl text-[9px] md:text-[11px] font-black uppercase tracking-widest">Add</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
