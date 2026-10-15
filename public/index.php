<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Szkoła Językowa</title>
</head>
<body>
    <main>
        <h1>Szkoła Językowa</h1>
        <p>Witaj w systemie szkoły językowej.</p>
        <nav aria-label="Konto użytkownika">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="profile.php">Przejdź do profilu</a>
                <a href="logout.php">Wyloguj się</a>
            <?php else: ?>
                <a href="login.php">Zaloguj się</a>
                <a href="register.php">Utwórz konto</a>
            <?php endif; ?>
        </nav>
    </main>
</body>
</html>
