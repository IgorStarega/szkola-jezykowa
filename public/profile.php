<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Profil użytkownika - Szkoła Językowa</title>
</head>
<body>
        <h1>Witaj, <?php echo htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($_SESSION['surname'], ENT_QUOTES, 'UTF-8'); ?>!</h1>
        <p>Twoja rola w systemie: <strong><?php echo htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8'); ?></strong></p>

        <nav aria-label="Profil użytkownika">
            <a href="edit_profile.php">Edytuj profil</a>
            <a href="index.php">Strona główna</a>
            <a href="logout.php">Wyloguj się</a>
        </nav>
</body>
</html>