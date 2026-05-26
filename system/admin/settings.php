<?php
require_once '../../config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
	header("Location: ../../admin.php");
	exit();
}

$feedback = '';
$error = '';

$upload_dir = __DIR__ . '/../../uploads/hero/';
$upload_web_path = 'uploads/hero/';

$dest_upload_dir = __DIR__ . '/../../uploads/destinations/';
$dest_upload_web_path = 'uploads/destinations/';

if (!is_dir($upload_dir)) {
	mkdir($upload_dir, 0755, true);
}

if (!is_dir($dest_upload_dir)) {
	mkdir($dest_upload_dir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
	$action = $_POST['action'];

	if ($action === 'upload_bg') {
		if (!isset($_FILES['bg_image']) || $_FILES['bg_image']['error'] !== UPLOAD_ERR_OK) {
			$error = 'Please select a valid image file.';
		} else {
			$file = $_FILES['bg_image'];
			$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
			$allowed_images = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

			if (!in_array($ext, $allowed_images, true)) {
				$error = 'Unsupported file type. Use JPG, PNG, WEBP, or GIF.';
			} else {
				$admin_bg_dir = __DIR__ . '/../../uploads/admin_bg/';
				if (!is_dir($admin_bg_dir)) {
					mkdir($admin_bg_dir, 0755, true);
				}
				
				// Clean directory first to avoid hoarding files, but keep default.png
				$files = glob($admin_bg_dir . '*');
				foreach ($files as $f) {
					if (is_file($f) && basename($f) !== 'default.png') {
						unlink($f);
					}
				}

				$filename = 'admin_bg_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
				$target_path = $admin_bg_dir . $filename;

				if (move_uploaded_file($file['tmp_name'], $target_path)) {
					// Store setting in database table admin_settings
					$db_path = 'uploads/admin_bg/' . $filename;
					$stmt = $pdo->prepare("INSERT INTO admin_settings (setting_key, setting_value) VALUES ('background_path', ?) 
											ON DUPLICATE KEY UPDATE setting_value = ?");
					$stmt->execute([$db_path, $db_path]);
					$feedback = 'Admin background image updated.';
				} else {
					$error = 'Upload failed. Please try again.';
				}
			}
		}
	}

	if ($action === 'reset_bg') {
		// Delete any custom files in directory
		$admin_bg_dir = __DIR__ . '/../../uploads/admin_bg/';
		$files = glob($admin_bg_dir . '*');
		foreach ($files as $f) {
			if (is_file($f) && basename($f) !== 'default.png') {
				unlink($f);
			}
		}
		
		// Update database setting to empty
		$stmt = $pdo->prepare("UPDATE admin_settings SET setting_value = '' WHERE setting_key = 'background_path'");
		$stmt->execute();
		$feedback = 'Admin background reset to default.';
	}

	if ($action === 'upload') {
		if (!isset($_FILES['media']) || $_FILES['media']['error'] !== UPLOAD_ERR_OK) {
			$error = 'Please select a valid image or video file.';
		} else {
			$file = $_FILES['media'];
			$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
			$allowed_images = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
			$allowed_videos = ['mp4', 'webm', 'ogg'];

			$media_type = '';
			if (in_array($ext, $allowed_images, true)) {
				$media_type = 'image';
			} elseif (in_array($ext, $allowed_videos, true)) {
				$media_type = 'video';
			}

			if ($media_type === '') {
				$error = 'Unsupported file type. Use JPG, PNG, WEBP, GIF, MP4, WEBM, or OGG.';
			} else {
				$filename = 'hero_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
				$target_path = $upload_dir . $filename;

				if (move_uploaded_file($file['tmp_name'], $target_path)) {
					$sort_order = (int)$pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM hero_slides")->fetchColumn();
					$is_active = isset($_POST['is_active']) ? 1 : 0;
					$stmt = $pdo->prepare("INSERT INTO hero_slides (media_path, media_type, sort_order, is_active) VALUES (?, ?, ?, ?)");
					$stmt->execute([$upload_web_path . $filename, $media_type, $sort_order, $is_active]);
					$feedback = 'Hero slide added.';
				} else {
					$error = 'Upload failed. Please try again.';
				}
			}
		}
	}

	if ($action === 'toggle' && isset($_POST['slide_id'], $_POST['is_active'])) {
		$slide_id = (int)$_POST['slide_id'];
		$is_active = (int)$_POST['is_active'] === 1 ? 1 : 0;
		$stmt = $pdo->prepare("UPDATE hero_slides SET is_active = ? WHERE id = ?");
		$stmt->execute([$is_active, $slide_id]);
		$feedback = 'Slide status updated.';
	}

	if ($action === 'sort' && isset($_POST['slide_id'], $_POST['sort_order'])) {
		$slide_id = (int)$_POST['slide_id'];
		$sort_order = (int)$_POST['sort_order'];
		$stmt = $pdo->prepare("UPDATE hero_slides SET sort_order = ? WHERE id = ?");
		$stmt->execute([$sort_order, $slide_id]);
		$feedback = 'Slide order updated.';
	}

	if ($action === 'delete' && isset($_POST['slide_id'])) {
		$slide_id = (int)$_POST['slide_id'];
		$stmt = $pdo->prepare("SELECT media_path FROM hero_slides WHERE id = ?");
		$stmt->execute([$slide_id]);
		$slide = $stmt->fetch();

		if ($slide) {
			$file_path = __DIR__ . '/../../' . $slide['media_path'];
			if (is_file($file_path)) {
				unlink($file_path);
			}
			$del = $pdo->prepare("DELETE FROM hero_slides WHERE id = ?");
			$del->execute([$slide_id]);
			$feedback = 'Slide deleted.';
		}
	}

	if ($action === 'dest_upload') {
		if (!isset($_FILES['dest_media']) || $_FILES['dest_media']['error'] !== UPLOAD_ERR_OK) {
			$error = 'Please select a valid destination image or video file.';
		} else {
			$file = $_FILES['dest_media'];
			$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
			$allowed_images = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
			$allowed_videos = ['mp4', 'webm', 'ogg'];

			$media_type = '';
			if (in_array($ext, $allowed_images, true)) {
				$media_type = 'image';
			} elseif (in_array($ext, $allowed_videos, true)) {
				$media_type = 'video';
			}

			if ($media_type === '') {
				$error = 'Unsupported file type. Use JPG, PNG, WEBP, GIF, MP4, WEBM, or OGG.';
			} else {
				$filename = 'dest_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
				$target_path = $dest_upload_dir . $filename;

				if (move_uploaded_file($file['tmp_name'], $target_path)) {
					$destination_name = trim($_POST['destination_name'] ?? '');
					$district_name = trim($_POST['district_name'] ?? '');
					$description = trim($_POST['description'] ?? '');
					$sort_order = (int)$pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM popular_destinations")->fetchColumn();
					$is_active = isset($_POST['dest_is_active']) ? 1 : 0;

					$stmt = $pdo->prepare("INSERT INTO popular_destinations (destination_name, district_name, description, media_path, media_type, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
					$stmt->execute([
						$destination_name,
						$district_name,
						$description,
						$dest_upload_web_path . $filename,
						$media_type,
						$sort_order,
						$is_active
					]);
					$feedback = 'Popular destination added.';
				} else {
					$error = 'Upload failed. Please try again.';
				}
			}
		}
	}

	if ($action === 'dest_toggle' && isset($_POST['destination_id'], $_POST['is_active'])) {
		$destination_id = (int)$_POST['destination_id'];
		$is_active = (int)$_POST['is_active'] === 1 ? 1 : 0;
		$stmt = $pdo->prepare("UPDATE popular_destinations SET is_active = ? WHERE id = ?");
		$stmt->execute([$is_active, $destination_id]);
		$feedback = 'Destination status updated.';
	}

	if ($action === 'dest_sort' && isset($_POST['destination_id'], $_POST['sort_order'])) {
		$destination_id = (int)$_POST['destination_id'];
		$sort_order = (int)$_POST['sort_order'];
		$stmt = $pdo->prepare("UPDATE popular_destinations SET sort_order = ? WHERE id = ?");
		$stmt->execute([$sort_order, $destination_id]);
		$feedback = 'Destination order updated.';
	}

	if ($action === 'dest_delete' && isset($_POST['destination_id'])) {
		$destination_id = (int)$_POST['destination_id'];
		$stmt = $pdo->prepare("SELECT media_path FROM popular_destinations WHERE id = ?");
		$stmt->execute([$destination_id]);
		$destination = $stmt->fetch();

		if ($destination) {
			$file_path = __DIR__ . '/../../' . $destination['media_path'];
			if (is_file($file_path)) {
				unlink($file_path);
			}
			$del = $pdo->prepare("DELETE FROM popular_destinations WHERE id = ?");
			$del->execute([$destination_id]);
			$feedback = 'Destination deleted.';
		}
	}
}

$slides = [];
try {
	$slides = $pdo->query("SELECT * FROM hero_slides ORDER BY sort_order, id")->fetchAll();
} catch (PDOException $e) {
	$error = 'Hero slides table is missing. Please add it to the database.';
}

$destinations = [];
try {
	$destinations = $pdo->query("SELECT * FROM popular_destinations ORDER BY sort_order, id")->fetchAll();
} catch (PDOException $e) {
	if ($error === '') {
		$error = 'Popular destinations table is missing. Please add it to the database.';
	}
}

$districts = [
	'Ampara', 'Anuradhapura', 'Badulla', 'Batticaloa', 'Colombo', 'Galle', 'Gampaha',
	'Hambantota', 'Jaffna', 'Kalutara', 'Kandy', 'Kegalle', 'Kilinochchi', 'Kurunegala',
	'Mannar', 'Matale', 'Matara', 'Monaragala', 'Mullaitivu', 'Nuwara Eliya', 'Polonnaruwa',
	'Puttalam', 'Ratnapura', 'Trincomalee', 'Vavuniya'
];

$current_bg = '';
try {
	$stmt = $pdo->prepare("SELECT setting_value FROM admin_settings WHERE setting_key = 'background_path'");
	$stmt->execute();
	$current_bg = $stmt->fetchColumn();
} catch (PDOException $e) {
	// Table not found/error
}

function h($value) {
	return htmlspecialchars((string)$value);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Settings - Bookingjaunt</title>
	<script src="https://cdn.tailwindcss.com"></script>
	<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
	<style>
		body {
			font-family: 'Plus Jakarta Sans', sans-serif;
			background-color: #f8fafc;
		}
	</style>
</head>

<body class="flex min-h-screen overflow-hidden">

	<?php include 'sidebar.php'; ?>

	<main class="flex-1 lg:ml-64 overflow-y-auto h-screen bg-[#f8fafc]">
		<header class="bg-white/80 backdrop-blur-md border-b border-gray-200 sticky top-0 z-40 px-4 lg:px-8 py-6 flex items-center gap-4">
			<button onclick="toggleSidebar()" class="lg:hidden w-10 h-10 bg-gray-50 rounded-xl flex items-center justify-center text-[#003580] hover:bg-gray-100 transition-all">
				<i class="fas fa-bars-staggered"></i>
			</button>
			<div class="flex flex-col gap-1">
				<h1 class="text-2xl font-black text-[#003580]">Settings</h1>
				<p class="text-xs text-gray-500 font-bold uppercase tracking-widest hidden sm:block">Hero slideshow customization</p>
			</div>
		</header>

		<div class="p-4 lg:p-8 space-y-6">
			<?php if ($feedback !== ''): ?>
				<div class="bg-emerald-50 border border-emerald-100 text-emerald-700 px-4 py-3 rounded-xl text-sm">
					<?php echo h($feedback); ?>
				</div>
			<?php endif; ?>
			<?php if ($error !== ''): ?>
				<div class="bg-red-50 border border-red-100 text-red-700 px-4 py-3 rounded-xl text-sm">
					<?php echo h($error); ?>
				</div>
			<?php endif; ?>

			<div class="bg-white rounded-2xl border border-gray-100 p-6">
				<h2 class="text-lg font-bold text-[#003580] mb-4">Admin Background Styling</h2>
				<div class="flex flex-col md:flex-row gap-8 items-start">
					<div class="flex-1 w-full">
						<form method="POST" enctype="multipart/form-data" class="space-y-4">
							<input type="hidden" name="action" value="upload_bg">
							<div>
								<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Upload New Background Image</label>
								<input type="file" name="bg_image" accept="image/*" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm" required>
							</div>
							<div class="flex gap-3">
								<button type="submit" class="px-5 py-2.5 rounded-xl bg-[#006ce4] text-white text-xs font-bold uppercase tracking-widest hover:bg-[#0053b3] transition-colors">Apply Background</button>
								<?php if (!empty($current_bg)): ?>
									<button type="submit" name="action" value="reset_bg" class="px-5 py-2.5 rounded-xl bg-red-50 text-red-600 text-xs font-bold uppercase tracking-widest hover:bg-red-100 transition-colors">Reset to Default</button>
								<?php endif; ?>
							</div>
						</form>
					</div>
					<div class="w-full md:w-64">
						<p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">Current Background Preview</p>
						<?php if (!empty($current_bg)): ?>
							<img src="../../<?php echo h($current_bg); ?>" class="w-full h-32 object-cover rounded-xl border border-gray-100 shadow-sm" alt="Current Background">
						<?php else: ?>
							<div class="w-full h-32 rounded-xl border border-dashed border-gray-200 flex flex-col items-center justify-center bg-gray-50/50 text-gray-400">
								<i class="fas fa-image text-2xl mb-1"></i>
								<span class="text-[10px] font-bold uppercase">Default Theme Active</span>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="bg-white rounded-2xl border border-gray-100 p-6">
				<h2 class="text-lg font-bold text-[#003580] mb-4">Add Hero Slide</h2>
				<form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
					<input type="hidden" name="action" value="upload">
					<div class="md:col-span-2">
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Media (Image or Video)</label>
						<input type="file" name="media" accept="image/*,video/*" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm" required>
					</div>
					<div class="flex items-center gap-2">
						<input type="checkbox" name="is_active" id="slideActive" checked>
						<label for="slideActive" class="text-sm text-gray-600">Active</label>
					</div>
					<div>
						<button class="px-5 py-2 rounded-xl bg-[#006ce4] text-white text-xs font-bold uppercase tracking-widest">Upload</button>
					</div>
				</form>
			</div>

			<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
				<div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
					<h2 class="font-bold text-[#003580]">Current Slides</h2>
					<span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Total <?php echo count($slides); ?></span>
				</div>

				<?php if (empty($slides)): ?>
					<div class="p-10 text-center">
						<h3 class="text-lg font-bold text-gray-700">No slides found</h3>
						<p class="text-xs text-gray-400 mt-2">Upload images or videos to build the hero slideshow.</p>
					</div>
				<?php else: ?>
					<div class="divide-y divide-gray-100">
						<?php foreach ($slides as $slide): ?>
							<div class="p-6 flex flex-col lg:flex-row gap-6">
								<div class="w-full lg:w-64">
									<?php if ($slide['media_type'] === 'video'): ?>
										<video class="w-full h-40 object-cover rounded-xl border border-gray-100" muted playsinline controls>
											<source src="../../<?php echo h($slide['media_path']); ?>">
										</video>
									<?php else: ?>
										<img src="../../<?php echo h($slide['media_path']); ?>" class="w-full h-40 object-cover rounded-xl border border-gray-100" alt="Hero slide">
									<?php endif; ?>
								</div>
								<div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
									<div>
										<p class="text-xs font-bold uppercase tracking-widest text-gray-400">Type</p>
										<p class="text-sm font-semibold text-gray-700"><?php echo h($slide['media_type']); ?></p>
									</div>
									<div>
										<p class="text-xs font-bold uppercase tracking-widest text-gray-400">Status</p>
										<p class="text-sm font-semibold text-gray-700"><?php echo (int)$slide['is_active'] === 1 ? 'Active' : 'Hidden'; ?></p>
									</div>
									<div>
										<p class="text-xs font-bold uppercase tracking-widest text-gray-400">Order</p>
										<p class="text-sm font-semibold text-gray-700"><?php echo (int)$slide['sort_order']; ?></p>
									</div>
								</div>
								<div class="flex items-center gap-2">
									<form method="POST">
										<input type="hidden" name="action" value="toggle">
										<input type="hidden" name="slide_id" value="<?php echo (int)$slide['id']; ?>">
										<input type="hidden" name="is_active" value="<?php echo (int)$slide['is_active'] === 1 ? 0 : 1; ?>">
										<button class="px-3 py-2 rounded-lg text-[11px] font-bold uppercase tracking-widest border border-blue-200 text-blue-600 hover:bg-blue-50">
											<?php echo (int)$slide['is_active'] === 1 ? 'Hide' : 'Show'; ?>
										</button>
									</form>
									<form method="POST" onsubmit="return confirm('Delete this slide?');">
										<input type="hidden" name="action" value="delete">
										<input type="hidden" name="slide_id" value="<?php echo (int)$slide['id']; ?>">
										<button class="px-3 py-2 rounded-lg text-[11px] font-bold uppercase tracking-widest border border-red-200 text-red-600 hover:bg-red-50">Delete</button>
									</form>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="bg-white rounded-2xl border border-gray-100 p-6">
				<h2 class="text-lg font-bold text-[#003580] mb-4">Add Popular Destination</h2>
				<form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
					<input type="hidden" name="action" value="dest_upload">
					<div class="md:col-span-2">
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Media (Image or Video)</label>
						<input type="file" name="dest_media" accept="image/*,video/*" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm" required>
					</div>
					<div>
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Destination Name</label>
						<input type="text" name="destination_name" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm" placeholder="E.g. Kandy" required>
					</div>
					<div>
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">District (Optional)</label>
						<input type="text" name="district_name" list="districtList" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm" placeholder="Sri Lankan district or city">
						<datalist id="districtList">
							<?php foreach ($districts as $district): ?>
								<option value="<?php echo h($district); ?>"></option>
							<?php endforeach; ?>
						</datalist>
					</div>
					<div class="md:col-span-2">
						<label class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Short Description</label>
						<input type="text" name="description" class="mt-2 w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-100 text-sm" placeholder="E.g. Temple visits, botanical gardens & lake walks" required>
					</div>
					<div class="flex items-center gap-2">
						<input type="checkbox" name="dest_is_active" id="destActive" checked>
						<label for="destActive" class="text-sm text-gray-600">Active</label>
					</div>
					<div>
						<button class="px-5 py-2 rounded-xl bg-[#006ce4] text-white text-xs font-bold uppercase tracking-widest">Upload</button>
					</div>
				</form>
			</div>

			<div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
				<div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
					<h2 class="font-bold text-[#003580]">Popular Destinations</h2>
					<span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Total <?php echo count($destinations); ?></span>
				</div>

				<?php if (empty($destinations)): ?>
					<div class="p-10 text-center">
						<h3 class="text-lg font-bold text-gray-700">No destinations found</h3>
						<p class="text-xs text-gray-400 mt-2">Upload media to show popular destinations on the homepage.</p>
					</div>
				<?php else: ?>
					<div class="divide-y divide-gray-100">
						<?php foreach ($destinations as $destination): ?>
							<div class="p-6 flex flex-col lg:flex-row gap-6">
								<div class="w-full lg:w-64">
									<?php if ($destination['media_type'] === 'video'): ?>
										<video class="w-full h-40 object-cover rounded-xl border border-gray-100" muted playsinline controls>
											<source src="../../<?php echo h($destination['media_path']); ?>">
										</video>
									<?php else: ?>
										<img src="../../<?php echo h($destination['media_path']); ?>" class="w-full h-40 object-cover rounded-xl border border-gray-100" alt="Destination media">
									<?php endif; ?>
								</div>
								<div class="flex-1 grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
									<div>
										<p class="text-xs font-bold uppercase tracking-widest text-gray-400">Destination</p>
										<p class="text-sm font-semibold text-gray-700"><?php echo h($destination['destination_name']); ?></p>
										<p class="text-xs text-gray-400"><?php echo h($destination['district_name'] ?: ''); ?></p>
									</div>
									<div>
										<p class="text-xs font-bold uppercase tracking-widest text-gray-400">Description</p>
										<p class="text-sm text-gray-700"><?php echo h($destination['description']); ?></p>
									</div>
									<div>
										<p class="text-xs font-bold uppercase tracking-widest text-gray-400">Status</p>
										<p class="text-sm font-semibold text-gray-700"><?php echo (int)$destination['is_active'] === 1 ? 'Active' : 'Hidden'; ?></p>
									</div>
								</div>
								<div class="flex flex-wrap items-center gap-2">
									<div class="px-3 py-2 rounded-lg text-[11px] font-bold uppercase tracking-widest border border-gray-200 text-gray-600">
										Order <?php echo (int)$destination['sort_order']; ?>
									</div>
									<form method="POST">
										<input type="hidden" name="action" value="dest_toggle">
										<input type="hidden" name="destination_id" value="<?php echo (int)$destination['id']; ?>">
										<input type="hidden" name="is_active" value="<?php echo (int)$destination['is_active'] === 1 ? 0 : 1; ?>">
										<button class="px-3 py-2 rounded-lg text-[11px] font-bold uppercase tracking-widest border border-blue-200 text-blue-600 hover:bg-blue-50">
											<?php echo (int)$destination['is_active'] === 1 ? 'Hide' : 'Show'; ?>
										</button>
									</form>
									<form method="POST" onsubmit="return confirm('Delete this destination?');">
										<input type="hidden" name="action" value="dest_delete">
										<input type="hidden" name="destination_id" value="<?php echo (int)$destination['id']; ?>">
										<button class="px-3 py-2 rounded-lg text-[11px] font-bold uppercase tracking-widest border border-red-200 text-red-600 hover:bg-red-50">Delete</button>
									</form>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</main>

</body>

</html>
