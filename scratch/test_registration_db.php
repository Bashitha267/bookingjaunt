<?php
require_once __DIR__ . '/../config.php';

// Prepare a mock $_POST structure
$data = [
    'business_type' => 'hotel',
    'hotel_category' => 'budget_friendly',
    'property_name' => 'Test Hotel',
    'description' => 'A nice test hotel',
    'street_address' => '123 Main St',
    'city' => 'Colombo',
    'district' => 'Colombo',
    'province' => 'Western',
    'country' => 'Sri Lanka',
    'google_map_location' => '',
    'fixed_telephone' => '123456789',
    'mobile_telephone' => '987654321',
    'closest_police_station' => 'Colombo Fort',
    'closest_hospital' => 'General Hospital',
    'airport_distance' => '30 km',
    'closest_main_town' => 'Colombo 01',
    'postal_code' => '00100',
    'logo_image' => '',
    'cover_image' => '',
    'manager_name' => 'John Doe',
    'manager_phone' => '123123123',
    'manager_nic' => '123456789V',
    'manager_photo' => '',
    'contact_number' => '123123123',
    'business_email' => 'hotel@example.com',
    'check_in_time' => '14:00',
    'check_out_time' => '12:00',
    'cancellation_policy' => 'Free cancellation',
    'rules' => ['no smoking', 'no pets'],
    'popular_amenities' => [1, 2, 3],
    'custom_rules' => ['have fun'],
    'bank_name' => 'BOC',
    'bank_branch' => 'Colombo',
    'bank_account_name' => 'Test Acc',
    'bank_account_number' => '123456789',
    'commission_rate' => 80,
    'tourist_attractions' => ['Beach', 'Museum'],
    'closest_fuel_station' => 'IOC',
    
    // Payment Options
    'pay_cash' => '1',
    'pay_cc' => '1',
    'refund_supported' => '1',
    'advance_payment_required' => '0',
    'payment_notes' => 'Some notes',
    'custom_payments' => ['Apple Pay'],
    
    // Food Options
    'food_breakfast_included' => '1',
    'food_breakfast_type' => 'Buffet',
    'food_restaurant_available' => '1',
    'food_restaurant_count' => '2',
    'food_room_service' => '1',
    'food_room_service_247' => '1',
    
    // Security Options
    'sec_staff_247' => '1',
    'sec_cctv' => '1',
    'sec_cctv_coverage' => 'lobby',
    'sec_smoke_detectors' => '1',
    'sec_fire_extinguishers' => '1',
    'sec_fire_alarm' => '1',
    'sec_emergency_exit_plan' => '1'
];

