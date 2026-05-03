<?php

require 'php/config/db.php';
try {
  $db = getDB();
  $tables = ['avis','reservations','activites','artisans'];
  foreach ($tables as $t) {
    echo "TABLE:$t\n";
    $rows = $db->query("SHOW COLUMNS FROM $t")->fetchAll();
    foreach ($rows as $r) {
      echo $r['Field'] . '|' . $r['Type'] . "\n";
    }
  }
} catch (Throwable $e) {
  echo 'ERR:' . $e->getMessage();
}


