<?php
// Oturum ayarları (session_start()'tan ÖNCE yapılmalı)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 10000); // 5 dakika
    session_set_cookie_params(10000); // 5 dakika
    session_start();
}

$dsn = 'mysql:host=localhost;dbname=isler;charset=utf8';
$username = 'root'; // mysql kullanıcı adınız
$password = ''; // mysql şifreniz

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Veritabanı bağlantı hatası: ' . $e->getMessage());
}
?>