<?php
// chat.php
require 'db.php';
session_start();

// O'quvchi tizimga kirganini tekshirish
if (!isset($_SESSION['student_id'])) {
    header("Location: index.php"); exit;
}

$user_id = $_SESSION['student_id'];

// O'quvchi ma'lumotlarini bazadan olish
$user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user->execute([$user_id]);
$student = $user->fetch();

// BA'ZA TEKSHIRUVI: Agar xabarlar jadvali bazada bo'lmasa, uni avtomatik yaratamiz
$pdo->query("CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    sender VARCHAR(50),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Yangi xabar yuborilganda uni saqlash logikasi
if (isset($_POST['send_msg']) && !empty(trim($_POST['message']))) {
    $stmt = $pdo->prepare("INSERT INTO messages (user_id, sender, message) VALUES (?, 'student', ?)");
    $stmt->execute([$user_id, trim($_POST['message'])]);
    header("Location: chat.php"); exit;
}

// Oxirgi 50 ta xabarni vaqt bo'yicha tartiblab yuklash
$messages = $pdo->prepare("SELECT * FROM messages WHERE user_id = ? ORDER BY id ASC LIMIT 50");
$messages->execute([$user_id]);
$all_msgs = $messages->fetchAll();
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ustoz bilan chat - O'yin Olami</title>
    <!-- 100% Barqaror Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Kosmik fon effekti */
        .chat-bg {
            background-image: linear-gradient(rgba(241, 245, 249, 0.9), rgba(226, 232, 240, 0.9)), url('https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
        }
    </style>
</head>
<body class="chat-bg min-h-screen flex flex-col justify-between font-sans text-slate-800">
    
    <!-- 1. YUQORI PANEL (Sarlavha) -->
    <div class="bg-white/95 backdrop-blur-md border-b-4 border-indigo-100 p-4 sticky top-0 z-10 shadow-md flex justify-between items-center">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-full overflow-hidden border-2 border-indigo-500 bg-indigo-50 flex items-center justify-center">
                <!-- Ustozning chiroyli rasmi -->
                <img src="https://images.unsplash.com/photo-1544717305-2782549b5136?auto=format&fit=crop&w=150&q=80" class="w-full h-full object-cover">
            </div>
            <div>
                <h1 class="text-base md:text-lg font-black text-slate-900 tracking-wide">Ustoz bilan jonli muloqot 👩‍🏫</h1>
                <p class="text-xs text-emerald-500 font-bold flex items-center gap-1 animate-pulse">
                    <span class="inline-block w-2 h-2 bg-emerald-500 rounded-full"></span> Tarmoqda (Sizga yordamga tayyor)
                </p>
            </div>
        </div>
        
        <!-- Orqaga qaytish chiroyli o'yin tugmasi -->
        <a href="dashboard.php" class="bg-amber-400 hover:bg-amber-500 text-slate-950 font-black px-4 py-2.5 rounded-2xl text-xs md:text-sm shadow-md transition transform active:scale-95 duration-150">
            Bosh sahifa 🏠
        </a>
    </div>

    <!-- 2. XABARLAR ALMASHINISH MAYDONI -->
    <div class="flex-1 p-4 overflow-y-auto space-y-4 max-w-3xl w-full mx-auto" id="chatContainer">
        <?php if(empty($all_msgs)): ?>
            <!-- Agar hali yozishmagan bo'lsa chiqadigan quvnoq oyna -->
            <div class="text-center py-16 bg-white/80 backdrop-blur-xs rounded-3xl p-6 shadow-sm border-2 border-dashed border-slate-300 mt-10">
                <span class="text-5xl animate-bounce inline-block">👋</span>
                <h3 class="text-lg font-black text-slate-800 mt-3">Salom, <?= htmlspecialchars($student['firstname']) ?>!</h3>
                <p class="text-sm font-bold text-slate-500 mt-1">Hali ustozga xat yozmabsan. Pastdagi maydonga birinchi bo'lib "Salom ustoz" deb yozib yubor!</p>
            </div>
        <?php else: ?>
            <!-- Xabarlar ro'yxati -->
            <?php foreach($all_msgs as $m): ?>
                <?php if($m['sender'] === 'student'): ?>
                    <!-- O'QUVCHINING XABARI (O'ng tomonda yashil/indigo rangda) -->
                    <div class="flex justify-end animate-in fade-in slide-in-from-bottom-2 duration-200">
                        <div class="bg-indigo-600 text-white p-4 rounded-3xl rounded-tr-none max-w-xs md:max-w-md shadow-lg shadow-indigo-100 border border-indigo-700">
                            <p class="text-sm font-bold tracking-wide leading-relaxed"><?= htmlspecialchars($m['message']) ?></p>
                            <span class="block text-[10px] text-indigo-200 text-right mt-1.5 font-semibold">
                                ⏱ <?= date('H:i', strtotime($m['created_at'])) ?>
                            </span>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- USTOZNING XABARI (Chap tomonda oq rangda) -->
                    <div class="flex justify-start animate-in fade-in slide-in-from-bottom-2 duration-200">
                        <div class="bg-white border-2 border-slate-200 p-4 rounded-3xl rounded-tl-none max-w-xs md:max-w-md shadow-md">
                            <p class="text-xs font-black text-indigo-600 mb-1 uppercase tracking-wider flex items-center gap-1">
                                <span>👩‍🏫</span> Ustozingiz
                            </p>
                            <p class="text-sm text-slate-800 font-extrabold leading-relaxed"><?= htmlspecialchars($m['message']) ?></p>
                            <span class="block text-[10px] text-slate-400 mt-1.5 font-semibold">
                                ⏱ <?= date('H:i', strtotime($m['created_at'])) ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- 3. PASTKI QISM (Xabar yozish shakli) -->
    <div class="bg-white/95 backdrop-blur-md border-t-4 border-slate-100 p-4 sticky bottom-0 z-10 shadow-lg">
        <form action="" method="POST" class="max-w-3xl w-full mx-auto flex gap-3">
            <!-- Matn kiritish maydoni -->
            <input type="text" 
                   name="message" 
                   autocomplete="off" 
                   required 
                   placeholder="Ustozga savolingizni yoki xatingizni yozing..." 
                   class="flex-1 p-4 border-3 border-slate-200 rounded-2xl focus:border-indigo-500 focus:ring-0 focus:outline-none font-bold text-slate-800 bg-slate-50 text-sm md:text-base placeholder-slate-400 transition">
            
            <!-- Yuborish tugmasi -->
            <button type="submit" 
                    name="send_msg" 
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-black px-6 md:px-8 rounded-2xl shadow-lg shadow-indigo-100 transition transform active:scale-95 duration-150 flex items-center gap-2 text-sm md:text-base">
                <span>Yuborish</span> 🚀
            </button>
        </form>
    </div>

    <!-- Avtomatik Skroll skripti -->
    <script>
        // Chat ochilishi bilan eng pastki (oxirgi) xabarga avtomatik tushiradi
        var chatBox = document.getElementById("chatContainer");
        chatBox.scrollTop = chatBox.scrollHeight;
    </script>
</body>
</html>