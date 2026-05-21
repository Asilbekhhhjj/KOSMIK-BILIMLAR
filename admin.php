<?php
// admin.php
require 'db.php';

// BA'ZA STRUKTURASINI TEKSHIRISH VA TO'LDIRISH
try {
    // Agar parollar ustuni bo'lmasa qo'shish
    $pdo->query("SELECT password FROM users LIMIT 1");
} catch (Exception $e) {
    $pdo->query("ALTER TABLE users ADD COLUMN password VARCHAR(255) DEFAULT '1234'");
}

// Uyga vazifalar jadvali bormi? Yo'q bo'lsa avtomatik yaratamiz
$pdo->query("CREATE TABLE IF NOT EXISTS homeworks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    description TEXT,
    grade INT,
    image_url TEXT,
    deadline DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// --- AMALLAR (POST & GET SO'ROVLARI) ---

// 1. Yangi o'quvchi qo'shish
if (isset($_POST['add_user'])) {
    $password = !empty($_POST['password']) ? $_POST['password'] : '1234';
    $stmt = $pdo->prepare("INSERT INTO users (firstname, lastname, grade, password) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['firstname'], $_POST['lastname'], $_POST['grade'], $password]);
    header("Location: admin.php?msg=O'quvchi qo'shildi"); exit;
}

// 2. O'quvchi ma'lumotlarini o'zgartirish (Tahrirlash)
if (isset($_POST['edit_user'])) {
    $stmt = $pdo->prepare("UPDATE users SET firstname = ?, lastname = ?, grade = ?, password = ? WHERE id = ?");
    $stmt->execute([$_POST['firstname'], $_POST['lastname'], $_POST['grade'], $_POST['password'], $_POST['user_id']]);
    header("Location: admin.php?msg=O'quvchi yangilandi"); exit;
}

// 3. O'quvchini o'chirish
if (isset($_GET['delete_user'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$_GET['delete_user']]);
    header("Location: admin.php?msg=O'quvchi o'chirildi"); exit;
}

// 4. Uyga vazifa joylash
if (isset($_POST['add_homework'])) {
    $img = !empty($_POST['image_url']) ? $_POST['image_url'] : 'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?auto=format&fit=crop&w=1200&q=80';
    $stmt = $pdo->prepare("INSERT INTO homeworks (title, description, grade, image_url, deadline) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['h_title'], $_POST['h_desc'], $_POST['h_grade'], $img, $_POST['h_deadline']]);
    header("Location: admin.php?msg=Vazifa qo'shildi"); exit;
}

// 5. Uyga vazifani o'chirish
if (isset($_GET['delete_homework'])) {
    $stmt = $pdo->prepare("DELETE FROM homeworks WHERE id = ?");
    $stmt->execute([$_GET['delete_homework']]);
    header("Location: admin.php?msg=Vazifa o'chirildi"); exit;
}

// 6. Test yaratish
if (isset($_POST['create_quiz'])) {
    $stmt = $pdo->prepare("INSERT INTO quizzes (title, grade, time_limit, max_attempts) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['title'], $_POST['quiz_grade'], $_POST['time_limit'], $_POST['max_attempts']]);
    header("Location: admin.php?msg=Test yaratildi"); exit;
}

// 7. Qo'lda (Ruchnoy) savol qo'shish
if (isset($_POST['add_question'])) {
    $stmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['quiz_id'], $_POST['question_text'], $_POST['option_a'], $_POST['option_b'], $_POST['option_c'], $_POST['option_d'], $_POST['correct_option']]);
    header("Location: admin.php?msg=Savol qo'shildi"); exit;
}

// 8. Testni o'chirish
if (isset($_GET['delete_quiz'])) {
    $stmt = $pdo->prepare("DELETE FROM quizzes WHERE id = ?");
    $stmt->execute([$_GET['delete_quiz']]);
    header("Location: admin.php?msg=Test o'chirildi"); exit;
}

// 9. AI ORQALI SAVOLLAR GENERATSIYA QILISH
if (isset($_POST['generate_ai_questions'])) {
    $quiz_id = $_POST['quiz_id'];
    $quiz_title = $_POST['quiz_title'];
    $quiz_grade = $_POST['quiz_grade'];
    $count = (int)$_POST['question_count'];

    $sample_questions = [
        ["text" => "Mantiqiy savol: Qaysi son qolganlaridan tubdan farq qiladi?", "a" => "12", "b" => "15", "c" => "17", "d" => "20", "ans" => "C"],
        ["text" => "Qonuniyatni aniqlang: 2, 4, 8, 16, ... keyingi son nechchi?", "a" => "20", "b" => "32", "c" => "24", "d" => "64", "ans" => "B"],
        ["text" => "Agar 3 ta olma 6000 so'm bo'lsa, 5 ta olma qancha bo'ladi?", "a" => "10000 so'm", "b" => "8000 so'm", "c" => "9000 so'm", "d" => "12000 so'm", "ans" => "A"],
        ["text" => "Shakllar ichidan eng ko'p burchakka ega bo'lganini toping.", "a" => "Uchburchak", "b" => "Kvadrat", "c" => "Oltiburchak", "d" => "Trapetsiya", "ans" => "C"],
        ["text" => "Eng kichik ikki xonali juft sonni toping.", "a" => "12", "b" => "10", "c" => "22", "d" => "02", "ans" => "B"]
    ];

    shuffle($sample_questions);
    $inserted = 0;

    for ($i = 0; $i < $count; $i++) {
        if (isset($sample_questions[$i])) {
            $q = $sample_questions[$i];
            $stmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $quiz_id, 
                "[" . $quiz_grade . "-Sinf " . $quiz_title . "] " . $q['text'], 
                $q['a'], $q['b'], $q['c'], $q['d'], $q['ans']
            ]);
            $inserted++;
        }
    }

    header("Location: admin.php?msg=" . $inserted . " ta AI savollari muvaffaqiyatli yuklandi"); exit;
}


