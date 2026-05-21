<?php
// index.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'db.php';
session_start();

$error = "";

if (isset($_POST['login'])) {
    $user_id = $_POST['user_id'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // Oddiy parollar uchun tekshiruv (Bazada parollar qanday bo'lsa shunday tekshiradi)
    if ($user && $user['password'] === $password) {
        $_SESSION['student_id'] = $user['id'];
        header("Location: dashboard.php"); 
        exit;
    } else {
        $error = "❌ Maxfiy kod noto'g'ri, mitti botir! Qaytadan urinib ko'r!";
    }
}

// Bazadan eski kodingiz kabi barcha foydalanuvchilaran olish
$users = $pdo->query("SELECT * FROM users ORDER BY lastname ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kosmik Bilimlar Portali</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Nunito', sans-serif; }
        .space-bg {
            background-image: linear-gradient(rgba(15, 23, 42, 0.8), rgba(30, 41, 59, 0.85)), url('https://images.unsplash.com/photo-1506318137071-a8e063b4bec0?auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
        }
        .fun-title { font-family: 'Fredoka One', cursive; }
        @keyframes bounce-slow {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .rocket-bounce { animation: bounce-slow 3s infinite ease-in-out; }
    </style>
</head>
<body class="space-bg min-h-screen flex items-center justify-center p-4">

    <div class="absolute top-10 left-10 text-6xl opacity-30 rocket-bounce">🪐</div>
    <div class="absolute bottom-10 right-10 text-6xl opacity-30 rocket-bounce" style="animation-delay: 1.5s;">🌍</div>

    <div class="bg-white/95 backdrop-blur-md p-6 sm:p-8 rounded-[2.5rem] shadow-[0_20px_50px_rgba(0,0,0,0.3)] border-8 border-cyan-400 max-w-md w-full text-center relative">
        
        <div class="w-28 h-28 mx-auto rounded-full border-4 border-indigo-500 overflow-hidden shadow-xl bg-indigo-50 flex items-center justify-center">
            <img id="student-avatar" src="https://api.dicebear.com/7.x/bottts/svg?seed=welcome" alt="Avatar" class="w-full h-full object-cover transition-all duration-300">
        </div>

        <h1 class="fun-title text-3xl font-black text-indigo-950 mt-4 tracking-wide">KOSMIK BILIMLAR</h1>
        <p class="text-sm font-black text-cyan-500 uppercase tracking-widest mb-4">Sarguzasht boshlanadi 🚀</p>

        <?php if(!empty($error)): ?>
            <div class="bg-rose-50 text-rose-600 p-3 rounded-2xl text-xs sm:text-sm font-black border-2 border-rose-200 mb-4">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" class="space-y-4 text-left">
            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-1">👤 Ismingizni toping:</label>
                <select name="user_id" id="user-select" required onchange="updateAvatar()" class="w-full p-4 border-4 border-slate-200 rounded-2xl bg-slate-50 font-black text-slate-800 focus:border-indigo-500 focus:ring-0 focus:outline-none text-base transition appearance-none cursor-pointer">
                    <option value="" disabled selected>Men kimman? 🤔</option>
                    <?php foreach($users as $u): ?>
                        <?php 
                        // Agar bazada avatar ustuni bo'lsa uni oladi, yo'q bo'lsa avtomatik yaratadi
                        $avatarUrl = (isset($u['avatar']) && $u['avatar']) ? $u['avatar'] : "https://api.dicebear.com/7.x/bottts/svg?seed=" . urlencode($u['firstname']); 
                        $gradeInfo = isset($u['grade']) ? $u['grade'] . "-sinf | " : "";
                        ?>
                        <option value="<?= $u['id'] ?>" data-avatar="<?= $avatarUrl ?>" data-name="<?= htmlspecialchars($u['firstname']) ?>">
                            👶 <?= $gradeInfo ?><?= htmlspecialchars($u['lastname'] . ' ' . $u['firstname']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-xs font-black text-slate-500 uppercase tracking-wider mb-1">🔑 Maxfiy Kodni kiriting:</label>
                <input type="password" name="password" id="pin-input" readonly required placeholder="••••" class="w-full p-3 border-4 border-slate-200 rounded-2xl tracking-[1em] text-center text-3xl font-black focus:border-indigo-500 focus:outline-none bg-slate-50 text-indigo-600 placeholder-slate-300">
            </div>

            <div class="grid grid-cols-3 gap-2 pt-2">
                <?php for($i = 1; $i <= 9; $i++): ?>
                    <button type="button" onclick="pressNum('<?= $i ?>')" class="bg-slate-100 hover:bg-slate-200 text-slate-800 text-xl font-black p-3 rounded-xl transition active:scale-95 border-b-4 border-slate-300"><?= $i ?></button>
                <?php endfor; ?>
                <button type="button" onclick="clearPin()" class="bg-rose-100 hover:bg-rose-200 text-rose-600 text-sm font-black p-3 rounded-xl transition active:scale-95 border-b-4 border-rose-300">O'chirish</button>
                <button type="button" onclick="pressNum('0')" class="bg-slate-100 hover:bg-slate-200 text-slate-800 text-xl font-black p-3 rounded-xl transition active:scale-95 border-b-4 border-slate-300">0</button>
                <div class="bg-indigo-100 text-indigo-600 text-xl font-black p-3 rounded-xl flex items-center justify-center border-b-4 border-indigo-200 select-none">✨</div>
            </div>

            <button type="submit" name="login" class="w-full bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-black text-xl p-4 rounded-2xl transition shadow-lg shadow-indigo-200 transform active:scale-95 duration-200 text-center mt-2 border-b-4 border-indigo-800">
                KOSMOSGA UCHISH! 🚀
            </button>
        </form>
    </div>

    <script>
        function speak(text) {
            if ('speechSynthesis' in window) {
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'uz-UZ';
                utterance.rate = 1.0;
                window.speechSynthesis.speak(utterance);
            }
        }

        window.onload = function() {
            <?php if(!empty($error)): ?>
                speak("Maxfiy kod noto'g'ri kiritildi, qaytadan urinib ko'r!");
            <?php else: ?>
                speak("Salom mitti botir! Ro'yxatdan ismingni tanla!");
            <?php endif; ?>
        };

        function updateAvatar() {
            const select = document.getElementById('user-select');
            const selectedOption = select.options[select.selectedIndex];
            const avatarUrl = selectedOption.getAttribute('data-avatar');
            const studentName = selectedOption.getAttribute('data-name');
            
            if(avatarUrl) {
                document.getElementById('student-avatar').src = avatarUrl;
                speak("Salom " + studentName + ", endi maxfiy kodingni kirit!");
            }
        }

        const pinInput = document.getElementById('pin-input');
        function pressNum(num) {
            if(pinInput.value.length < 6) { // Maksimal uzunlikni 6 qildik (eski kodingizga mos)
                pinInput.value += num;
            }
        }
        function clearPin() { pinInput.value = ""; }
    </script>
</body>
</html>