<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

include 'db.php';

// Station speichern
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['event_id']) && !empty($_POST['station_name'])) {
    $event_id = (int)$_POST['event_id'];
    $station = trim($_POST['station_name']);
    $insert = $db->prepare("INSERT INTO stations (event_id, name) VALUES (?, ?)");
    $insert->execute([$event_id, $station]);
    $success = "✅ Station erfolgreich hinzugefügt.";
}

// Veranstaltungen laden
$stmt = $db->query("SELECT id, name FROM events ORDER BY id DESC");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Station hinzufügen</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css" rel="stylesheet">
    <style>
        form {
            max-width: 500px;
            background: #fff;
            padding: 25px;
            margin-top: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        label {
            font-weight: bold;
            display: block;
            margin-top: 12px;
        }

input, select {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
    box-sizing: border-box;
    border: 1px solid #ccc;
    border-radius: 4px;
}

        .success {
            background: #d2f5d2;
            border: 1px solid #9cdb9c;
            color: #226622;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            max-width: 500px;
        }
    </style>
</head>
<body>
    <h1><span class="mdi mdi-plus"></span> Station hinzufügen</h1>

    <?php if ($success): ?>
        <div class="success"><?= $success ?></div>
    <?php endif; ?>

    <form method="post">
        <label for="event_id">Veranstaltung auswählen:</label>
        <select name="event_id" id="event_id" required>
            <option value="">-- Bitte auswählen --</option>
            <?php foreach ($events as $event): ?>
                <option value="<?= $event['id'] ?>">
                    <?= $event['id'] ?> – <?= htmlspecialchars($event['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="station_name">Name der Station:</label>
        <input type="text" name="station_name" id="station_name" required>

        <br><br>
        <button type="submit"><span class="mdi mdi-content-save"></span> Speichern</button>
		<a href="index.php"><button type="button"><span class="mdi mdi-backspace-outline"></span> Zurück</button></a>
    </form>
    <?php include 'footer.php'; ?>
</body>
</html>
