<?php
require_once __DIR__ . '/../../includes/admin_init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sprawdz_csrf();
    if (($_POST['akcja'] ?? '') === 'przelacz') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id === (int) $_SESSION['user_id']) {
            ustaw_komunikat('Nie możesz zablokować własnego konta.', 'blad');
        } else {
            $stmt = $pdo->prepare("UPDATE users SET active = 1 - active WHERE id = ?");
            $stmt->execute([$id]);
            ustaw_komunikat('Zmieniono status konta.');
        }
    }
    wroc('users.php', $_POST['powrot'] ?? '');
}

$q = trim($_GET['q'] ?? '');
$rola = $_GET['rola'] ?? '';
if (!array_key_exists($rola, ROLE)) {
    $rola = '';
}

$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(u.name LIKE ? OR u.surname LIKE ? OR u.email LIKE ?)';
    $szukane = '%' . $q . '%';
    array_push($params, $szukane, $szukane, $szukane);
}
if ($rola !== '') {
    $where[] = 'u.role = ?';
    $params[] = $rola;
}
$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$strona = numer_strony();
$na_strone = 10;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM users u $sql_where");
$stmt->execute($params);
$ile = (int) $stmt->fetchColumn();

$offset = ($strona - 1) * $na_strone;
$stmt = $pdo->prepare(
    "SELECT u.id, u.name, u.surname, u.email, u.phone, u.role, u.active, u.created_at,
            (SELECT COUNT(*) FROM reservations r WHERE r.user_id = u.id) AS liczba_rezerwacji
     FROM users u
     $sql_where
     ORDER BY u.surname, u.name
     LIMIT " . (int) $na_strone . " OFFSET " . (int) $offset
);
$stmt->execute($params);
$uzytkownicy = $stmt->fetchAll();

$filtry = array_filter(['q' => $q, 'rola' => $rola], fn($v) => $v !== '');

$tytul = 'Użytkownicy';
$aktywna = 'users';
require __DIR__ . '/../../includes/admin_header.php';
?>

<form method="GET" action="users.php" class="filtry">
    <input type="text" name="q" placeholder="Imię, nazwisko lub e-mail" value="<?= h($q) ?>">
    <select name="rola">
        <option value="">Wszystkie role</option>
        <?php foreach (ROLE as $klucz => $nazwa): ?>
            <option value="<?= h($klucz) ?>"<?= $rola === $klucz ? ' selected' : '' ?>><?= h($nazwa) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn">Szukaj</button>
    <a href="users.php" class="btn btn-szary">Wyczyść</a>
</form>

<p>Znaleziono użytkowników: <strong><?= $ile ?></strong>. Pracowników dodaje się w zakładce <a href="employees.php">Pracownicy</a>.</p>

<div class="tabela-wrap">
<table>
    <tr><th>Imię i nazwisko</th><th>E-mail</th><th>Telefon</th><th>Rola</th><th>Rezerwacje</th><th>Założone</th><th>Konto</th><th>Akcje</th></tr>
    <?php foreach ($uzytkownicy as $u): ?>
        <tr>
            <td><?= h($u['name']) ?> <?= h($u['surname']) ?></td>
            <td><?= h($u['email']) ?></td>
            <td><?= h($u['phone']) ?></td>
            <td><?= h(ROLE[$u['role']] ?? $u['role']) ?></td>
            <td><?= (int) $u['liczba_rezerwacji'] ?></td>
            <td><?= h(substr((string) $u['created_at'], 0, 10)) ?></td>
            <td><?= $u['active'] ? 'aktywne' : 'zablokowane' ?></td>
            <td class="akcje">
                <?php if ((int) $u['id'] !== (int) $_SESSION['user_id']): ?>
                    <form method="POST" action="users.php" onsubmit="return confirm('Na pewno zmienić status tego konta?');">
                        <?= csrf_pole() ?>
                        <input type="hidden" name="akcja" value="przelacz">
                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                        <input type="hidden" name="powrot" value="<?= h($_SERVER['QUERY_STRING'] ?? '') ?>">
                        <button type="submit" class="btn btn-maly btn-szary"><?= $u['active'] ? 'Zablokuj' : 'Odblokuj' ?></button>
                    </form>
                <?php else: ?>
                    <em>to Ty</em>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$uzytkownicy): ?><tr><td colspan="8">Brak użytkowników.</td></tr><?php endif; ?>
</table>
</div>
<?= paginacja($ile, $na_strone, $strona, $filtry) ?>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
