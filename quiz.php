<?php
// quiz.php
require 'db.php';
session_start();

if (!isset($_SESSION['student_id']) || !isset($_GET['id'])) {
    header("Location: index.php"); exit;
}

$user_id = $_SESSION['student_id'];
$quiz_id = $_GET['id'];

// Test ma'lumotlarini olish
$quiz_stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
$quiz_stmt->execute([$quiz_id]);
$quiz = $quiz_stmt->fetch();

if (!$quiz) { header("Location: dashboard.php"); exit; }

// Admin panelda qo'lda qo'shilgan (ruchnoy) barcha savollarni yuklash
$q_stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id ASC");
$q_stmt->execute([$quiz_id]);
$questions = $q_stmt->fetchAll();

// Agar test topshirib bo'lingan bo'lsa va javoblar jo'natilgan bo'lsa
if (isset($_POST['submit_quiz'])) {
    $correct_answers = 0;
    $total_questions = count($questions);

    if ($total_questions > 0) {
        foreach ($questions as $q) {
            $selected = $_POST['answers'][$q['id']] ?? '';
            if ($selected === $q['correct_option']) {
                $correct_answers++;
            }
        }
        // Ball hisoblash formulasi (M: 10 ta savoldan 8 tasi to'g'ri bo'lsa = 80 ball)
        $final_score = round(($correct_answers / $total_questions) * 100);
    } else {
        $final_score = 0;
    }

    // Natijani attempts jadvaliga yozish (Reytingni avtomatik yangilaydi)
    $attempt_stmt = $pdo->prepare("INSERT INTO attempts (user_id, quiz_id, score) VALUES (?, ?, ?)");
    $attempt_stmt->execute([$user_id, $quiz_id, $final_score]);

    header("Location: dashboard.php?msg=Test tugadi! To'plagan balingiz: " . $final_score); exit;
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($quiz['title']) ?> - O'yin Testi</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-b from-indigo-50 to-slate-100 min-h-screen p-4 md:p-8 font-sans">
    <div class="max-w-3xl mx-auto bg-white rounded-3xl shadow-xl border-4 border-indigo-200 overflow-hidden">
        
        <!-- Sarlavha -->
        <div class="bg-indigo-600 text-white p-5 text-center relative">
            <h1 class="text-xl md:text-2xl font-black tracking-wide"><?= htmlspecialchars($quiz['title']) ?></h1>
            <p class="text-xs text-indigo-200 font-bold mt-1 uppercase tracking-widest">⏱ Ajratilgan vaqt: <?= $quiz['time_limit'] ?> daqiqa</p>
        </div>

        <?php if(empty($questions)): ?>
            <div class="p-10 text-center">
                <span class="text-4xl">⚙️</span>
                <p class="text-slate-500 font-black mt-2">Ushbu test paketi ichiga hali savollar qo'shilmagan! Ustoz tez orada savollarni ruchnoy yuklaydi.</p>
                <a href="dashboard.php" class="mt-4 inline-block bg-indigo-600 text-white px-6 py-2.5 rounded-xl font-bold text-sm">Orqaga qaytish</a>
            </div>
        <?php else: ?>
            <!-- TEST SAVOLLARI FORMASI -->
            <form action="" method="POST" class="p-6 space-y-6">
                <?php $num = 1; foreach($questions as $q): ?>
                    <div class="bg-slate-50 p-4 md:p-5 rounded-2xl border-2 border-slate-100 space-y-3">
                        <p class="text-sm md:text-base font-black text-slate-900">
                            <span class="text-indigo-600"><?= $num ?>.</span> <?= htmlspecialchars($q['question_text']) ?>
                        </p>
                        
                        <!-- Variantlar ro'yxati -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs md:text-sm font-bold">
                            <?php foreach(['A' => $q['option_a'], 'B' => $q['option_b'], 'C' => $q['option_c'], 'D' => $q['option_d']] as $key => $text): ?>
                                <label class="flex items-center gap-3 p-3 bg-white border-2 border-slate-200 rounded-xl cursor-pointer hover:border-indigo-400 transition">
                                    <input type="radio" name="answers[<?= $q['id'] ?>]" value="<?= $key ?>" required class="w-4 h-4 text-indigo-600 focus:ring-0">
                                    <span><strong class="text-indigo-600"><?= $key ?>)</strong> <?= htmlspecialchars($text) ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php $num++; endforeach; ?>

                <button type="submit" name="submit_quiz" onclick="return confirm('Testni yakunlab, javoblarni ustozga tekshirish uchun yuborasizmi?')" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white p-4 rounded-2xl font-black text-lg shadow-lg tracking-wide transition transform active:scale-95">
                    Testni Yakunlash & Ballni Hisoblash 🏁
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>