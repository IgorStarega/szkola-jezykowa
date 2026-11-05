<?php

const STATUSY = ['oczekująca', 'potwierdzona', 'zrealizowana', 'anulowana'];

const DNI_TYGODNIA = [
    1 => 'Poniedziałek', 2 => 'Wtorek', 3 => 'Środa', 4 => 'Czwartek',
    5 => 'Piątek', 6 => 'Sobota', 7 => 'Niedziela',
];

const ROLE = ['client' => 'Klient', 'employee' => 'Pracownik', 'admin' => 'Administrator'];

function h($tekst) {
    return htmlspecialchars((string) $tekst, ENT_QUOTES, 'UTF-8');
}

function ustaw_komunikat($tekst, $typ = 'ok') {
    $_SESSION['komunikat'] = ['tekst' => $tekst, 'typ' => $typ];
}

function pokaz_komunikat() {
    if (isset($_SESSION['komunikat'])) {
        $k = $_SESSION['komunikat'];
        unset($_SESSION['komunikat']);
        echo '<div class="alert alert-' . h($k['typ']) . '">' . h($k['tekst']) . '</div>';
    }
}

function wroc($plik, $powrot = '') {
    parse_str((string) $powrot, $parametry);
    $query = http_build_query($parametry);
    header('Location: ' . $plik . ($query !== '' ? '?' . $query : ''));
    exit;
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_pole() {
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function sprawdz_csrf() {
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
        http_response_code(403);
        die('Nieprawidłowy token formularza. Odśwież stronę i spróbuj ponownie.');
    }
}

function poprawna_data($data) {
    if (!is_string($data)) {
        return false;
    }
    $d = DateTime::createFromFormat('Y-m-d', $data);
    return $d && $d->format('Y-m-d') === $data;
}

function poprawna_godzina($godzina) {
    return is_string($godzina) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $godzina) === 1;
}

function cena($kwota) {
    return number_format((float) $kwota, 2, ',', ' ') . ' zł';
}

function godzina($czas) {
    return substr((string) $czas, 0, 5);
}

function numer_strony() {
    return max(1, (int) ($_GET['strona'] ?? 1));
}

function paginacja($ile_wszystkich, $na_strone, $strona, $parametry = []) {
    $stron = (int) ceil($ile_wszystkich / $na_strone);
    if ($stron <= 1) {
        return '';
    }
    $html = '<div class="paginacja">';
    for ($i = 1; $i <= $stron; $i++) {
        $parametry['strona'] = $i;
        if ($i === $strona) {
            $html .= '<span class="aktywna">' . $i . '</span>';
        } else {
            $html .= '<a href="?' . h(http_build_query($parametry)) . '">' . $i . '</a>';
        }
    }
    return $html . '</div>';
}

function klasa_statusu($status) {
    $klasy = [
        'oczekująca' => 'oczekujaca',
        'potwierdzona' => 'potwierdzona',
        'zrealizowana' => 'zrealizowana',
        'anulowana' => 'anulowana',
    ];
    return 'status status-' . ($klasy[$status] ?? 'oczekujaca');
}
