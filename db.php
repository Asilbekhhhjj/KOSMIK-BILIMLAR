<?php
// db.php

// 1. Render panelidagi ma'lumotlar
$host    = 'dpg-d876usd7vvec738oatvg-a.oregon-postgres.render.com'; // External Hostname
$port    = '5432';
$db      = 'bbt_gaid';
$user    = 'bbt_gaid_user';
$pass    = '8J0sWG9wiP9m99sstLnG3Tmbo1zr52xl';

try {
    // 2. MUHIM O'ZGARISH: DSN satrining oxiriga "sslmode=require" qo'shildi
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require;";
    
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // Agar hammasi muvaffaqiyatli bo'lsa, hech narsa chiqmaydi (yoki tekshirish uchun quyidagidan foydalanish mumkin)
    // echo "Baza bilan aloqa bor!"; 

} catch (\PDOException $e) {
    // Xavfsizlik nuqtai nazaridan parollar ko'rinib qolmasligi uchun xabarni tozalaymiz
    // Lekin hozir tekshiruv jarayonida xatoni aniq ko'rish uchun quyidagicha qoldiramiz:
    die("Baza bilan aloqa yo'q! Xato tafsiloti: " . $e->getMessage());
}
