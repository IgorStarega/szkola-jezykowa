<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

$komunikat = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $komunikat = "Wypełnij wszystkie pola!";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, name, surname, password, role, active FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {

                // Nieaktywne konto nie może rozpocząć sesji.
                if ((int) $user['active'] !== 1) {
                    $komunikat = "To konto zostało zablokowane.";
                } else {
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['surname'] = $user['surname'];
                    $_SESSION['role'] = $user['role'];

                    header("Location: profile.php");
                    exit;
                }
            } else {
                $komunikat = "Nieprawidłowy e-mail lub hasło!";
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $komunikat = "Nie udało się zalogować. Spróbuj ponownie później.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Logowanie - Szkoła Językowa</title>
</head>
<body>
    <div class="container">
        <h2>Logowanie do systemu</h2>
        
        <?php if (!empty($komunikat)): ?>
            <div class="alert"><?php echo htmlspecialchars($komunikat, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div>
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div>
                <label for="password">Hasło:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Zaloguj się</button>
        </form>
        <p>Nie masz konta? <a href="register.php">Zarejestruj się</a></p>
    </div>
</body>
</html>