<?php
// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Your PayMongo secret key (for webhook signature verification, optional but recommended)
$secret_key = "sk_live_QgT5uys2TTPF2M8sLCmd4Qgw";

// Get the raw POST body
$payload = file_get_contents("php://input");

// Decode JSON
$event_json = json_decode($payload, true);

// Log incoming payload for debugging (optional)
file_put_contents('webhook.log', date('Y-m-d H:i:s') . " - " . $payload . PHP_EOL, FILE_APPEND);

// Check for 'data' key
if (!isset($event_json['data']['attributes'])) {
    http_response_code(400);
    echo "Invalid webhook payload";
    exit;
}

$attributes = $event_json['data']['attributes'] ?? [];
$event_type = $attributes['type'] ?? '';

switch ($event_type) {
    
    case "payment.paid":
        $payment = $attributes['data']['attributes'];
        $amount = $payment['amount'] / 100; // convert from centavos
        $currency = $payment['currency'];
        $customer = $payment['billing']['name'] ?? 'N/A';
        $payment_intent_id = $payment['payment_intent_id'] ?? '';
        
        // TODO: Mark order as paid in your DB
        file_put_contents('webhook.log', date('Y-m-d H:i:s') . " - Payment PAID: $amount $currency by $customer (Intent: $payment_intent_id)" . PHP_EOL, FILE_APPEND);
        
        break;

    case "payment.failed":
        $payment = $attributes['data']['attributes'];
        $amount = $payment['amount'] / 100;
        $customer = $payment['billing']['name'] ?? 'N/A';
        $failed_message = $payment['failed_message'] ?? 'Unknown error';
        $payment_intent_id = $payment['payment_intent_id'] ?? '';
        
        // TODO: Mark order as failed in your DB
        file_put_contents('webhook.log', date('Y-m-d H:i:s') . " - Payment FAILED: $amount by $customer. Reason: $failed_message (Intent: $payment_intent_id)" . PHP_EOL, FILE_APPEND);
        
        break;

    case "qrph.expired":
        $qrph = $attributes['data']['attributes'];
        $payment_intent_id = $qrph['payment_intent_id'] ?? '';
        
        // TODO: Mark QR as expired, generate a new one if needed
        file_put_contents('webhook.log', date('Y-m-d H:i:s') . " - QR PH EXPIRED (Intent: $payment_intent_id)" . PHP_EOL, FILE_APPEND);
        
        break;

    default:
        // Unknown event
        file_put_contents('webhook.log', date('Y-m-d H:i:s') . " - Unknown webhook event: $event_type" . PHP_EOL, FILE_APPEND);
        break;
}

// Return 200 to acknowledge receipt
http_response_code(200);
echo json_encode(['status' => 'success']);
