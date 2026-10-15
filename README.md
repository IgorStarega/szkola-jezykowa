# Szkoła Językowa – System Rezerwacji Zajęć

Aplikacja internetowa do obsługi rezerwacji zajęć w szkole językowej. Pozwala klientom na przeglądanie oferty kursów, wybór lektora i rezerwację terminów lekcji. Lektorzy oraz administrator posiadają dedykowane panele do zarządzania grafikiem, zmianą statusów zajęć oraz edycją oferty.

## Autorzy

* Julian Dmowski
* Adrian Cibor

## Stack technologiczny

* **PHP** – logika backendowa (czysty kod, połączenie z bazą przez PDO)
* **HTML5 / CSS3 / JavaScript** – frontend (AJAX)
* **MySQL** – baza danych (InnoDB, kodowanie utf8mb4)
* **Git / GitHub** – wersjonowanie i praca zespołowa (branch → PR → main)

## Struktura bazy danych

Plik `database/database.sql` zawiera relacyjną strukturę bazy danych dostosowaną do wymagań projektu:

* **users** – konta użytkowników z podziałem na role (client, employee, admin)
* **employees** – dodatkowe profile i biogramy lektorów
* **service_categories** oraz **services** – katalog języków i konkretnych zajęć (z określeniem czasu trwania i ceny)
* **employee_services** – przypisanie lektorów do prowadzonych przez nich kursów (relacja N:M)
* **reservations** – rezerwacje zajęć składane przez klientów
* **employee_availability** – cotygodniowe grafiki dostępności lektorów

Baza danych posiada nałożone klucze obce (FOREIGN KEY) oraz ograniczenia unikalności (UNIQUE), m.in. dla adresów e-mail oraz nazw kategorii językowych, co zapobiega duplikowaniu danych. Diagram encji znajduje się w pliku `ERD.png`.

## Jak uruchomić lokalnie

1. Zaimportuj plik `database/database.sql` do MySQL (np. przez phpMyAdmin w XAMPP).
2. Skopiuj pliki projektu do katalogu serwera lokalnego (np. `htdocs`).
3. Ustaw prawidłowe dane połączenia w pliku `includes/db.php`.
4. Otwórz projekt w przeglądarce pod adresem: `http://localhost/nazwa_folderu/index.php`.

## Status projektu

* **Etap 1:** Konfiguracja repozytorium Git, struktura folderów, projekt i plik bazy danych, dokumentacja `README.md`.
* **Kolejne etapy:** Obsługa rejestracji/logowania w PHP, panele użytkowników, integracja kalendarza rezerwacji i obsługa zmian statusów.

## Konta testowe

> **Domyślne hasło dla wszystkich kont:** `password123`

* **Administrator**
  * Admin Systemu — `admin@szkola.pl`

* **Pracownicy**
  * Jan Kowalski — `jan.kowalski@szkola.pl`
  * Anna Nowak — `anna.nowak@szkola.pl`

* **Klient**
  * Piotr Wiśniewski — `piotr.w@gmail.com`
