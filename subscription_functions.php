<?php
include 'db.php';

include 'db.php';

function get_user_invoices($user_id) {
    global $conn;
    $invoices = [];

    $query = "SELECT * FROM invoices WHERE user_id = ? ORDER BY created_at DESC";
    
    $stmt = $conn->prepare($query);

    // Check if statement was prepared successfully
    if (!$stmt) {
        // Display the error message for debugging
        die("Query preparation failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $invoices[] = $row;
    }

    return $invoices;
}


function generate_invoice($user_id, $amount, $subscription_details = 'No details provided') {
    global $conn;

    $invoice_number = 'INV-' . date('Ymd') . '-' . $user_id . '-' . rand(1000, 9999);

    $query = "INSERT INTO invoices (user_id, invoice_number, amount, currency, status, subscription_details) 
              VALUES (?, ?, ?, 'USD', 'pending', ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isds", $user_id, $invoice_number, $amount, $subscription_details);
    
    if ($stmt->execute()) {
        $invoice_id = $conn->insert_id;
        $query = "SELECT * FROM invoices WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $invoice_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    } else {
        return null;
    }
}

function convert_currency($amount, $from_currency, $to_currency) {
    $exchange_rates = [
        'USD' => ['EUR' => 0.85, 'GBP' => 0.75, 'JPY' => 110.0 , 'IND'=> 83.23],
        'EUR' => ['USD' => 1.18, 'GBP' => 0.88, 'JPY' => 129.5, 'IND' => 91.92],
        'GBP' => ['USD' => 1.33, 'EUR' => 1.14, 'JPY' => 147.0, 'IND' => 108.09],
        'JPY' => ['USD' => 0.0091, 'EUR' => 0.0077, 'GBP' => 0.0068 , 'IND' => 0.55],
        'IND' => ['USD' => 0.012 , 'EUR' => 0.011, 'JPY' => 1.82 , 'GBP' => 0.0092]
    ];

    if ($from_currency == $to_currency) {
        return $amount;
    }

    if (isset($exchange_rates[$from_currency][$to_currency])) {
        return $amount * $exchange_rates[$from_currency][$to_currency];
    }
    return $amount;
}

?>