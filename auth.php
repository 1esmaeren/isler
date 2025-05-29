<?php
// auth.php - Oturum kontrolü için
require 'config.php'; // config.php zaten session_start() içeriyor

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// dakika inaktivite kontrolü (1800 saniye)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 10000)) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit;
}

// Son aktivite zamanını güncelle
$_SESSION['last_activity'] = time();
?>