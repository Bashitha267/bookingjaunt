<?php
$ch = curl_init('http://localhost/bookingjaunt/register');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['action' => 'request_edit', 'property_id' => '29']);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "HTTP CODE: " . $httpcode . "\n";
echo "RESPONSE LENGTH: " . strlen($response) . "\n";
echo "RESPONSE BODY:\n" . substr($response, 0, 200) . "\n";
?>
