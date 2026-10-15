<?php
function sprawdz_zalogowanie() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

function sprawdz_admin() {
    sprawdz_zalogowanie();
    if ($_SESSION['role'] !== 'admin') {
        die("Brak dostępu! Ta strona jest zarezerwowana dla administratora.");
    }
}

function sprawdz_pracownik() {
    sprawdz_zalogowanie();
    if ($_SESSION['role'] !== 'employee' && $_SESSION['role'] !== 'admin') {
        die("Brak dostępu! Strona dostępna tylko dla pracowników.");
    }
}
?>