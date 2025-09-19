<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';

$success = '';
$error = '';

// Prüfen und Eintrag speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $participating = $_POST['participates'] ?? '1';  // aus Formular: 1 = ja, 0 = nein
    $token = $_POST['token'] ?? '';

    if ($participating === '1') {
        $station_id = $_POST['station_id'] ?? '';
        $shift_date = $_POST['shift_date'] ?? '';
        $shift_start = $_POST['shift_start'] ?? '';
        $shift_end = $_POST['shift_end'] ?? '';
        $shirt_size = $_POST['shirt_size'] ?? null;

        if ($shift_start >= $shift_end) {
            $error = "Die Startzeit muss vor der Endzeit liegen.";
        } elseif ($name && $event_id && $station_id && $shift_date && $shift_start && $shift_end) {
            $stmt = $db->prepare("INSERT INTO signups (event_id, name, station_id, shift_date, shift_start, shift_end, shirt_size, participating) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$event_id, $name, $station_id, $shift_date, $shift_start, $shift_end, $shirt_size]);

            $success = " Danke, <strong>" . htmlspecialchars($name) . "</strong>! Dein Eintrag wurde gespeichert.";
        } else {
            $error = "Bitte fülle alle Felder korrekt aus.";
        }

    } elseif ($participating === '0') {
        if ($name && $event_id) {
            $stmt = $db->prepare("INSERT INTO signups (event_id, name, participating) VALUES (?, ?, 0)");
            $stmt->execute([$event_id, $name]);

            $success = "Danke, <strong>" . htmlspecialchars($name) . "</strong>. Wir haben deine Absage gespeichert.";
        } else {
            $error = "Bitte gib deinen Namen an.";
        }

    } else {
        $error = "Ungültige Teilnahme-Auswahl.";
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Eintragung abgeschlossen</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css" rel="stylesheet">
    <style>
        .message {
            max-width: 600px;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }

        .success {
            background: #d2f5d2;
            border: 1px solid #9cdb9c;
            color: #226622;
        }

        .error {
            background: #ffe0e0;
            border: 1px solid #f5bcbc;
            color: #a00;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            background-color: #1976d2;
            color: white;
            padding: 10px 16px;
            text-decoration: none;
            border-radius: 6px;
        }

        .back-link:hover {
            background-color: #125ca1;
        }
    </style>
</head>
<body>
    <h1><i class="mdi mdi-calendar-month-outline"></i> Teilnahme</h1>

    <?php if ($success): ?>
        <div class="message success">
            <?= $success ?>
        </div>
    <?php elseif ($error): ?>
        <div class="message error"><?= $error ?></div>
    <?php endif; ?>

    <a class="button" href="event.php?token=<?= urlencode($token) ?>"><i class="mdi mdi-account-plus"></i> Weitere Schicht eintragen</a> <a class="button" href="view_by_user.php?token=<?= urlencode($token) ?>"><i class="mdi mdi-account-plus"></i> Zur Übersicht</a>
<?php include 'footer.php'; ?>
</body>
</html>
