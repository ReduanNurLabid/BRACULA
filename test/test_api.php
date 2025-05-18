<?php
echo "Testing API access...<br>";

// Test direct access to register.php
$register_url = "http://localhost:8081/api/auth/register.php";
echo "Testing URL: " . $register_url . "<br>";

$ch = curl_init($register_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $httpcode . "<br>";
echo "Response:<br><pre>" . htmlspecialchars($response) . "</pre>";
?> 