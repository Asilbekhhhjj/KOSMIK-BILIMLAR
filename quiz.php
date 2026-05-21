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

// Barcha savollarni yuklash
$q_stmt = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ? ORDER BY id ASC");
$q_stmt->execute([$quiz_id]);
$questions = $q_stmt->fetchAll();

// Fondan kelgan AJAX so'rovni qayta ishlash (Natijani saqlash uchun)
if (isset($_POST['ajax_submit'])) {
    $correct_answers = 0;
    $total_questions = count($questions);

    if ($total_questions > 0) {
        foreach ($questions as $q) {
            $selected = $_POST['answers'][$q['id']] ?? '';
            if ($selected === $q['correct_option']) {
                $correct_answers++;
            }
        }
        $final_score = round(($correct_answers / $total_questions) * 100);
    } else {
        $final_score = 0;
    }

    $attempt_stmt = $pdo->prepare("INSERT INTO attempts (user_id, quiz_id, score) VALUES (?, ?, ?)");
    $attempt_stmt->execute([$user_id, $quiz_id, $final_score]);

    // JavaScript ga natijani JSON qilib qaytaramiz
    echo json_encode(['status' => 'success', 'score' => $final_score]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($quiz['title']) ?> - LUVIONX AI Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .correct-anim { animation: pulseGreen 1s infinite alternate; }
        @keyframes pulseGreen { 0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); } 100% { box-shadow: 0 0 15px 5px rgba(16, 185, 129, 0.2); } }
    </style>
