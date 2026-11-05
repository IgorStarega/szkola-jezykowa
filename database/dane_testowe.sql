USE `szkola_jezykowa`;

-- ============================================================
-- DANE TESTOWE (hasło wszystkich kont: password123)
-- ============================================================
INSERT INTO `users` (`id`, `name`, `surname`, `email`, `password`, `phone`, `role`, `active`) VALUES
(1, 'Admin', 'Systemu', 'admin@szkola.pl', '$2y$12$TOWyrL0ONMGYRM4WcaTwG.bJn0gl/CuMUI7f/F.GnaTod/rxTaWle', '500100100', 'admin', 1),
(2, 'Jan', 'Kowalski', 'jan.kowalski@szkola.pl', '$2y$12$TOWyrL0ONMGYRM4WcaTwG.bJn0gl/CuMUI7f/F.GnaTod/rxTaWle', '500100200', 'employee', 1),
(3, 'Anna', 'Nowak', 'anna.nowak@szkola.pl', '$2y$12$TOWyrL0ONMGYRM4WcaTwG.bJn0gl/CuMUI7f/F.GnaTod/rxTaWle', '500100300', 'employee', 1),
(4, 'Piotr', 'Wiśniewski', 'piotr.w@gmail.com', '$2y$12$TOWyrL0ONMGYRM4WcaTwG.bJn0gl/CuMUI7f/F.GnaTod/rxTaWle', '500100400', 'client', 1);

INSERT INTO `service_categories` (`id`, `name`, `description`) VALUES
(1, 'Język angielski', 'Kursy i lekcje języka angielskiego'),
(2, 'Język niemiecki', 'Kursy i lekcje języka niemieckiego'),
(3, 'Język hiszpański', 'Kursy i lekcje języka hiszpańskiego');

INSERT INTO `services` (`id`, `category_id`, `name`, `description`, `duration`, `price`, `active`) VALUES
(1, 1, 'Angielski - lekcja indywidualna', 'Lekcja 1 na 1 dopasowana do poziomu ucznia', 60, 100.00, 1),
(2, 1, 'Angielski - konwersacje', 'Lekcja konwersacyjna', 45, 80.00, 1),
(3, 2, 'Niemiecki - lekcja indywidualna', 'Lekcja 1 na 1 dla początkujących i zaawansowanych', 60, 100.00, 1),
(4, 3, 'Hiszpański - lekcja indywidualna', 'Lekcja 1 na 1 z lektorem', 60, 110.00, 1);

INSERT INTO `employees` (`id`, `user_id`, `description`, `active`) VALUES
(1, 2, 'Lektor języka angielskiego i niemieckiego, 8 lat doświadczenia.', 1),
(2, 3, 'Lektorka języka angielskiego i hiszpańskiego.', 1);

INSERT INTO `employee_services` (`employee_id`, `service_id`) VALUES
(1, 1), (1, 2), (1, 3),
(2, 1), (2, 4);

INSERT INTO `employee_availability` (`employee_id`, `day_of_week`, `start_time`, `end_time`) VALUES
(1, 1, '08:00:00', '16:00:00'),
(1, 2, '08:00:00', '16:00:00'),
(1, 3, '08:00:00', '16:00:00'),
(1, 4, '08:00:00', '16:00:00'),
(1, 5, '08:00:00', '14:00:00'),
(2, 1, '12:00:00', '20:00:00'),
(2, 3, '12:00:00', '20:00:00'),
(2, 5, '12:00:00', '20:00:00');

INSERT INTO `reservations` (`user_id`, `employee_id`, `service_id`, `reservation_date`, `start_time`, `end_time`, `status`, `comment`) VALUES
(4, 1, 1, '2026-11-09', '10:00:00', '11:00:00', 'potwierdzona', 'Pierwsza lekcja'),
(4, 2, 4, '2026-11-11', '13:00:00', '14:00:00', 'oczekująca', NULL),
(4, 1, 2, '2026-10-28', '09:00:00', '09:45:00', 'zrealizowana', NULL),
(4, 1, 3, '2026-10-30', '11:00:00', '12:00:00', 'anulowana', 'Odwołane przez klienta');
