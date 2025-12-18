<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$secret_key = "sk_live_QgT5uys2TTPF2M8sLCmd4Qgw";
$amount = 100; // ₱1.00

/* -----------------------------
   STEP 1: CREATE PAYMENT INTENT
--------------------------------*/
$ch = curl_init("https://api.paymongo.com/v1/payment_intents");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_USERPWD => $secret_key . ":",
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    CURLOPT_POSTFIELDS => json_encode([
        "data" => [
            "attributes" => [
                "amount" => $amount,
                "currency" => "PHP",
                "payment_method_allowed" => ["qrph"],
                "capture_type" => "automatic"
            ]
        ]
    ])
]);

$intent_response = curl_exec($ch);
curl_close($ch);

$intent = json_decode($intent_response, true);

if (!isset($intent['data']['id'])) {
    die("<pre>Error creating payment intent:\n" . print_r($intent, true) . "</pre>");
}

$payment_intent_id = $intent['data']['id'];


/* -----------------------------
   STEP 2: CREATE QR PH METHOD
--------------------------------*/
$ch = curl_init("https://api.paymongo.com/v1/payment_methods");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_USERPWD => $secret_key . ":",
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    CURLOPT_POSTFIELDS => json_encode([
        "data" => [
            "attributes" => [
                "type" => "qrph",
                "billing" => [
                    "name"  => "Test Customer",
                    "email" => "test@email.com"
                ]
            ]
        ]
    ])
]);

$method_response = curl_exec($ch);
curl_close($ch);

$method = json_decode($method_response, true);

if (!isset($method['data']['id'])) {
    die("<pre>Error creating payment method:\n" . print_r($method, true) . "</pre>");
}

$payment_method_id = $method['data']['id'];


/* -----------------------------
   STEP 3: ATTACH METHOD
--------------------------------*/
$BASE_URL = "https://ossicular-nonpractically-ronna.ngrok-free.dev/PAYMONGO_QR_PH";

$ch = curl_init("https://api.paymongo.com/v1/payment_intents/$payment_intent_id/attach");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_USERPWD => $secret_key . ":",
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    CURLOPT_POSTFIELDS => json_encode([
        "data" => [
            "attributes" => [
                "payment_method" => $payment_method_id,
                "return_url" => "$BASE_URL/success.php"
            ]
        ]
    ])
]);

$attach_response = curl_exec($ch);
curl_close($ch);

$attached = json_decode($attach_response, true);

$qr_image = $attached['data']['attributes']['next_action']['code']['image_url'] ?? null;

if (!$qr_image) {
    die("<pre>Error attaching QR PH:\n" . print_r($attached, true) . "</pre>");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>QR PH Online Checkout</title>
</head>
<body>

<h2>Scan QR PH</h2>
<p>Amount: ₱1.00</p>

<img src="<?= $qr_image ?>" alt="QR PH">

</body>
</html>
