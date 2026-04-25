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

// Fetch Media
$media_stmt = $pdo->prepare("SELECT media_path, is_featured FROM property_media WHERE property_id = ? AND media_type = 'image' ORDER BY is_featured DESC, sort_order ASC");
$media_stmt->execute([$property_id]);
$media_rows = $media_stmt->fetchAll(PDO::FETCH_ASSOC);

$images = [];
if (!empty($property['cover_image'])) {
    $images[] = $property['cover_image'];
}
if (!empty($media_rows)) {
    foreach ($media_rows as $row) {
        if (!in_array($row['media_path'], $images)) {
            $images[] = $row['media_path'];
        }
    }
}

$totalImages = count($images);
$currency = $_SESSION['currency'] ?? 'LKR';
$exchange_rate = 300;

// Stars logic
$stars = 0;
if ($property['hotel_category'] == 'budget_friendly') $stars = 3;
if ($property['hotel_category'] == 'luxury') $stars = 4;
if ($property['hotel_category'] == 'super_luxury') $stars = 5;

$reviews = [
    ['name' => 'John Doe', 'country' => 'United States', 'avatar' => 'JD', 'text' => 'The location was absolutely perfect, right next to the beach.'],
    ['name' => 'Sarah Miller', 'country' => 'United Kingdom', 'avatar' => 'SM', 'text' => 'Exceptional spa treatments! Highly recommended.'],
    ['name' => 'Alex Murphy', 'country' => 'Canada', 'avatar' => 'AM', 'text' => 'Modern rooms with stunning views. best place to catch the sunset.']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($property['property_name']); ?> - Bookingjaunt</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'], display: ['Outfit', 'sans-serif'] },
                    colors: {
                        brand: { 50: '#f0f6ff', 100: '#e0edff', 600: '#006ce4', 700: '#0057b8', 900: '#003580' },
                        palm: '#008009', gold: '#febb02'
                    }
                }
            }
        }
    </script>
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        body { font-family: 'Inter', sans-serif; color: #1a1a1a; }
        .font-display { font-family: 'Outfit', sans-serif; }
        .lightbox-modal { display: none; position: fixed; z-index: 9999; padding-top: 50px; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); backdrop-filter: blur(10px); }
        .lightbox-content { margin: auto; display: block; max-width: 90%; max-height: 80vh; border-radius: 8px; }
        .close-lightbox { position: absolute; top: 20px; right: 35px; color: #fff; font-size: 50px; cursor: pointer; }
    </style>
    <script>
        function openLightbox(src) { document.getElementById("myLightbox").style.display = "block"; document.getElementById("imgLightbox").src = src; document.body.style.overflow = "hidden"; }
        function closeLightbox() { document.getElementById("myLightbox").style.display = "none"; document.body.style.overflow = "auto"; }
        function openGallery() { document.getElementById("galleryModal").classList.remove("hidden"); document.body.style.overflow = "hidden"; }
        function closeGallery() { document.getElementById("galleryModal").classList.add("hidden"); document.body.style.overflow = "auto"; }
    </script>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <main class="max-w-[1150px] mx-auto px-4 md:px-4 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between gap-6 mb-6">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <span class="bg-neutral-600 text-white text-[10px] font-bold px-2 py-0.5 rounded">HOTEL</span>
                    <div class="flex gap-0.5 text-gold text-[11px]">
                        <?php for ($i = 0; $i < $stars; $i++): ?><i class="fas fa-star"></i><?php endfor; ?>
                    </div>
                </div>
                <h1 class="text-2xl md:text-3xl font-extrabold font-display"><?php echo htmlspecialchars($property['property_name']); ?></h1>
                <div class="flex items-center gap-2 text-[13px] text-neutral-600 mt-2">
                    <i class="fas fa-map-marker-alt text-brand-600"></i>
                    <span><?php echo htmlspecialchars($property['street_address'] . ', ' . $property['city'] . ', ' . $property['district']); ?></span>
                </div>
            </div>
            <div class="hidden md:flex items-center gap-3">
                <button class="bg-brand-600 text-white px-8 py-2.5 rounded-lg font-bold">Reserve</button>
            </div>
        </div>

        <!-- Gallery -->
        <?php if (!empty($images)): ?>
        <?php $totalImages = count($images); ?>

        <!-- Mobile: Horizontal Scroll -->
        <div class="md:hidden flex overflow-x-auto snap-x snap-mandatory no-scrollbar h-[300px] mb-6 rounded-xl">
            <?php foreach ($images as $img): ?>
                <img src="<?php echo htmlspecialchars($img); ?>" class="w-full shrink-0 snap-center object-cover" onclick="openLightbox(this.src)">
            <?php endforeach; ?>
        </div>

        <!-- Desktop: Premium Mosaic Gallery -->
        <div class="hidden md:grid grid-cols-4 grid-rows-2 gap-2 h-[480px] mb-10 rounded-xl overflow-hidden shadow-sm relative">
            <!-- Large Main Image -->
            <div class="col-span-2 row-span-2 relative group overflow-hidden border border-neutral-100">
                <img src="<?php echo htmlspecialchars($images[0]); ?>" class="w-full h-full object-cover cursor-pointer hover:scale-105 transition-transform duration-700" onclick="openLightbox(this.src)">
            </div>

            <!-- Small Images (up to 4 more) -->
            <?php 
            $smallImages = array_slice($images, 1, 4);
            foreach ($smallImages as $index => $img): 
                $isLast = ($index === 3 && $totalImages > 5);
            ?>
                <div class="relative group overflow-hidden border border-neutral-100 <?php echo $isLast ? 'bg-neutral-900' : ''; ?>">
                    <img src="<?php echo htmlspecialchars($img); ?>" 
                         class="w-full h-full object-cover cursor-pointer hover:scale-105 transition-transform duration-700 <?php echo $isLast ? 'opacity-50' : ''; ?>" 
                         onclick="openLightbox(this.src)">
                    
                    <?php if ($isLast): ?>
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-white bg-black/40 cursor-pointer" onclick="openGallery()">
                            <i class="fas fa-images text-2xl mb-2"></i>
                            <span class="font-bold text-[13px] tracking-wide">+<?php echo $totalImages - 5; ?> PHOTOS</span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <!-- Fallback placeholders if fewer than 5 images -->
            <?php for($i = count($smallImages); $i < 4; $i++): ?>
                <div class="bg-white border border-neutral-100"></div>
            <?php endfor; ?>

            <?php if ($totalImages > 5): ?>
                <button onclick="openGallery()" class="absolute bottom-4 right-4 bg-white/95 backdrop-blur-md text-brand-900 border border-neutral-200 rounded-full px-5 py-2 text-[12px] font-bold shadow-xl hover:bg-white transition-all flex items-center gap-2 z-20">
                    <i class="fas fa-th-large text-brand-600"></i>
                    <span>See all <?php echo $totalImages; ?> photos</span>
                </button>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Content -->
        <div class="grid grid-cols-1 lg:grid-cols-[1fr_350px] gap-10">
            <div>
                <p class="text-[15px] text-neutral-700 leading-relaxed mb-10"><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
                <div class="mb-10">
                    <h3 class="text-xl font-bold font-display mb-6">Most popular facilities</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-5">
                        <?php foreach ($amenities as $am): ?>
                            <div class="flex items-center gap-3 text-[14px] text-palm font-semibold">
                                <i class="fas <?php echo !empty($am['icon']) ? htmlspecialchars($am['icon']) : 'fa-check'; ?> text-brand-600"></i>
                                <span><?php echo htmlspecialchars($am['amenity_name']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="hidden lg:block">
                <div class="bg-brand-50 p-6 rounded-xl border border-brand-100 sticky top-6">
                    <h4 class="font-bold mb-4">Property highlights</h4>
                    <button class="w-full bg-brand-600 text-white py-3 rounded-lg font-bold">Reserve your stay</button>
                </div>
            </div>
        </div>

        <!-- Availability -->
        <div class="mt-12">
            <h3 class="text-2xl font-bold font-display mb-6">Availability</h3>
            <div class="hidden md:block border border-neutral-200 rounded-xl overflow-hidden shadow-sm">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-brand-900 text-white text-[11px] uppercase">
                        <tr>
                            <th class="p-4 w-[35%]">Room Type</th>
                            <th class="p-4 text-center">Sleeps</th>
                            <th class="p-4">Price</th>
                            <th class="p-4">Choices</th>
                            <th class="p-4">Select</th>
                            <th class="p-4"></th>
                        </tr>
                    </thead>
                    <tbody class="text-[13px]">
                        <?php foreach ($rooms as $room): ?>
                            <tr class="border-t border-neutral-200 hover:bg-neutral-50/50 transition-colors">
                                <td class="p-5 align-top">
                                    <div class="flex gap-4">
                                        <?php if (!empty($room['room_image'])): ?>
                                            <div class="w-24 h-24 shrink-0 rounded-lg overflow-hidden border border-neutral-100">
                                                <img src="<?php echo htmlspecialchars($room['room_image']); ?>" class="w-full h-full object-cover cursor-pointer hover:scale-110 transition-transform duration-500" onclick="openLightbox(this.src)">
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="font-bold text-brand-600 text-[16px] mb-1 hover:underline cursor-pointer"><?php echo htmlspecialchars($room['room_name']); ?></div>
                                            <div class="text-neutral-500 text-[12px] flex items-center gap-2">
                                                <i class="fas fa-bed text-neutral-400"></i>
                                                <span>1 extra-large double bed</span>
                                            </div>
                                            <div class="text-neutral-400 text-[11px] mt-2 flex items-center gap-3">
                                                <span><i class="fas fa-expand mr-1"></i> 35 m²</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-5 text-center align-top">
                                    <div class="flex justify-center gap-0.5 text-neutral-700">
                                        <?php for($i=0;$i<$room['adults'];$i++): ?>
                                            <i class="fas fa-user text-[13px]"></i>
                                        <?php endfor; ?>
                                        <?php if($room['children'] > 0): ?>
                                            <?php for($i=0;$i<$room['children'];$i++): ?>
                                                <i class="fas fa-child text-[11px]"></i>
                                            <?php endfor; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="p-5 align-top">
                                    <div class="font-black text-xl text-neutral-900">LKR <?php echo number_format($room['price_lkr']); ?></div>
                                    <div class="text-[10px] text-neutral-500 uppercase font-bold mt-1 tracking-tight">taxes & fees included</div>
                                </td>
                                <td class="p-5 align-top">
                                    <div class="space-y-2">
                                        <div class="text-palm font-bold text-[12px] flex items-center gap-2">
                                            <i class="fas fa-check text-[10px]"></i> <span>Free cancellation</span>
                                        </div>
                                        <div class="text-palm font-bold text-[12px] flex items-center gap-2">
                                            <i class="fas fa-check text-[10px]"></i> <span>No prepayment needed</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-5 align-top">
                                    <select class="w-full p-2 border border-neutral-300 rounded-lg outline-none focus:ring-2 focus:ring-brand-600/20 bg-white text-sm font-medium">
                                        <option>0</option>
                                        <option>1</option>
                                        <option>2</option>
                                    </select>
                                </td>
                                <td class="p-5 align-top">
                                    <button class="w-full bg-brand-600 text-white py-2.5 rounded-lg font-bold hover:bg-brand-700 transition-all shadow-md shadow-brand-600/10 mb-2">
                                        Reserve
                                    </button>
                                    <p class="text-[10px] text-neutral-500 font-medium text-center">Confirmation is instant</p>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Mobile Room Cards -->
            <div class="md:hidden space-y-5">
                <?php foreach ($rooms as $room): ?>
                    <div class="bg-white border border-neutral-200 rounded-2xl overflow-hidden shadow-sm">
                        <?php if (!empty($room['room_image'])): ?>
                            <div class="h-48 overflow-hidden">
                                <img src="<?php echo htmlspecialchars($room['room_image']); ?>" class="w-full h-full object-cover" onclick="openLightbox(this.src)">
                            </div>
                        <?php endif; ?>
                        <div class="p-5">
                            <div class="flex justify-between items-start mb-3">
                                <h4 class="font-extrabold text-[18px] text-neutral-900 leading-tight font-display"><?php echo htmlspecialchars($room['room_name']); ?></h4>
                                <div class="flex gap-0.5 text-neutral-600">
                                    <?php for($i=0;$i<$room['adults'];$i++): ?><i class="fas fa-user text-[11px]"></i><?php endfor; ?>
                                </div>
                            </div>
                            <p class="text-[13px] text-neutral-500 mb-4 font-medium">1 extra-large double bed • 35 m²</p>
                            <div class="bg-brand-50 p-4 rounded-xl mb-5 space-y-2">
                                <div class="text-[12px] text-palm font-bold flex items-center gap-2">
                                    <i class="fas fa-check text-[10px]"></i> Free cancellation
                                </div>
                            </div>
                            <div class="flex items-center justify-between">
                                <div class="font-black text-xl">LKR <?php echo number_format($room['price_lkr']); ?></div>
                                <button class="bg-brand-600 text-white px-6 py-2.5 rounded-xl font-bold text-[14px]">Select</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <footer class="bg-neutral-50 border-t border-neutral-200 py-12 px-6 mt-16">
        <div class="max-w-[1150px] mx-auto text-center text-sm text-neutral-500">
            © 2026 Bookingjaunt.com All rights reserved.
        </div>
    </footer>

    <div id="myLightbox" class="lightbox-modal" onclick="closeLightbox()"><span class="close-lightbox">&times;</span><img class="lightbox-content" id="imgLightbox"></div>
    <div id="galleryModal" class="fixed inset-0 z-[9998] bg-black/95 hidden overflow-y-auto" onclick="if(event.target===this)closeGallery()">
        <div class="max-w-5xl mx-auto p-6 text-white">
            <div class="flex justify-between mb-6"><h3 class="text-xl font-bold">All Photos</h3><button onclick="closeGallery()" class="text-3xl">&times;</button></div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                <?php foreach ($images as $img): ?><img src="<?php echo htmlspecialchars($img); ?>" class="rounded-lg object-cover w-full h-48 cursor-pointer" onclick="openLightbox(this.src)"><?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
