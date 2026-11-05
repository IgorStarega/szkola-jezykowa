<?php
require_once __DIR__ . '/../../includes/admin_init.php';

// Pracowników nie usuwamy (mają powiązane rezerwacje), tylko dezaktywujemy
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sprawdz_csrf();
    if (($_POST['akcja'] ?? '') === 'przelacz') {
        $stmt = $pdo->prepare("UPDATE employees SET active = 1 - active WHERE id = ?");
        $stmt->execute([(int) ($_POST['id'] ?? 0)]);
        ustaw_komunikat('Zmieniono aktywność pracownika.');
    }
    wroc('employees.php');
}

$pracownicy = $pdo->query(
    "SELECT e.id, e.active, u.name, u.surname, u.email, u.phone,
            (SELECT COUNT(*) FROM employee_services es WHERE es.employee_id = e.id) AS liczba_uslug,
            (SELECT COUNT(*) FROM employee_availability a WHERE a.employee_id = e.id) AS liczba_dni
     FROM employees e
     JOIN users u ON u.id = e.user_id
     ORDER BY u.surname, u.name"
)->fetchAll();

$tytul = 'Pracownicy';
$aktywna = 'employees';
require __DIR__ . '/../../includes/admin_header.php';
?>

<p><a href="employee_form.php" class="btn">Dodaj pracownika</a></p>

<div class="tabela-wrap">
<table>
    <tr><th>Imię i nazwisko</th><th>E-mail</th><th>Telefon</th><th>Usługi</th><th>Dni pracy</th><th>Aktywny</th><th>Akcje</th></tr>
    <?php foreach ($pracownicy as $p): ?>
        <tr>
            <td><?= h($p['name']) ?> <?= h($p['surname']) ?></td>
            <td><?= h($p['email']) ?></td>
            <td><?= h($p['phone']) ?></td>
            <td><?= (int) $p['liczba_uslug'] ?></td>
            <td><?= (int) $p['liczba_dni'] ?></td>
            <td><?= $p['active'] ? 'tak' : 'nie' ?></td>
            <td class="akcje">
                <a href="employee_form.php?id=<?= (int) $p['id'] ?>">Edytuj</a>
                <form method="POST" action="employees.php">
                    <?= csrf_pole() ?>
                    <input type="hidden" name="akcja" value="przelacz">
                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                    <button type="submit" class="btn btn-maly btn-szary"><?= $p['active'] ? 'Dezaktywuj' : 'Aktywuj' ?></button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$pracownicy): ?><tr><td colspan="7">Brak pracowników.</td></tr><?php endif; ?>
</table>
</div>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
