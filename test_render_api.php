<?php
$data = json_encode(["email" => "admin@electro.vn", "password" => "password"]);
$options = [
    "http" => [
        "method"  => "POST",
        "header"  => "Content-Type: application/json\r\nAccept: application/json\r\n",
        "content" => $data
    ]
];
$context = stream_context_create($options);
$response = @file_get_contents("https://doantotnghiep-u4gt.onrender.com/api/admin/login", false, $context);
if ($response === false) { die("Login failed: " . error_get_last()["message"]); }
$responseData = json_decode($response, true);
$token = $responseData["token"] ?? "";
if (!$token) { die("No token: " . $response); }

// Test single update
$putData = json_encode(["status" => "processing"]);
$putOptions = [
    "http" => [
        "method"  => "PUT",
        "header"  => "Content-Type: application/json\r\nAccept: application/json\r\nAuthorization: Bearer " . $token . "\r\n",
        "content" => $putData,
        "ignore_errors" => true
    ]
];
$putContext = stream_context_create($putOptions);
$putResponse = file_get_contents("https://doantotnghiep-u4gt.onrender.com/api/admin/orders/1", false, $putContext);

echo "Response Headers:\n";
print_r($http_response_header);
echo "\nResponse Body:\n";
echo $putResponse;

