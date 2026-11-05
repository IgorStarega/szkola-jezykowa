<?php
require_once __DIR__ . '/../../includes/admin_init.php';

$dzis = date('Y-m-d');

$klienci = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();
$pracownicy = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$uslugi = $pdo->query("SELECT COUNT(*) FROM services")->fetchColumn();
$rezerwacje = $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE reservation_date = ? AND status <> 'anulowana'");
$stmt->execute([$dzis]);
$dzisiaj = $stmt->fetchColumn();

$wg_statusu = $pdo->query("SELECT status, COUNT(*) FROM reservations GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

$stmt = $pdo->prepare(
    "SELECT r.id, r.reservation_date, r.start_time, r.status, s.name AS usluga,
            CONCAT(cu.name, ' ', cu.surname) AS klient,
            CONCAT(eu.name, ' ', eu.surname) AS pracownik
     FROM reservations r
     JOIN users cu ON cu.id = r.user_id
     JOIN services s ON s.id = r.service_id
     JOIN employees e ON e.id = r.employee_id
     JOIN users eu ON eu.id = e.user_id
     WHERE r.reservation_date >= ? AND r.status IN ('oczekująca', 'potwierdzona')
     ORDER BY r.reservation_date, r.start_time
     LIMIT 5"
);
$stmt->execute([$dzis]);
$najblizsze = $stmt->fetchAll();

$tytul = 'Pulpit';
$aktywna = 'index';
require __DIR__ . '/../../includes/admin_header.php';
?>

<div class="kafelki">
    <div class="kafelek"><span class="liczba"><?= (int) $klienci ?></span>Klienci</div>
    <div class="kafelek"><span class="liczba"><?= (int) $pracownicy ?></span>Pracownicy</div>
    <div class="kafelek"><span class="liczba"><?= (int) $uslugi ?></span>Usługi</div>
    <div class="kafelek"><span class="liczba"><?= (int) $rezerwacje ?></span>Wszystkie rezerwacje</div>
    <div class="kafelek"><span class="liczba"><?= (int) $dzisiaj ?></span>Wizyty dzisiaj</div>
</div>

<h2>Rezerwacje według statusu</h2>
<div class="tabela-wrap">
<table>
    <tr><th>Status</th><th>Liczba</th></tr>
    <?php foreach (STATUSY as $status): ?>
        <tr>
            <td><span class="<?= klasa_statusu($status) ?>"><?= h($status) ?></span></td>
            <td><?= (int) ($wg_statusu[$status] ?? 0) ?></td>
        </tr>
    <?php endforeach; ?>
</table>
</div>

<h2>Najbliższe wizyty</h2>
<?php if (!$najblizsze): ?>
    <p>Brak nadchodzących rezerwacji.</p>
<?php else: ?>
<div class="tabela-wrap">
<table>
    <tr><th>Data</th><th>Godzina</th><th>Usługa</th><th>Klient</th><th>Pracownik</th><th>Status</th><th></th></tr>
    <?php foreach ($najblizsze as $r): ?>
        <tr>
            <td><?= h($r['reservation_date']) ?></td>
            <td><?= h(godzina($r['start_time'])) ?></td>
            <td><?= h($r['usluga']) ?></td>
            <td><?= h($r['klient']) ?></td>
            <td><?= h($r['pracownik']) ?></td>
            <td><span class="<?= klasa_statusu($r['status']) ?>"><?= h($r['status']) ?></span></td>
            <td><a href="reservation.php?id=<?= (int) $r['id'] ?>">Szczegóły</a></td>
        </tr>
    <?php endforeach; ?>
</table>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
