<?php
require 'php/config/db.php';
try {
  $db = getDB();
  $rows = $db->query("SELECT id_activite, titre, ville FROM activites WHERE statut='publie' ORDER BY date_creation DESC, id_activite DESC LIMIT 6")->fetchAll();
  foreach ($rows as $r) {
    echo $r['id_activite'] . '|' . $r['titre'] . '|' . $r['ville'] . "\n";
  }
} catch (Throwable $e) {
  echo 'ERR:' . $e->getMessage();
}
