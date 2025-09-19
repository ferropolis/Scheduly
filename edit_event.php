<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

include 'db.php';

$event_id = $_GET['id'] ?? null;
if (!$event_id) {
    echo "❌ Ungültige Anfrage.";
    exit;
}

$stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    echo "❌ Veranstaltung nicht gefunden.";
    exit;
}

// Formular wurde abgeschickt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $start_date = $_POST['start'];
    $end_date = $_POST['end'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $enable_shirt_size = isset($_POST['enable_shirt_size']) ? 1 : 0;

    $start = $start_date . ' ' . $start_time;
    $end = $end_date . ' ' . $end_time;

    try {
        $update = $db->prepare("UPDATE events SET name = ?, start = ?, end = ?, enable_shirt_size = ? WHERE id = ?");
        $update->execute([$name, $start, $end, $enable_shirt_size, $event_id]);
        header("Location: index.php");
        exit;
    } catch (PDOException $e) {
        echo "❌ Fehler beim Speichern: " . $e->getMessage();
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Veranstaltung bearbeiten</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css" rel="stylesheet">
    <style>
        .toggle-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 20px;
        }
        .switch {
            position: relative;
            display: inline-block;
            width: 48px;
            height: 24px;
        }
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0; left: 0; right: 0; bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .slider {
            background-color: #66bb6a;
        }
        input:checked + .slider:before {
            transform: translateX(24px);
        }
        .switch-label {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1><span class="mdi mdi-pencil"></span> Veranstaltung bearbeiten</h1>

    <form method="POST" class="form-card" style="max-width: 500px;">
        <label for="name"><span class="mdi mdi-calendar"></span> Name der Veranstaltung:</label>
        <input type="text" name="name" id="name" value="<?= htmlspecialchars($event['name']) ?>" required>

        <label for="start_date"><span class="mdi mdi-calendar-start"></span> Startdatum:</label>
        <input type="date" name="start" id="start" value="<?= substr($event['start'], 0, 10) ?>" required>

        <label for="start_time"><span class="mdi mdi-clock-start"></span> Startzeit:</label>
        <input type="time" name="start_time" id="start_time" value="<?= date('H:i', strtotime($event['start'])) ?>" required>

        <label for="end_date"><span class="mdi mdi-calendar-end"></span> Enddatum:</label>
        <input type="date" name="end" id="end" value="<?= substr($event['end'], 0, 10) ?>" required>

        <label for="end_time"><span class="mdi mdi-clock-end"></span> Endzeit:</label>
        <input type="time" name="end_time" id="end_time" value="<?= date('H:i', strtotime($event['end'])) ?>" required>

        <div class="toggle-wrapper">
            <label class="switch">
                <input type="checkbox" name="enable_shirt_size" value="1" <?= $event['enable_shirt_size'] ? 'checked' : '' ?>>
                <span class="slider"></span>
            </label>
            <span class="switch-label"><span class="mdi mdi-tshirt-crew"> T-Shirt-Größe erfassen</span>
        </div>

        <br><br>
        <button type="submit"><span class="mdi mdi-content-save"></span> Änderungen speichern</button>
        <a href="index.php"><button type="button"><span class="mdi mdi-backspace-outline"></span> Abbrechen</button></a>
    </form>
<?php include 'footer.php'; ?>
</body>
</html>
