<?php

require_once __DIR__ . '/engagement.php';

function ensureActivityCapacityColumns(PDO $db): void {
    static $ready = false;
    if ($ready) return;

    $changes = [
        "ALTER TABLE activites ADD COLUMN capacite INT NULL DEFAULT NULL",
        "ALTER TABLE activites ADD COLUMN places_reservees INT NOT NULL DEFAULT 0",
        "ALTER TABLE activites ADD COLUMN availability_status VARCHAR(20) NOT NULL DEFAULT 'disponible'",
    ];

    foreach ($changes as $sql) {
        try {
            $db->exec($sql);
        } catch (Throwable $e) {
            // Ignore duplicate-column errors on existing databases.
        }
    }

    $db->exec("
        UPDATE activites
        SET
            capacite = CASE
                WHEN capacite IS NULL OR capacite < 1 THEN GREATEST(COALESCE(max_participants, 1), 1)
                ELSE capacite
            END
    ");

    $db->exec("
        UPDATE activites
        SET
            max_participants = CASE
                WHEN max_participants IS NULL OR max_participants < 1 THEN GREATEST(COALESCE(capacite, 1), 1)
                ELSE max_participants
            END
    ");

    $db->exec("
        UPDATE activites
        SET
            places_reservees = CASE
                WHEN places_reservees < 0 THEN 0
                WHEN places_reservees > COALESCE(capacite, max_participants, 1) THEN COALESCE(capacite, max_participants, 1)
                ELSE places_reservees
            END
    ");

    $db->exec("
        UPDATE activites
        SET availability_status = CASE
            WHEN COALESCE(capacite, max_participants, 1) - COALESCE(places_reservees, 0) <= 0 THEN 'complet'
            ELSE 'disponible'
        END
    ");

    $ready = true;
}

function loadActivityForAvailability(PDO $db, int $activityId, bool $forUpdate = false): ?array {
    ensureActivityCapacityColumns($db);
    $sql = "
        SELECT
            id_activite,
            id_artisan,
            titre,
            statut,
            COALESCE(capacite, max_participants, 1) AS capacite,
            COALESCE(places_reservees, 0) AS places_reservees,
            availability_status
        FROM activites
        WHERE id_activite = ?
        LIMIT 1
    ";
    if ($forUpdate) $sql .= " FOR UPDATE";
    $stmt = $db->prepare($sql);
    $stmt->execute([$activityId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function remainingPlacesFromRow(array $activity): int {
    $cap = max(0, (int)($activity['capacite'] ?? 0));
    $reserved = max(0, (int)($activity['places_reservees'] ?? 0));
    return max(0, $cap - $reserved);
}

function syncActivityAvailability(PDO $db, int $activityId): ?array {
    $activity = loadActivityForAvailability($db, $activityId, true);
    if (!$activity) return null;

    $capacity = max(1, (int)$activity['capacite']);
    $reserved = max(0, min(activeOccupiedParticipantsCount($db, $activityId), $capacity));
    $remaining = max(0, $capacity - $reserved);
    $status = $remaining <= 0 ? 'complet' : 'disponible';

    $stmt = $db->prepare("
        UPDATE activites
        SET
            capacite = ?,
            max_participants = ?,
            places_reservees = ?,
            availability_status = ?
        WHERE id_activite = ?
    ");
    $stmt->execute([$capacity, $capacity, $reserved, $status, $activityId]);

    $activity['capacite'] = $capacity;
    $activity['max_participants'] = $capacity;
    $activity['places_reservees'] = $reserved;
    $activity['availability_status'] = $status;
    $activity['places_restantes'] = $remaining;
    return $activity;
}

function adjustReservedPlaces(PDO $db, int $activityId, int $deltaParticipants): ?array {
    $activity = loadActivityForAvailability($db, $activityId, true);
    if (!$activity) return null;

    $capacity = max(1, (int)$activity['capacite']);
    $reserved = max(0, (int)$activity['places_reservees']);
    $newReserved = $reserved + $deltaParticipants;
    if ($newReserved < 0) $newReserved = 0;
    if ($newReserved > $capacity) {
        throw new RuntimeException('Not enough available places for this activity');
    }

    $status = ($capacity - $newReserved) <= 0 ? 'complet' : 'disponible';
    $stmt = $db->prepare("
        UPDATE activites
        SET
            capacite = ?,
            max_participants = ?,
            places_reservees = ?,
            availability_status = ?
        WHERE id_activite = ?
    ");
    $stmt->execute([$capacity, $capacity, $newReserved, $status, $activityId]);

    return [
        'id_activite' => $activityId,
        'capacite' => $capacity,
        'places_reservees' => $newReserved,
        'places_restantes' => max(0, $capacity - $newReserved),
        'availability_status' => $status,
        'titre' => $activity['titre'] ?? '',
    ];
}

function confirmedParticipantsCount(PDO $db, int $activityId): int {
    return activeOccupiedParticipantsCount($db, $activityId);
}

function activeOccupiedParticipantsCount(PDO $db, int $activityId): int {
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(nb_participants), 0)
        FROM reservations
        WHERE id_activite = ?
          AND statut = 'confirmee'
    ");
    $stmt->execute([$activityId]);
    return (int)$stmt->fetchColumn();
}

function revalidatePendingReservations(PDO $db, int $activityId, string $reason = 'capacity_updated'): array {
    ensureNotificationsTable($db);
    $activity = syncActivityAvailability($db, $activityId);
    if (!$activity) {
        return ['cancelled' => 0, 'remaining_after' => 0];
    }

    $available = max(0, (int)$activity['places_restantes']);

    $pendingStmt = $db->prepare("
        SELECT
            r.id_reservation,
            r.id_touriste,
            r.nb_participants,
            a.titre AS activite_titre
        FROM reservations r
        JOIN activites a ON a.id_activite = r.id_activite
        WHERE r.id_activite = ?
          AND r.statut = 'en_attente'
        ORDER BY r.date_reservation ASC, r.id_reservation ASC
    ");
    $pendingStmt->execute([$activityId]);
    $pending = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

    $cancelled = 0;
    foreach ($pending as $row) {
        $need = max(1, (int)$row['nb_participants']);
        if ($need <= $available) {
            $available -= $need;
            continue;
        }

        $upd = $db->prepare("UPDATE reservations SET statut = 'annulee' WHERE id_reservation = ? AND statut = 'en_attente'");
        $upd->execute([(int)$row['id_reservation']]);
        if ($upd->rowCount() > 0) {
            $cancelled++;
            createNotification(
                $db,
                (int)$row['id_touriste'],
                'booking_cancelled',
                'Reservation Cancelled',
                'Your pending reservation for "' . ($row['activite_titre'] ?? 'activity') . '" was cancelled because not enough places remain.',
                'tuncover-tourist.html',
                (int)$row['id_reservation']
            );
        }
    }

    return ['cancelled' => $cancelled, 'remaining_after' => $available];
}

function cancelReservationsForActivity(PDO $db, int $activityId, string $messagePrefix = 'This activity is no longer available.'): int {
    ensureNotificationsTable($db);
    ensurePaymentsTable($db);

    $stmt = $db->prepare("
        SELECT
            r.id_reservation,
            r.id_touriste,
            r.statut,
            a.titre AS activite_titre,
            COALESCE(p.payment_status, 'pending') AS payment_status
        FROM reservations r
        JOIN activites a ON a.id_activite = r.id_activite
        LEFT JOIN payments p ON p.id_reservation = r.id_reservation
        WHERE r.id_activite = ?
          AND r.statut IN ('en_attente', 'confirmee')
    ");
    $stmt->execute([$activityId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $count = 0;
    foreach ($rows as $row) {
        $upd = $db->prepare("UPDATE reservations SET statut = 'annulee' WHERE id_reservation = ?");
        $upd->execute([(int)$row['id_reservation']]);
        $count += $upd->rowCount();

        if (($row['payment_status'] ?? 'pending') === 'completed') {
            $db->prepare("UPDATE payments SET payment_status = 'refunded', updated_at = NOW() WHERE id_reservation = ?")
               ->execute([(int)$row['id_reservation']]);
        }

        createNotification(
            $db,
            (int)$row['id_touriste'],
            'booking_cancelled',
            'Reservation Cancelled',
            $messagePrefix . ' Reservation for "' . ($row['activite_titre'] ?? 'activity') . '" was cancelled automatically.',
            'tuncover-tourist.html',
            (int)$row['id_reservation']
        );
    }

    return $count;
}
