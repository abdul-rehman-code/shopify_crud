<?php
// Connection Variables
$accessToken = "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";
$shopUrl = "shanelibasshop.myshopify.com";
$apiVersion = "2024-01"; // Ya latest version jo aapne select kiya

// Naya product banaye ka data
$productData = [
    "product" => [
        "title" => "PHP Test Product",
        "body_html" => "<strong>This is my first product from PHP!</strong>",
        "vendor" => "Shan-e-Libas",
        "product_type" => "Clothing",
        "variants" => [
            [
                "price" => "2500",
                "sku" => "TEST-001"
            ]
        ]
    ]
];

// Shopify API Endpoint
$url = "https://$shopUrl/admin/api/$apiVersion/products.json";

// cURL Request setup
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-Shopify-Access-Token: $accessToken",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($productData));

// Request execute karein
$response = curl_exec($ch);
curl_close($ch);

// Result dekhein
$result = json_decode($response, true);

if (isset($result['product'])) {
    echo "Success! Product created. ID: " . $result['product']['id'];
} else {
    echo "Error: " . $response;
}
?>