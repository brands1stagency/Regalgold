<?php
// Security headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Start session for CSRF token and rate limiting
session_start();

// CORS - only allow same origin
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Methods: POST');
    header('Access-Control-Allow-Headers: Content-Type');
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// Rate limiting - 1 submission per 5 minutes per session
if (isset($_SESSION['last_contact_submission'])) {
    if (time() - $_SESSION['last_contact_submission'] < 300) {
        http_response_code(429);
        echo json_encode(['success' => false, 'error' => 'Please wait before submitting another message']);
        exit();
    }
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Security token validation failed']);
    exit();
}

// Get and sanitize form inputs
$fullName = sanitizeInput($_POST['fullName'] ?? '');
$email = sanitizeInput($_POST['email'] ?? '');
$phone = sanitizeInput($_POST['phone'] ?? '');
$subject = sanitizeInput($_POST['subject'] ?? '');
$message = sanitizeInput($_POST['message'] ?? '');

// Validate required fields
$errors = [];
if (empty($fullName)) {
    $errors[] = 'Full name is required';
}
if (empty($email)) {
    $errors[] = 'Email is required';
}
if (empty($subject)) {
    $errors[] = 'Subject is required';
}
if (empty($message)) {
    $errors[] = 'Message is required';
}

// Validate email format
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email address';
}

// Validate phone if provided
if (!empty($phone) && !preg_match('/^[\d\s\-\+\(\)]{7,}$/', $phone)) {
    $errors[] = 'Invalid phone number';
}

// Return validation errors
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit();
}

// Prepare email
$to = 'info@regalgoldanddiamonds.com';
$emailSubject = 'New Contact Form Submission: ' . $subject;

// Build email body
$emailBody = "Contact Form Submission\n";
$emailBody .= "======================\n\n";
$emailBody .= "Full Name: " . $fullName . "\n";
$emailBody .= "Email: " . $email . "\n";
if (!empty($phone)) {
    $emailBody .= "Phone: " . $phone . "\n";
}
$emailBody .= "Subject: " . $subject . "\n";
$emailBody .= "\nMessage:\n";
$emailBody .= $message . "\n";
$emailBody .= "\n======================\n";
$emailBody .= "Submitted from: " . $_SERVER['HTTP_HOST'] . "\n";
$emailBody .= "Submission time: " . date('Y-m-d H:i:s') . "\n";
$emailBody .= "IP Address: " . getClientIP() . "\n";

// Prepare headers to prevent header injection
$headers = [
    'From' => $email,
    'Reply-To' => $email,
    'X-Mailer' => 'Regal Gold Contact Form',
    'Content-Type' => 'text/plain; charset=UTF-8'
];

// Format headers for mail() function
$headerString = '';
foreach ($headers as $key => $value) {
    // Sanitize header values to prevent injection
    $value = str_replace(["\r", "\n"], '', $value);
    $headerString .= $key . ': ' . $value . "\r\n";
}

// Send email
if (mail($to, $emailSubject, $emailBody, $headerString)) {
    // Update rate limiting
    $_SESSION['last_contact_submission'] = time();
    
    // Send confirmation email to user
    $confirmationSubject = 'We received your message - Regal Gold';
    $confirmationBody = "Dear " . $fullName . ",\n\n";
    $confirmationBody .= "Thank you for contacting Regal Gold. We have received your message and will get back to you shortly.\n\n";
    $confirmationBody .= "Best regards,\n";
    $confirmationBody .= "Regal Gold Team\n";
    $confirmationBody .= "info@regalgoldanddiamonds.com\n";
    
    $confirmationHeaders = "From: info@regalgoldanddiamonds.com\r\nContent-Type: text/plain; charset=UTF-8";
    mail($email, $confirmationSubject, $confirmationBody, $confirmationHeaders);
    
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your message! We will get back to you soon.'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to send message. Please try again later.'
    ]);
}

// Helper function to sanitize input
function sanitizeInput($input) {
    // Trim whitespace
    $input = trim($input);
    
    // Remove any null bytes
    $input = str_replace("\0", '', $input);
    
    // HTMLspecialchars for display purposes
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    
    return $input;
}

// Helper function to get client IP safely
function getClientIP() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        // Cloudflare
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Other proxy
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        return $_SERVER['REMOTE_ADDR'];
    }
    return 'Unknown';
}
?>
