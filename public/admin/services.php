<?php
require_once __DIR__ . '/../../includes/admin_init.php';

$bledy = [];
$formularz = ['id' => 0, 'category_id' => 0, 'name' => '', 'description' => '', 'duration' => 60, 'price' => '', 'active' => 1];

$kategorie = $pdo->query("SELECT id, name FROM service_categories ORDER BY name")->fetchAll();
$id_kategorii = array_map('intval', array_column($kategorie, 'id'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sprawdz_csrf();
    $akcja = $_POST['akcja'] ?? '';
    $powrot = $_POST['powrot'] ?? '';

    if ($akcja === 'zapisz') {
        $formularz['id'] = (int) ($_POST['id'] ?? 0);
        $formularz['category_id'] = (int) ($_POST['category_id'] ?? 0);
        $formularz['name'] = trim($_POST['name'] ?? '');
        $formularz['description'] = trim($_POST['description'] ?? '');
        $formularz['duration'] = trim($_POST['duration'] ?? '');
        $formularz['price'] = trim($_POST['price'] ?? '');
        $formularz['active'] = isset($_POST['active']) ? 1 : 0;

        if ($formularz['name'] === '') {
            $bledy[] = 'Podaj nazwę usługi.';
        } elseif (mb_strlen($formularz['name']) > 100) {
            $bledy[] = 'Nazwa usługi może mieć maksymalnie 100 znaków.';
        }
        if (!in_array($formularz['category_id'], $id_kategorii, true)) {
            $bledy[] = 'Wybierz kategorię z listy.';
        }
        $czas = filter_var($formularz['duration'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 5, 'max_range' => 600]]);
        if ($czas === false) {
            $bledy[] = 'Czas trwania musi być liczbą minut od 5 do 600.';
        }
        $cena_txt = str_replace(',', '.', $formularz['price']);
        if (!is_numeric($cena_txt) || (float) $cena_txt < 0 || (float) $cena_txt > 99999) {
            $bledy[] = 'Cena musi być liczbą od 0 do 99999.';
        }

        if (!$bledy) {
            $cena_liczba = round((float) $cena_txt, 2);
            if ($formularz['id'] > 0) {
                $stmt = $pdo->prepare("UPDATE services SET category_id = ?, name = ?, description = ?, duration = ?, price = ?, active = ? WHERE id = ?");
                $stmt->execute([$formularz['category_id'], $formularz['name'], $formularz['description'], $czas, $cena_liczba, $formularz['active'], $formularz['id']]);
                ustaw_komunikat('Usługa została zaktualizowana.');
            } else {
                $stmt = $pdo->prepare("INSERT INTO services (category_id, name, description, duration, price, active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$formularz['category_id'], $formularz['name'], $formularz['description'], $czas, $cena_liczba, $formularz['active']]);
                ustaw_komunikat('Usługa została dodana.');
            }
            wroc('services.php');
        }
    } elseif ($akcja === 'przelacz') {
        $stmt = $pdo->prepare("UPDATE services SET active = 1 - active WHERE id = ?");
        $stmt->execute([(int) ($_POST['id'] ?? 0)]);
        ustaw_komunikat('Zmieniono aktywność usługi.');
        wroc('services.php', $powrot);
    } elseif ($akcja === 'usun') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE service_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            ustaw_komunikat('Ta usługa ma rezerwacje, więc nie można jej usunąć. Możesz ją dezaktywować.', 'blad');
        } else {
            $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
            $stmt->execute([$id]);
            ustaw_komunikat('Usługa została usunięta.');
        }
        wroc('services.php', $powrot);
    }
} elseif (isset($_GET['edytuj'])) {
    $stmt = $pdo->prepare("SELECT id, category_id, name, description, duration, price, active FROM services WHERE id = ?");
    $stmt->execute([(int) $_GET['edytuj']]);
    $znaleziona = $stmt->fetch();
    if ($znaleziona) {
        $formularz = $znaleziona;
    }
}

// Filtrowanie i wyszukiwanie listy usług
$q = trim($_GET['q'] ?? '');
$kat = (int) ($_GET['kategoria'] ?? 0);
$strona = numer_strony();
$na_strone = 10;

$where = [];
$params = [];
if ($q !== '') {
    $where[] = 's.name LIKE ?';
    $params[] = '%' . $q . '%';
}
if ($kat > 0) {
    $where[] = 's.category_id = ?';
    $params[] = $kat;
}
$sql_where = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM services s $sql_where");
$stmt->execute($params);
$ile = (int) $stmt->fetchColumn();

$offset = ($strona - 1) * $na_strone;
$stmt = $pdo->prepare(
    "SELECT s.id, s.name, s.duration, s.price, s.active, c.name AS kategoria
     FROM services s
     JOIN service_categories c ON c.id = s.category_id
     $sql_where
     ORDER BY c.name, s.name
     LIMIT " . (int) $na_strone . " OFFSET " . (int) $offset
);
$stmt->execute($params);
$uslugi = $stmt->fetchAll();

