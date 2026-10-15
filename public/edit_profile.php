<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$komunikat = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name) || empty($surname) || empty($phone)) {
        $komunikat = "Wszystkie pola muszą być wypełnione!";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE users SET name = ?, surname = ?, phone = ? WHERE id = ?");
            if ($stmt->execute([$name, $surname, $phone, $user_id])) {
                // Aktualizujemy też dane w sesji, żeby od razu było widać zmianę imienia
                $_SESSION['name'] = $name;
                $_SESSION['surname'] = $surname;
                $komunikat = "Profil został pomyślnie zaktualizowany!";
            } else {
                $komunikat = "Błąd podczas aktualizacji profilu.";
            }
        } catch (PDOException $e) {
            $komunikat = "Błąd bazy danych: " . $e->getMessage();
        }
    }
}

$stmt = $pdo->prepare("SELECT name, surname, email, phone FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Edycja profilu - Szkoła Językowa</title>
</head>
<body>
    <div class="container">
        <h2>Edycja profilu</h2>
        
        <?php if (!empty($komunikat)): ?>
            <div class="alert"><?php echo htmlspecialchars($komunikat); ?></div>
        <?php endif; ?>

        <form action="edit_profile.php" method="POST">
            <div>
                <label>Imię:</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>
            <div>
                <label>Nazwisko:</label>
                <input type="text" name="surname" value="<?php echo htmlspecialchars($user['surname']); ?>" required>
            </div>
            <div>
                <label>E-mail (tylko do odczytu):</label>
                <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
            </div>
            <div>
                <label>Telefon:</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
            </div>
            <button type="submit">Zapisz zmiany</button>
        </form>
        <br>
        <p><a href="profile.php">Powrót do profilu</a></p>
    </div>
</body>
</html>