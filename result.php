<?php
// result.php
session_start();
if (!isset($_SESSION['last_score'])) {
    header("Location: index.php"); exit;
}
$score = $_SESSION['last_score'];
$total = $_SESSION['total_q'];

// Sessiyani tozalaymiz
unset($_SESSION['last_score']);
unset($_SESSION['total_q']);
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Sening Natijang</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka+One&display=swap" rel="stylesheet">
</head>
<body class="bg-emerald-50 min-h-screen flex flex-col items-center justify-center p-4">
    <div class="bg-white p-8 rounded-3xl shadow-2xl border-4 border-emerald-400 text-center max-w-md w-full">
        <h1 class="text-4xl font-black text-emerald-500 mb-4" style="font-family: 'Fredoka One', cursive;">URAA! TUGADI! 🎉</h1>
        
        <p class="text-2xl text-slate-700 font-bold mb-6">Sening Natijang:</p>
        
        <div class="inline-block bg-amber-100 text-amber-600 text-5xl font-black py-4 px-8 rounded-2xl mb-6">
            <?= $score ?> / <?= $total ?>
        </div>

        <div class="flex justify-center gap-2 text-4xl mb-6">
            <?php 
            // Yulduzchalar bilan rag'batlantirish
            for($i=1; $i<=$total; $i++) {
                if($i <= $score) echo "⭐";
                else echo "⚫";
            }
            ?>
        </div>

        <p class="text-slate-600 font-semibold mb-8">
            <?= $score == $total ? 'Barakalla! Sen daho bolajonsan! 🏆' : 'Ajoyib! Yanada ko\'proq harakat qil! 💪' ?>
        </p>

        <a href="dashboard.php" class="inline-block w-full bg-indigo-500 text-white font-bold py-3 rounded-xl text-lg shadow-md hover:bg-indigo-600 transition">
            Kabinetga qaytish 🏠
        </a>
    </div>
</body>
</html>