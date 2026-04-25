<?php
require_once 'config.php';
session_start();

$property_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($property_id <= 0) {
    header("Location: index.php");
    exit();
}

// Fetch Property Details
$stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
$stmt->execute([$property_id]);
$property = $stmt->fetch();

if (!$property) {
    header("Location: index.php");
    exit();
}

// Fetch Amenities
$amenities_stmt = $pdo->prepare("SELECT am.amenity_name, am.icon FROM property_amenities pa JOIN amenities_master am ON pa.amenity_id = am.id WHERE pa.property_id = ?");
$amenities_stmt->execute([$property_id]);
$amenities = $amenities_stmt->fetchAll();

// Fetch Rooms
$rooms_stmt = $pdo->prepare("SELECT * FROM property_rooms WHERE property_id = ?");
$rooms_stmt->execute([$property_id]);
$rooms = $rooms_stmt->fetchAll();

// Fetch Media (Up to 4 images)
$media_stmt = $pdo->prepare("SELECT media_path FROM property_media WHERE property_id = ? AND media_type = 'image' ORDER BY sort_order LIMIT 4");
$media_stmt->execute([$property_id]);
$media = $media_stmt->fetchAll();

// If no media in property_media, use cover_image and some defaults
$images = [];
if (!empty($property['cover_image'])) {
    $images[] = $property['cover_image'];
}
foreach ($media as $m) {
    $images[] = $m['media_path'];
}
// Fill up to 4 with defaults if needed
while (count($images) < 4) {
    $images[] = 'assets/hotel' . (count($images) + 1) . '.png';
}

$currency = $_SESSION['currency'] ?? 'LKR';
$exchange_rate = 300;

// Stars logic
$stars = 0;
if ($property['hotel_category'] == 'budget_friendly')
    $stars = 3;
if ($property['hotel_category'] == 'luxury')
    $stars = 4;
if ($property['hotel_category'] == 'super_luxury')
    $stars = 5;

// Reviews (Hardcoded for now)
$reviews = [
    [
        'name' => 'John Doe',
        'country' => 'United States',
        'avatar' => 'JD',
        'text' => 'The location was absolutely perfect, right next to the beach. The staff was incredibly helpful and the breakfast spread was impressive.'
    ],
    [
        'name' => 'Sarah Miller',
        'country' => 'United Kingdom',
        'avatar' => 'SM',
        'text' => 'Exceptional spa treatments! I’ve stayed at many luxury hotels, but the service here really stands out. Highly recommended.'
    ],
    [
        'name' => 'Alex Murphy',
        'country' => 'Canada',
        'avatar' => 'AM',
        'text' => 'Modern rooms with stunning views. The rooftop pool area is the best place to catch the sunset in the city.'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($property['property_name']); ?> - Bookingjaunt</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#003580',
                        secondary: '#006ce4',
                        gold: '#febb02',
                        palm: '#008009',
                        ocean: '#003580',
                    }
                }
            }
        }
    </script>
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        body { background-color: #fff; }
        .lightbox-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            padding-top: 50px;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.9);
            backdrop-filter: blur(5px);
        }
        .lightbox-content {
            margin: auto;
            display: block;
            width: 80%;
            max-width: 1000px;
            animation: zoom 0.3s;
        }
        @keyframes zoom {
            from {transform:scale(0.8); opacity: 0;}
            to {transform:scale(1); opacity: 1;}
        }
        .close-lightbox {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
            cursor: pointer;
        }
    </style>
    <script>
        function openLightbox(src) {
            const modal = document.getElementById("myLightbox");
            const modalImg = document.getElementById("imgLightbox");
            modal.style.display = "block";
            modalImg.src = src;
            document.body.style.overflow = "hidden";
        }
        function closeLightbox() {
            document.getElementById("myLightbox").style.display = "none";
            document.body.style.overflow = "auto";
        }
    </script>
</head>

