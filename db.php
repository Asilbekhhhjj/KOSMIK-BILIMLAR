<?php
// db.php

$host    = 'dpg-d876usd7vvec738oatvg-a.oregon-postgres.render.com'; // Render'dan olingan External Hostname
$port    = '5432'; // PostgreSQL standart porti
$db      = 'bbt_gaid';
$user    = 'bbt_gaid_user';
$pass    = '8J0sWG9wiP9m99sstLnG3Tmbo1zr52xl'; // Skrinshotdagi parol

try {
    // Diqqat: mysql: o'rniga pgsql: yoziladi
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (\PDOException $e) {
    // Muammo drayverda bo'lsa, xatoni to'liq ko'rish uchun vaqtincha quyidagicha yozamiz:
    die("Baza bilan aloqa yo'q! Xato tafsiloti: " . $e->getMessage());
}
