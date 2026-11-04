<?php
require_once __DIR__ . '/../../includes/admin_init.php';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$bledy = [];

$wszystkie_uslugi = $pdo->query(
    "SELECT s.id, s.name, c.name AS kategoria
     FROM services s
     JOIN service_categories c ON c.id = s.category_id
     ORDER BY c.name, s.name"
)->fetchAll();
$id_uslug = array_map('intval', array_column($wszystkie_uslugi, 'id'));

$dane = ['name' => '', 'surname' => '', 'email' => '', 'phone' => '', 'description' => '', 'active' => 1];
$wybrane_uslugi = [];
$dostepnosc = [];   // numer dnia => [od, do]
$user_id = 0;

if ($id > 0) {
    $stmt = $pdo->prepare(
        "SELECT e.user_id, e.description, e.active, u.name, u.surname, u.email, u.phone
         FROM employees e JOIN users u ON u.id = e.user_id
         WHERE e.id = ?"
    );
    $stmt->execute([$id]);
    $pracownik = $stmt->fetch();
    if (!$pracownik) {
        ustaw_komunikat('Nie znaleziono takiego pracownika.', 'blad');
        wroc('employees.php');
    }
    $user_id = (int) $pracownik['user_id'];
    $dane = [
        'name' => $pracownik['name'],
        'surname' => $pracownik['surname'],
        'email' => $pracownik['email'],
        'phone' => $pracownik['phone'],
        'description' => $pracownik['description'],
        'active' => (int) $pracownik['active'],
    ];

    $stmt = $pdo->prepare("SELECT service_id FROM employee_services WHERE employee_id = ?");
    $stmt->execute([$id]);
    $wybrane_uslugi = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

    $stmt = $pdo->prepare("SELECT day_of_week, start_time, end_time FROM employee_availability WHERE employee_id = ? ORDER BY start_time");
    $stmt->execute([$id]);
    foreach ($stmt->fetchAll() as $w) {
        $dzien = (int) $w['day_of_week'];
        if (!isset($dostepnosc[$dzien])) {
            $dostepnosc[$dzien] = [godzina($w['start_time']), godzina($w['end_time'])];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sprawdz_csrf();

    $dane['name'] = trim($_POST['name'] ?? '');
    $dane['surname'] = trim($_POST['surname'] ?? '');
    $dane['email'] = trim($_POST['email'] ?? '');
    $dane['phone'] = trim($_POST['phone'] ?? '');
    $dane['description'] = trim($_POST['description'] ?? '');
    $dane['active'] = isset($_POST['active']) ? 1 : 0;
    $haslo = $_POST['password'] ?? '';

    $wybrane_uslugi = array_map('intval', (array) ($_POST['uslugi'] ?? []));
    $wybrane_uslugi = array_values(array_intersect($wybrane_uslugi, $id_uslug));

    if ($dane['name'] === '' || $dane['surname'] === '' || $dane['phone'] === '') {
        $bledy[] = 'Imię, nazwisko i telefon są wymagane.';
    }
    if (mb_strlen($dane['name']) > 50 || mb_strlen($dane['surname']) > 50 || mb_strlen($dane['phone']) > 20) {
        $bledy[] = 'Imię i nazwisko mogą mieć do 50 znaków, a telefon do 20.';
    }
    if (!filter_var($dane['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($dane['email']) > 100) {
        $bledy[] = 'Podaj poprawny adres e-mail.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
        $stmt->execute([$dane['email'], $user_id]);
        if ($stmt->fetch()) {
            $bledy[] = 'Ten adres e-mail jest już zajęty.';
        }
    }
    if ($id === 0 && $haslo === '') {
        $bledy[] = 'Podaj hasło dla nowego pracownika.';
    } elseif ($haslo !== '' && strlen($haslo) < 8) {
        $bledy[] = 'Hasło musi mieć co najmniej 8 znaków.';
    }

    // Godziny pracy: jeden przedział na dzień tygodnia
    $p_dzien = is_array($_POST['dzien'] ?? null) ? $_POST['dzien'] : [];
    $p_od = is_array($_POST['od'] ?? null) ? $_POST['od'] : [];
    $p_do = is_array($_POST['do'] ?? null) ? $_POST['do'] : [];
    $dostepnosc = [];
    foreach (DNI_TYGODNIA as $nr => $nazwa) {
        if (isset($p_dzien[$nr])) {
            $od = $p_od[$nr] ?? '';
            $do = $p_do[$nr] ?? '';
            $dostepnosc[$nr] = [is_string($od) ? $od : '', is_string($do) ? $do : ''];
            if (!poprawna_godzina($od) || !poprawna_godzina($do)) {
                $bledy[] = $nazwa . ': podaj godziny w formacie GG:MM.';
            } elseif ($od >= $do) {
                $bledy[] = $nazwa . ': koniec pracy musi być później niż początek.';
            }
        }
    }

    if (!$bledy) {
        try {
            $pdo->beginTransaction();

            if ($id === 0) {
                $stmt = $pdo->prepare("INSERT INTO users (name, surname, email, phone, password, role, active) VALUES (?, ?, ?, ?, ?, 'employee', 1)");
                $stmt->execute([$dane['name'], $dane['surname'], $dane['email'], $dane['phone'], password_hash($haslo, PASSWORD_DEFAULT)]);
                $user_id = (int) $pdo->lastInsertId();

                $stmt = $pdo->prepare("INSERT INTO employees (user_id, description, active) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $dane['description'], $dane['active']]);
                $id = (int) $pdo->lastInsertId();
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, surname = ?, email = ?, phone = ? WHERE id = ?");
                $stmt->execute([$dane['name'], $dane['surname'], $dane['email'], $dane['phone'], $user_id]);

                if ($haslo !== '') {
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([password_hash($haslo, PASSWORD_DEFAULT), $user_id]);
                }

                $stmt = $pdo->prepare("UPDATE employees SET description = ?, active = ? WHERE id = ?");
                $stmt->execute([$dane['description'], $dane['active'], $id]);
            }

            // Przypisane usługi (relacja N:M) - kasujemy stare i wstawiamy zaznaczone
            $stmt = $pdo->prepare("DELETE FROM employee_services WHERE employee_id = ?");
            $stmt->execute([$id]);
            $stmt = $pdo->prepare("INSERT INTO employee_services (employee_id, service_id) VALUES (?, ?)");
            foreach ($wybrane_uslugi as $id_uslugi) {
                $stmt->execute([$id, $id_uslugi]);
            }

            // Dostępność
            $stmt = $pdo->prepare("DELETE FROM employee_availability WHERE employee_id = ?");
            $stmt->execute([$id]);
            $stmt = $pdo->prepare("INSERT INTO employee_availability (employee_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
            foreach ($dostepnosc as $nr => $godziny) {
                $stmt->execute([$id, $nr, $godziny[0] . ':00', $godziny[1] . ':00']);
            }

            $pdo->commit();
            ustaw_komunikat('Dane pracownika zostały zapisane.');
            wroc('employees.php');
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log($e->getMessage());
            $bledy[] = 'Nie udało się zapisać danych. Spróbuj ponownie.';
        }
    }
}

$tytul = $id > 0 ? 'Edycja pracownika' : 'Nowy pracownik';
$aktywna = 'employees';
require __DIR__ . '/../../includes/admin_header.php';
?>

<?php if ($bledy): ?>
    <div class="alert alert-blad">
        <?php foreach ($bledy as $b): ?><div><?= h($b) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="POST" action="employee_form.php<?= $id > 0 ? '?id=' . $id : '' ?>" class="formularz">
    <?= csrf_pole() ?>
    <input type="hidden" name="id" value="<?= $id ?>">

    <h2>Dane pracownika</h2>
    <div class="dwie-kolumny">
        <label>Imię
            <input type="text" name="name" value="<?= h($dane['name']) ?>" maxlength="50" required>
        </label>
        <label>Nazwisko
            <input type="text" name="surname" value="<?= h($dane['surname']) ?>" maxlength="50" required>
        </label>
    </div>
    <div class="dwie-kolumny">
        <label>E-mail (login)
            <input type="email" name="email" value="<?= h($dane['email']) ?>" maxlength="100" required>
        </label>
        <label>Telefon
            <input type="text" name="phone" value="<?= h($dane['phone']) ?>" maxlength="20" required>
        </label>
    </div>
    <label>Hasło <?= $id > 0 ? '(zostaw puste, aby nie zmieniać)' : '' ?>
        <input type="password" name="password" autocomplete="new-password"<?= $id === 0 ? ' required' : '' ?>>
    </label>
    <label>Opis
        <textarea name="description" rows="3"><?= h($dane['description']) ?></textarea>
    </label>
    <label class="checkbox">
        <input type="checkbox" name="active" value="1"<?= $dane['active'] ? ' checked' : '' ?>> Pracownik aktywny (można u niego rezerwować)
    </label>

    <h2>Prowadzone usługi</h2>
    <?php if (!$wszystkie_uslugi): ?>
        <p>Nie ma jeszcze żadnych usług.</p>
    <?php endif; ?>
    <?php foreach ($wszystkie_uslugi as $u): ?>
        <label class="checkbox">
            <input type="checkbox" name="uslugi[]" value="<?= (int) $u['id'] ?>"<?= in_array((int) $u['id'], $wybrane_uslugi, true) ? ' checked' : '' ?>>
            <?= h($u['name']) ?> <small>(<?= h($u['kategoria']) ?>)</small>
        </label>
    <?php endforeach; ?>

    <h2>Godziny pracy</h2>
    <div class="tabela-wrap">
    <table>
        <tr><th>Pracuje</th><th>Dzień</th><th>Od</th><th>Do</th></tr>
        <?php foreach (DNI_TYGODNIA as $nr => $nazwa): ?>
            <?php $ma = isset($dostepnosc[$nr]); ?>
            <tr>
                <td><input type="checkbox" name="dzien[<?= $nr ?>]" value="1"<?= $ma ? ' checked' : '' ?>></td>
                <td><?= h($nazwa) ?></td>
                <td><input type="time" name="od[<?= $nr ?>]" value="<?= h($ma ? $dostepnosc[$nr][0] : '08:00') ?>"></td>
                <td><input type="time" name="do[<?= $nr ?>]" value="<?= h($ma ? $dostepnosc[$nr][1] : '16:00') ?>"></td>
            </tr>
        <?php endforeach; ?>
    </table>
    </div>

    <div>
        <button type="submit" class="btn">Zapisz</button>
        <a href="employees.php" class="btn btn-szary">Anuluj</a>
    </div>
</form>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