<body class="font-sans text-[#1a1a1a]">

    <?php include 'navbar.php'; ?>

    <main class="max-w-[1100px] mx-auto px-0 md:px-4 py-0 md:py-6 pb-24 md:pb-6">

        <!-- Breadcrumbs (Hidden on Mobile) -->
        <nav
            class="hidden md:flex items-center gap-2 text-[12px] text-secondary mb-4 overflow-x-auto no-scrollbar whitespace-nowrap">
            <a href="index.php" class="hover:underline">Home</a>
            <i class="fas fa-chevron-right text-[8px] text-neutral-400"></i>
            <a href="#" class="hover:underline"><?php echo htmlspecialchars($property['country']); ?></a>
            <i class="fas fa-chevron-right text-[8px] text-neutral-400"></i>
            <a href="#" class="hover:underline"><?php echo htmlspecialchars($property['district']); ?></a>
            <i class="fas fa-chevron-right text-[8px] text-neutral-400"></i>
            <span class="text-neutral-500"><?php echo htmlspecialchars($property['property_name']); ?></span>
        </nav>

        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-4 px-4 md:px-0 mt-4 md:mt-0">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span
                        class="bg-neutral-500 text-white text-[9px] md:text-[10px] font-bold px-1.5 py-0.5 rounded uppercase">Hotel</span>
                    <div class="flex gap-0.5 text-gold text-[9px] md:text-[10px]">
                        <?php for ($i = 0; $i < $stars; $i++): ?>
                            <i class="fas fa-star"></i>
                        <?php endfor; ?>
                    </div>
                    <i class="fas fa-thumbs-up text-gold text-[10px] md:text-xs"></i>
                </div>
                <h1 class="text-xl md:text-3xl font-extrabold text-[#1a1a1a] leading-tight">
                    <?php echo htmlspecialchars($property['property_name']); ?></h1>
                <div class="flex items-start md:items-center gap-2 text-[12px] md:text-[13px] text-neutral-600 mt-1.5">
                    <i class="fas fa-map-marker-alt text-secondary mt-0.5 md:mt-0"></i>
                    <span
                        class="flex-1"><?php echo htmlspecialchars($property['street_address'] . ', ' . $property['city'] . ', ' . $property['district']); ?></span>
                </div>
                <a href="#"
                    class="text-secondary font-bold hover:underline text-[12px] md:text-[13px] mt-1 inline-block">Excellent
                    location – show map</a>
            </div>
            <div class="hidden md:flex items-center gap-2">
                <button
                    class="p-2.5 border border-secondary text-secondary rounded hover:bg-secondary/5 transition-colors"><i
                        class="far fa-heart"></i></button>
                <button
                    class="p-2.5 border border-secondary text-secondary rounded hover:bg-secondary/5 transition-colors"><i
                        class="fas fa-share-alt"></i></button>
                <button
                    class="bg-secondary text-white px-6 py-2.5 rounded font-bold text-[14px] hover:bg-primary transition-colors">Reserve</button>
            </div>
        </div>

        <!-- Image Gallery -->
        <?php if (!empty($images)): ?>
            <!-- Mobile: Horizontal Scroll -->
            <div class="md:hidden flex overflow-x-auto snap-x snap-mandatory no-scrollbar h-[280px] mb-4">
                <?php foreach ($images as $img): ?>
                    <div class="w-full shrink-0 snap-center relative">
                <img src="<?php echo htmlspecialchars($img); ?>" class="w-full h-full object-cover cursor-pointer" alt="Property View" onclick="openLightbox(this.src)">
                <div class="absolute bottom-4 right-4 bg-black/60 text-white text-[10px] px-2 py-1 rounded">
                            <?php echo (array_search($img, $images) + 1) . '/' . count($images); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Image Gallery Grid -->
            <div class="hidden md:grid grid-cols-4 grid-rows-2 gap-2 h-[450px] mb-8">
                <!-- Large Main Image -->
                <div class="col-span-2 row-span-2 relative group overflow-hidden rounded-l-md">
                    <img src="<?php echo htmlspecialchars($images[0]); ?>"
                        class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 cursor-pointer"
                        alt="Main View" onclick="openLightbox(this.src)">
                </div>
                <!-- Second Large Image -->
                <div class="col-span-2 row-span-1 relative group overflow-hidden rounded-tr-md">
                    <img src="<?php echo htmlspecialchars($images[1]); ?>"
                        class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 cursor-pointer"
                        alt="Room View" onclick="openLightbox(this.src)">
                </div>
                <!-- Smaller Images -->
                <div class="relative group overflow-hidden">
                    <img src="<?php echo htmlspecialchars($images[2]); ?>"
                        class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 cursor-pointer"
                        alt="Amenity" onclick="openLightbox(this.src)">
                </div>
                <div class="relative group overflow-hidden rounded-br-md">
                    <img src="<?php echo htmlspecialchars($images[3]); ?>"
                        class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105 cursor-pointer" onclick="openLightbox(this.src)">
                    <div
                        class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer" onclick="openLightbox('<?php echo htmlspecialchars($images[3]); ?>')">
                        <span class="text-white font-bold text-sm">+ See all photos</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Description & Sidebar -->
        <div class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-8 px-4 md:px-0">
            <div>
                <div class="text-[14px] md:text-[15px] text-neutral-700 leading-relaxed mb-8">
                    <?php echo nl2br(htmlspecialchars($property['description'])); ?>
                </div>

                <h3 class="text-lg font-extrabold mb-4">Most popular facilities</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-y-4 gap-x-2 mb-8">
                    <?php foreach ($amenities as $am): ?>
                        <div class="flex items-center gap-3 text-[13px] md:text-[14px] text-palm font-bold">
                            <?php if (!empty($am['icon'])): ?>
                                <i class="fas <?php echo htmlspecialchars($am['icon']); ?> w-5 text-center text-secondary"></i>
                            <?php endif; ?>
                            <span><?php echo htmlspecialchars($am['amenity_name']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sidebar Info Card (Hidden on Mobile) -->
            <div class="hidden lg:block space-y-4">
                <div class="bg-[#ebf3ff] p-4 rounded-lg border border-secondary/20">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h4 class="font-bold text-[15px]">Property highlights</h4>
                            <p class="text-[12px] text-neutral-600">Perfect for a 3-night stay!</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="text-right">
                                <div class="font-bold text-sm">Exceptional</div>
                                <div class="text-[11px] text-neutral-500">1,204 reviews</div>
                            </div>
                            <div
                                class="bg-primary text-white w-8 h-8 rounded flex items-center justify-center font-bold">
                                9.2</div>
                        </div>
                    </div>

                    <div class="space-y-3 mb-6">
                        <div class="flex gap-2 text-[13px] text-neutral-700">
                            <i class="fas fa-map-marker-alt mt-1 text-neutral-500"></i>
                            <span>Located in the top-rated area in <?php echo htmlspecialchars($property['city']); ?>,
                                this hotel has an excellent location score of 9.5</span>
                        </div>
                        <div class="flex gap-2 text-[13px] text-neutral-700">
                            <i class="fas fa-bed mt-1 text-neutral-500"></i>
                            <span>Want a great night's sleep? This hotel was highly rated for its very comfy
                                beds.</span>
                        </div>
                    </div>

                    <div class="text-right mb-4">
                        <div class="text-[11px] text-red-600 line-through">LKR 45,000</div>
                        <div class="text-2xl font-black text-[#1a1a1a] leading-none">LKR 38,500</div>
                        <div class="text-[11px] text-neutral-500">Includes taxes and fees</div>
                    </div>

                    <button
                        class="w-full bg-secondary text-white py-3 rounded font-bold text-[15px] hover:bg-primary transition-colors">Reserve
                        your stay</button>
                    <p class="text-center text-[11px] text-neutral-500 mt-2"># No credit card needed</p>
                </div>
            </div>
        </div>

        <!-- Availability Section -->
        <div class="mt-8 md:mt-12 px-4 md:px-0">
            <h3 class="text-xl font-extrabold mb-4 md:mb-6">Availability</h3>

            <!-- Desktop Table View -->
            <div class="hidden md:block border border-neutral-200 rounded overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-primary text-white text-[13px]">
                        <tr>
                            <th class="p-3 border-r border-white/10 font-bold">Room Type</th>
                            <th class="p-3 border-r border-white/10 font-bold">Sleeps</th>
                            <th class="p-3 border-r border-white/10 font-bold">Price</th>
                            <th class="p-3 border-r border-white/10 font-bold">Your Choices</th>
                            <th class="p-3 font-bold">Select Amount</th>
                            <th class="p-3"></th>
                        </tr>
                    </thead>
                    <tbody class="text-[13px]">
                        <?php foreach ($rooms as $room):
                            $price = $room['price_lkr'];
                            $display_price_formatted = number_format($currency === 'USD' ? ceil($price / $exchange_rate) : $price);
                            $price_label = ($currency === 'USD' ? 'USD ' : 'LKR ') . $display_price_formatted;
                            ?>
                            <tr class="border-t border-neutral-200 hover:bg-neutral-50 transition-colors">
                                <td class="p-4 border-r border-neutral-200 align-top">
                                    <div class="flex flex-col gap-2">
                                        <?php if (!empty($room['room_image'])): ?>
                                            <img src="<?php echo htmlspecialchars($room['room_image']); ?>"
                                                class="w-full max-w-[200px] h-32 object-cover rounded shadow-sm mb-2 cursor-pointer"
                                                alt="<?php echo htmlspecialchars($room['room_name']); ?>" onclick="openLightbox(this.src)">
                                        <?php endif; ?>
                                        <div class="font-bold text-secondary text-[15px] hover:underline cursor-pointer">
                                            <?php echo htmlspecialchars($room['room_name']); ?></div>
                                        <div class="text-neutral-500 text-[11px]">1 extra-large double bed</div>
                                        <div class="flex gap-4 mt-2">
                                            <span class="text-neutral-500"><i class="fas fa-expand mr-1"></i> 35 m²</span>
                                            <span class="text-neutral-500"><i class="fas fa-umbrella-beach mr-1"></i> Sea
                                                view</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 border-r border-neutral-200 align-top">
                                    <div class="flex gap-0.5">
                                        <?php for ($i = 0; $i < $room['adults']; $i++): ?>
                                            <i class="fas fa-user text-xs"></i>
                                        <?php endfor; ?>
                                    </div>
                                </td>
                                <td class="p-4 border-r border-neutral-200 align-top">
                                    <div class="font-bold text-lg"><?php echo $price_label; ?></div>
                                    <div class="text-[10px] text-neutral-500 uppercase">Includes taxes and fees</div>
                                </td>
                                <td class="p-4 border-r border-neutral-200 align-top">
                                    <div class="space-y-1">
                                        <div class="text-palm font-bold"><i class="fas fa-check mr-1"></i> Free cancellation
                                            before Mar 10</div>
                                        <div class="text-palm font-bold"><i class="fas fa-check mr-1"></i> No prepayment
                                            needed</div>
                                    </div>
                                </td>
                                <td class="p-4 border-r border-neutral-200 align-top">
                                    <select
                                        class="w-full p-2 border border-neutral-300 rounded outline-none focus:border-secondary">
                                        <option>0</option>
                                        <option>1</option>
                                        <option>2</option>
                                    </select>
                                </td>
                                <td class="p-4 align-top">
                                    <button
                                        class="bg-secondary text-white px-4 py-2 rounded font-bold hover:bg-primary transition-colors whitespace-nowrap">I'll
                                        reserve</button>
                                    <p class="text-[10px] text-neutral-500 mt-1">Confirmation is instant</p>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Room Cards -->
            <div class="md:hidden space-y-4">
                <?php foreach ($rooms as $room):
                    $price = $room['price_lkr'];
                    $display_price_formatted = number_format($currency === 'USD' ? ceil($price / $exchange_rate) : $price);
                    $price_label = ($currency === 'USD' ? 'USD ' : 'LKR ') . $display_price_formatted;
                    ?>
                    <div class="bg-white border border-neutral-200 rounded-xl overflow-hidden shadow-sm">
                        <?php if (!empty($room['room_image'])): ?>
                            <img src="<?php echo htmlspecialchars($room['room_image']); ?>" class="w-full h-48 object-cover cursor-pointer"
                                alt="<?php echo htmlspecialchars($room['room_name']); ?>" onclick="openLightbox(this.src)">
                        <?php endif; ?>
                        <div class="p-4">
                            <div class="flex justify-between items-start mb-2">
                                <h4 class="font-extrabold text-[16px] text-secondary">
                                    <?php echo htmlspecialchars($room['room_name']); ?></h4>
                                <div class="flex gap-0.5">
                                    <?php for ($i = 0; $i < $room['adults']; $i++): ?>
                                        <i class="fas fa-user text-[10px] text-neutral-600"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <p class="text-[12px] text-neutral-500 mb-3">1 extra-large double bed • 35 m²</p>

                            <div class="bg-neutral-50 p-3 rounded-lg mb-4">
                                <div class="text-[11px] text-palm font-bold mb-1"><i class="fas fa-check mr-1"></i> Free
                                    cancellation</div>
                                <div class="text-[11px] text-palm font-bold"><i class="fas fa-check mr-1"></i> No prepayment
                                    needed</div>
                            </div>

                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-[18px] font-black"><?php echo $price_label; ?></div>
                                    <div class="text-[10px] text-neutral-500 uppercase">taxes & fees included</div>
                                </div>
                                <button
                                    class="bg-secondary text-white px-5 py-2 rounded-lg font-bold text-[13px]">Select</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Guest Reviews -->
        <div class="mt-12 md:mt-16 mb-12 px-4 md:px-0">
            <h3 class="text-xl font-extrabold mb-6">Guest reviews</h3>
            <div class="flex md:grid md:grid-cols-3 overflow-x-auto no-scrollbar gap-4 pb-4 md:pb-0">
                <?php foreach ($reviews as $review): ?>
                    <div
                        class="min-w-[280px] md:min-w-0 bg-neutral-50 p-6 rounded-xl border border-neutral-100 shadow-sm shrink-0">
                        <div class="flex items-center gap-3 mb-4">
                            <div
                                class="w-10 h-10 bg-neutral-300 rounded-full flex items-center justify-center font-bold text-neutral-600 text-sm">
                                <?php echo $review['avatar']; ?>
                            </div>
                            <div>
                                <div class="font-bold text-sm"><?php echo htmlspecialchars($review['name']); ?></div>
                                <div class="text-[11px] text-neutral-500">
                                    <?php echo htmlspecialchars($review['country']); ?></div>
                            </div>
                        </div>
                        <p class="text-[13px] italic text-neutral-700 leading-relaxed">
                            "<?php echo htmlspecialchars($review['text']); ?>"
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-8">
                <a href="#" class="text-secondary font-bold hover:underline text-[14px]">Read all 1,204 reviews</a>
            </div>
        </div>

    </main>

    <!-- Sticky Mobile Bottom Bar -->
    <div
        class="md:hidden fixed bottom-0 left-0 w-full bg-white border-t border-neutral-200 p-4 flex items-center justify-between z-50 shadow-[0_-4px_10px_rgba(0,0,0,0.05)]">
        <div>
            <?php
            $lowest_price = !empty($rooms) ? min(array_column($rooms, 'price_lkr')) : 0;
            $display_lowest = number_format($currency === 'USD' ? ceil($lowest_price / $exchange_rate) : $lowest_price);
            ?>
            <div class="text-[18px] font-black text-primary leading-none">
                <?php echo ($currency === 'USD' ? 'USD ' : 'LKR ') . $display_lowest; ?></div>
            <div class="text-[10px] text-neutral-500 uppercase font-bold mt-1">Starting price</div>
        </div>
        <button
            class="bg-secondary text-white px-8 py-3 rounded-lg font-bold text-[15px] shadow-lg shadow-secondary/20">Select
            rooms</button>
    </div>

    <!-- Simple Footer -->
    <footer class="bg-neutral-50 border-t border-neutral-200 py-12 px-4 mt-12">
        <div class="max-w-[1100px] mx-auto grid grid-cols-2 md:grid-cols-4 gap-8">
            <div>
                <h4 class="font-bold text-sm mb-4">Support</h4>
                <ul class="text-[12px] space-y-2 text-neutral-600">
                    <li class="hover:underline cursor-pointer">Help Center</li>
                    <li class="hover:underline cursor-pointer">Safety Resource Center</li>
                    <li class="hover:underline cursor-pointer">Terms & Conditions</li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold text-sm mb-4">Discover</h4>
                <ul class="text-[12px] space-y-2 text-neutral-600">
                    <li class="hover:underline cursor-pointer">Travel articles</li>
                    <li class="hover:underline cursor-pointer">Seasonal deals</li>
                    <li class="hover:underline cursor-pointer">Holiday rentals</li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold text-sm mb-4">Partner with us</h4>
                <ul class="text-[12px] space-y-2 text-neutral-600">
                    <li class="hover:underline cursor-pointer">List your property</li>
                    <li class="hover:underline cursor-pointer">Become an affiliate</li>
                </ul>
            </div>
            <div>
                <h4 class="font-bold text-sm mb-4">About</h4>
                <ul class="text-[12px] space-y-2 text-neutral-600">
                    <li class="hover:underline cursor-pointer">About StayCase</li>
                    <li class="hover:underline cursor-pointer">Careers</li>
                    <li class="hover:underline cursor-pointer">Sustainability</li>
                </ul>
            </div>
        </div>
        <div class="max-w-[1100px] mx-auto text-center mt-12 text-[11px] text-neutral-400">
            © 2026 Bookingjaunt.com All rights reserved.
        </div>
    </footer>

    <!-- Lightbox Modal -->
    <div id="myLightbox" class="lightbox-modal" onclick="closeLightbox()">
        <span class="close-lightbox" onclick="closeLightbox()">&times;</span>
        <img class="lightbox-content" id="imgLightbox">
    </div>

</body>

</html>