<?php
require 'php/config/db.php';
try {
  $db = getDB();
  foreach (['reservations','artisans','utilisateurs'] as $t) {
    echo "TABLE:$t\n";
    foreach ($db->query("SHOW COLUMNS FROM $t")->fetchAll() as $r) {
      echo $r['Field'] . '|' . $r['Type'] . "\n";
    }
  }
} catch (Throwable $e) { echo 'ERR:' . $e->getMessage(); }
