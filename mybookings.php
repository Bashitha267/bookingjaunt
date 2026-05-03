<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle Feedback Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    $booking_id = (int)$_POST['booking_id'];
    $property_id = (int)$_POST['property_id'];
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);

    if ($rating >= 1 && $rating <= 5) {
        $stmt = $pdo->prepare("INSERT INTO reviews (user_id, property_id, booking_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $property_id, $booking_id, $rating, $comment])) {
            $success = "Thank you for your feedback!";
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}

// Fetch user bookings
$stmt = $pdo->prepare("
    SELECT b.*, p.property_name, p.city, p.cover_image, r.room_name,
           rev.rating as user_rating
    FROM bookings b
    JOIN properties p ON b.property_id = p.id
    JOIN property_rooms r ON b.room_id = r.id
    LEFT JOIN reviews rev ON rev.booking_id = b.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Bookingjaunt</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        primary: '#003580',
                        secondary: '#006ce4',
                        palm: '#008009',
                        gold: '#febb02',
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f7f9fb; }
        .font-display { font-family: 'Outfit', sans-serif; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <main class="max-w-[1100px] mx-auto px-4 py-8 md:py-12">
        <div class="flex items-center justify-between mb-6 md:mb-8">
            <div>
                <h1 class="text-2xl md:text-3xl font-black font-display text-primary">My Bookings</h1>
                <p class="text-xs md:text-sm text-neutral-500 mt-0.5">Manage your trips and share experiences</p>
            </div>
            <a href="index.php" class="text-secondary font-bold hover:underline text-[11px] md:text-sm flex items-center gap-2">
                <i class="fas fa-search"></i> <span class="hidden xs:inline">Find more stays</span><span class="xs:hidden">Search</span>
            </a>
        </div>

        <?php if (isset($success)): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 md:px-6 py-3 md:py-4 rounded-xl mb-6 md:mb-8 flex items-center gap-3 text-sm">
                <i class="fas fa-check-circle text-lg md:text-xl"></i>
                <span class="font-bold"><?php echo $success; ?></span>
            </div>
        <?php endif; ?>

        <?php if (empty($bookings)): ?>
            <div class="bg-white rounded-2xl p-8 md:p-12 text-center border border-neutral-200 shadow-sm">
                <div class="w-16 h-16 md:w-20 md:h-20 bg-neutral-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-suitcase-rolling text-2xl md:text-3xl text-neutral-400"></i>
                </div>
                <h3 class="text-lg md:text-xl font-bold text-neutral-800">No bookings yet</h3>
                <p class="text-xs md:text-sm text-neutral-500 mt-2 mb-8 max-w-sm mx-auto">Your upcoming and past trips will appear here. Start exploring paradise!</p>
                <a href="index.php" class="bg-primary text-white px-8 py-3 rounded-xl font-bold hover:bg-opacity-90 transition-all text-sm">Search Hotels</a>
            </div>
        <?php else: ?>
            <div class="space-y-4 md:space-y-6">
                <?php foreach ($bookings as $booking): 
                    $status_colors = [
                        'pending' => 'bg-amber-100 text-amber-700',
                        'confirmed' => 'bg-emerald-100 text-emerald-700',
                        'checked_in' => 'bg-blue-100 text-blue-700',
                        'checked_out' => 'bg-neutral-100 text-neutral-700',
                        'cancelled' => 'bg-red-100 text-red-700'
                    ];
                    $status_class = $status_colors[$booking['status']] ?? 'bg-neutral-100 text-neutral-700';
                    $can_review = ($booking['status'] == 'checked_out' && !$booking['user_rating']);
                ?>
                    <div class="bg-white rounded-2xl border border-neutral-200 shadow-sm overflow-hidden flex flex-col md:flex-row transition-all hover:shadow-md">
                        <!-- Property Image -->
                        <div class="w-full md:w-64 h-40 md:h-auto relative shrink-0">
                            <img src="<?php echo htmlspecialchars($booking['cover_image'] ?: 'assets/hero_bg.png'); ?>" 
                                 class="w-full h-full object-cover">
                            <div class="absolute top-3 left-3 md:top-4 md:left-4">
                                <span class="px-2.5 py-1 rounded-full text-[9px] md:text-[11px] font-black uppercase tracking-wider <?php echo $status_class; ?> shadow-sm">
                                    <?php echo str_replace('_', ' ', $booking['status']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Booking Details -->
                        <div class="flex-1 p-4 md:p-6 flex flex-col justify-between">
                            <div class="flex flex-col md:flex-row justify-between gap-3 md:gap-4 mb-3 md:mb-4">
                                <div>
                                    <h2 class="text-lg md:text-xl font-black text-primary leading-tight mb-1"><?php echo htmlspecialchars($booking['property_name']); ?></h2>
                                    <p class="text-[11px] md:text-sm text-neutral-500 font-medium flex items-center gap-1.5">
                                        <i class="fas fa-map-marker-alt text-secondary"></i> <?php echo htmlspecialchars($booking['city']); ?>
                                    </p>
                                </div>
                                <div class="md:text-right">
                                    <p class="text-[9px] md:text-[11px] text-neutral-400 font-bold uppercase tracking-widest">Total Paid</p>
                                    <p class="text-base md:text-lg font-black text-neutral-900">LKR <?php echo number_format($booking['total_price']); ?></p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 py-3 md:py-4 border-y border-neutral-100">
                                <div>
                                    <p class="text-[9px] md:text-[10px] text-neutral-400 font-bold uppercase mb-0.5">Check-in</p>
                                    <p class="text-xs md:text-sm font-bold text-neutral-800"><?php echo date('D, M d', strtotime($booking['check_in_date'])); ?></p>
                                </div>
                                <div>
                                    <p class="text-[9px] md:text-[10px] text-neutral-400 font-bold uppercase mb-0.5">Check-out</p>
                                    <p class="text-xs md:text-sm font-bold text-neutral-800"><?php echo date('D, M d', strtotime($booking['check_out_date'])); ?></p>
                                </div>
                                <div>
                                    <p class="text-[9px] md:text-[10px] text-neutral-400 font-bold uppercase mb-0.5">Room</p>
                                    <p class="text-xs md:text-sm font-bold text-neutral-800 truncate"><?php echo htmlspecialchars($booking['room_name']); ?></p>
                                </div>
                                <div>
                                    <p class="text-[9px] md:text-[10px] text-neutral-400 font-bold uppercase mb-0.5">Guests</p>
                                    <p class="text-xs md:text-sm font-bold text-neutral-800"><?php echo $booking['adults'] + $booking['children']; ?> People</p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between mt-4 md:mt-6">
                                <div class="flex items-center gap-3 md:gap-4">
                                    <a href="system/hotel/booking_details.php?id=<?php echo $booking['id']; ?>" 
                                       class="text-[11px] md:text-sm font-bold text-secondary hover:underline">View Receipt</a>
                                    
                                    <?php if ($booking['user_rating']): ?>
                                        <div class="flex items-center gap-0.5 text-gold ml-1 md:ml-2">
                                            <?php for($i=1; $i<=5; $i++): ?>
                                                <i class="<?php echo $i <= $booking['user_rating'] ? 'fas' : 'far'; ?> fa-star text-[10px] md:text-[12px]"></i>
                                            <?php endfor; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if ($can_review): ?>
                                    <button onclick="openReviewModal(<?php echo $booking['id']; ?>, <?php echo $booking['property_id']; ?>, '<?php echo addslashes($booking['property_name']); ?>')" 
                                            class="bg-secondary text-white px-3 md:px-5 py-1.5 md:py-2 rounded-lg font-bold text-[11px] md:text-sm hover:bg-primary transition-all">
                                        Feedback
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Review Modal -->
    <div id="reviewModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-primary/40 backdrop-blur-sm">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden animate-fade-in">
            <div class="p-5 md:p-6 border-b border-neutral-100 flex justify-between items-center">
                <h3 class="text-lg md:text-xl font-black font-display text-primary">Share feedback</h3>
                <button onclick="closeReviewModal()" class="text-neutral-400 hover:text-neutral-600 text-2xl">&times;</button>
            </div>
            <form action="mybookings.php" method="POST" class="p-5 md:p-6">
                <input type="hidden" name="booking_id" id="modal_booking_id">
                <input type="hidden" name="property_id" id="modal_property_id">
                
                <p class="text-xs md:text-sm text-neutral-500 mb-4 md:mb-6">How was your stay at <span id="modal_property_name" class="font-bold text-neutral-800"></span>?</p>

                <div class="flex justify-center gap-2 md:gap-3 mb-6 md:mb-8">
                    <?php for($i=1; $i<=5; $i++): ?>
                        <button type="button" onclick="setRating(<?php echo $i; ?>)" class="rating-star text-neutral-200 text-2xl md:text-3xl transition-all hover:scale-110" data-val="<?php echo $i; ?>">
                            <i class="fas fa-star"></i>
                        </button>
                    <?php endfor; ?>
                    <input type="hidden" name="rating" id="modal_rating" value="0" required>
                </div>

                <div class="mb-5 md:mb-6">
                    <label class="block text-[9px] md:text-[11px] font-bold text-neutral-400 uppercase tracking-widest mb-2">Comment</label>
                    <textarea name="comment" rows="3" class="w-full bg-neutral-50 border border-neutral-200 rounded-xl p-3 md:p-4 text-xs md:text-sm outline-none focus:ring-2 focus:ring-secondary/20 focus:border-secondary transition-all resize-none" placeholder="Tell us about your stay..."></textarea>
                </div>

                <button type="submit" name="submit_review" class="w-full bg-primary text-white py-3 rounded-xl font-bold text-sm md:text-[15px] hover:bg-opacity-95 transition-all shadow-lg active:scale-95">
                    Submit Review
                </button>
            </form>
        </div>
    </div>

    <script>
        function openReviewModal(bookingId, propertyId, propertyName) {
            document.getElementById('modal_booking_id').value = bookingId;
            document.getElementById('modal_property_id').value = propertyId;
            document.getElementById('modal_property_name').textContent = propertyName;
            document.getElementById('reviewModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeReviewModal() {
            document.getElementById('reviewModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function setRating(val) {
            document.getElementById('modal_rating').value = val;
            const stars = document.querySelectorAll('.rating-star');
            stars.forEach(star => {
                const starVal = parseInt(star.getAttribute('data-val'));
                if (starVal <= val) {
                    star.classList.remove('text-neutral-200');
                    star.classList.add('text-gold');
                } else {
                    star.classList.remove('text-gold');
                    star.classList.add('text-neutral-200');
                }
            });
        }
    </script>
</body>
</html>