$filtry = array_filter(['q' => $q, 'kategoria' => $kat ?: ''], fn($v) => $v !== '');

$tytul = 'Usługi';
$aktywna = 'services';
require __DIR__ . '/../../includes/admin_header.php';
?>

<?php if ($bledy): ?>
    <div class="alert alert-blad">
        <?php foreach ($bledy as $b): ?><div><?= h($b) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<h2><?= $formularz['id'] ? 'Edycja usługi' : 'Nowa usługa' ?></h2>
<?php if (!$kategorie): ?>
    <p>Najpierw dodaj przynajmniej jedną <a href="categories.php">kategorię</a>.</p>
<?php else: ?>
<form method="POST" action="services.php" class="formularz">
    <?= csrf_pole() ?>
    <input type="hidden" name="akcja" value="zapisz">
    <input type="hidden" name="id" value="<?= (int) $formularz['id'] ?>">
    <label>Nazwa
        <input type="text" name="name" value="<?= h($formularz['name']) ?>" maxlength="100" required>
    </label>
    <label>Kategoria
        <select name="category_id" required>
            <option value="">-- wybierz --</option>
            <?php foreach ($kategorie as $k): ?>
                <option value="<?= (int) $k['id'] ?>"<?= (int) $formularz['category_id'] === (int) $k['id'] ? ' selected' : '' ?>><?= h($k['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Opis
        <textarea name="description" rows="3"><?= h($formularz['description']) ?></textarea>
    </label>
    <div class="dwie-kolumny">
        <label>Czas trwania (minuty)
            <input type="number" name="duration" min="5" max="600" value="<?= h($formularz['duration']) ?>" required>
        </label>
        <label>Cena (zł)
            <input type="text" name="price" value="<?= h($formularz['price']) ?>" required>
        </label>
    </div>
    <label class="checkbox">
        <input type="checkbox" name="active" value="1"<?= $formularz['active'] ? ' checked' : '' ?>> Usługa aktywna (widoczna dla klientów)
    </label>
    <div>
        <button type="submit" class="btn">Zapisz</button>
        <?php if ($formularz['id']): ?><a href="services.php" class="btn btn-szary">Anuluj</a><?php endif; ?>
    </div>
</form>
<?php endif; ?>

<h2>Lista usług</h2>
<form method="GET" action="services.php" class="filtry">
    <input type="text" name="q" placeholder="Szukaj po nazwie" value="<?= h($q) ?>">
    <select name="kategoria">
        <option value="">Wszystkie kategorie</option>
        <?php foreach ($kategorie as $k): ?>
            <option value="<?= (int) $k['id'] ?>"<?= $kat === (int) $k['id'] ? ' selected' : '' ?>><?= h($k['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn">Filtruj</button>
    <a href="services.php" class="btn btn-szary">Wyczyść</a>
</form>

<div class="tabela-wrap">
<table>
    <tr><th>Nazwa</th><th>Kategoria</th><th>Czas</th><th>Cena</th><th>Aktywna</th><th>Akcje</th></tr>
    <?php foreach ($uslugi as $u): ?>
        <tr>
            <td><?= h($u['name']) ?></td>
            <td><?= h($u['kategoria']) ?></td>
            <td><?= (int) $u['duration'] ?> min</td>
            <td><?= h(cena($u['price'])) ?></td>
            <td><?= $u['active'] ? 'tak' : 'nie' ?></td>
            <td class="akcje">
                <a href="services.php?edytuj=<?= (int) $u['id'] ?>">Edytuj</a>
                <form method="POST" action="services.php">
                    <?= csrf_pole() ?>
                    <input type="hidden" name="akcja" value="przelacz">
                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                    <input type="hidden" name="powrot" value="<?= h($_SERVER['QUERY_STRING'] ?? '') ?>">
                    <button type="submit" class="btn btn-maly btn-szary"><?= $u['active'] ? 'Dezaktywuj' : 'Aktywuj' ?></button>
                </form>
                <form method="POST" action="services.php" onsubmit="return confirm('Na pewno usunąć tę usługę?');">
                    <?= csrf_pole() ?>
                    <input type="hidden" name="akcja" value="usun">
                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                    <input type="hidden" name="powrot" value="<?= h($_SERVER['QUERY_STRING'] ?? '') ?>">
                    <button type="submit" class="btn btn-maly btn-czerwony">Usuń</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$uslugi): ?><tr><td colspan="6">Brak usług.</td></tr><?php endif; ?>
</table>
</div>
<?= paginacja($ile, $na_strone, $strona, $filtry) ?>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
