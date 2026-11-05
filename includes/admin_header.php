<?php
// Przed dołączeniem ustaw: $tytul (tytuł strony) i $aktywna (pozycja menu)
$menu = [
    'index' => 'Pulpit',
    'categories' => 'Kategorie',
    'services' => 'Usługi',
    'employees' => 'Pracownicy',
    'reservations' => 'Rezerwacje',
    'users' => 'Użytkownicy',
];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($tytul) ?> - Panel administratora</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<header class="pasek">
    <div class="pasek-wnetrze">
        <span class="logo">Szkoła Językowa - panel administratora</span>
        <nav>
            <?php foreach ($menu as $plik => $nazwa): ?>
                <a href="<?= $plik ?>.php"<?= $plik === $aktywna ? ' class="aktywny"' : '' ?>><?= h($nazwa) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="konto">
            <?= h($_SESSION['name']) ?> <?= h($_SESSION['surname']) ?>
            | <a href="../index.php">Strona główna</a>
            | <a href="../logout.php">Wyloguj</a>
        </div>
    </div>
</header>
<main class="tresc">
    <h1><?= h($tytul) ?></h1>
    <?php pokaz_komunikat(); ?>
