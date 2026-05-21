<?php
// db.php

// InfinityFree hosting ma'lumotlari
$host    = 'sql313.infinityfree.com';
$db      = 'if0_40928446_if0_4077532_bbt'; // Sizning to'liq ma'lumotlar bazangiz nomi
$user    = 'if0_40928446';                 // MySQL Username
$pass    = 'LfGxWhtAaNyQ';                 // MySQL Password
$port    = '3306';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;port=$port;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // Xavfsizlik nuqtai nazaridan hostingda parollar xatolik ichida ko'rinib qolmasligi uchun xabarni tozalaymiz
     die("Ma'lumotlar bazasiga ulanishda xatolik yuz berdi ulanib bo'lmadi.");
}
