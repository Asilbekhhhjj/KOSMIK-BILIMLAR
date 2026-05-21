<?php
// db.php

// 1. Render'dan olingan aniq ma'lumotlar
$host    = 'dpg-d876usd7vvec738oatvg-a.oregon-postgres.render.com';
$port    = '5432';
$db      = 'bbt_gaid';
$user    = 'bbt_gaid_user';
$pass    = '8J0sWG9wIP9m99sstLnG3Tmbo1zr52xl';

try {
    // 2. SSL rejim bilan ulanish
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require;";
    
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // 3. MANA SHU YERGA YOZILADI: users jadvali yo'q bo'lsa, avtomatik yaratish
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        firstname VARCHAR(100),
        lastname VARCHAR(100) NOT NULL,
        email VARCHAR(150),
        password VARCHAR(255) NOT NULL,
        role VARCHAR(50) DEFAULT 'student',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );";
    
    $pdo->exec($sql);

} catch (\PDOException $e) {
    // Agar ulanishda yoki jadval yaratishda xato bo'lsa ko'rsatadi
    die("Baza bilan aloqa yo'q! Xato tafsiloti: " . $e->getMessage());
}
