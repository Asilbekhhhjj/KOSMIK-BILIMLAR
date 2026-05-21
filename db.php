<?php
// db.php

// Render'dan olingan aniq va to'g'ri ma'lumotlar
$host    = 'dpg-d876usd7vvec738oatvg-a.oregon-postgres.render.com'; // External Database URL ichidan olingan xost
$port    = '5432';
$db      = 'bbt_gaid';
$user    = 'bbt_gaid_user';
$pass    = '8J0sWG9wIP9m99sstLnG3Tmbo1zr52xl'; // Siz yuborgan aniq parol

try {
    // Render majburiy talab qilgan SSL rejimi bilan ulanish
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require;";
    
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Ulanish muvaffaqiyatli bo'lsa, hech qanday xato chiqmaydi va loyiha ishlaydi

} catch (\PDOException $e) {
    die("Baza bilan aloqa yo'q! Xato tafsiloti: " . $e->getMessage());
}