</head>
<body class="bg-slate-900 min-h-screen p-4 md:p-8 flex items-center justify-center text-slate-100">

    <div class="w-full max-w-3xl bg-slate-800/50 backdrop-blur-xl rounded-3xl shadow-2xl border border-slate-700/50 overflow-hidden">
        
        <div class="bg-gradient-to-r from-indigo-600 to-violet-600 p-6 text-center relative border-b border-indigo-500/30">
            <div class="absolute top-3 left-4 bg-black/20 text-xs font-bold px-3 py-1 rounded-full text-indigo-200">
                LUVIONX 7+
            </div>
            <h1 class="text-2xl md:text-3xl font-black tracking-wide text-white drop-shadow-sm mt-2"><?= htmlspecialchars($quiz['title']) ?></h1>
            <div class="flex items-center justify-center gap-4 mt-3">
                <span class="text-xs font-bold uppercase tracking-widest bg-white/10 px-3 py-1 rounded-md text-indigo-100">
                    ⏱ Vaqt: <?= $quiz['time_limit'] ?> daqiqa
                </span>
                <span class="text-xs font-bold uppercase tracking-widest bg-white/10 px-3 py-1 rounded-md text-indigo-100">
                    📝 Savollar: <?= count($questions) ?> ta
                </span>
            </div>
        </div>

        <?php if(empty($questions)): ?>
            <div class="p-12 text-center">
                <div class="text-5xl mb-4 animate-bounce">⚙️</div>
                <p class="text-slate-400 font-semibold text-lg">Ushbu test guruhiga hali savollar yuklanmagan.</p>
                <p class="text-sm text-slate-500 mt-1">Ustoz tez orada savollarni tizimga qo'shadi.</p>
                <a href="dashboard.php" class="mt-6 inline-block bg-indigo-600 hover:bg-indigo-500 text-white px-8 py-3 rounded-xl font-bold text-sm transition shadow-lg shadow-indigo-600/30">Orqaga qaytish</a>
            </div>
        <?php else: ?>

            <div id="result-box" class="hidden p-6 bg-gradient-to-br from-indigo-950 to-slate-900 border-b border-slate-700 text-center space-y-4">
                <div class="inline-flex p-4 bg-emerald-500/10 text-emerald-400 rounded-full text-4xl font-black mb-2">🏁</div>
                <h2 class="text-2xl font-black text-white">Test muvaffaqiyatli yakunlandi!</h2>
                <p class="text-xl font-bold text-slate-300">Sizning natijangiz: <span id="display-score" class="text-emerald-400 text-3xl font-black">0</span> ball</p>
                
                <div class="bg-slate-800/80 rounded-2xl p-4 max-w-md mx-auto border border-slate-700">
                    <p class="text-sm font-semibold text-slate-400">Tog'ri javoblar bilan tanishib chiqing.</p>
                    <p class="text-xs font-bold text-indigo-400 uppercase tracking-wider mt-1">
                        ⏱ <span id="countdown-timer" class="text-base text-amber-400 font-black">15</span> soniyadan keyin chiqiladi...
                    </p>
                    <div class="w-full bg-slate-700 h-2 rounded-full mt-2 overflow-hidden">
                        <div id="timer-bar" class="bg-gradient-to-r from-amber-400 to-orange-500 h-full w-full transition-all duration-1000 linear"></div>
                    </div>
                </div>
            </div>

            <form id="quiz-form" class="p-6 space-y-6">
                <script>
                    const correctAnswers = {
                        <?php foreach($questions as $q): ?>
                            '<?= $q['id'] ?>': '<?= $q['correct_option'] ?>',
                        <?php endforeach; ?>
                    };
                </script>

                <?php $num = 1; foreach($questions as $q): ?>
                    <div id="question-card-<?= $q['id'] ?>" class="bg-slate-800/40 p-5 rounded-2xl border-2 border-slate-700/50 space-y-4 transition-all duration-300">
                        <div class="flex items-start gap-3">
                            <span class="flex items-center justify-center bg-indigo-600/20 text-indigo-400 font-black rounded-lg px-2.5 py-1 text-sm border border-indigo-500/30">
                                <?= $num ?>
                            </span>
                            <p class="text-base font-bold text-slate-100 mt-0.5 leading-relaxed">
                                <?= htmlspecialchars($q['question_text']) ?>
                            </p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm font-semibold">
                            <?php foreach(['A' => $q['option_a'], 'B' => $q['option_b'], 'C' => $q['option_c'], 'D' => $q['option_d']] as $key => $text): ?>
                                <label class="option-label flex items-center gap-3 p-3.5 bg-slate-800 border-2 border-slate-700/60 rounded-xl cursor-pointer hover:border-indigo-500/50 hover:bg-slate-700/40 transition active:scale-[0.99]">
                                    <input type="radio" name="answers[<?= $q['id'] ?>]" value="<?= $key ?>" required 
                                           class="w-4 h-4 text-indigo-500 focus:ring-0 border-slate-600 bg-slate-700 checked:bg-indigo-500 checked:border-indigo-500">
                                    <span class="text-slate-300 flex-1"><strong class="text-indigo-400 mr-1"><?= $key ?>)</strong> <?= htmlspecialchars($text) ?></span>
                                    
                                    <span class="status-icon hidden font-black text-base"></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php $num++; endforeach; ?>

                <button type="submit" id="submit-btn" class="w-full bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white p-4 rounded-2xl font-black text-lg shadow-lg shadow-emerald-950/50 tracking-wide transition transform active:scale-95 flex items-center justify-center gap-2">
                    <span>Testni Yakunlash & Ballni Hisoblash 🏁</span>
                </button>
            </form>
        <?php endif; ?>
    </div>

    <script>
    // Variant tanlanganda uni vizual ravishda chiroyli ajratib ko'rsatish
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', function() {
            // Bir xil nomli (bitta savol ichidagi) barcha variantlardan klasni olib tashlash
            const parentCard = this.closest('.space-y-4');
            parentCard.querySelectorAll('.option-label').forEach(label => {
                label.classList.remove('border-indigo-500', 'bg-indigo-600/10');
                label.classList.add('border-slate-700/60', 'bg-slate-800');
            });
            // Tanlangan variantga yangi dizayn berish
            if (this.checked) {
                const label = this.closest('.option-label');
                label.classList.remove('border-slate-700/60', 'bg-slate-800');
                label.classList.add('border-indigo-500', 'bg-indigo-600/10');
            }
        });
    });

    // Formani topshirish (AJAX orqali)
    const form = document.getElementById('quiz-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if(!confirm('Testni yakunlab, javoblarni tekshirishga rozimisiz?')) return;

            // Tugmani o'chirish (Double-click oldini olish)
            const submitBtn = document.getElementById('submit-btn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = "Tekshirilmoqda...";

            const formData = new FormData(form);
            formData.append('ajax_submit', '1');

            // Ma'lumotlarni bazaga yuborish
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.status === 'success') {
                    // 1. Natija oynasini ochish va ballni yozish
                    document.getElementById('result-box').classList.remove('hidden');
                    document.getElementById('display-score').innerText = data.score;
                    submitBtn.classList.add('hidden'); // Tugmani butunlay yashirish

                    // 2. To'g'ri va noto'g'ri javoblarni ekranda ranglar bilan ko'rsatish
                    for (const [qId, correctAnswer] of Object.entries(correctAnswers)) {
                        const card = document.getElementById(`question-card-${qId}`);
                        const selectedRadio = form.querySelector(`input[name="answers[${qId}]"]:checked`);
                        const selectedValue = selectedRadio ? selectedRadio.value : null;

                        // Barcha radio inputlarni muzlatish (bosib bo'lmaydigan qilish)
                        card.querySelectorAll('input[type="radio"]').forEach(r => r.disabled = true);

                        card.querySelectorAll('.option-label').forEach(label => {
                            const radio = label.querySelector('input[type="radio"]');
                            const iconSpan = label.querySelector('.status-icon');
                            iconSpan.classList.remove('hidden');

                            // Agar bu to'g'ri variant bo'lsa (Yashil rang berish)
                            if (radio.value === correctAnswer) {
                                label.className = "option-label flex items-center gap-3 p-3.5 bg-emerald-950/40 border-2 border-emerald-500 rounded-xl correct-anim text-emerald-400";
                                iconSpan.innerHTML = "✅";
                            } 
                            // Agar o'quvchi xato javobni belgilagan bo'lsa (Uni qizil rang qilish)
                            else if (selectedValue && radio.value === selectedValue && selectedValue !== correctAnswer) {
                                label.className = "option-label flex items-center gap-3 p-3.5 bg-rose-950/40 border-2 border-rose-500/80 rounded-xl text-rose-400";
                                iconSpan.innerHTML = "❌";
                            }
                        });
                    }

                    // Sahifani tepaga silliq ko'chirish (natija ko'rinishi uchun)
                    window.scrollTo({ top: 0, behavior: 'smooth' });

                    // 3. 15 soniyalik taymerni boshlash
                    let timeLeft = 15;
                    const countdownElement = document.getElementById('countdown-timer');
                    const timerBar = document.getElementById('timer-bar');

                    const interval = setInterval(() => {
                        timeLeft--;
                        countdownElement.innerText = timeLeft;
                        
                        // Progress bar kengligini kamaytirish
                        let percent = (timeLeft / 15) * 100;
                        timerBar.style.width = percent + '%';

                        if(timeLeft <= 0) {
                            clearInterval(interval);
                            // Dashboardga yo'naltirish
                            window.location.href = "dashboard.php?msg=Test yakunlandi. Natija bazaga saqlandi!";
                        }
                    }, 1000);
                }
            })
            .catch(error => {
                console.error('Xatolik:', error);
                alert('Tizimda xatolik yuz berdi. Qayta urinib ko\'ring!');
                submitBtn.disabled = false;
                submitBtn.innerHTML = "Qayta urinish 🏁";
            });
        });
    }
    </script>
</body>
</html>
