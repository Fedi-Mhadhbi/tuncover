<?php

require_once __DIR__ . '/db.php';

function ensurePaymentsTable(PDO $db): void {
    static $ready = false;

    if ($ready) {
        return;
    }

    $db->exec("
        CREATE TABLE IF NOT EXISTS payments (
            id_payment INT AUTO_INCREMENT PRIMARY KEY,
            id_reservation INT NOT NULL,
            id_artisan INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            platform_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
            artisan_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            currency VARCHAR(8) NOT NULL DEFAULT 'TND',
            payment_method VARCHAR(50) NOT NULL DEFAULT 'fake_card',
            card_holder VARCHAR(120) DEFAULT NULL,
            card_last4 VARCHAR(4) DEFAULT NULL,
            transaction_ref VARCHAR(60) NOT NULL,
            payment_status VARCHAR(30) NOT NULL DEFAULT 'completed',
            paid_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_payment_reservation (id_reservation),
            KEY idx_payment_artisan (id_artisan),
            KEY idx_payment_status (payment_status),
            CONSTRAINT fk_payment_reservation
                FOREIGN KEY (id_reservation) REFERENCES reservations(id_reservation)
                ON DELETE CASCADE,
            CONSTRAINT fk_payment_artisan
                FOREIGN KEY (id_artisan) REFERENCES artisans(id_artisan)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $ready = true;
}

function ensureTestCardsTable(PDO $db): void {
    static $ready = false;

    if ($ready) {
        return;
    }

    $db->exec("
        CREATE TABLE IF NOT EXISTS test_cards (
            id_card INT AUTO_INCREMENT PRIMARY KEY,
            card_holder VARCHAR(120) NOT NULL,
            card_number VARCHAR(19) NOT NULL,
            exp_month CHAR(2) NOT NULL,
            exp_year CHAR(4) NOT NULL,
            cvv VARCHAR(4) NOT NULL,
            card_brand VARCHAR(30) NOT NULL DEFAULT 'VISA',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_test_card_number (card_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $seed = $db->prepare("
        INSERT INTO test_cards (card_holder, card_number, exp_month, exp_year, cvv, card_brand, is_active)
        VALUES (?, ?, ?, ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE
            card_holder = VALUES(card_holder),
            exp_month = VALUES(exp_month),
            exp_year = VALUES(exp_year),
            cvv = VALUES(cvv),
            card_brand = VALUES(card_brand),
            is_active = 1
    ");

    // Deactivate all existing cards first, then reactivate only cards mapped to real tourists.
    $db->exec("UPDATE test_cards SET is_active = 0");

    $tourists = $db->query("
        SELECT id_utilisateur, prenom, nom
        FROM utilisateurs
        WHERE role = 'touriste' AND statut = 'actif'
        ORDER BY id_utilisateur ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $brands = ['VISA', 'MASTERCARD', 'VISA', 'DISCOVER'];
    $defaultYear = (int)date('Y') + 3;

    foreach ($tourists as $idx => $t) {
        $id = (int)$t['id_utilisateur'];
        $holder = trim(($t['prenom'] ?? '') . ' ' . ($t['nom'] ?? ''));
        if ($holder === '') {
            $holder = 'Tourist ' . $id;
        }

        // Deterministic 16-digit fake card per tourist user id.
        $cardNumber = '411111111111' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
        $expMonth = str_pad((string)(($id % 12) + 1), 2, '0', STR_PAD_LEFT);
        $expYear = (string)($defaultYear + ($id % 2));
        $cvv = str_pad((string)(100 + ($id % 900)), 3, '0', STR_PAD_LEFT);
        $brand = $brands[$idx % count($brands)];

        $seed->execute([$holder, $cardNumber, $expMonth, $expYear, $cvv, $brand]);
    }

    $ready = true;
}
