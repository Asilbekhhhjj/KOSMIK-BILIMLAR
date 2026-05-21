<?php
// db.php

$host    = 'dpg-d876usd7vvec738oatvg-a.oregon-postgres.render.com';
$port    = '5432';
$db      = 'bbt_gaid';
$user    = 'bbt_gaid_user';
$pass    = '8J0sWG9wIP9m99sstLnG3Tmbo1zr52xl';

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require;";
    
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // 🔥 MUHIM: Strukturani yangilash uchun eski chala jadvallarni o'chirib tashlaymiz (Faqat 1 marta bajariladi)
    // Agar eski jadvallar bo'lsa, ularni to'liq tozalaydi
    $pdo->exec("DROP TABLE IF EXISTS attempts CASCADE;");
    $pdo->exec("DROP TABLE IF EXISTS questions CASCADE;");
    $pdo->exec("DROP TABLE IF EXISTS quizzes CASCADE;");
    $pdo->exec("DROP TABLE IF EXISTS users CASCADE;");

    // 🚀 ENDI JADVALLARNI ENG TO'G'RI VARIANTDA BOSHQADAN YARATAMIZ

    // 1. O'quvchilar jadvali (Hamma kerakli ustunlar bilan)
    $pdo->exec("CREATE TABLE users (
        id SERIAL PRIMARY KEY,
        firstname VARCHAR(50) NOT NULL,
        lastname VARCHAR(50) NOT NULL,
        grade INT NOT NULL,
        password VARCHAR(255) DEFAULT '1234',
        avatar VARCHAR(255) DEFAULT NULL
    );");

    // 2. Testlar jadvali
    $pdo->exec("CREATE TABLE quizzes (
        id SERIAL PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        grade INT NOT NULL,
        time_limit INT NOT NULL, 
        max_attempts INT DEFAULT 1 NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );");

    // 3. Savollar jadvali
    $pdo->exec("CREATE TABLE questions (
        id SERIAL PRIMARY KEY,
        quiz_id INT NOT NULL,
        question_text TEXT NOT NULL,
        option_a VARCHAR(255) NOT NULL,
        option_b VARCHAR(255) NOT NULL,
        option_c VARCHAR(255) NOT NULL,
        option_d VARCHAR(255) NOT NULL,
        correct_option CHAR(1) NOT NULL,
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
    );");

    // 4. Urinishlar va natijalar jadvali
    $pdo->exec("CREATE TABLE attempts (
        id SERIAL PRIMARY KEY,
        user_id INT NOT NULL,
        quiz_id INT NOT NULL,
        score INT NOT NULL,
        total_questions INT NOT NULL,
        attempt_number INT NOT NULL,
        completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
    );");

    // 5. Boshlang'ich mitti botirlarni toza jadvalga kiritish
    $pdo->exec("INSERT INTO users (firstname, lastname, grade, password) VALUES 
        ('Asilbek', 'Eshonqulov', 1, '1111'),
        ('Oydina', 'Ziyodullayeva', 1, '2222'),
        ('Jasur', 'Karimov', 2, '3333'),
        ('Madina', 'Aliyeva', 3, '4444');");

} catch (\PDOException $e) {
    die("Baza bilan aloqa yo'q! Xato tafsiloti: " . $e->getMessage());
}
