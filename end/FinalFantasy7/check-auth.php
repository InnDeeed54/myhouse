<?php
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['user_id'])) {
    $currentRole = $_SESSION['role'] ?? 'user';
    
    // ЖЕСТКАЯ СТРАХОВКА: если логин notnp, то это 100% админ
    if ($_SESSION['username'] === 'notnp') {
        $currentRole = 'admin';
    }

    echo json_encode([
        'authorized' => true,
        'username' => $_SESSION['username'],
        'role' => $currentRole
    ]);
} else {
    echo json_encode([
        'authorized' => false
    ]);
}
?>
