<?php
require_once __DIR__ . '/../../includes/admin_init.php';

$bledy = [];
$formularz = ['id' => 0, 'name' => '', 'description' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    sprawdz_csrf();
    $akcja = $_POST['akcja'] ?? '';

    if ($akcja === 'zapisz') {
        $formularz['id'] = (int) ($_POST['id'] ?? 0);
        $formularz['name'] = trim($_POST['name'] ?? '');
        $formularz['description'] = trim($_POST['description'] ?? '');

        if ($formularz['name'] === '') {
            $bledy[] = 'Podaj nazwę kategorii.';
        } elseif (mb_strlen($formularz['name']) > 100) {
            $bledy[] = 'Nazwa kategorii może mieć maksymalnie 100 znaków.';
        }

        if (!$bledy) {
            // Baza nie ma UNIQUE na nazwie kategorii, więc pilnujemy tego tutaj
            $stmt = $pdo->prepare("SELECT id FROM service_categories WHERE name = ? AND id <> ?");
            $stmt->execute([$formularz['name'], $formularz['id']]);
            if ($stmt->fetch()) {
                $bledy[] = 'Kategoria o takiej nazwie już istnieje.';
            }
        }

        if (!$bledy) {
            if ($formularz['id'] > 0) {
                $stmt = $pdo->prepare("UPDATE service_categories SET name = ?, description = ? WHERE id = ?");
                $stmt->execute([$formularz['name'], $formularz['description'], $formularz['id']]);
                ustaw_komunikat('Kategoria została zaktualizowana.');
            } else {
                $stmt = $pdo->prepare("INSERT INTO service_categories (name, description) VALUES (?, ?)");
                $stmt->execute([$formularz['name'], $formularz['description']]);
                ustaw_komunikat('Kategoria została dodana.');
            }
            wroc('categories.php');
        }
    } elseif ($akcja === 'usun') {
        $id = (int) ($_POST['id'] ?? 0);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM services WHERE category_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            ustaw_komunikat('Nie można usunąć kategorii, która ma przypisane usługi.', 'blad');
        } else {
            $stmt = $pdo->prepare("DELETE FROM service_categories WHERE id = ?");
            $stmt->execute([$id]);
            ustaw_komunikat('Kategoria została usunięta.');
        }
        wroc('categories.php');
    }
} elseif (isset($_GET['edytuj'])) {
    $stmt = $pdo->prepare("SELECT id, name, description FROM service_categories WHERE id = ?");
    $stmt->execute([(int) $_GET['edytuj']]);
    $znaleziona = $stmt->fetch();
    if ($znaleziona) {
        $formularz = $znaleziona;
    }
}

$kategorie = $pdo->query(
    "SELECT c.id, c.name, c.description, COUNT(s.id) AS liczba_uslug
     FROM service_categories c
     LEFT JOIN services s ON s.category_id = c.id
     GROUP BY c.id, c.name, c.description
     ORDER BY c.name"
)->fetchAll();

$tytul = 'Kategorie usług';
$aktywna = 'categories';
require __DIR__ . '/../../includes/admin_header.php';
?>

<?php if ($bledy): ?>
    <div class="alert alert-blad">
        <?php foreach ($bledy as $b): ?><div><?= h($b) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<h2><?= $formularz['id'] ? 'Edycja kategorii' : 'Nowa kategoria' ?></h2>
<form method="POST" action="categories.php" class="formularz">
    <?= csrf_pole() ?>
    <input type="hidden" name="akcja" value="zapisz">
    <input type="hidden" name="id" value="<?= (int) $formularz['id'] ?>">
    <label>Nazwa
        <input type="text" name="name" value="<?= h($formularz['name']) ?>" maxlength="100" required>
    </label>
    <label>Opis
        <textarea name="description" rows="3"><?= h($formularz['description']) ?></textarea>
    </label>
    <div>
        <button type="submit" class="btn">Zapisz</button>
        <?php if ($formularz['id']): ?><a href="categories.php" class="btn btn-szary">Anuluj</a><?php endif; ?>
    </div>
</form>

<h2>Lista kategorii</h2>
<div class="tabela-wrap">
<table>
    <tr><th>Nazwa</th><th>Opis</th><th>Usługi</th><th>Akcje</th></tr>
    <?php foreach ($kategorie as $k): ?>
        <tr>
            <td><?= h($k['name']) ?></td>
            <td><?= h($k['description']) ?></td>
            <td><?= (int) $k['liczba_uslug'] ?></td>
            <td class="akcje">
                <a href="categories.php?edytuj=<?= (int) $k['id'] ?>">Edytuj</a>
                <form method="POST" action="categories.php" onsubmit="return confirm('Na pewno usunąć tę kategorię?');">
                    <?= csrf_pole() ?>
                    <input type="hidden" name="akcja" value="usun">
                    <input type="hidden" name="id" value="<?= (int) $k['id'] ?>">
                    <button type="submit" class="btn btn-maly btn-czerwony">Usuń</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$kategorie): ?><tr><td colspan="4">Brak kategorii.</td></tr><?php endif; ?>
</table>
</div>

<?php require __DIR__ . '/../../includes/admin_footer.php'; ?>