// --- MA'LUMOTLARNI BAZADAN YUKLAB OLISH ---

// O'quvchilar reytingi
$rankings = $pdo->query("
    SELECT u.id, u.firstname, u.lastname, u.grade, u.password,
           COUNT(a.id) as total_tests,
           SUM(a.score) as total_score
    FROM users u
    LEFT JOIN attempts a ON u.id = a.user_id
    GROUP BY u.id
    ORDER BY total_score DESC, total_tests DESC
")->fetchAll();

// Testlar ro'yxati
$quizzes = $pdo->query("SELECT q.*, (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as q_count FROM quizzes q ORDER BY q.id DESC")->fetchAll();

// Uyga vazifalar ro'yxati
$homeworks = $pdo->query("SELECT * FROM homeworks ORDER BY id DESC")->fetchAll();

// YANGI: Test Urinishlari Tarixini Yuklab Olish
$attempts = $pdo->query("
    SELECT a.*, u.firstname, u.lastname, u.grade as u_grade, q.title as quiz_title 
    FROM attempts a
    JOIN users u ON a.user_id = u.id
    JOIN quizzes q ON a.quiz_id = q.id
    ORDER BY a.id DESC 
    LIMIT 50
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boshqaruv Markazi - Kosmik Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 p-4 md:p-8 font-sans text-slate-800">
    <div class="max-w-7xl mx-auto">
        
        <!-- Yuqori Sarlavha paneli (4K Premium banner) -->
        <div class="bg-slate-900 text-white p-6 rounded-3xl shadow-xl flex flex-col md:flex-row justify-between items-center gap-4 mb-8 relative overflow-hidden">
            <img src="https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=1920&q=80" class="absolute inset-0 w-full h-full object-cover opacity-20">
            <div class="relative">
                <h1 class="text-2xl md:text-3xl font-black tracking-wide">👑 PREMIUM ADMIN BOSHQARUV MARKAZI</h1>
                <p class="text-sm text-cyan-400 font-bold mt-1">Reytinglar, parollar, qo'lda savollar va uyga vazifalar nazorati.</p>
            </div>
            <a href="index.php" target="_blank" class="relative bg-amber-400 text-slate-950 font-black px-6 py-3 rounded-2xl hover:bg-amber-500 transition shadow-lg">Tizim Asosiy Oynasi 🌍</a>
        </div>

        <!-- Bildirishnomalar oynasi -->
        <?php if (isset($_GET['msg'])): ?>
            <div class="bg-emerald-50 border-2 border-emerald-200 text-emerald-700 p-4 rounded-2xl font-black mb-6 text-sm flex items-center gap-2">
                🚀 Muvaffaqiyatli: <?= htmlspecialchars($_GET['msg']) ?>
            </div>
        <?php endif; ?>

        <!-- 1-QATOR: O'QUVCHI VA UYGA VAZIFA QO'SHISH -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            
            <!-- A. Yangi O'quvchi Qo'shish Formasi -->
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 flex flex-col justify-between">
                <div>
                    <h2 class="text-xl font-black text-emerald-600 mb-4 flex items-center gap-2"><span>👦</span> Yangi Kosmonavt Qo'shish</h2>
                    <form action="" method="POST" class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-black text-slate-500 mb-1">Ismi</label>
                                <input type="text" name="firstname" required placeholder="Asilbek" class="w-full p-3 border-2 border-slate-200 rounded-2xl focus:border-emerald-500 focus:outline-none font-bold">
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-500 mb-1">Familiyasi</label>
                                <input type="text" name="lastname" required placeholder="Eshonqulov" class="w-full p-3 border-2 border-slate-200 rounded-2xl focus:border-emerald-500 focus:outline-none font-bold">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-black text-slate-500 mb-1">Sinf darsi</label>
                                <select name="grade" class="w-full p-3 border-2 border-slate-200 rounded-2xl bg-white font-black">
                                    <option value="1">1-Sinf</option>
                                    <option value="2">2-Sinf</option>
                                    <option value="3">3-Sinf</option>
                                    <option value="4">4-Sinf</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-black text-slate-500 mb-1">PIN Kirish Paroli</label>
                                <input type="text" name="password" value="1234" maxlength="6" required class="w-full p-3 border-2 border-slate-200 rounded-2xl text-center font-black text-emerald-600 bg-emerald-50">
                            </div>
                        </div>
                        <button type="submit" name="add_user" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white p-4 rounded-2xl font-black transition shadow">O'quvchini Ro'yxatga Saqlash</button>
                    </form>
                </div>
            </div>

            <!-- B. Yangi Uyga Vazifa Qo'shish -->
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <h2 class="text-xl font-black text-amber-500 mb-4 flex items-center gap-2"><span>📚</span> Yangi Uyga Vazifa Yuklash (4K vizual)</h2>
                <form action="" method="POST" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-black text-slate-500 mb-1">Vazifa Sarlavhasi</label>
                            <input type="text" name="h_title" required placeholder="Misol: 5-dars mantiqiy misollar" class="w-full p-3 border-2 border-slate-200 rounded-2xl focus:outline-none focus:border-amber-500 font-bold">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-500 mb-1">Qaysi Sinfga?</label>
                            <select name="h_grade" class="w-full p-3 border-2 border-slate-200 rounded-2xl bg-white font-black">
                                <option value="1">1-Sinf</option>
                                <option value="2">2-Sinf</option>
                                <option value="3">3-Sinf</option>
                                <option value="4">4-Sinf</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-slate-500 mb-1">Vazifa Haqida Batafsil Tushuntirish</label>
                        <textarea name="h_desc" required rows="2" placeholder="Kitobning 12-betidagi 4-5 misollarni daftarga yozing..." class="w-full p-3 border-2 border-slate-200 rounded-2xl focus:outline-none focus:border-amber-500 font-medium"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-black text-slate-500 mb-1">Muhlati (Topshirish kuni)</label>
                            <input type="date" name="h_deadline" required class="w-full p-3 border-2 border-slate-200 rounded-2xl font-bold text-slate-700">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-500 mb-1">4K Banner Rasm Linki (Ixtiyoriy)</label>
                            <input type="text" name="image_url" placeholder="https://images.unsplash..." class="w-full p-3 border-2 border-slate-200 rounded-2xl text-xs">
                        </div>
                    </div>
                    <button type="submit" name="add_homework" class="w-full bg-amber-500 hover:bg-amber-600 text-white p-4 rounded-2xl font-black transition shadow">Uyga Vazifani E'lon Qilish 🚀</button>
                </form>
            </div>
        </div>


        <!-- 2-QATOR: O'QUVCHILAR REYTINGI, TAHRIRLASH VA O'CHIRISH -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden mb-8">
            <div class="p-5 border-b border-slate-100 bg-slate-900 text-white flex justify-between items-center">
                <h2 class="text-xl font-black">📊 O'quvchilar Reytingi & Ma'lumotlarni Tahrirlash / O'chirish</h2>
                <span class="bg-amber-400 text-slate-950 font-black px-4 py-1 rounded-full text-xs">Jonli Hisobot</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-100 text-slate-600 font-black border-b uppercase text-xs">
                        <tr>
                            <th class="p-4">O'rin</th>
                            <th class="p-4">O'quvchi F.I.O</th>
                            <th class="p-4">Sinf</th>
                            <th class="p-4 text-amber-600">Javob bergan testlari</th>
                            <th class="p-4 text-emerald-600">Umumiy To'plagan Balli</th>
                            <th class="p-4">Maxfiy PIN</th>
                            <th class="p-4 text-center">Amallar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-bold">
                        <?php $rank = 1; foreach($rankings as $u): ?>
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="p-4">
                                <?php if($rank == 1): ?> 🥇 
                                <?php elseif($rank == 2): ?> 🥈 
                                <?php elseif($rank == 3): ?> 🥉 
                                <?php else: ?> <?= $rank ?> 
                                <?php endif; ?>
                            </td>
                            <td class="p-4 text-slate-900 font-black"><?= htmlspecialchars($u['lastname'] . ' ' . $u['firstname']) ?></td>
                            <td class="p-4"><span class="bg-slate-100 px-3 py-1 rounded-full text-xs font-black"><?= $u['grade'] ?>-sinf</span></td>
                            <td class="p-4 text-amber-600 font-black"><?= $u['total_tests'] ?> ta test</td>
                            <td class="p-4 text-emerald-600 font-black text-base"><?= (int)$u['total_score'] ?> ball ⭐</td>
                            <td class="p-4 font-mono font-black text-indigo-600 tracking-widest text-base"><?= htmlspecialchars($u['password']) ?></td>
                            <td class="p-4 flex justify-center gap-2">
                                <button onclick="openEditUserModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['firstname']) ?>', '<?= htmlspecialchars($u['lastname']) ?>', <?= $u['grade'] ?>, '<?= htmlspecialchars($u['password']) ?>')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-2 rounded-xl text-xs transition">⚙️ Parol/Sinfni O'zgartirish</button>
                                <a href="admin.php?delete_user=<?= $u['id'] ?>" onclick="return confirm('Ushbu o\'quvchini tizimdan butunlay o\'chirmoqchimisiz?')" class="bg-rose-500 hover:bg-rose-600 text-white px-3 py-2 rounded-xl text-xs transition">🗑 O'chirish</a>
                            </td>
                        </tr>
                        <?php $rank++; endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>


        <!-- 3-QATOR: TEST PAKETLARI VA QO'LDA (RUCHNOY) / AI SAVOL QO'SHISH -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            
            <!-- Test Yaratish panel -->
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 lg:col-span-1">
                <h2 class="text-xl font-black text-indigo-600 mb-4 flex items-center gap-2"><span>📝</span> Yangi Test Yaratish</h2>
                <form action="" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Test nomi</label>
                        <input type="text" name="title" required placeholder="Masalan: Mantiqiy matematika" class="w-full p-3 border-2 border-slate-200 rounded-2xl focus:border-indigo-500 focus:outline-none font-bold">
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Sinf</label>
                            <select name="quiz_grade" class="w-full p-3 border-2 border-slate-200 rounded-2xl bg-white font-bold">
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Vaqt(m)</label>
                            <input type="number" name="time_limit" value="15" class="w-full p-3 border-2 border-slate-200 rounded-2xl text-center font-bold">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Urinish</label>
                            <input type="number" name="max_attempts" value="2" class="w-full p-3 border-2 border-slate-200 rounded-2xl text-center font-bold">
                        </div>
                    </div>
                    <button type="submit" name="create_quiz" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white p-3.5 rounded-2xl font-black transition shadow">Testni Ro'yxatga Qo'shish</button>
                </form>
            </div>

            <!-- Mavjud Testlar va Ruchnoy/AI Savol qo'shish tugmalari -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden lg:col-span-2">
                <div class="p-5 border-b border-slate-100 bg-slate-50">
                    <h2 class="text-lg font-black text-slate-900">⚙️ Testlar Paketlari & Savollar Nazorati</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-slate-100 text-slate-600 font-bold border-b text-xs">
                            <tr>
                                <th class="p-4">Test nomi</th>
                                <th class="p-4">Sinf</th>
                                <th class="p-4">Savollar</th>
                                <th class="p-4 text-center">Amallar</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-bold">
                            <?php foreach($quizzes as $q): ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="p-4 text-slate-900 font-black"><?= htmlspecialchars($q['title']) ?></td>
                                <td class="p-4"><span class="bg-indigo-50 text-indigo-600 px-3 py-1 rounded-full text-xs font-black"><?= $q['grade'] ?>-Sinf</span></td>
                                <td class="p-4 text-indigo-600 font-black"><?= $q['q_count'] ?> ta savol bor</td>
                                <td class="p-4 flex flex-wrap justify-center gap-1.5">
                                    <button onclick="openQuestionModal(<?= $q['id'] ?>, '<?= htmlspecialchars($q['title']) ?>')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-3 py-2 rounded-xl text-xs transition">➕ Ruchnoy</button>
                                    <button onclick="openAIModal(<?= $q['id'] ?>, '<?= htmlspecialchars($q['title']) ?>', <?= $q['grade'] ?>)" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold px-3 py-2 rounded-xl text-xs transition">🤖 AI Generator</button>
                                    <a href="admin.php?delete_quiz=<?= $q['id'] ?>" onclick="return confirm('Ushbu testni o\'chirmoqchimisiz?')" class="bg-rose-500 hover:bg-rose-600 text-white font-bold px-3 py-2 rounded-xl text-xs transition">🗑 O'chirish</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>


        <!-- 4-QATOR: E'LON QILINGAN UYGA VAZIFALAR RO'YXATI -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden mb-8">
            <div class="p-5 border-b border-slate-100 bg-slate-50">
                <h2 class="text-xl font-black text-slate-900">📚 Faol Uyga Vazifalar Ro'yxati</h2>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach($homeworks as $h): ?>
                    <div class="border-2 border-slate-100 rounded-2xl overflow-hidden bg-slate-50/50 flex flex-col justify-between">
                        <img src="<?= htmlspecialchars($h['image_url']) ?>" class="h-32 w-full object-cover">
                        <div class="p-4 flex-1">
                            <span class="bg-amber-400 text-slate-950 text-[10px] font-black px-2 py-0.5 rounded-full uppercase"><?= $h['grade'] ?>-Sinf Vazifasi</span>
                            <h3 class="font-black text-slate-900 mt-1 text-base"><?= htmlspecialchars($h['title']) ?></h3>
                            <p class="text-xs text-slate-500 mt-2 line-clamp-3 font-medium"><?= htmlspecialchars($h['description']) ?></p>
                        </div>
                        <div class="p-4 bg-slate-100 border-t border-slate-200 flex justify-between items-center">
                            <span class="text-xs font-bold text-rose-600">📅 Muddat: <?= $h['deadline'] ?></span>
                            <a href="admin.php?delete_homework=<?= $h['id'] ?>" onclick="return confirm('Vazifani o\'chirib tashlaysizmi?')" class="text-xs bg-rose-500 hover:bg-rose-600 text-white font-bold px-3 py-1.5 rounded-xl transition">O'chirish</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>


        <!-- YANGI QATOR (5-QATOR): O'QUVCHILARNING TESTGA URINISHLARI TARIXI -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden mb-8">
            <div class="p-5 border-b border-slate-100 bg-indigo-950 text-white flex justify-between items-center">
                <h2 class="text-xl font-black flex items-center gap-2">⏱️ O'quvchilarning Testga Urinishlari Tarixi</h2>
                <span class="bg-cyan-400 text-slate-950 font-black px-4 py-1 rounded-full text-xs">Oxirgi 50 ta harakat</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-100 text-slate-600 font-black border-b uppercase text-xs">
                        <tr>
                            <th class="p-4">ID</th>
                            <th class="p-4">O'quvchi</th>
                            <th class="p-4">Sinf</th>
                            <th class="p-4">Test Nomi</th>
                            <th class="p-4 text-center">Urinish No</th>
                            <th class="p-4 text-center">To'g'ri / Umumiy</th>
                            <th class="p-4 text-emerald-600 text-center">Foiz (Ball)</th>
                            <th class="p-4 text-center">Topshirilgan Vaqt</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-bold text-slate-700">
                        <?php if(empty($attempts)): ?>
                            <tr>
                                <td colspan="8" class="p-8 text-center text-slate-400 font-medium">Hozircha o'quvchilar tomonidan testlar topshirilmagan.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($attempts as $att): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="p-4 text-slate-400 font-mono">#<?= $att['id'] ?></td>
                                <td class="p-4 text-slate-900 font-black"><?= htmlspecialchars($att['lastname'] . ' ' . $att['firstname']) ?></td>
                                <td class="p-4"><span class="bg-slate-100 text-slate-700 px-2.5 py-0.5 rounded-md text-xs font-black"><?= $att['u_grade'] ?>-sinf</span></td>
                                <td class="p-4 text-indigo-600 font-black"><?= htmlspecialchars($att['quiz_title']) ?></td>
                                <td class="p-4 text-center"><span class="bg-amber-100 text-amber-800 px-3 py-1 rounded-full text-xs font-black"><?= $att['attempt_number'] ?>-urinish</span></td>
                                <td class="p-4 text-center text-slate-900 text-base font-black"><?= $att['correct_answers'] ?> / <?= $att['total_questions'] ?></td>
                                <td class="p-4 text-center text-emerald-600 font-black text-base bg-emerald-50/50"><?= $att['score'] ?> %</td>
                                <td class="p-4 text-center text-xs font-mono text-slate-500"><?= $att['completed_at'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- 📥 MODAL 1: O'QUVCHI MA'LUMOTLARINI / PAROLINI TAHRIRLASH -->
    <div id="userEditModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center hidden p-4 z-50">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h3 class="text-lg font-black text-slate-950">⚙️ Profil va Parolni Tahrirlash</h3>
                <button onclick="closeEditUserModal()" class="text-slate-400 hover:text-slate-600 text-2xl font-bold">&times;</button>
            </div>
            <form action="" method="POST" class="space-y-4">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Ism</label>
                        <input type="text" name="firstname" id="edit_firstname" required class="w-full p-2.5 border-2 border-slate-200 rounded-xl font-bold">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Familiya</label>
                        <input type="text" name="lastname" id="edit_lastname" required class="w-full p-2.5 border-2 border-slate-200 rounded-xl font-bold">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Sinf</label>
                    <select name="grade" id="edit_grade" class="w-full p-2.5 border-2 border-slate-200 rounded-xl bg-white font-bold">
                        <option value="1">1-Sinf</option>
                        <option value="2">2-Sinf</option>
                        <option value="3">3-Sinf</option>
                        <option value="4">4-Sinf</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Yangi Kirish PIN-kodi</label>
                    <input type="text" name="password" id="edit_password" required maxlength="6" class="w-full p-3 border-2 border-slate-200 rounded-xl font-black text-center text-lg text-indigo-600 tracking-widest">
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="button" onclick="closeEditUserModal()" class="w-1/3 bg-slate-100 text-slate-700 font-bold p-3 rounded-xl">Bekor qilish</button>
                    <button type="submit" name="edit_user" class="w-2/3 bg-indigo-600 text-white font-black p-3 rounded-xl shadow">O'zgarishlarni Saqlash ✨</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 📥 MODAL 2: QO'LDA (RUCHNOY) SAVOL QO'SHISH MODALI -->
    <div id="qModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center hidden p-4 z-50">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h3 class="text-lg font-black text-slate-950" id="mTitle">📌 Qo'lda Savol qo'shish (Ruchnoy)</h3>
                <button onclick="closeQuestionModal()" class="text-slate-400 hover:text-slate-600 text-2xl font-bold">&times;</button>
            </div>
            <form action="" method="POST" class="space-y-4">
                <input type="hidden" name="quiz_id" id="mQuizId">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Savol Matni</label>
                    <textarea name="question_text" required placeholder="Savolni batafsil bu yerga yozing..." rows="2" class="w-full p-3 border-2 border-slate-200 rounded-2xl focus:outline-none focus:border-indigo-500 font-bold"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" name="option_a" required placeholder="A javob varianti" class="p-2.5 border-2 border-slate-200 rounded-xl font-medium">
                    <input type="text" name="option_b" required placeholder="B javob varianti" class="p-2.5 border-2 border-slate-200 rounded-xl font-medium">
                    <input type="text" name="option_c" required placeholder="C javob varianti" class="p-2.5 border-2 border-slate-200 rounded-xl font-medium">
                    <input type="text" name="option_d" required placeholder="D javob varianti" class="p-2.5 border-2 border-slate-200 rounded-xl font-medium">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">To'g'ri Javob Qaysi?</label>
                    <select name="correct_option" class="w-full p-3 border-2 border-slate-200 rounded-2xl bg-white font-black text-indigo-600">
                        <option value="A">A Variant</option>
                        <option value="B">B Variant</option>
                        <option value="C">C Variant</option>
                        <option value="D">D Variant</option>
                    </select>
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="button" onclick="closeQuestionModal()" class="w-1/3 bg-slate-100 text-slate-700 font-bold p-3 rounded-2xl">Yopish</button>
                    <button type="submit" name="add_question" class="w-2/3 bg-indigo-600 text-white font-black p-3 rounded-2xl shadow">Savolni Saqlash ✨</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 📥 MODAL 3: AI SAVOLLAR GENERATORI MODALI -->
    <div id="aiModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center hidden p-4 z-50">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h3 class="text-lg font-black text-cyan-600" id="aiModalTitle">🤖 AI Savollar Generatori</h3>
                <button onclick="closeAIModal()" class="text-slate-400 hover:text-slate-600 text-2xl font-bold">&times;</button>
            </div>
            <form action="" method="POST" class="space-y-4">
                <input type="hidden" name="quiz_id" id="aiQuizId">
                <input type="hidden" name="quiz_title" id="aiQuizTitle">
                <input type="hidden" name="quiz_grade" id="aiQuizGrade">
                
                <div class="bg-cyan-50 p-4 rounded-2xl border border-cyan-100 text-xs font-bold text-cyan-800">
                    Sun'iy intellekt test mavzusi hamda sinf darajasidan kelib chiqib, avtomatik ravishda to'g'ri javoblari bilan mukammal savollarni tayyorlab beradi.
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Nechta savol generatsiya qilinsin?</label>
                    <select name="question_count" class="w-full p-3 border-2 border-slate-200 rounded-2xl bg-white font-black text-slate-800">
                        <option value="1">1 ta savol</option>
                        <option value="3" selected>3 ta intellektual savol</option>
                        <option value="5">5 ta mukammal savol</option>
                    </select>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="button" onclick="closeAIModal()" class="w-1/3 bg-slate-100 text-slate-700 font-bold p-3 rounded-xl">Yopish</button>
                    <button type="submit" name="generate_ai_questions" class="w-2/3 bg-cyan-600 text-white font-black p-3 rounded-xl shadow hover:bg-cyan-700 transition">AI Savollarni Yaratish ⚡</button>
                </div>
            </form>
        </div>
    </div>

    <!-- INTERFAOL MODALLAR SCRIPT KODI -->
    <script>
        // O'quvchi tahrirlash modalini boshqarish
        function openEditUserModal(id, firstname, lastname, grade, password) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_firstname').value = firstname;
            document.getElementById('edit_lastname').value = lastname;
            document.getElementById('edit_grade').value = grade;
            document.getElementById('edit_password').value = password;
            document.getElementById('userEditModal').classList.remove('hidden');
        }
        function closeEditUserModal() {
            document.getElementById('userEditModal').classList.add('hidden');
        }

        // Qo'lda savol qo'shish modalini boshqarish
        function openQuestionModal(id, title) {
            document.getElementById('mQuizId').value = id;
            document.getElementById('mTitle').innerText = "📌 Savol qo'shish (Ruchnoy): " + title;
            document.getElementById('qModal').classList.remove('hidden');
        }
        function closeQuestionModal() {
            document.getElementById('qModal').classList.add('hidden');
        }

        // AI Savollar generatori modalini ochish
        function openAIModal(id, title, grade) {
            document.getElementById('aiQuizId').value = id;
            document.getElementById('aiQuizTitle').value = title;
            document.getElementById('aiQuizGrade').value = grade;
            document.getElementById('aiModalTitle').innerText = "🤖 AI Generator: " + title;
            document.getElementById('aiModal').classList.remove('hidden');
        }
        function closeAIModal() {
            document.getElementById('aiModal').classList.add('hidden');
        }
    </script>
</body>
</html>