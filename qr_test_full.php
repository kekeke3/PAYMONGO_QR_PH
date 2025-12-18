<?php
// Enable errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- PAYMONGO TEST SECRET KEY ---
$secret_key = "sk_live_QgT5uys2TTPF2M8sLCmd4Qgw";

// --- AMOUNT (in centavos, 100 = ₱10) ---
$amount = 100;

// --- STEP 1: Create Payment Intent ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.paymongo.com/v1/payment_intents");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);

$intent_data = json_encode([
    "data" => [
        "attributes" => [
            "amount" => $amount,
            "currency" => "PHP",
            "payment_method_allowed" => ["qrph"], // QR PH via GCash
            "capture_type" => "automatic"
        ]
    ]
]);

curl_setopt($ch, CURLOPT_POSTFIELDS, $intent_data);
curl_setopt($ch, CURLOPT_USERPWD, $secret_key . ":");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

$intent_response = curl_exec($ch);
curl_close($ch);

$intent = json_decode($intent_response, true);
if (!isset($intent['data']['id'])) {
    echo "Error creating Payment Intent:";
    print_r($intent);
    exit;
}

$payment_intent_id = $intent['data']['id'];
echo "<h3>Payment Intent Created:</h3>";
echo "<pre>"; print_r($intent); echo "</pre>";

// --- STEP 2: Create Payment Method (GCash) ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.paymongo.com/v1/payment_methods");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);

$method_data = json_encode([
    "data" => [
        "attributes" => [
            "type" => "qrph",
            "billing" => [
                "name"  => "Test User",
                "email" => "test@example.com",
                "phone" => "+639171234567"
            ]
        ]
    ]
]);


curl_setopt($ch, CURLOPT_POSTFIELDS, $method_data);
curl_setopt($ch, CURLOPT_USERPWD, $secret_key . ":");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

$method_response = curl_exec($ch);
curl_close($ch);

$payment_method = json_decode($method_response, true);
if (!isset($payment_method['data']['id'])) {
    echo "Error creating Payment Method:";
    print_r($payment_method);
    exit;
}

$payment_method_id = $payment_method['data']['id'];
echo "<h3>Payment Method Created:</h3>";
echo "<pre>"; print_r($payment_method); echo "</pre>";

// --- STEP 3: Attach Payment Method to Payment Intent ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.paymongo.com/v1/payment_intents/$payment_intent_id/attach");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);

$attach_data = json_encode([
    "data" => [
        "attributes" => [
            "payment_method" => $payment_method_id,
            "return_url" => "https://ossicular-nonpractically-ronna.ngrok-free.dev/PAYMONGO_QR_PH"
        ]
    ]
]);

curl_setopt($ch, CURLOPT_POSTFIELDS, $attach_data);
curl_setopt($ch, CURLOPT_USERPWD, $secret_key . ":");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

$attach_response = curl_exec($ch);
curl_close($ch);

$attached = json_decode($attach_response, true);

echo "<h3>Payment Method Attached:</h3>";
echo "<pre>"; print_r($attached); echo "</pre>";

// --- STEP 4: Show Checkout URL ---
if (isset($attached['data']['attributes']['next_action']['redirect']['checkout_url'])) {
    $checkout_url = $attached['data']['attributes']['next_action']['redirect']['checkout_url'];
    echo "<h2>Scan this QR PH Checkout URL:</h2>";
    echo "<a href='$checkout_url' target='_blank'>$checkout_url</a>";
} else {
    echo "Error generating checkout URL.";
    print_r($attached);
}
?>
