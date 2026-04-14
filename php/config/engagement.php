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
