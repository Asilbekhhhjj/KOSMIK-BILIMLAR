<?php
// db.php

// 1. Render ma'lumotlari
$host    = 'dpg-d876usd7vvec738oatvg-a.oregon-postgres.render.com';
$port    = '5432';
$db      = 'bbt_gaid';
$user    = 'bbt_gaid_user';
$pass    = '8J0sWG9wIP9m99sstLnG3Tmbo1zr52xl';

try {
    // 2. SSL bilan ulanish
    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require;";
    
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // 3. Barcha jadvallarni avtomatik yaratish (PostgreSQL mos versiyasi)
    
    // -- 1. O'quvchilar jadvali
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id SERIAL PRIMARY KEY,
        firstname VARCHAR(50) NOT NULL,
        lastname VARCHAR(50) NOT NULL,
        grade INT NOT NULL,
        password VARCHAR(255) DEFAULT '1234', -- Tizimga kirish kodi (standart: 1234)
        avatar VARCHAR(255) DEFAULT NULL
    );");

    // -- 2. Testlar jadvali
    $pdo->exec("CREATE TABLE IF NOT EXISTS quizzes (
        id SERIAL PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        grade INT NOT NULL,
        time_limit INT NOT NULL, 
        max_attempts INT DEFAULT 1 NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );");

    // -- 3. Savollar jadvali
    $pdo->exec("CREATE TABLE IF NOT EXISTS questions (
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

    // -- 4. Urinishlar va natijalar jadvali
    $pdo->exec("CREATE TABLE IF NOT EXISTS attempts (
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

    // -- 5. Baza bo'sh bo'lsa, test uchun boshlang'ich o'quvchilarni qo'shish
    $checkUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($checkUsers == 0) {
        $pdo->exec("INSERT INTO users (firstname, lastname, grade, password) VALUES 
            ('Asilbek', 'Eshonqulov', 1, '1111'),
            ('Oydina', 'Ziyodullayeva', 1, '2222'),
            ('Jasur', 'Karimov', 2, '3333'),
            ('Madina', 'Aliyeva', 3, '4444');");
    }

} catch (\PDOException $e) {
    die("Baza bilan aloqa yo'q! Xato tafsiloti: " . $e->getMessage());
}
