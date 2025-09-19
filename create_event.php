<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

include 'db.php';

// Nach dem Absenden speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $start = $_POST['start_date'] . ' ' . $_POST['start_time'];
    $end = $_POST['end_date'] . ' ' . $_POST['end_time'];
    $token = bin2hex(random_bytes(16));

    $stmt = $db->prepare("INSERT INTO events (name, start, end, url_token) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $start, $end, $token]);

    // Weiterleitung mit Hinweis
    header("Location: add_station.php?token=$token");
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>➕ Neue Veranstaltung anlegen</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css" rel="stylesheet">
    <style>
        form {
            max-width: 600px;
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
        }

        h1 {
            margin-bottom: 10px;
        }

        .info {
            background: #f0f8ff;
            border: 1px solid #cce;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            max-width: 600px;
        }
    </style>
</head>
<body>
    <h1><span class="mdi mdi-plus"></span> Veranstaltung erstellen</h1>

    <div class="info">
        <p>Bitte gib den Namen der Veranstaltung sowie den Zeitraum an. Du wirst anschließend zur Stationserstellung weitergeleitet.</p>
    </div>

    <form method="POST">
        <label for="name"><span class="mdi mdi-calendar"></span> Name der Veranstaltung:</label>
        <input type="text" name="name" id="name" required>

        <label for="start_date"><span class="mdi mdi-calendar-start"></span> Startdatum:</label>
        <input type="date" name="start_date" required>

        <label for="start_time"><span class="mdi mdi-clock-start"></span> Startzeit:</label>
        <input type="time" name="start_time" step="900" required>

        <label for="end_date"><span class="mdi mdi-calendar-end"></span> Enddatum:</label>
        <input type="date" name="end_date" required>

        <label for="end_time"><span class="mdi mdi-clock-end"></span> Endzeit:</label>
        <input type="time" name="end_time" step="900" required>

        <br><br>
        <button type="submit"><span class="mdi mdi-content-save"></span> Veranstaltung anlegen</button>
    </form>
</body>
</html>
