<?php

header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/payments.php';
require_once __DIR__ . '/../config/pdf_simple.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin only']);
    exit;
}

$db = getDB();
ensurePaymentsTable($db);

$statut = trim((string)($_GET['statut'] ?? ''));
$payment = trim((string)($_GET['payment'] ?? ''));
$q = trim((string)($_GET['q'] ?? ''));
$dateFrom = trim((string)($_GET['date_from'] ?? ''));
$dateTo = trim((string)($_GET['date_to'] ?? ''));
$format = strtolower(trim((string)($_GET['format'] ?? 'csv')));

if ($dateFrom !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
    echo json_encode(['success' => false, 'error' => 'Invalid date_from']);
    exit;
}
if ($dateTo !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
    echo json_encode(['success' => false, 'error' => 'Invalid date_to']);
    exit;
}

$sql = "
    SELECT
        r.id_reservation,
        r.date_reservation,
        r.date_activite,
        r.heure_activite,
        r.nb_participants,
        r.montant_total,
        r.statut,
        COALESCE(p.payment_status, 'pending') AS payment_status,
        p.transaction_ref,
        p.paid_at,
        p.artisan_amount,
        p.platform_fee,
        p.amount AS paid_amount,
        a.titre AS activite_titre,
        a.ville,
        CONCAT(ut.prenom, ' ', ut.nom) AS tourist_nom,
        ut.email AS tourist_email,
        CONCAT(ua.prenom, ' ', ua.nom) AS artisan_nom,
        DATE(COALESCE(p.paid_at, r.date_reservation)) AS report_date
    FROM reservations r
    JOIN activites a ON r.id_activite = a.id_activite
    JOIN utilisateurs ut ON r.id_touriste = ut.id_utilisateur
    JOIN artisans ar ON a.id_artisan = ar.id_artisan
    JOIN utilisateurs ua ON ar.id_utilisateur = ua.id_utilisateur
    LEFT JOIN payments p ON p.id_reservation = r.id_reservation
    WHERE 1=1
";
$params = [];

if ($statut !== '') {
    $sql .= " AND r.statut = ?";
    $params[] = $statut;
}
if ($payment === 'paid') {
    $sql .= " AND COALESCE(p.payment_status,'pending') = 'completed'";
}
if ($payment === 'unpaid') {
    $sql .= " AND COALESCE(p.payment_status,'pending') <> 'completed'";
}
if ($q !== '') {
    $sql .= " AND (ut.nom LIKE ? OR ut.prenom LIKE ? OR a.titre LIKE ? OR ua.nom LIKE ? OR ua.prenom LIKE ?)";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($dateFrom !== '') {
    $sql .= " AND DATE(COALESCE(p.paid_at, r.date_reservation)) >= ?";
    $params[] = $dateFrom;
}
if ($dateTo !== '') {
    $sql .= " AND DATE(COALESCE(p.paid_at, r.date_reservation)) <= ?";
    $params[] = $dateTo;
}

$sql .= " ORDER BY r.date_reservation DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($format === 'pdf') {
    $lines = [];
    $lines[] = 'TunCover Transactions Report';
    $lines[] = 'Generated at: ' . date('Y-m-d H:i:s');
    $lines[] = 'Filters -> status: ' . ($statut !== '' ? $statut : 'all')
        . ', payment: ' . ($payment !== '' ? $payment : 'all')
        . ', from: ' . ($dateFrom !== '' ? $dateFrom : '-')
        . ', to: ' . ($dateTo !== '' ? $dateTo : '-');
    $lines[] = 'Total records: ' . count($rows);
    $lines[] = str_repeat('-', 110);

    foreach ($rows as $r) {
        $lines[] = sprintf(
            '#%s | %s | %s | Tourist: %s | Artisan: %s | %s TND | %s | %s',
            $r['id_reservation'],
            $r['date_activite'] ?: '-',
            $r['activite_titre'] ?: '-',
            $r['tourist_nom'] ?: '-',
            $r['artisan_nom'] ?: '-',
            number_format((float)($r['montant_total'] ?? 0), 2, '.', ''),
            $r['statut'] ?: '-',
            $r['payment_status'] ?: 'pending'
        );
        if (!empty($r['transaction_ref'])) {
            $lines[] = '   Transaction: ' . $r['transaction_ref'] . ' | Paid at: ' . ($r['paid_at'] ?: '-');
        }
    }

    $pdf = buildSimpleTextPdf($lines, 'TunCover Transactions Report');
    $filename = 'tuncover-transactions-' . date('Ymd-His') . '.pdf';
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit;
}

if ($format === 'csv') {
    $filename = 'tuncover-transactions-' . date('Ymd-His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'Reservation ID',
        'Report Date',
        'Reservation Date',
        'Activity Date',
        'Activity Time',
        'Tourist',
        'Tourist Email',
        'Artisan',
        'Activity',
        'City',
        'Guests',
        'Amount TND',
        'Reservation Status',
        'Payment Status',
        'Transaction Ref',
        'Paid At',
        'Platform Fee TND',
        'Artisan Amount TND'
    ]);

    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id_reservation'],
            $r['report_date'],
            $r['date_reservation'],
            $r['date_activite'],
            $r['heure_activite'],
            $r['tourist_nom'],
            $r['tourist_email'],
            $r['artisan_nom'],
            $r['activite_titre'],
            $r['ville'],
            $r['nb_participants'],
            number_format((float)($r['montant_total'] ?? 0), 2, '.', ''),
            $r['statut'],
            $r['payment_status'],
            $r['transaction_ref'],
            $r['paid_at'],
            number_format((float)($r['platform_fee'] ?? 0), 2, '.', ''),
            number_format((float)($r['artisan_amount'] ?? 0), 2, '.', ''),
        ]);
    }
    fclose($out);
    exit;
}

echo json_encode(['success' => true, 'count' => count($rows), 'rows' => $rows]);

