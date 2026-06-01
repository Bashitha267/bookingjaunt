<?php
require_once '../../config.php';
session_start();

require_once '../auth_guard.php';
requireRole(['site_staff']);

$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$property = null;
$error = '';

if ($property_id <= 0) {
	$error = 'Property ID is missing.';
} else {
	$stmt = $pdo->prepare("SELECT p.*, u.first_name, u.last_name, u.email AS owner_email,
								  u.phone_number AS owner_phone, u.whatsapp_number AS owner_whatsapp,
								  u.nic_passport AS owner_nic, u.address AS owner_address, u.country AS owner_country
						   FROM properties p
						   JOIN users u ON p.owner_id = u.id
						   WHERE p.id = ?");
	$stmt->execute([$property_id]);
	$property = $stmt->fetch();
	if (!$property) {
		$error = 'Property not found.';
	}
}

$amenities = [];
$custom_amenities = [];
$media_items = [];
$rooms = [];
$room_types = [];
$extra_services = [];
$bank_details = [];
$staff = [];
$staff_names = [];

if ($property) {
	$stmt = $pdo->prepare("SELECT am.category, am.amenity_name
						   FROM property_amenities pa
						   JOIN amenities_master am ON pa.amenity_id = am.id
						   WHERE pa.property_id = ?
						   ORDER BY am.category, am.amenity_name");
	$stmt->execute([$property_id]);
	$amenities = $stmt->fetchAll();

	$stmt = $pdo->prepare("SELECT amenity_name FROM property_custom_amenities WHERE property_id = ? ORDER BY id");
	$stmt->execute([$property_id]);
	$custom_amenities = $stmt->fetchAll();

	$stmt = $pdo->prepare("SELECT media_path, media_type, is_featured, sort_order, created_at
						   FROM property_media WHERE property_id = ? ORDER BY sort_order, id");
	$stmt->execute([$property_id]);
	$media_items = $stmt->fetchAll();

	$stmt = $pdo->prepare("SELECT room_name, adults, children, room_image, price_lkr, price_usd
						   FROM property_rooms WHERE property_id = ? ORDER BY id");
	$stmt->execute([$property_id]);
	$rooms = $stmt->fetchAll();

	$stmt = $pdo->prepare("SELECT room_name, total_rooms, max_adults, max_children, base_price, is_hall, room_image, price_lkr, price_usd
						   FROM room_types WHERE property_id = ? ORDER BY id");
	$stmt->execute([$property_id]);
	$room_types = $stmt->fetchAll();

	$stmt = $pdo->prepare("SELECT service_name, price, pricing_type, description, status
						   FROM extra_services WHERE property_id = ? ORDER BY id");
	$stmt->execute([$property_id]);
	$extra_services = $stmt->fetchAll();

	$stmt = $pdo->prepare("SELECT bank_name, account_number, account_holder_name
						   FROM property_bank_details WHERE property_id = ? ORDER BY id");
	$stmt->execute([$property_id]);
	$bank_details = $stmt->fetchAll();

	$stmt = $pdo->prepare("SELECT ps.staff_role, u.first_name, u.last_name, u.email, u.phone_number
						   FROM property_staff ps
						   JOIN users u ON ps.user_id = u.id
						   WHERE ps.property_id = ?");
	$stmt->execute([$property_id]);
	$staff = $stmt->fetchAll();

	$stmt = $pdo->prepare("SELECT staff_name FROM property_staff_names WHERE property_id = ? ORDER BY id");
	$stmt->execute([$property_id]);
	$staff_names = $stmt->fetchAll();

	// Proposed Request Overlay logic
	$request_id = isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0;
	if ($request_id > 0) {
		$req_stmt = $pdo->prepare("SELECT * FROM property_requests WHERE id = ?");
		$req_stmt->execute([$request_id]);
		$request = $req_stmt->fetch();
		if ($request) {
			$new_data = json_decode($request['new_data'], true);
			if (is_array($new_data)) {
				// Overlay simple fields on $property
				foreach ($new_data as $k => $v) {
					if (!in_array($k, ['rooms', 'property_photos', 'property_videos', 'popular_amenities', 'custom_rules', 'rules', 'tourist_attractions', 'custom_payments', 'custom_food', 'custom_security'])) {
						$property[$k] = $v;
					}
				}
				
				// Handle boolean conversions
				$booleans = ['smoking_allowed', 'pets_allowed', 'events_allowed', 'id_required',
							 'pay_cash', 'pay_cc', 'pay_debit', 'pay_online', 'pay_bank', 'pay_installments',
							 'food_breakfast_included', 'food_restaurant_available', 'food_room_service', 'food_room_service_247',
							 'food_vegetarian', 'food_vegan', 'food_halal', 'food_buffet', 'food_delivery_allowed',
							 'food_dietary_options', 'food_kitchen_in_room', 'food_minibar',
							 'sec_staff_247', 'sec_cctv', 'sec_smoke_detectors', 'sec_fire_extinguishers', 'sec_fire_alarm',
							 'sec_emergency_exit_plan', 'sec_key_card_access', 'sec_digital_lock', 'sec_biometric_access',
							 'sec_safe_box', 'sec_luggage_storage', 'sec_female_floor', 'sec_panic_button', 'sec_first_aid',
							 'sec_medical_support'];
				foreach ($booleans as $bool_key) {
					if (isset($new_data[$bool_key])) {
						$property[$bool_key] = ($new_data[$bool_key] == '1' || $new_data[$bool_key] === 1) ? 1 : 0;
					}
				}

				// Overlay custom json fields
				if (isset($new_data['rules'])) {
					$property['rules_json'] = json_encode($new_data['rules']);
				}
				if (isset($new_data['popular_amenities'])) {
					$property['popular_amenities_json'] = json_encode($new_data['popular_amenities']);
				}
				if (isset($new_data['custom_rules'])) {
					$property['custom_rules_json'] = json_encode($new_data['custom_rules']);
				}
				if (isset($new_data['tourist_attractions'])) {
					$property['tourist_attractions'] = json_encode($new_data['tourist_attractions']);
				}
				if (isset($new_data['custom_payments'])) {
					$property['custom_payments_json'] = json_encode($new_data['custom_payments']);
				}
				if (isset($new_data['custom_food'])) {
					$property['custom_food_json'] = json_encode($new_data['custom_food']);
				}
				if (isset($new_data['custom_security'])) {
					$property['custom_security_json'] = json_encode($new_data['custom_security']);
				}

				// Overlay Rooms
				if (isset($new_data['rooms']) && is_array($new_data['rooms'])) {
					$rooms = [];
					foreach ($new_data['rooms'] as $r) {
						if (!empty($r['name'])) {
							$rooms[] = [
								'room_name' => $r['name'],
								'adults' => $r['adults'] ?? 2,
								'children' => $r['children'] ?? 0,
								'price_lkr' => $r['price_lkr'] ?? 0,
								'price_usd' => $r['price_usd'] ?? 0,
								'room_image' => $r['image'] ?? ''
							];
						}
					}
				}

				// Overlay Media
				$media_items = [];
				if (isset($new_data['property_photos']) && is_array($new_data['property_photos'])) {
					foreach ($new_data['property_photos'] as $idx => $photo) {
						if (!empty($photo)) {
							$media_items[] = [
								'media_type' => 'image',
								'media_path' => $photo,
								'is_featured' => ($idx === 0) ? 1 : 0,
								'sort_order' => $idx,
								'created_at' => date('Y-m-d H:i:s')
							];
						}
					}
				}
				if (isset($new_data['property_videos']) && is_array($new_data['property_videos'])) {
					foreach ($new_data['property_videos'] as $idx => $video) {
						if (!empty($video)) {
							$media_items[] = [
								'media_type' => 'video',
								'media_path' => $video,
								'is_featured' => 0,
								'sort_order' => $idx + 100,
								'created_at' => date('Y-m-d H:i:s')
							];
						}
					}
				}

				// Overlay Amenities
				if (isset($new_data['popular_amenities']) && is_array($new_data['popular_amenities'])) {
					$amenities = [];
					if (!empty($new_data['popular_amenities'])) {
						$ids_str = implode(',', array_map('intval', $new_data['popular_amenities']));
						$am_stmt = $pdo->query("SELECT category, amenity_name FROM amenities_master WHERE id IN ($ids_str) ORDER BY category, amenity_name");
						$amenities = $am_stmt->fetchAll();
					}
				}
			}
		}
	}
}

function h($value) {
	return htmlspecialchars((string)$value);
}

function format_bool($value) {
	return ((int)$value === 1) ? 'Yes' : 'No';
}

function format_text_block($value) {
	$text = trim((string)$value);
	return $text === '' ? 'N/A' : nl2br(htmlspecialchars($text));
}

$amenities_by_category = [];
foreach ($amenities as $item) {
	$category = $item['category'] ?? 'Other';
	if (!isset($amenities_by_category[$category])) {
		$amenities_by_category[$category] = [];
	}
	$amenities_by_category[$category][] = $item['amenity_name'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Property Details - Bookingjaunt</title>
	<style>
		body {
			font-family: "Times New Roman", serif;
			color: #111;
			background: transparent;
		}
		.container {
			max-width: 1100px;
			margin: <?php echo isset($_GET['iframe']) && $_GET['iframe'] == 1 ? '10px' : '24px'; ?> auto;
			padding: <?php echo isset($_GET['iframe']) && $_GET['iframe'] == 1 ? '0 10px 20px' : '0 16px 40px'; ?>;
		}
		h1 {
			font-size: 24px;
			margin: 0 0 8px;
			color: #1a1a1a;
		}
		.meta {
			font-size: 13px;
			color: #333;
		}
		.section {
			margin-top: 24px;
		}
		.section h2 {
			font-size: 18px;
			margin: 0 0 8px;
			padding-bottom: 4px;
			border-bottom: 1px solid #111;
			color: #1f2937;
		}
		.media-thumb {
			width: 140px;
			height: 90px;
			object-fit: cover;
			border: 1px solid #111;
		}
		table {
			width: 100%;
			border-collapse: collapse;
		}
		th, td {
			border: 1px solid #111;
			padding: 6px 8px;
			vertical-align: top;
			font-size: 14px;
		}
		th {
			text-align: left;
			width: 30%;
			font-weight: bold;
		}
		.list-table th {
			width: auto;
		}
		.empty {
			font-style: italic;
			color: #4b5563;
		}
		.back-link {
			display: inline-block;
			margin-bottom: 12px;
			font-size: 13px;
			color: #1d4ed8;
		}
		.page-header {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 12px;
			flex-wrap: wrap;
		}
		.pdf-btn {
			border: 1px solid #111;
			padding: 6px 12px;
			font-size: 13px;
			cursor: pointer;
			background: transparent;
		}
		@media print {
			.no-print {
				display: none !important;
			}
			body {
				margin: 0;
			}
		}
	</style>
</head>

<body>
	<div class="container">
		<?php if (!isset($_GET['iframe']) || $_GET['iframe'] != 1): ?>
		<div class="page-header no-print">
			<a class="back-link" href="properties.php">Back to Properties</a>
			<button class="pdf-btn" type="button" onclick="window.print()">Download PDF</button>
		</div>
		<?php endif; ?>

		<?php if ($error !== ''): ?>
			<h1>Property Details</h1>
			<p><?php echo h($error); ?></p>
		<?php else: ?>
			<h1>
				<?php echo h($property['property_name']); ?> - Property Report
				<?php if (isset($request_id) && $request_id > 0): ?>
					<span style="color: #ea580c; background: #ffedd5; border: 1px solid #fed7aa; padding: 2px 10px; font-size: 12px; border-radius: 6px; margin-left: 10px; font-family: sans-serif; font-weight: bold; text-transform: uppercase; vertical-align: middle; display: inline-block;">Proposed Changes (Pending Approval)</span>
				<?php endif; ?>
			</h1>
			<div class="meta">
				<div>Property ID: <?php echo (int)$property['id']; ?></div>
				<div>Business Type: <?php echo h(str_replace('_', ' ', $property['business_type'])); ?></div>
				<div>Hotel Category: <?php echo h($property['hotel_category'] ?: 'N/A'); ?></div>
				<div>Created At: <?php echo h($property['created_at']); ?></div>
			</div>

			<div class="section">
				<h2>Owner Information</h2>
				<table>
					<tr><th>Owner Name</th><td><?php echo h(trim($property['first_name'] . ' ' . $property['last_name'])); ?></td></tr>
					<tr><th>Email</th><td><?php echo h($property['owner_email']); ?></td></tr>
					<tr><th>Phone</th><td><?php echo h($property['owner_phone'] ?: 'N/A'); ?></td></tr>
					<tr><th>WhatsApp</th><td><?php echo h($property['owner_whatsapp'] ?: 'N/A'); ?></td></tr>
					<tr><th>NIC/Passport</th><td><?php echo h($property['owner_nic'] ?: 'N/A'); ?></td></tr>
					<tr><th>Address</th><td><?php echo h($property['owner_address'] ?: 'N/A'); ?></td></tr>
					<tr><th>Country</th><td><?php echo h($property['owner_country'] ?: 'N/A'); ?></td></tr>
				</table>
			</div>

			<div class="section">
				<h2>Property Overview</h2>
				<table>
					<tr><th>Description</th><td><?php echo format_text_block($property['description']); ?></td></tr>
					<tr><th>Street Address</th><td><?php echo h($property['street_address'] ?: 'N/A'); ?></td></tr>
					<tr><th>City</th><td><?php echo h($property['city'] ?: 'N/A'); ?></td></tr>
					<tr><th>District</th><td><?php echo h($property['district'] ?: 'N/A'); ?></td></tr>
					<tr><th>Province</th><td><?php echo h($property['province'] ?: 'N/A'); ?></td></tr>
					<tr><th>Country</th><td><?php echo h($property['country'] ?: 'N/A'); ?></td></tr>
					<tr><th>Postal Code</th><td><?php echo h($property['postal_code'] ?: 'N/A'); ?></td></tr>
					<tr><th>Google Map Location</th><td><?php echo h($property['google_map_location'] ?: 'N/A'); ?></td></tr>
				</table>
			</div>

			<div class="section">
				<h2>Contact Details</h2>
				<table>
					<tr><th>Fixed Telephone</th><td><?php echo h($property['fixed_telephone'] ?: 'N/A'); ?></td></tr>
					<tr><th>Mobile Telephone</th><td><?php echo h($property['mobile_telephone'] ?: 'N/A'); ?></td></tr>
					<tr><th>Contact Number</th><td><?php echo h($property['contact_number'] ?: 'N/A'); ?></td></tr>
					<tr><th>WhatsApp Number</th><td><?php echo h($property['whatsapp_number'] ?: 'N/A'); ?></td></tr>
					<tr><th>Business Email</th><td><?php echo h($property['business_email'] ?: 'N/A'); ?></td></tr>
					<tr><th>Closest Police Station</th><td><?php echo h($property['closest_police_station'] ?: 'N/A'); ?></td></tr>
					<tr><th>Closest Hospital</th><td><?php echo h($property['closest_hospital'] ?: 'N/A'); ?></td></tr>
					<tr><th>Airport Distance</th><td><?php echo h($property['airport_distance'] ?: 'N/A'); ?></td></tr>
					<tr><th>Closest Main Town</th><td><?php echo h($property['closest_main_town'] ?: 'N/A'); ?></td></tr>
				</table>
			</div>

			<div class="section">
				<h2>Manager Details</h2>
				<table>
					<tr><th>Manager Name</th><td><?php echo h($property['manager_name'] ?: 'N/A'); ?></td></tr>
					<tr><th>Manager Email</th><td><?php echo h($property['manager_email'] ?: 'N/A'); ?></td></tr>
					<tr><th>Manager Phone</th><td><?php echo h($property['manager_phone'] ?: 'N/A'); ?></td></tr>
					<tr><th>Manager NIC</th><td><?php echo h($property['manager_nic'] ?: 'N/A'); ?></td></tr>
					<tr><th>Manager Photo</th><td><?php echo h($property['manager_photo'] ?: 'N/A'); ?></td></tr>
				</table>
			</div>

			<div class="section">
				<h2>Policies & Rules</h2>
				<table>
					<tr><th>Check-in Time</th><td><?php echo h($property['check_in_time'] ?: 'N/A'); ?></td></tr>
					<tr><th>Check-out Time</th><td><?php echo h($property['check_out_time'] ?: 'N/A'); ?></td></tr>
					<tr><th>ID Required</th><td><?php echo format_bool($property['id_required']); ?></td></tr>
					<tr><th>Cancellation Policy</th><td><?php echo format_text_block($property['cancellation_policy']); ?></td></tr>
					<tr><th>Smoking Allowed</th><td><?php echo format_bool($property['smoking_allowed']); ?></td></tr>
					<tr><th>Pets Allowed</th><td><?php echo format_bool($property['pets_allowed']); ?></td></tr>
					<tr><th>Events Allowed</th><td><?php echo format_bool($property['events_allowed']); ?></td></tr>
				</table>
			</div>

			<div class="section">
				<h2>Food & Dining Details</h2>
				<table>
					<tr><th>Breakfast Included</th><td><?php echo format_bool($property['food_breakfast_included']); ?></td></tr>
					<?php if ($property['food_breakfast_included']): ?>
						<tr><th>Breakfast Type</th><td><?php echo h($property['food_breakfast_type'] ?: 'N/A'); ?></td></tr>
					<?php endif; ?>
					<tr><th>Restaurant Available</th><td><?php echo format_bool($property['food_restaurant_available']); ?></td></tr>
					<?php if ($property['food_restaurant_available']): ?>
						<tr><th>Restaurant Count</th><td><?php echo h($property['food_restaurant_count'] ?: 'N/A'); ?></td></tr>
					<?php endif; ?>
					<tr><th>Room Service</th><td><?php echo format_bool($property['food_room_service']); ?></td></tr>
					<?php if ($property['food_room_service']): ?>
						<tr><th>24/7 Room Service</th><td><?php echo format_bool($property['food_room_service_247']); ?></td></tr>
					<?php endif; ?>
					<tr><th>Vegetarian Options</th><td><?php echo format_bool($property['food_vegetarian']); ?></td></tr>
					<tr><th>Vegan Options</th><td><?php echo format_bool($property['food_vegan']); ?></td></tr>
					<tr><th>Halal Food Available</th><td><?php echo format_bool($property['food_halal']); ?></td></tr>
					<tr><th>Buffet Available</th><td><?php echo format_bool($property['food_buffet']); ?></td></tr>
					<tr><th>Food Delivery Allowed</th><td><?php echo format_bool($property['food_delivery_allowed']); ?></td></tr>
					<tr><th>Special Dietary Options</th><td><?php echo format_bool($property['food_dietary_options']); ?></td></tr>
					<tr><th>Kitchen in Room</th><td><?php echo format_bool($property['food_kitchen_in_room']); ?></td></tr>
					<tr><th>Mini Bar Available</th><td><?php echo format_bool($property['food_minibar']); ?></td></tr>
					<?php 
					$custom_food = [];
					if (!empty($property['custom_food_json'])) {
						$decoded = json_decode($property['custom_food_json'], true);
						if (is_array($decoded)) {
							$custom_food = array_filter(array_map('trim', $decoded));
						}
					}
					if (!empty($custom_food)): ?>
						<tr><th>Custom Dining Options</th><td><?php echo h(implode(', ', $custom_food)); ?></td></tr>
					<?php endif; ?>
					<tr><th>Food Notes</th><td><?php echo format_text_block($property['food_notes']); ?></td></tr>
				</table>
			</div>

			<div class="section">
				<h2>Payment Options & Policies</h2>
				<table>
					<tr><th>Cash Accepted</th><td><?php echo format_bool($property['pay_cash']); ?></td></tr>
					<tr><th>Credit Card Accepted</th><td><?php echo format_bool($property['pay_cc']); ?></td></tr>
					<tr><th>Debit Card Accepted</th><td><?php echo format_bool($property['pay_debit']); ?></td></tr>
					<tr><th>Online Payment Support (UPI/Gateway)</th><td><?php echo format_bool($property['pay_online']); ?></td></tr>
					<tr><th>Bank Transfer Support</th><td><?php echo format_bool($property['pay_bank']); ?></td></tr>
					<tr><th>Installment Option</th><td><?php echo format_bool($property['pay_installments']); ?></td></tr>
					<tr><th>Refund Supported</th><td><?php 
						if ($property['refund_supported'] === 1) echo 'Yes';
						elseif ($property['refund_supported'] === 0) echo 'No';
						else echo 'N/A';
					?></td></tr>
					<tr><th>Advance Payment Required</th><td><?php 
						if ($property['advance_payment_required'] === 1) echo 'Yes';
						elseif ($property['advance_payment_required'] === 0) echo 'No';
						else echo 'N/A';
					?></td></tr>
					<?php 
					$custom_payments = [];
					if (!empty($property['custom_payments_json'])) {
						$decoded = json_decode($property['custom_payments_json'], true);
						if (is_array($decoded)) {
							$custom_payments = array_filter(array_map('trim', $decoded));
						}
					}
					if (!empty($custom_payments)): ?>
						<tr><th>Custom Payment Methods</th><td><?php echo h(implode(', ', $custom_payments)); ?></td></tr>
					<?php endif; ?>
					<tr><th>Payment Notes</th><td><?php echo format_text_block($property['payment_notes']); ?></td></tr>
				</table>
			</div>

			<div class="section">
				<h2>Safety & Security Features</h2>
				<table>
					<tr><th>24/7 Security Staff</th><td><?php echo format_bool($property['sec_staff_247']); ?></td></tr>
					<tr><th>CCTV Available</th><td><?php echo format_bool($property['sec_cctv']); ?></td></tr>
					<?php if ($property['sec_cctv']): ?>
						<tr><th>CCTV Coverage Details</th><td><?php echo h($property['sec_cctv_coverage'] ?: 'N/A'); ?></td></tr>
					<?php endif; ?>
					<tr><th>Smoke Detectors</th><td><?php echo format_bool($property['sec_smoke_detectors']); ?></td></tr>
					<tr><th>Fire Extinguishers</th><td><?php echo format_bool($property['sec_fire_extinguishers']); ?></td></tr>
					<tr><th>Fire Alarm</th><td><?php echo format_bool($property['sec_fire_alarm']); ?></td></tr>
					<tr><th>Emergency Exit Plan</th><td><?php echo format_bool($property['sec_emergency_exit_plan']); ?></td></tr>
					<tr><th>Emergency Evac Instructions</th><td><?php echo format_text_block($property['sec_emergency_evac_instructions']); ?></td></tr>
					<tr><th>Patrol Frequency</th><td><?php echo h($property['sec_patrol_frequency'] ?: 'N/A'); ?></td></tr>
					<tr><th>Key Card Access</th><td><?php echo format_bool($property['sec_key_card_access']); ?></td></tr>
					<tr><th>Digital Lock</th><td><?php echo format_bool($property['sec_digital_lock']); ?></td></tr>
					<tr><th>Biometric Access</th><td><?php echo format_bool($property['sec_biometric_access']); ?></td></tr>
					<tr><th>Safe Box</th><td><?php echo format_bool($property['sec_safe_box']); ?></td></tr>
					<tr><th>Luggage Storage</th><td><?php echo format_bool($property['sec_luggage_storage']); ?></td></tr>
					<tr><th>Parking Security</th><td><?php echo h($property['sec_parking_security'] ?: 'N/A'); ?></td></tr>
					<tr><th>Female-only Floor</th><td><?php echo format_bool($property['sec_female_floor']); ?></td></tr>
					<tr><th>Panic Button</th><td><?php echo format_bool($property['sec_panic_button']); ?></td></tr>
					<tr><th>First Aid Kit</th><td><?php echo format_bool($property['sec_first_aid']); ?></td></tr>
					<tr><th>Medical Support</th><td><?php echo format_bool($property['sec_medical_support']); ?></td></tr>
					<tr><th>Hospital Distance</th><td><?php echo h($property['sec_hospital_distance'] ?: 'N/A'); ?></td></tr>
					<?php 
					$custom_security = [];
					if (!empty($property['custom_security_json'])) {
						$decoded = json_decode($property['custom_security_json'], true);
						if (is_array($decoded)) {
							$custom_security = array_filter(array_map('trim', $decoded));
						}
					}
					if (!empty($custom_security)): ?>
						<tr><th>Custom Security Features</th><td><?php echo h(implode(', ', $custom_security)); ?></td></tr>
					<?php endif; ?>
					<tr><th>Security Notes</th><td><?php echo format_text_block($property['sec_notes']); ?></td></tr>
				</table>
			</div>

			<div class="section">
				<h2>Financial & Payout Settings</h2>
				<table>
					<tr><th>Payout Percentage</th><td><?php echo h($property['payout_percentage']); ?>%</td></tr>
					<tr><th>Commission Percentage</th><td><?php echo h($property['commission_percentage']); ?>%</td></tr>
					<tr><th>Allow Payout Requests</th><td><?php echo format_bool($property['allow_payout_requests']); ?></td></tr>
					<tr><th>Minimum Payout Amount</th><td><?php echo h($property['min_payout_amount']); ?></td></tr>
					<tr><th>Currency</th><td><?php echo h($property['currency'] ?: 'N/A'); ?></td></tr>
					<tr><th>VAT Percentage</th><td><?php echo h($property['vat_percentage']); ?>%</td></tr>
					<tr><th>Service Charge Percentage</th><td><?php echo h($property['service_charge_percentage']); ?>%</td></tr>
					<tr><th>Commission Rate</th><td><?php echo h($property['commission_rate']); ?>%</td></tr>
				</table>
			</div>

			<div class="section">
				<h2>Media Files</h2>
				<?php if (empty($media_items)): ?>
					<p class="empty">No media records found.</p>
				<?php else: ?>
					<table class="list-table">
						<thead>
							<tr>
								<th>Type</th>
								<th>Preview</th>
								<th>Featured</th>
								<th>Sort Order</th>
								<th>Created</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($media_items as $media): ?>
								<tr>
									<td><?php echo h($media['media_type']); ?></td>
									<td>
										<?php if ($media['media_type'] === 'video'): ?>
											<video class="media-thumb" muted controls>
												<source src="../../<?php echo h($media['media_path']); ?>">
											</video>
										<?php else: ?>
											<img class="media-thumb" src="../../<?php echo h($media['media_path']); ?>" alt="Property media">
										<?php endif; ?>
									</td>
									<td><?php echo format_bool($media['is_featured']); ?></td>
									<td><?php echo h($media['sort_order']); ?></td>
									<td><?php echo h($media['created_at']); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<div class="section">
				<h2>Amenities</h2>
				<?php if (empty($amenities_by_category) && empty($custom_amenities)): ?>
					<p class="empty">No amenities listed.</p>
				<?php else: ?>
					<?php if (!empty($amenities_by_category)): ?>
						<table class="list-table">
							<thead>
								<tr>
									<th>Category</th>
									<th>Amenities</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($amenities_by_category as $category => $items): ?>
									<tr>
										<td><?php echo h($category); ?></td>
										<td><?php echo h(implode(', ', $items)); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>

					<?php if (!empty($custom_amenities)): ?>
						<p><strong>Custom Amenities:</strong> <?php echo h(implode(', ', array_column($custom_amenities, 'amenity_name'))); ?></p>
					<?php endif; ?>
				<?php endif; ?>
			</div>

			<div class="section">
				<h2>Room Inventory</h2>
				<?php if (empty($rooms)): ?>
					<p class="empty">No room entries found.</p>
				<?php else: ?>
					<table class="list-table">
						<thead>
							<tr>
								<th>Room Name</th>
								<th>Adults</th>
								<th>Children</th>
								<th>Price LKR</th>
								<th>Price USD</th>
								<th>Image</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($rooms as $room): ?>
								<tr>
									<td><?php echo h($room['room_name']); ?></td>
									<td><?php echo h($room['adults']); ?></td>
									<td><?php echo h($room['children']); ?></td>
									<td><?php echo h($room['price_lkr']); ?></td>
									<td><?php echo h($room['price_usd']); ?></td>
									<td>
										<?php if (!empty($room['room_image'])): ?>
											<img class="media-thumb" src="../../<?php echo h($room['room_image']); ?>" alt="Room image">
										<?php else: ?>
											N/A
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>

				<?php if (!empty($room_types)): ?>
					<div class="section">
						<h2>Room Types</h2>
						<table class="list-table">
							<thead>
								<tr>
									<th>Room Name</th>
									<th>Total Rooms</th>
									<th>Max Adults</th>
									<th>Max Children</th>
									<th>Base Price</th>
									<th>Hall</th>
									<th>Price LKR</th>
									<th>Price USD</th>
									<th>Image</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($room_types as $type): ?>
									<tr>
										<td><?php echo h($type['room_name']); ?></td>
										<td><?php echo h($type['total_rooms']); ?></td>
										<td><?php echo h($type['max_adults']); ?></td>
										<td><?php echo h($type['max_children']); ?></td>
										<td><?php echo h($type['base_price']); ?></td>
										<td><?php echo format_bool($type['is_hall']); ?></td>
										<td><?php echo h($type['price_lkr']); ?></td>
										<td><?php echo h($type['price_usd']); ?></td>
									<td>
										<?php if (!empty($type['room_image'])): ?>
											<img class="media-thumb" src="../../<?php echo h($type['room_image']); ?>" alt="Room type image">
										<?php else: ?>
											N/A
										<?php endif; ?>
									</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>

			<div class="section">
				<h2>Extra Services</h2>
				<?php if (empty($extra_services)): ?>
					<p class="empty">No extra services listed.</p>
				<?php else: ?>
					<table class="list-table">
						<thead>
							<tr>
								<th>Service</th>
								<th>Price</th>
								<th>Pricing Type</th>
								<th>Status</th>
								<th>Description</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($extra_services as $service): ?>
								<tr>
									<td><?php echo h($service['service_name']); ?></td>
									<td><?php echo h($service['price']); ?></td>
									<td><?php echo h($service['pricing_type'] ?: 'N/A'); ?></td>
									<td><?php echo h($service['status']); ?></td>
									<td><?php echo format_text_block($service['description']); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<div class="section">
				<h2>Bank Details</h2>
				<?php if (empty($bank_details)): ?>
					<p class="empty">No bank details recorded.</p>
				<?php else: ?>
					<table class="list-table">
						<thead>
							<tr>
								<th>Bank Name</th>
								<th>Account Number</th>
								<th>Account Holder</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($bank_details as $bank): ?>
								<tr>
									<td><?php echo h($bank['bank_name'] ?: 'N/A'); ?></td>
									<td><?php echo h($bank['account_number'] ?: 'N/A'); ?></td>
									<td><?php echo h($bank['account_holder_name'] ?: 'N/A'); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>

			<div class="section">
				<h2>Staff</h2>
				<?php if (empty($staff) && empty($staff_names)): ?>
					<p class="empty">No staff records found.</p>
				<?php else: ?>
					<?php if (!empty($staff)): ?>
						<table class="list-table">
							<thead>
								<tr>
									<th>Name</th>
									<th>Role</th>
									<th>Email</th>
									<th>Phone</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ($staff as $member): ?>
									<tr>
										<td><?php echo h(trim($member['first_name'] . ' ' . $member['last_name'])); ?></td>
										<td><?php echo h($member['staff_role'] ?: 'N/A'); ?></td>
										<td><?php echo h($member['email']); ?></td>
										<td><?php echo h($member['phone_number'] ?: 'N/A'); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>

					<?php if (!empty($staff_names)): ?>
						<p><strong>Additional Staff Names:</strong> <?php echo h(implode(', ', array_column($staff_names, 'staff_name'))); ?></p>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</body>

</html>
