<?php
require_once __DIR__ . '/../includes/db.php';

$komunikat = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $surname = trim($_POST['surname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($name === '' || $surname === '' || $email === '' || $phone === '' || $password === '' || $confirm_password === '') {
        $komunikat = "Wypełnij wszystkie pola!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $komunikat = "Niepoprawny format adresu e-mail!";
    } elseif ($password !== $confirm_password) {
        $komunikat = "Hasła muszą być takie same!";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT 1 FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);

            if ($stmt->fetchColumn() !== false) {
                $komunikat = "Ten adres e-mail jest już zarejestrowany!";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Rejestracja publiczna tworzy wyłącznie konta klientów.
                $stmt = $pdo->prepare("INSERT INTO users (name, surname, email, phone, password, role, active) VALUES (?, ?, ?, ?, ?, 'client', 1)");
                $stmt->execute([$name, $surname, $email, $phone, $hashed_password]);

                $komunikat = "Rejestracja zakończona sukcesem! Możesz się teraz zalogować.";
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $komunikat = "Nie udało się utworzyć konta. Spróbuj ponownie później.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Rejestracja - Szkoła Językowa</title>
</head>
<body>
    <div class="container">
        <h2>Rejestracja użytkownika</h2>
        
        <?php if (!empty($komunikat)): ?>
            <div class="alert"><?php echo htmlspecialchars($komunikat, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <div>
                <label for="name">Imię:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div>
                <label for="surname">Nazwisko:</label>
                <input type="text" id="surname" name="surname" required>
            </div>
            <div>
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div>
                <label for="phone">Telefon:</label>
                <input type="tel" id="phone" name="phone" required>
            </div>
            <div>
                <label for="password">Hasło:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div>
                <label for="confirm_password">Powtórz hasło:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit">Zarejestruj się</button>
        </form>
        <p>Masz już konto? <a href="login.php">Zaloguj się</a></p>
    </div>
</body>
</html>