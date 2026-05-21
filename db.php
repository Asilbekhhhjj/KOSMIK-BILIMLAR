<?php
// db.php

// Render panelingizdan olingan PostgreSQL ma'lumotlari
$host    = 'dpg-d876usd7vvec738oatvg-a.oregon-postgres.render.com'; 
$port    = '5432'; 
$db      = 'bbt_gaid'; 
$user    = 'bbt_gaid_user'; 
$pass    = '8JOsWG9wIP9m99sstLnG3Tmbo1zr52xl'; 

// MySQL o'rniga PostgreSQL (pgsql) drayverini ishlatamiz
$dsn = "pgsql:host=$host;port=$port;dbname=$db";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // Agar biror xato bo'lsa, ekranda aniq ko'rinishi uchun
     echo "<h3>Baza bilan aloqa yo'q!</h3>";
     die("Xato tafsiloti: " . $e->getMessage());
}
