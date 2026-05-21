<?php
// dashboard.php
require 'db.php';
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: index.php"); exit;
}

$user_id = $_SESSION['student_id'];

// 1. O'quvchining shaxsiy ma'lumotlarini yuklash
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$student = $user_stmt->fetch();

if (!$student) {
    session_destroy();
    header("Location: index.php"); exit;
}

// 2. O'quvchining joriy sinfiga mos faol testlarni va urinishlarni olish
$quiz_stmt = $pdo->prepare("
    SELECT q.*, 
    (SELECT COUNT(*) FROM attempts WHERE user_id = ? AND quiz_id = q.id) as used_attempts 
    FROM quizzes q 
    WHERE q.grade = ? 
    ORDER BY q.id DESC
");
$quiz_stmt->execute([$user_id, $student['grade']]);
$quizzes = $quiz_stmt->fetchAll();

// 3. Uyga vazifalarni yuklash
$hw_stmt = $pdo->prepare("SELECT * FROM homeworks WHERE grade = ? ORDER BY id DESC");
$hw_stmt->execute([$student['grade']]);
$homeworks = $hw_stmt->fetchAll();

// 4. Umumiy Reyting Tizimi
$rank_stmt = $pdo->query("
    SELECT u.id, u.firstname, u.lastname, SUM(a.score) as total_score
    FROM users u
    LEFT JOIN attempts a ON u.id = a.user_id
    GROUP BY u.id
    ORDER BY total_score DESC, u.lastname ASC
");
$all_rankings = $rank_stmt->fetchAll();

$my_rank = 1;
$my_total_score = 0;
foreach ($all_rankings as $index => $rank_user) {
    if ($rank_user['id'] == $user_id) {
        $my_rank = $index + 1;
        $my_total_score = (int)$rank_user['total_score'];
        break;
    }
}

// Avatarni generatsiya qilish (agar bazada rasmi bo'lmasa, DiceBear robotini yasaydi)
$myAvatarUrl = (isset($student['avatar']) && $student['avatar']) ? $student['avatar'] : "https://api.dicebear.com/7.x/bottts/svg?seed=" . urlencode($student['firstname']);
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sehrli Bilimlar Portali - Bosh Sahifa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Nunito:wght@700;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Nunito', sans-serif; }
        .fun-title { font-family: 'Fredoka One', cursive; }
        
        /* Multfilm uslubidagi kosmik fon */
        .space-bg {
            background-image: linear-gradient(rgba(15, 23, 42, 0.85), rgba(30, 41, 59, 0.9)), url('https://images.unsplash.com/photo-1506318137071-a8e063b4bec0?auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        /* Neon porlash effektlari */
        .neon-border-cyan { box-shadow: 0 0 20px rgba(34, 211, 238, 0.3); border: 4px solid #22d3ee; }
        .neon-border-amber { box-shadow: 0 0 20px rgba(251, 191, 36, 0.3); border: 4px solid #fbbf24; }
        .neon-border-indigo { box-shadow: 0 0 20px rgba(129, 140, 248, 0.3); border: 4px solid #818cf8; }

        /* Sekin uchish animatsiyasi */
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(3deg); }
        }
        .floating-element { animation: float 4s infinite ease-in-out; }
    </style>
</head>
<body class="space-bg min-h-screen text-slate-100 pb-12">

    <!-- UCHUVCHI SAYYORALAR (DEKORATSIYA) -->
    <div class="absolute top-24 left-6 text-4xl opacity-20 floating-element">🛸</div>
    <div class="absolute top-1/2 right-12 text-5xl opacity-25 floating-element" style="animation-delay: 2s;">🪐</div>

    <!-- 1. KOSMIK PROFIL BANNERI -->
    <div class="relative bg-slate-950/60 backdrop-blur-md border-b-4 border-indigo-500/50 shadow-2xl overflow-hidden mb-8">
        <div class="max-w-7xl mx-auto px-4 py-6 md:py-8 flex flex-col md:flex-row justify-between items-center gap-6">
            
            <!-- Chap tomon: O'quvchi ma'lumotlari -->
            <div class="flex items-center gap-5 text-center md:text-left flex-col md:flex-row">
                <div class="w-24 h-24 rounded-[2rem] bg-indigo-100 p-1 shadow-2xl transform -rotate-3 border-4 border-amber-400 floating-element relative group overflow-hidden">
                    <img src="<?= $myAvatarUrl ?>" alt="Avatar" class="w-full h-full object-cover rounded-[1.8rem]">
                    <div class="absolute inset-0 bg-gradient-to-t from-indigo-900/40 to-transparent"></div>
                </div>
                
                <div>
                    <h1 class="fun-title text-3xl md:text-4xl font-black tracking-wider text-transparent bg-clip-text bg-gradient-to-r from-amber-300 via-cyan-300 to-emerald-300">
                        SALOM, <?= htmlspecialchars($student['firstname']) ?>! ⭐
                    </h1>
                    <p class="text-slate-400 font-black text-sm mt-1">Kosmik kemangizga xush kelibsiz! Bugun yangi bilimlarni zabt etamiz!</p>
                    
                    <!-- Katta status chiplari -->
                    <div class="flex flex-wrap gap-2 mt-3 justify-center md:justify-start">
                        <span class="bg-indigo-500/20 text-indigo-300 border border-indigo-500/50 font-black text-xs px-4 py-1.5 rounded-2xl uppercase tracking-wider">
                            🚀 <?= $student['grade'] ?>-Sinf Qahramoni
                        </span>
                        <span class="bg-amber-400 text-slate-950 font-black text-xs px-4 py-1.5 rounded-2xl uppercase tracking-wider shadow-[0_4px_15px_rgba(251,191,36,0.4)]">
                            🏆 Reyting: #<?= $my_rank ?>-o'rin
                        </span>
                        <span class="bg-emerald-500 text-white font-black text-xs px-4 py-1.5 rounded-2xl uppercase tracking-wider shadow-[0_4px_15px_rgba(16,185,129,0.4)]">
                            ✨ Jamg'argan Ball: <?= $my_total_score ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- O'ng tomon: Chiqish tugmasi -->
            <a href="logout.php" class="bg-rose-500/20 hover:bg-rose-600 border border-rose-500/50 hover:border-rose-600 text-rose-300 hover:text-white font-black py-3 px-6 rounded-2xl shadow-lg transition transform active:scale-95 duration-150 text-sm flex items-center gap-2">
                Kemani tark etish 🚪
            </a>
        </div>
    </div>

    <!-- 2. ASOSIY PANELLAR SPESI -->
    <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- CHAP USTUN (2 KATTA BLOK): UYGA VAZIFALAR VA TESTLAR -->
        <div class="lg:col-span-2 space-y-10">
            
            <!-- A) UYGA VAZIFALAR SEKTORI -->
            <div>
                <h2 class="fun-title text-2xl font-black mb-4 flex items-center gap-3 text-amber-400">
                    <span class="p-2.5 bg-amber-500/20 rounded-2xl text-2xl border border-amber-500/30">📚</span> Bugungi Uyga Vazifalar:
                </h2>
                
                <?php if(empty($homeworks)): ?>
                    <div class="bg-slate-900/60 backdrop-blur-md p-8 rounded-[2rem] text-center border-4 border-dashed border-slate-700/60">
                        <p class="text-4xl mb-2">🎉</p>
                        <p class="text-slate-400 font-black text-base">Ura! Hozircha uyga vazifalar yo'q. Haqiqiy kosmonavtlar kabi dam oling!</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach($homeworks as $hw): ?>
                            <div class="bg-slate-900/80 backdrop-blur-md rounded-[2.5rem] overflow-hidden flex flex-col justify-between hover:scale-[1.02] transition-all duration-300 neon-border-amber group">
                                <div class="relative h-40 w-full overflow-hidden">
                                    <img src="<?= htmlspecialchars($hw['image_url'] ?: 'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?auto=format&fit=crop&w=600&q=80') ?>" class="h-full w-full object-cover group-hover:scale-110 transition duration-500">
                                    <div class="absolute inset-0 bg-gradient-to-t from-slate-900 via-transparent to-transparent"></div>
                                </div>
                                
                                <div class="p-5 flex-1">
                                    <h3 class="font-black text-amber-300 text-lg fun-title"><?= htmlspecialchars($hw['title']) ?></h3>
                                    <p class="text-xs text-slate-300 mt-2 font-bold leading-relaxed"><?= htmlspecialchars($hw['description']) ?></p>
                                </div>
                                
                                <div class="p-4 bg-amber-500/10 border-t-2 border-amber-500/20 flex justify-between items-center text-xs font-black text-amber-400">
                                    <span class="flex items-center gap-1">⏳ Muhlat:</span>
                                    <span class="bg-amber-400 text-slate-950 px-3 py-1 rounded-xl font-black shadow-sm"><?= $hw['deadline'] ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- B) TEST PAKETLARI SEKTORI -->
            <div>
                <h2 class="fun-title text-2xl font-black mb-4 flex items-center gap-3 text-cyan-400">
                    <span class="p-2.5 bg-cyan-500/20 rounded-2xl text-2xl border border-cyan-500/30">⚡</span> Bilim Sinov Markazi:
                </h2>
                
                <?php if(empty($quizzes)): ?>
                    <div class="bg-slate-900/60 backdrop-blur-md p-8 rounded-[2rem] text-center border-4 border-dashed border-slate-700/60">
                        <p class="text-4xl mb-2">🔭</p>
                        <p class="text-slate-400 font-black text-base">Yaqin orada yangi testlar koinotidan xabar keladi. Kutib qolamiz!</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php foreach($quizzes as $q): ?>
                            <div class="bg-slate-900/80 backdrop-blur-md rounded-[2.5rem] overflow-hidden flex flex-col justify-between hover:scale-[1.02] transition-all duration-300 neon-border-cyan">
                                <div class="p-6">
                                    <div class="w-12 h-12 bg-cyan-500/20 rounded-2xl flex items-center justify-center text-xl mb-3 border border-cyan-500/30">🎯</div>
                                    <h3 class="text-base font-black text-slate-100 mb-4 fun-title tracking-wide"><?= htmlspecialchars($q['title']) ?></h3>
                                    
                                    <div class="grid grid-cols-2 gap-3 text-center text-xs font-black">
                                        <div class="bg-slate-950/50 p-3 rounded-2xl border border-slate-800">
                                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">⏱ Berilgan Vaqt</p>
                                            <p class="text-cyan-400 text-sm mt-0.5"><?= $q['time_limit'] ?> daqiqa</p>
                                        </div>
                                        <div class="bg-slate-950/50 p-3 rounded-2xl border border-slate-800">
                                            <p class="text-[10px] text-slate-400 uppercase tracking-wider">📊 Urinishlar</p>
                                            <p class="text-amber-400 text-sm mt-0.5"><?= $q['used_attempts'] ?> / <?= $q['max_attempts'] ?></p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="p-4 bg-slate-950/40 border-t-2 border-slate-800">
                                    <?php if($q['used_attempts'] >= $q['max_attempts']): ?>
                                        <button disabled class="w-full bg-slate-800/80 border border-slate-700 text-slate-500 font-black py-3 rounded-2xl cursor-not-allowed text-xs text-center flex items-center justify-center gap-2">
                                            Imkoniyatlar tugadi 🔒
                                        </button>
                                    <?php else: ?>
                                        <a href="quiz.php?id=<?= $q['id'] ?>" class="w-full bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-600 hover:to-blue-700 text-white font-black text-center py-3 rounded-2xl block text-xs shadow-lg shadow-cyan-500/20 transition transform active:scale-95 duration-150 uppercase tracking-widest border-b-4 border-blue-800">
                                            Kosmik Testni Boshlash 🚀
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- O'NG USTUN: REYTING DOSKASI (TOP-5 YETAKCHILAR) -->
        <div class="lg:col-span-1">
            <div class="bg-slate-900/90 backdrop-blur-md rounded-[2.5rem] shadow-2xl p-5 sticky top-6 neon-border-indigo">
                <h2 class="fun-title text-lg font-black text-indigo-300 mb-4 flex items-center gap-2 border-b-2 border-slate-800 pb-3 uppercase tracking-wider">
                    <span>🏆</span> Eng Kuchli Top-5 Kosmonavt
                </h2>
                
                <div class="space-y-3">
                    <?php $r = 1; foreach(array_slice($all_rankings, 0, 5) as $rank_user): ?>
                        <?php 
                        // Har bir reytingdagi bola uchun ham dinamik robot avatar yaratamiz
                        $userAvatar = "https://api.dicebear.com/7.x/bottts/svg?seed=" . urlencode($rank_user['firstname']);
                        $isMe = ($rank_user['id'] == $user_id);
                        ?>
                        <div class="flex items-center justify-between p-3 rounded-2xl transition duration-200 <?= $isMe ? 'bg-indigo-500/20 border-2 border-indigo-400 shadow-[0_0_15px_rgba(129,140,248,0.2)]' : 'bg-slate-950/40 border border-slate-800' ?>">
                            
                            <div class="flex items-center gap-3">
                                <!-- Medal / O'rin belgilari -->
                                <span class="w-6 text-center text-base font-black">
                                    <?php if($r == 1): ?>🥇<?php elseif($r == 2): ?>🥈<?php elseif($r == 3): ?>🥉<?php else: ?><span class="text-slate-400 text-sm"><?= $r ?></span><?php endif; ?>
                                </span>
                                
                                <!-- Mitti Avatar -->
                                <div class="w-9 h-9 rounded-xl bg-slate-800 p-0.5 border border-slate-700 overflow-hidden">
                                    <img src="<?= $userAvatar ?>" class="w-full h-full object-cover">
                                </div>

                                <span class="text-xs font-black <?= $isMe ? 'text-indigo-300' : 'text-slate-200' ?>">
                                    <?= htmlspecialchars($rank_user['lastname'] . ' ' . substr($rank_user['firstname'], 0, 2)) ?>.
                                    <?= $isMe ? ' <span class="text-[10px] bg-indigo-500/40 px-1.5 py-0.5 rounded text-white ml-1">Siz</span>' : '' ?>
                                </span>
                            </div>
                            
                            <span class="bg-emerald-500/10 border border-emerald-500/30 px-2.5 py-1 rounded-xl text-xs font-black text-emerald-400 shadow-sm">
                                <?= (int)$rank_user['total_score'] ?> ball ⭐
                            </span>
                        </div>
                    <?php $r++; endforeach; ?>
                </div>
                
                <p class="text-[10px] text-slate-500 text-center font-bold mt-4 tracking-wide bg-slate-950/60 py-2 rounded-xl border border-slate-800">
                    Natijalar real vaqt rejimida yangilanadi 🛸
                </p>
            </div>
        </div>

    </div>

    <!-- CHAT TUGMASI -->
    <div class="fixed bottom-6 right-6 z-50">
        <a href="chat.php" class="flex items-center gap-2 bg-gradient-to-r from-indigo-500 to-purple-600 hover:from-indigo-600 hover:to-purple-700 text-white font-black px-6 py-4 rounded-full shadow-[0_10px_30px_rgba(99,102,241,0.4)] transition transform hover:scale-105 active:scale-95 text-xs uppercase tracking-widest border-b-4 border-indigo-800">
            <span class="text-lg animate-bounce">💬</span> Ustoz bilan chat
        </a>
    </div>

</body>
</html>