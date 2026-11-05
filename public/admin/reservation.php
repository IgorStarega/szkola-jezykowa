<?php
require_once __DIR__ . '/../../includes/admin_init.php';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

function pobierz_rezerwacje($pdo, $id) {
    $stmt = $pdo->prepare(
        "SELECT r.*, s.name AS usluga, s.price, s.duration,
                cu.name AS klient_imie, cu.surname AS klient_nazwisko, cu.email AS klient_email, cu.phone AS klient_telefon,
                eu.name AS prac_imie, eu.surname AS prac_nazwisko
         FROM reservations r
         JOIN users cu ON cu.id = r.user_id
         JOIN services s ON s.id = r.service_id
         JOIN employees e ON e.id = r.employee_id
         JOIN users eu ON eu.id = e.user_id
         WHERE r.id = ?"
    );
    $stmt->execute([$id]);
    return $stmt->fetch();
}

$r = pobierz_rezerwacje($pdo, $id);
if (!$r) {
    ustaw_komunikat('Nie znaleziono takiej rezerwacji.', 'blad');
    wroc('reservations.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sprawdz_csrf();
    $nowy = $_POST['status'] ?? '';

    if (!in_array($nowy, STATUSY, true)) {
        ustaw_komunikat('Wybrano nieprawidłowy status.', 'blad');
    } else {
        $konflikt = false;
        // Przywracając anulowaną rezerwację sprawdzamy, czy termin nie jest już zajęty
        if ($r['status'] === 'anulowana' && $nowy !== 'anulowana') {
            $stmt = $pdo->prepare(
                "SELECT COUNT(*) FROM reservations
                 WHERE employee_id = ? AND reservation_date = ? AND id <> ?
                   AND status <> 'anulowana' AND start_time < ? AND end_time > ?"
            );
            $stmt->execute([$r['employee_id'], $r['reservation_date'], $r['id'], $r['end_time'], $r['start_time']]);
            $konflikt = $stmt->fetchColumn() > 0;
        }

        if ($konflikt) {
            ustaw_komunikat('Nie można przywrócić rezerwacji, bo ten termin zajmuje już inna wizyta.', 'blad');
        } else {
            $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
            $stmt->execute([$nowy, $id]);
            ustaw_komunikat('Status rezerwacji został zmieniony.');
        }
    }
    wroc('reservation.php', 'id=' . $id);
}

$tytul = 'Rezerwacja nr ' . $r['id'];
$aktywna = 'reservations';
require __DIR__ . '/../../includes/admin_header.php';
?>

<div class="karta">
    <dl>
        <dt>Usługa</dt><dd><?= h($r['usluga']) ?> (<?= (int) $r['duration'] ?> min, <?= h(cena($r['price'])) ?>)</dd>
        <dt>Data</dt><dd><?= h($r['reservation_date']) ?></dd>
        <dt>Godziny</dt><dd><?= h(godzina($r['start_time'])) ?>-<?= h(godzina($r['end_time'])) ?></dd>
        <dt>Pracownik</dt><dd><?= h($r['prac_imie']) ?> <?= h($r['prac_nazwisko']) ?></dd>
        <dt>Klient</dt><dd><?= h($r['klient_imie']) ?> <?= h($r['klient_nazwisko']) ?></dd>
        <dt>E-mail klienta</dt><dd><?= h($r['klient_email']) ?></dd>
        <dt>Telefon klienta</dt><dd><?= h($r['klient_telefon']) ?></dd>
        <dt>Komentarz</dt><dd><?= h($r['comment'] ?: '-') ?></dd>
        <dt>Utworzona</dt><dd><?= h($r['created_at']) ?></dd>
        <dt>Status</dt><dd><span class="<?= klasa_statusu($r['status']) ?>"><?= h($r['status']) ?></span></dd>
    </dl>
</div>

<h2>Zmiana statusu</h2>
<form method="POST" action="reservation.php?id=<?= (int) $r['id'] ?>" class="filtry">
    <?= csrf_pole() ?>
    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
    <select name="status">
        <?php foreach (STATUSY as $s): ?>
            <option value="<?= h($s) ?>"<?= $r['status'] === $s ? ' selected' : '' ?>><?= h($s) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn">Zapisz status</button>
    <a href="reservations.php" class="btn btn-szary">Wróć do listy</a>
</form>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
