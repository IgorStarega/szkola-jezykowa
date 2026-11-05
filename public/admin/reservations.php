<?php
require_once __DIR__ . '/../../includes/admin_init.php';

// Szybkie anulowanie rezerwacji z listy
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sprawdz_csrf();
    if (($_POST['akcja'] ?? '') === 'anuluj') {
        $stmt = $pdo->prepare("UPDATE reservations SET status = 'anulowana' WHERE id = ? AND status IN ('oczekująca', 'potwierdzona')");
        $stmt->execute([(int) ($_POST['id'] ?? 0)]);
        ustaw_komunikat('Rezerwacja została anulowana.');
    }
    wroc('reservations.php', $_POST['powrot'] ?? '');
}

// Filtry: zakres dat, status, pracownik
$od = $_GET['od'] ?? '';
$do = $_GET['do'] ?? '';
$status = $_GET['status'] ?? '';
$pracownik = (int) ($_GET['pracownik'] ?? 0);
if (!poprawna_data($od)) $od = '';
if (!poprawna_data($do)) $do = '';
if (!in_array($status, STATUSY, true)) $status = '';

$where = [];
$params = [];
if ($od !== '') { $where[] = 'r.reservation_date >= ?'; $params[] = $od; }
if ($do !== '') { $where[] = 'r.reservation_date <= ?'; $params[] = $do; }
if ($status !== '') { $where[] = 'r.status = ?'; $params[] = $status; }
if ($pracownik > 0) { $where[] = 'r.employee_id = ?'; $params[] = $pracownik; }
$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$strona = numer_strony();
$na_strone = 10;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations r $sql_where");
$stmt->execute($params);
$ile = (int) $stmt->fetchColumn();

$offset = ($strona - 1) * $na_strone;
$stmt = $pdo->prepare(
    "SELECT r.id, r.reservation_date, r.start_time, r.end_time, r.status,
            s.name AS usluga, s.price,
            CONCAT(cu.name, ' ', cu.surname) AS klient,
            CONCAT(eu.name, ' ', eu.surname) AS pracownik
     FROM reservations r
     JOIN users cu ON cu.id = r.user_id
     JOIN services s ON s.id = r.service_id
     JOIN employees e ON e.id = r.employee_id
     JOIN users eu ON eu.id = e.user_id
     $sql_where
     ORDER BY r.reservation_date DESC, r.start_time DESC
     LIMIT " . (int) $na_strone . " OFFSET " . (int) $offset
);
$stmt->execute($params);
$lista = $stmt->fetchAll();

$lista_pracownikow = $pdo->query(
    "SELECT e.id, u.name, u.surname FROM employees e JOIN users u ON u.id = e.user_id ORDER BY u.surname, u.name"
)->fetchAll();

$filtry = array_filter(
    ['od' => $od, 'do' => $do, 'status' => $status, 'pracownik' => $pracownik ?: ''],
    fn($v) => $v !== ''
);

$tytul = 'Rezerwacje';
$aktywna = 'reservations';
require __DIR__ . '/../../includes/admin_header.php';
?>

<form method="GET" action="reservations.php" class="filtry">
    <label>Od <input type="date" name="od" value="<?= h($od) ?>"></label>
    <label>Do <input type="date" name="do" value="<?= h($do) ?>"></label>
    <label>Status
        <select name="status">
            <option value="">wszystkie</option>
            <?php foreach (STATUSY as $s): ?>
                <option value="<?= h($s) ?>"<?= $status === $s ? ' selected' : '' ?>><?= h($s) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Pracownik
        <select name="pracownik">
            <option value="">wszyscy</option>
            <?php foreach ($lista_pracownikow as $p): ?>
                <option value="<?= (int) $p['id'] ?>"<?= $pracownik === (int) $p['id'] ? ' selected' : '' ?>><?= h($p['name']) ?> <?= h($p['surname']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <button type="submit" class="btn">Filtruj</button>
    <a href="reservations.php" class="btn btn-szary">Wyczyść</a>
</form>

<p>Znaleziono rezerwacji: <strong><?= $ile ?></strong></p>

<div class="tabela-wrap">
<table>
    <tr><th>Data</th><th>Godziny</th><th>Usługa</th><th>Cena</th><th>Klient</th><th>Pracownik</th><th>Status</th><th>Akcje</th></tr>
    <?php foreach ($lista as $r): ?>
        <tr>
            <td><?= h($r['reservation_date']) ?></td>
            <td><?= h(godzina($r['start_time'])) ?>-<?= h(godzina($r['end_time'])) ?></td>
            <td><?= h($r['usluga']) ?></td>
            <td><?= h(cena($r['price'])) ?></td>
            <td><?= h($r['klient']) ?></td>
            <td><?= h($r['pracownik']) ?></td>
            <td><span class="<?= klasa_statusu($r['status']) ?>"><?= h($r['status']) ?></span></td>
            <td class="akcje">
                <a href="reservation.php?id=<?= (int) $r['id'] ?>">Szczegóły</a>
                <?php if (in_array($r['status'], ['oczekująca', 'potwierdzona'], true)): ?>
                    <form method="POST" action="reservations.php" onsubmit="return confirm('Na pewno anulować tę rezerwację?');">
                        <?= csrf_pole() ?>
                        <input type="hidden" name="akcja" value="anuluj">
                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                        <input type="hidden" name="powrot" value="<?= h($_SERVER['QUERY_STRING'] ?? '') ?>">
                        <button type="submit" class="btn btn-maly btn-czerwony">Anuluj</button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$lista): ?><tr><td colspan="8">Brak rezerwacji dla wybranych filtrów.</td></tr><?php endif; ?>
</table>
</div>
<?= paginacja($ile, $na_strone, $strona, $filtry) ?>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
