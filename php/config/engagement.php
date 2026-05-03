<?php

require_once __DIR__ . '/db.php';

function ensureNotificationsTable(PDO $db): void {
    static $ready = false;
    if ($ready) return;

    $db->exec("
        CREATE TABLE IF NOT EXISTS notifications (
            id_notification INT AUTO_INCREMENT PRIMARY KEY,
            id_utilisateur INT NOT NULL,
            type VARCHAR(40) NOT NULL DEFAULT 'general',
            title VARCHAR(180) NOT NULL,
            message TEXT NOT NULL,
            link_url VARCHAR(255) DEFAULT NULL,
            reference_id INT DEFAULT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_notif_user_read (id_utilisateur, is_read),
            KEY idx_notif_created (created_at),
            CONSTRAINT fk_notif_user
              FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id_utilisateur)
              ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $ready = true;
}

function ensureChatMessagesTable(PDO $db): void {
    static $ready = false;
    if ($ready) return;

    $db->exec("
        CREATE TABLE IF NOT EXISTS chat_messages (
            id_chat INT AUTO_INCREMENT PRIMARY KEY,
            id_reservation INT NOT NULL,
            id_sender INT NOT NULL,
            id_receiver INT NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_chat_reservation (id_reservation),
            KEY idx_chat_sender (id_sender),
            KEY idx_chat_receiver_read (id_receiver, is_read),
            CONSTRAINT fk_chat_reservation
              FOREIGN KEY (id_reservation) REFERENCES reservations(id_reservation)
              ON DELETE CASCADE,
            CONSTRAINT fk_chat_sender
              FOREIGN KEY (id_sender) REFERENCES utilisateurs(id_utilisateur)
              ON DELETE CASCADE,
            CONSTRAINT fk_chat_receiver
              FOREIGN KEY (id_receiver) REFERENCES utilisateurs(id_utilisateur)
              ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $ready = true;
}

function ensureDirectMessagesTable(PDO $db): void {
    static $ready = false;
    if ($ready) return;

    $db->exec("
        CREATE TABLE IF NOT EXISTS direct_messages (
            id_direct_message INT AUTO_INCREMENT PRIMARY KEY,
            id_sender INT NOT NULL,
            id_receiver INT NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_dm_sender (id_sender),
            KEY idx_dm_receiver_read (id_receiver, is_read),
            KEY idx_dm_created (created_at),
            CONSTRAINT fk_dm_sender
              FOREIGN KEY (id_sender) REFERENCES utilisateurs(id_utilisateur)
              ON DELETE CASCADE,
            CONSTRAINT fk_dm_receiver
              FOREIGN KEY (id_receiver) REFERENCES utilisateurs(id_utilisateur)
              ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $ready = true;
}

function ensureArtisanPortfolioTable(PDO $db): void {
    static $ready = false;
    if ($ready) return;

    $db->exec("
        CREATE TABLE IF NOT EXISTS artisan_portfolio (
            id_portfolio INT AUTO_INCREMENT PRIMARY KEY,
            id_artisan INT NOT NULL,
            album_key VARCHAR(120) DEFAULT NULL,
            album_title VARCHAR(180) DEFAULT NULL,
            title VARCHAR(180) NOT NULL,
            media_type ENUM('image','video') NOT NULL DEFAULT 'image',
            media_url VARCHAR(600) NOT NULL,
            thumbnail_url VARCHAR(600) DEFAULT NULL,
            display_order INT NOT NULL DEFAULT 0,
            is_public TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY idx_portfolio_artisan (id_artisan),
            KEY idx_portfolio_public (is_public),
            CONSTRAINT fk_portfolio_artisan
              FOREIGN KEY (id_artisan) REFERENCES artisans(id_artisan)
              ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    try {
        $db->exec("ALTER TABLE artisan_portfolio ADD COLUMN album_key VARCHAR(120) DEFAULT NULL AFTER id_artisan");
    } catch (Throwable $e) {
    }
    try {
        $db->exec("ALTER TABLE artisan_portfolio ADD COLUMN album_title VARCHAR(180) DEFAULT NULL AFTER album_key");
    } catch (Throwable $e) {
    }

    $ready = true;
}

function createNotification(
    PDO $db,
    int $userId,
    string $type,
    string $title,
    string $message,
    ?string $linkUrl = null,
    ?int $referenceId = null
): void {
    try {
        $stmt = $db->prepare("
            INSERT INTO notifications (id_utilisateur, type, title, message, link_url, reference_id, is_read, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 0, NOW())
        ");
        $stmt->execute([$userId, $type, $title, $message, $linkUrl, $referenceId]);
    } catch (Throwable $e) {
        // Notifications are best-effort and must never break main booking/payment flows.
    }
}
