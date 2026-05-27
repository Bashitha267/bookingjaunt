<?php
require_once '../../config.php';
session_start();

require_once '../auth_guard.php';
requireRole(['manager']);

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
			margin: 24px auto;
			padding: 0 16px 40px;
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
		<div class="page-header no-print">
			<a class="back-link" href="properties.php">Back to Properties</a>
			<a class="back-link" href="../../property_wizard.php?edit=<?php echo (int)$property['id']; ?>&type=<?php echo htmlspecialchars($property['business_type']); ?>">✏️ Edit Property</a>
			<button class="pdf-btn" type="button" onclick="window.print()">Download PDF</button>
		</div>

		<?php if ($error !== ''): ?>
			<h1>Property Details</h1>
			<p><?php echo h($error); ?></p>
		<?php else: ?>
			<h1><?php echo h($property['property_name']); ?> - Property Report</h1>
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
