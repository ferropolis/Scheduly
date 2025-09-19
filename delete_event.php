<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    echo "❌ Ungültige ID.";
    exit;
}

// Optional: zuerst abhängige Einträge löschen
$db->prepare("DELETE FROM signups WHERE event_id = ?")->execute([$id]);
$db->prepare("DELETE FROM stations WHERE event_id = ?")->execute([$id]);

// Dann Event löschen
$stmt = $db->prepare("DELETE FROM events WHERE id = ?");
$stmt->execute([$id]);

header("Location: index.php");
exit;
