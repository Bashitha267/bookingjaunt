<?php
require_once __DIR__ . '/../config.php';

try {
    // Find the first property in the database
    $stmt = $pdo->query("SELECT id, property_name FROM properties LIMIT 1");
    $property = $stmt->fetch();

    if (!$property) {
        echo "No properties found in the database. Please register a property first.\n";
        exit(1);
    }

    $id = $property['id'];
    echo "Found property: '{$property['property_name']}' (ID: {$id})\n";

    // Update with comprehensive test values
    $update_stmt = $pdo->prepare("
        UPDATE properties SET
            pay_cash = 1,
            pay_cc = 1,
            pay_debit = 1,
            pay_online = 1,
            pay_bank = 0,
            pay_installments = 1,
            refund_supported = 1,
            advance_payment_required = 0,
            payment_notes = 'Please pay at reception or via online transfer. Installment options are available for stays longer than 5 nights.',
            custom_payments_json = '[\"Apple Pay\", \"Google Pay\"]',

            food_breakfast_included = 1,
            food_breakfast_type = 'Continental & Buffet',
            food_restaurant_available = 1,
            food_restaurant_count = 2,
            food_room_service = 1,
            food_room_service_247 = 1,
            food_vegetarian = 1,
            food_vegan = 1,
            food_halal = 1,
            food_buffet = 1,
            food_delivery_allowed = 1,
            food_dietary_options = 1,
            food_kitchen_in_room = 0,
            food_minibar = 1,
            food_notes = 'On-site restaurant opens from 6 AM to 11 PM. Vegan and Halal choices are prepared in dedicated kitchens.',
            custom_food_json = '[\"Organic Fruit Bar\", \"Gluten-Free Pastries\"]',

            sec_staff_247 = 1,
            sec_cctv = 1,
            sec_cctv_coverage = 'All corridors, entrances, and parking area',
            sec_smoke_detectors = 1,
            sec_fire_extinguishers = 1,
            sec_fire_alarm = 1,
            sec_emergency_exit_plan = 1,
            sec_emergency_evac_instructions = 'Follow glowing green exit signs to the assembly point at the lawn.',
            sec_patrol_frequency = 'Every 30 minutes',
            sec_key_card_access = 1,
            sec_digital_lock = 0,
            sec_biometric_access = 0,
            sec_safe_box = 1,
            sec_luggage_storage = 1,
            sec_parking_security = 'Guarded parking with entry barriers',
            sec_female_floor = 1,
            sec_panic_button = 1,
            sec_first_aid = 1,
            sec_medical_support = 1,
            sec_hospital_distance = '1.2 km (City Hospital)',
            sec_notes = 'Emergency contact numbers are listed behind the door. Guard room is next to the gate.',
            custom_security_json = '[\"Armed Guard Response\", \"Secure Perimeter Laser Fencing\"]'
        WHERE id = ?
    ");

    $update_stmt->execute([$id]);

    echo "Successfully updated property ID {$id} with new payment, food, and security test data.\n";

    // Fetch back and print a quick summary
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
    $stmt->execute([$id]);
    $updated = $stmt->fetch();

    echo "\nVerification of saved columns:\n";
    echo "- pay_cash: {$updated['pay_cash']}\n";
    echo "- food_breakfast_type: {$updated['food_breakfast_type']}\n";
    echo "- sec_patrol_frequency: {$updated['sec_patrol_frequency']}\n";
    echo "- custom_payments_json: {$updated['custom_payments_json']}\n";
    echo "- custom_food_json: {$updated['custom_food_json']}\n";
    echo "- custom_security_json: {$updated['custom_security_json']}\n";

} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
