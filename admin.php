<?php
// admin.php
require 'db.php';

// BA'ZA STRUKTURASINI TEKSHIRISH VA TO'LDIRISH
try {
    $pdo->query("SELECT password FROM users LIMIT 1");
} catch (Exception $e) {
    $pdo->query("ALTER TABLE users ADD COLUMN password VARCHAR(255) DEFAULT '1234'");
}

$pdo->query("CREATE TABLE IF NOT EXISTS homeworks (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255),
    description TEXT,
    grade INT,
    image_url TEXT,
    deadline DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// --- AJAX ORQALI SAHIFANI YANGILAMASDAN SAVOL QO'SHISH ---
if (isset($_POST['ajax_add_question'])) {
    header('Content-Type: application/json');
    try {
        $stmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            (int)$_POST['quiz_id'], 
            $_POST['question_text'], 
            $_POST['option_a'], 
            $_POST['option_b'], 
            $_POST['option_c'], 
            $_POST['option_d'], 
            $_POST['correct_option']
        ]);

        // Ushbu testda hozir jami nechta savol bo'lganini qaytaramiz
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE quiz_id = ?");
        $stmtCount->execute([(int)$_POST['quiz_id']]);
        $currentCount = $stmtCount->fetchColumn();

        echo json_encode(['status' => 'success', 'message' => 'Savol saqlandi!', 'current_count' => $currentCount]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// 1. Yangi o'quvchi qo'shish
if (isset($_POST['add_user'])) {
    $password = !empty($_POST['password']) ? $_POST['password'] : '1234';
    $firstname = !empty($_POST['firstname']) ? $_POST['firstname'] : 'Ismsiz';
    $lastname = !empty($_POST['lastname']) ? $_POST['lastname'] : 'Familiyasiz';
    $grade = (int)$_POST['grade'];
    
    $stmt = $pdo->prepare("INSERT INTO users (firstname, lastname, grade, password) VALUES (?, ?, ?, ?)");
    $stmt->execute([$firstname, $lastname, $grade, $password]);
    header("Location: admin.php?msg=O'quvchi qo'shildi"); exit;
}

// 2. O'quvchi ma'lumotlarini o'zgartirish
if (isset($_POST['edit_user'])) {
    $stmt = $pdo->prepare("UPDATE users SET firstname = ?, lastname = ?, grade = ?, password = ? WHERE id = ?");
    $stmt->execute([$_POST['firstname'], $_POST['lastname'], (int)$_POST['grade'], $_POST['password'], (int)$_POST['user_id']]);
    header("Location: admin.php?msg=O'quvchi yangilandi"); exit;
}

// 3. O'quvchini o'chirish
if (isset($_GET['delete_user'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([(int)$_GET['delete_user']]);
    header("Location: admin.php?msg=O'quvchi o'chirildi"); exit;
}

// 4. Uyga vazifa joylash
if (isset($_POST['add_homework'])) {
    $img = !empty($_POST['image_url']) ? $_POST['image_url'] : 'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?auto=format&fit=crop&w=1200&q=80';
    $stmt = $pdo->prepare("INSERT INTO homeworks (title, description, grade, image_url, deadline) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_POST['h_title'], $_POST['h_desc'], (int)$_POST['h_grade'], $img, $_POST['h_deadline']]);
    header("Location: admin.php?msg=Vazifa qo'shildi"); exit;
}

// 5. Uyga vazifani o'chirish
if (isset($_GET['delete_homework'])) {
    $stmt = $pdo->prepare("DELETE FROM homeworks WHERE id = ?");
    $stmt->execute([(int)$_GET['delete_homework']]);
    header("Location: admin.php?msg=Vazifa o'chirildi"); exit;
}

// 6. Test yaratish
if (isset($_POST['create_quiz'])) {
    $stmt = $pdo->prepare("INSERT INTO quizzes (title, grade, time_limit, max_attempts) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['title'], (int)$_POST['quiz_grade'], (int)$_POST['time_limit'], (int)$_POST['max_attempts']]);
    header("Location: admin.php?msg=Test yaratildi"); exit;
}

// 8. Testni o'chirish
if (isset($_GET['delete_quiz'])) {
    $stmt = $pdo->prepare("DELETE FROM quizzes WHERE id = ?");
    $stmt->execute([(int)$_GET['delete_quiz']]);
    header("Location: admin.php?msg=Test o'chirildi"); exit;
}

// 9. AI ORQALI SAVOLLAR GENERATSIYA QILISH
if (isset($_POST['generate_ai_questions'])) {
    $quiz_id = (int)$_POST['quiz_id'];
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

    header("Location: admin.php?msg=" . $inserted . " ta AI savollari yuklandi"); exit;
}

// --- MA'LUMOTLARNI BAZADAN YUKLAB OLISH ---
$rankings = $pdo->query("
    SELECT u.id, u.firstname, u.lastname, u.grade, u.password,
           COUNT(a.id) as total_tests,
           COALESCE(SUM(a.score), 0) as total_score
    FROM users u
    LEFT JOIN attempts a ON u.id = a.user_id
    GROUP BY u.id, u.firstname, u.lastname, u.grade, u.password
    ORDER BY total_score DESC, total_tests DESC
")->fetchAll();

$quizzes = $pdo->query("SELECT q.*, (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as q_count FROM quizzes q ORDER BY q.id DESC")->fetchAll();
$homeworks = $pdo->query("SELECT * FROM homeworks ORDER BY id DESC")->fetchAll();

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
        
        <div class="bg-slate-900 text-white p-6 rounded-3xl shadow-xl flex flex-col md:flex-row justify-between items-center gap-4 mb-8 relative overflow-hidden">
            <img src="https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&w=1920&q=80" class="absolute inset-0 w-full h-full object-cover opacity-20">
            <div class="relative">
                <h1 class="text-2xl md:text-3xl font-black tracking-wide">👑 PREMIUM ADMIN BOSHQARUV MARKAZI</h1>
                <p class="text-sm text-cyan-400 font-bold mt-1">Reytinglar, parollar, uzluksiz savollar va uyga vazifalar nazorati.</p>
            </div>
            <div class="flex gap-2 relative">
                <a href="admin.php" class="bg-slate-800 text-white font-black px-4 py-3 rounded-2xl hover:bg-slate-700 transition">🔄 Yangilash</a>
                <a href="index.php" target="_blank" class="bg-amber-400 text-slate-950 font-black px-5 py-3 rounded-2xl hover:bg-amber-500 transition shadow-lg">Asosiy Sahifa 🌍</a>
            </div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div id="status-alert" class="bg-emerald-50 border-2 border-emerald-200 text-emerald-700 p-4 rounded-2xl font-black mb-6 text-sm flex justify-between items-center">
                <span>🚀 Muvaffaqiyatli: <?= htmlspecialchars($_GET['msg']) ?></span>
                <button onclick="document.getElementById('status-alert').remove()" class="text-xl">&times;</button>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <h2 class="text-xl font-black text-emerald-600 mb-4">👦 Yangi Kosmonavt Qo'shish</h2>
                <form action="" method="POST" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-black text-slate-500 mb-1">Ismi</label>
                            <input type="text" name="firstname" required placeholder="Asilbek" class="w-full p-3 border-2 border-slate-200 rounded-2xl font-bold">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-500 mb-1">Familiyasi</label>
                            <input type="text" name="lastname" required placeholder="Aliyev" class="w-full p-3 border-2 border-slate-200 rounded-2xl font-bold">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-black text-slate-500 mb-1">Sinf</label>
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
                    <button type="submit" name="add_user" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white p-4 rounded-2xl font-black transition">O'quvchini Ro'yxatga Saqlash</button>
                </form>
            </div>

            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                <h2 class="text-xl font-black text-amber-500 mb-4">📚 Yangi Uyga Vazifa Yuklash</h2>
                <form action="" method="POST" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-black text-slate-500 mb-1">Vazifa Sarlavhasi</label>
                            <input type="text" name="h_title" required placeholder="5-dars misollar" class="w-full p-3 border-2 border-slate-200 rounded-2xl font-bold">
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
                        <label class="block text-xs font-black text-slate-500 mb-1">Tushuntirish</label>
                        <textarea name="h_desc" required rows="2" placeholder="12-betdagi misollar..." class="w-full p-3 border-2 border-slate-200 rounded-2xl font-medium"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-black text-slate-500 mb-1">Muhlati</label>
                            <input type="date" name="h_deadline" required class="w-full p-3 border-2 border-slate-200 rounded-2xl font-bold">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-500 mb-1">Rasm Linki (Ixtiyoriy)</label>
                            <input type="text" name="image_url" placeholder="https://..." class="w-full p-3 border-2 border-slate-200 rounded-2xl text-xs">
                        </div>
                    </div>
                    <button type="submit" name="add_homework" class="w-full bg-amber-500 hover:bg-amber-600 text-white p-4 rounded-2xl font-black transition">Uyga Vazifani E'lon Qilish 🚀</button>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden mb-8">
            <div class="p-5 bg-slate-900 text-white flex justify-between items-center">
                <h2 class="text-xl font-black">📊 O'quvchilar Reytingi & Ma'lumotlar nazorati</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-100 text-slate-600 font-black border-b uppercase text-xs">
                        <tr>
                            <th class="p-4">O'rin</th>
                            <th class="p-4">O'quvchi F.I.O</th>
                            <th class="p-4">Sinf</th>
                            <th class="p-4">Testlari</th>
                            <th class="p-4">Balli</th>
                            <th class="p-4">PIN Parol</th>
                            <th class="p-4 text-center">Amallar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-bold">
                        <?php $rank = 1; foreach($rankings as $u): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="p-4"><?= $rank == 1 ? '🥇' : ($rank == 2 ? '🥈' : ($rank == 3 ? '🥉' : $rank)) ?></td>
                            <td class="p-4 text-slate-900 font-black"><?= htmlspecialchars($u['lastname'] . ' ' . $u['firstname']) ?></td>
                            <td class="p-4"><span class="bg-slate-100 px-3 py-1 rounded-full text-xs font-black"><?= $u['grade'] ?>-sinf</span></td>
                            <td class="p-4 text-amber-600"><?= $u['total_tests'] ?> ta</td>
                            <td class="p-4 text-emerald-600 text-base"><?= (int)$u['total_score'] ?> ball ⭐</td>
                            <td class="p-4 font-mono text-indigo-600 text-base"><?= htmlspecialchars($u['password']) ?></td>
                            <td class="p-4 flex justify-center gap-2">
                                <button onclick="openEditUserModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['firstname'])) ?>', '<?= htmlspecialchars(addslashes($u['lastname'])) ?>', <?= $u['grade'] ?>, '<?= htmlspecialchars(addslashes($u['password'])) ?>')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-2 rounded-xl text-xs transition">⚙️ Tahrirlash</button>
                                <a href="admin.php?delete_user=<?= $u['id'] ?>" onclick="return confirm('O\'chirishni tasdiqlaysizmi?')" class="bg-rose-500 hover:bg-rose-600 text-white px-3 py-2 rounded-xl text-xs transition">🗑 O'chirish</a>
                            </td>
                        </tr>
                        <?php $rank++; endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100 lg:col-span-1">
                <h2 class="text-xl font-black text-indigo-600 mb-4">📝 Yangi Test Yaratish</h2>
                <form action="" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Test nomi</label>
                        <input type="text" name="title" required placeholder="Mantiqiy matematika" class="w-full p-3 border-2 border-slate-200 rounded-2xl font-bold">
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1">Sinf</label>
                            <select name="quiz_grade" class="w-full p-3 border-2 border-slate-200 rounded-2xl bg-white font-bold">
                                <option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option>
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
                    <button type="submit" name="create_quiz" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white p-3.5 rounded-2xl font-black transition">Testni Qo'shish</button>
                </form>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden lg:col-span-2">
                <div class="p-5 bg-slate-50 border-b border-slate-100">
                    <h2 class="text-lg font-black text-slate-900">⚙️ Testlar Ro'yxati va Savol Qo'shish</h2>
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
                            <tr class="hover:bg-slate-50 transition">
                                <td class="p-4 text-slate-900 font-black"><?= htmlspecialchars($q['title']) ?></td>
                                <td class="p-4"><span class="bg-indigo-50 text-indigo-600 px-3 py-1 rounded-full text-xs font-black"><?= $q['grade'] ?>-Sinf</span></td>
                                <td class="p-4 text-indigo-600">
                                    <span id="q-count-<?= $q['id'] ?>"><?= $q['q_count'] ?></span> ta savol
                                </td>
                                <td class="p-4 flex flex-wrap justify-center gap-1.5">
                                    <button onclick="openQuestionModal(<?= $q['id'] ?>, '<?= htmlspecialchars(addslashes($q['title'])) ?>')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-3 py-2 rounded-xl text-xs transition">➕ Savol kiritish</button>
                                    <button onclick="openAIModal(<?= $q['id'] ?>, '<?= htmlspecialchars(addslashes($q['title'])) ?>', <?= $q['grade'] ?>)" class="bg-cyan-600 hover:bg-cyan-700 text-white font-bold px-3 py-2 rounded-xl text-xs transition">🤖 AI Generator</button>
                                    <a href="admin.php?delete_quiz=<?= $q['id'] ?>" onclick="return confirm('Testni o\'chirmoqchimisiz?')" class="bg-rose-500 hover:bg-rose-600 text-white font-bold px-3 py-2 rounded-xl text-xs transition">🗑 O'chirish</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden mb-8">
            <div class="p-5 bg-slate-50 border-b border-slate-100">
                <h2 class="text-xl font-black text-slate-900">📚 Faol Uyga Vazifalar Ro'yxati</h2>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach($homeworks as $h): ?>
                    <div class="border-2 border-slate-100 rounded-2xl overflow-hidden bg-slate-50/50 flex flex-col justify-between">
                        <img src="<?= htmlspecialchars($h['image_url']) ?>" class="h-32 w-full object-cover">
                        <div class="p-4 flex-1">
                            <span class="bg-amber-400 text-slate-950 text-[10px] font-black px-2 py-0.5 rounded-full uppercase"><?= $h['grade'] ?>-Sinf</span>
                            <h3 class="font-black text-slate-900 mt-1 text-base"><?= htmlspecialchars($h['title']) ?></h3>
                            <p class="text-xs text-slate-500 mt-2 line-clamp-3 font-medium"><?= htmlspecialchars($h['description']) ?></p>
                        </div>
                        <div class="p-4 bg-slate-100 border-t border-slate-200 flex justify-between items-center">
                            <span class="text-xs font-bold text-rose-600">📅 Muddat: <?= $h['deadline'] ?></span>
                            <a href="admin.php?delete_homework=<?= $h['id'] ?>" onclick="return confirm('O\'chirilsinmi?')" class="text-xs bg-rose-500 hover:bg-rose-600 text-white font-bold px-3 py-1.5 rounded-xl transition">O'chirish</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden mb-8">
            <div class="p-5 bg-indigo-950 text-white flex justify-between items-center">
                <h2 class="text-xl font-black">⏱️ O'quvchilarning Testga Urinishlari Tarixi</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-100 text-slate-600 font-black border-b uppercase text-xs">
                        <tr>
                            <th>ID</th><th>O'quvchi</th><th>Sinf</th><th>Test Nomi</th><th>Urinish</th><th>To'g'ri</th><th>Foiz</th><th>Vaqt</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-bold text-slate-700">
                        <?php if(empty($attempts)): ?>
                            <tr><td colspan="8" class="p-8 text-center text-slate-400 font-medium">Hozircha urinishlar yo'q.</td></tr>
                        <?php if(!empty($attempts)) foreach($attempts as $att): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="p-4 text-slate-400">#<?= $att['id'] ?></td>
                                <td class="p-4 text-slate-900 font-black"><?= htmlspecialchars($att['lastname'] . ' ' . $att['firstname']) ?></td>
                                <td class="p-4"><?= $att['u_grade'] ?>-sinf</td>
                                <td class="p-4 text-indigo-600"><?= htmlspecialchars($att['quiz_title']) ?></td>
                                <td class="p-4"><span class="bg-amber-100 text-amber-800 px-2 py-0.5 rounded text-xs"><?= $att['attempt_number'] ?>-urinish</span></td>
                                <td class="p-4 text-center"><?= $att['score'] ?> / <?= $att['total_questions'] ?></td>
                                <td class="p-4 text-emerald-600 bg-emerald-50/50"><?= $att['score'] ?> %</td>
                                <td class="p-4 text-xs text-slate-500"><?= $att['completed_at'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div id="userEditModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center hidden p-4 z-50">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h3 class="text-lg font-black text-slate-950">⚙️ Profil va Parolni Tahrirlash</h3>
                <button onclick="closeEditUserModal()" class="text-slate-400 text-2xl font-bold">&times;</button>
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
                        <option value="1">1-Sinf</option><option value="2">2-Sinf</option><option value="3">3-Sinf</option><option value="4">4-Sinf</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Yangi Kirish PIN-kodi</label>
                    <input type="text" name="password" id="edit_password" required maxlength="6" class="w-full p-3 border-2 border-slate-200 rounded-xl text-center font-black text-indigo-600">
                </div>
                <div class="flex gap-2 pt-2">
                    <button type="button" onclick="closeEditUserModal()" class="w-1/3 bg-slate-100 text-slate-700 font-bold p-3 rounded-xl">Bekor qilish</button>
                    <button type="submit" name="edit_user" class="w-2/3 bg-indigo-600 text-white font-black p-3 rounded-xl shadow">Saqlash ✨</button>
                </div>
            </form>
        </div>
    </div>

    <div id="qModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center hidden p-4 z-50">
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <div>
                    <h3 class="text-lg font-black text-slate-950" id="mTitle">📌 Savol qo'shish</h3>
                    <p class="text-xs text-emerald-600 font-bold mt-0.5 animate-pulse" id="ajax-status">Muvaffaqiyatli saqlandi! Keyingi savolni yozishingiz mumkin 👇</p>
                </div>
                <button onclick="closeQuestionModal()" class="text-slate-400 text-2xl font-bold">&times;</button>
            </div>
            
            <form id="ajaxQuestionForm" class="space-y-4">
                <input type="hidden" name="ajax_add_question" value="1">
                <input type="hidden" name="quiz_id" id="mQuizId">
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Savol Matni</label>
                    <textarea name="question_text" id="q_text" required placeholder="Masalan: 12 + 5 nechchi bo'ladi?" rows="2" class="w-full p-3 border-2 border-slate-200 rounded-2xl font-bold focus:border-indigo-500 focus:outline-none"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <input type="text" name="option_a" id="q_a" required placeholder="A javob" class="p-2.5 border-2 border-slate-200 rounded-xl font-medium focus:border-indigo-500 focus:outline-none">
                    <input type="text" name="option_b" id="q_b" required placeholder="B javob" class="p-2.5 border-2 border-slate-200 rounded-xl font-medium focus:border-indigo-500 focus:outline-none">
                    <input type="text" name="option_c" id="q_c" required placeholder="C javob" class="p-2.5 border-2 border-slate-200 rounded-xl font-medium focus:border-indigo-500 focus:outline-none">
                    <input type="text" name="option_d" id="q_d" required placeholder="D javob" class="p-2.5 border-2 border-slate-200 rounded-xl font-medium focus:border-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">To'g'ri javob varianti</label>
                    <select name="correct_option" id="q_ans" class="w-full p-2.5 border-2 border-slate-200 rounded-xl bg-white font-bold focus:border-indigo-500 focus:outline-none">
                        <option value="A">A Variant</option>
                        <option value="B">B Variant</option>
                        <option value="C">C Variant</option>
                        <option value="D">D Variant</option>
                    </select>
                </div>
                
                <div class="flex gap-2 pt-2">
                    <button type="button" onclick="closeQuestionModal()" class="w-1/3 bg-slate-100 text-slate-700 font-black p-3 rounded-xl">Tugatish ❌</button>
                    <button type="submit" id="submitBtn" class="w-2/3 bg-indigo-600 hover:bg-indigo-700 text-white p-3 rounded-xl font-black shadow flex items-center justify-center gap-2">
                        <span>Keyingi Savolni Saqlash 💾</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="aiModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center hidden p-4 z-50">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl">
            <div class="flex justify-between items-center mb-4 border-b pb-2">
                <h3 class="text-lg font-black text-slate-950">🤖 AI Savollar Generatori</h3>
                <button onclick="closeAIModal()" class="text-slate-400 text-2xl font-bold">&times;</button>
            </div>
            <form action="" method="POST" class="space-y-4">
                <input type="hidden" name="quiz_id" id="aiQuizId">
                <input type="hidden" name="quiz_title" id="aiQuizTitle">
                <input type="hidden" name="quiz_grade" id="aiQuizGrade">
                <div>
                    <p class="text-sm font-bold text-slate-600">Test: <span id="aiDisplayTitle" class="text-cyan-600"></span></p>
                    <p class="text-xs font-bold text-slate-400 mt-1">AI ushbu test uchun mantiqiy savollarni avtomatik yaratib beradi.</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Nechta savol yaratilsin?</label>
                    <select name="question_count" class="w-full p-2.5 border-2 border-slate-200 rounded-xl bg-white font-bold">
                        <option value="1">1 ta savol</option>
                        <option value="3">3 ta savol</option>
                        <option value="5">5 ta savol (Maksimum)</option>
                    </select>
                </div>
                <button type="submit" name="generate_ai_questions" class="w-full bg-cyan-600 hover:bg-cyan-700 text-white p-3 rounded-xl font-black shadow">Savollarni Avtomatik Yaratish ✨</button>
            </form>
        </div>
    </div>

    <script>
        // Holat bildirish matnini yashirish
        document.getElementById('ajax-status').style.display = 'none';

        // 1. O'quvchi Tahrirlash Modali
        function openEditUserModal(id, fname, lname, grade, pass) {
            document.getElementById('edit_user_id').value = id;
            document.getElementById('edit_firstname').value = fname;
            document.getElementById('edit_lastname').value = lname;
            document.getElementById('edit_grade').value = grade;
            document.getElementById('edit_password').value = pass;
            document.getElementById('userEditModal').classList.remove('hidden');
        }
        function closeEditUserModal() {
            document.getElementById('userEditModal').classList.add('hidden');
        }

        // 2. Savol Qo'shish Modali (Yangi format)
        function openQuestionModal(id, title) {
            document.getElementById('mQuizId').value = id;
            document.getElementById('mTitle').innerText = "📌 [" + title + "] testiga savol qo'shish";
            document.getElementById('ajax-status').style.display = 'none';
            document.getElementById('ajaxQuestionForm').reset();
            document.getElementById('qModal').classList.remove('hidden');
            setTimeout(() => document.getElementById('q_text').focus(), 100);
        }
        function closeQuestionModal() {
            document.getElementById('qModal').classList.add('hidden');
        }

        // 3. AI Generatsiya Modali
        function openAIModal(id, title, grade) {
            document.getElementById('aiQuizId').value = id;
            document.getElementById('aiQuizTitle').value = title;
            document.getElementById('aiQuizGrade').value = grade;
            document.getElementById('aiDisplayTitle').innerText = title;
            document.getElementById('aiModal').classList.remove('hidden');
        }
        function closeAIModal() {
            document.getElementById('aiModal').classList.add('hidden');
        }

        // 🚀 ENG ASOSIY QISM: SAHIFANI YANGILAMASDAN SAVOL QO'SHISH JAVASCRIPT KODI (AJAX)
        document.getElementById('ajaxQuestionForm').addEventListener('submit', function(e) {
            e.preventDefault(); // Sahifa yangilanib ketishini to'xtatamiz

            const submitBtn = document.getElementById('submitBtn');
            const statusText = document.getElementById('ajax-status');
            const quizId = document.getElementById('mQuizId').value;
            
            // Tugmani yuklanish holatiga o'tkazamiz
            submitBtn.disabled = true;
            submitBtn.innerHTML = "<span>Saqlanmoqda... ⏳</span>";

            // Formadagi ma'lumotlarni yig'ish
            const formData = new FormData(this);

            // PHP backendga so'rov yuborish
            fetch('admin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = "<span>Keyingi Savolni Saqlash 💾</span>";

                if (data.status === 'success') {
                    // 1. Yashil bildirishnomani ko'rsatish
                    statusText.style.display = 'block';
                    
                    // 2. Asosiy sahifadagi jadval ichida turgan savollar sonini jonli ravishda yangilash
                    const countSpan = document.getElementById('q-count-' + quizId);
                    if (countSpan) {
                        countSpan.innerText = data.current_count;
                    }

                    // 3. Inputlarni tozalash (Keyingi savol uchun tayyorlash)
                    document.getElementById('q_text').value = '';
                    document.getElementById('q_a').value = '';
                    document.getElementById('q_b').value = '';
                    document.getElementById('q_c').value = '';
                    document.getElementById('q_d').value = '';
                    document.getElementById('q_ans').selectedIndex = 0;

                    // 4. Kursorni avtomatik ravishda yana savol yozish matniga olib borib qo'yish
                    document.getElementById('q_text').focus();
                } else {
                    alert('Xatolik yuz berdi: ' + data.message);
                }
            })
            .catch(error => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = "<span>Keyingi Savolni Saqlash 💾</span>";
                console.error('Error:', error);
                alert('Server bilan bog\'lanishda xato!');
            });
        });
    </script>
</body>
</html>