try {
    $pdo->beginTransaction();
    
    // Find a valid user to use as owner_id
    $user_stmt = $pdo->query("SELECT id FROM users LIMIT 1");
    $user = $user_stmt->fetch();
    $owner_id = $user ? $user['id'] : 1;

    // Copying the exact query from register.php
    $stmt = $pdo->prepare("INSERT INTO properties (
        owner_id, business_type, hotel_category, property_name, description, 
        street_address, city, district, province, country, google_map_location, 
        fixed_telephone, mobile_telephone, closest_police_station, closest_hospital, 
        airport_distance, closest_main_town, postal_code, logo_image, cover_image, 
        manager_name, manager_phone, manager_nic, manager_photo, contact_number, 
        business_email, check_in_time, check_out_time, cancellation_policy, 
        rules_json, popular_amenities_json, custom_rules_json,
        bank_name, bank_branch, bank_account_name, bank_account_number, commission_rate, tourist_attractions, closest_fuel_station,
        pay_cash, pay_cc, pay_debit, pay_online, pay_bank, pay_installments, refund_supported, advance_payment_required, payment_notes, custom_payments_json,
        food_breakfast_included, food_breakfast_type, food_restaurant_available, food_restaurant_count, food_room_service, food_room_service_247, food_vegetarian, food_vegan, food_halal, food_buffet, food_delivery_allowed, food_dietary_options, food_kitchen_in_room, food_minibar, food_notes, custom_food_json,
        sec_staff_247, sec_cctv, sec_cctv_coverage, sec_smoke_detectors, sec_fire_extinguishers, sec_fire_alarm, sec_emergency_exit_plan, sec_emergency_evac_instructions, sec_patrol_frequency, sec_key_card_access, sec_digital_lock, sec_biometric_access, sec_safe_box, sec_luggage_storage, sec_parking_security, sec_female_floor, sec_panic_button, sec_first_aid, sec_medical_support, sec_hospital_distance, sec_notes, custom_security_json
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $owner_id,
        $data['business_type'] ?? null,
        $data['hotel_category'] ?? null,
        $data['property_name'],
        $data['description'] ?? '',
        $data['street_address'] ?? '',
        $data['city'] ?? '',
        $data['district'] ?? '',
        $data['province'] ?? '',
        $data['country'] ?? 'Sri Lanka',
        $data['google_map_location'] ?? '',
        $data['fixed_telephone'] ?? '',
        $data['mobile_telephone'] ?? '',
        $data['closest_police_station'] ?? '',
        $data['closest_hospital'] ?? '',
        $data['airport_distance'] ?? '',
        $data['closest_main_town'] ?? '',
        $data['postal_code'] ?? '',
        $data['logo_image'] ?? '',
        $data['cover_image'] ?? '',
        $data['manager_name'] ?? '',
        $data['manager_phone'] ?? '',
        $data['manager_nic'] ?? '',
        $data['manager_photo'] ?? '',
        $data['contact_number'] ?? '',
        $data['business_email'] ?? '',
        $data['check_in_time'] ?? '14:00',
        $data['check_out_time'] ?? '12:00',
        $data['cancellation_policy'] ?? '',
        json_encode($data['rules'] ?? []),
        json_encode($data['popular_amenities'] ?? []),
        json_encode($data['custom_rules'] ?? []),
        $data['bank_name'] ?? null,
        $data['bank_branch'] ?? null,
        $data['bank_account_name'] ?? null,
        $data['bank_account_number'] ?? null,
        $data['commission_rate'] ?? 80,
        !empty($data['tourist_attractions']) ? json_encode(array_values(array_filter($data['tourist_attractions']))) : null,
        $data['closest_fuel_station'] ?? null,
        // Payment Options
        isset($data['pay_cash']) ? 1 : 0,
        isset($data['pay_cc']) ? 1 : 0,
        isset($data['pay_debit']) ? 1 : 0,
        isset($data['pay_online']) ? 1 : 0,
        isset($data['pay_bank']) ? 1 : 0,
        isset($data['pay_installments']) ? 1 : 0,
        isset($data['refund_supported']) && $data['refund_supported'] !== '' ? (int)$data['refund_supported'] : null,
        isset($data['advance_payment_required']) && $data['advance_payment_required'] !== '' ? (int)$data['advance_payment_required'] : null,
        $data['payment_notes'] ?? null,
        !empty($data['custom_payments']) ? json_encode(array_values(array_filter($data['custom_payments']))) : null,
        // Food & Dining
        isset($data['food_breakfast_included']) ? 1 : 0,
        $data['food_breakfast_type'] ?? null,
        isset($data['food_restaurant_available']) ? 1 : 0,
        isset($data['food_restaurant_count']) && $data['food_restaurant_count'] !== '' ? (int)$data['food_restaurant_count'] : null,
        isset($data['food_room_service']) ? 1 : 0,
        isset($data['food_room_service_247']) ? 1 : 0,
        isset($data['food_vegetarian']) ? 1 : 0,
        isset($data['food_vegan']) ? 1 : 0,
        isset($data['food_halal']) ? 1 : 0,
        isset($data['food_buffet']) ? 1 : 0,
        isset($data['food_delivery_allowed']) ? 1 : 0,
        isset($data['food_dietary_options']) ? 1 : 0,
        isset($data['food_kitchen_in_room']) ? 1 : 0,
        isset($data['food_minibar']) ? 1 : 0,
        $data['food_notes'] ?? null,
        !empty($data['custom_food']) ? json_encode(array_values(array_filter($data['custom_food']))) : null,
        // Security
        isset($data['sec_staff_247']) ? 1 : 0,
        isset($data['sec_cctv']) ? 1 : 0,
        $data['sec_cctv_coverage'] ?? null,
        isset($data['sec_smoke_detectors']) ? 1 : 0,
        isset($data['sec_fire_extinguishers']) ? 1 : 0,
        isset($data['sec_fire_alarm']) ? 1 : 0,
        isset($data['sec_emergency_exit_plan']) ? 1 : 0,
        $data['sec_emergency_evac_instructions'] ?? null,
        $data['sec_patrol_frequency'] ?? null,
        isset($data['sec_key_card_access']) ? 1 : 0,
        isset($data['sec_digital_lock']) ? 1 : 0,
        isset($data['sec_biometric_access']) ? 1 : 0,
        isset($data['sec_safe_box']) ? 1 : 0,
        isset($data['sec_luggage_storage']) ? 1 : 0,
        $data['sec_parking_security'] ?? null,
        isset($data['sec_female_floor']) ? 1 : 0,
        isset($data['sec_panic_button']) ? 1 : 0,
        isset($data['sec_first_aid']) ? 1 : 0,
        isset($data['sec_medical_support']) ? 1 : 0,
        $data['sec_hospital_distance'] ?? null,
        $data['sec_notes'] ?? null,
        !empty($data['custom_security']) ? json_encode(array_values(array_filter($data['custom_security']))) : null
    ]);

    echo "SUCCESS: The insert statement executed without any column or value mismatch!\n";
    $pdo->rollBack();
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}
