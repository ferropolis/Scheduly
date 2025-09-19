<?php
include 'db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$event_id = null;

if (isset($_GET['event_id'])) {
    $event_id = (int)$_GET['event_id'];
} elseif (isset($_GET['token'])) {
    $stmt = $db->prepare("SELECT id FROM events WHERE url_token = ?");
    $stmt->execute([$_GET['token']]);
    $eventFound = $stmt->fetch();
    if ($eventFound && isset($eventFound['id'])) {
        $event_id = $eventFound['id'];
    }
}

if (!$event_id) {
    echo "❌ Kein gültiges Event gefunden.";
    exit;
}

// Aktionen speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descriptions = $_POST['description'] ?? [];
    $starts = $_POST['start_hour'] ?? [];
    $ends = $_POST['end_hour'] ?? [];

    $db->prepare("DELETE FROM event_schedule WHERE event_id = ?")->execute([$event_id]);

    $insert = $db->prepare("INSERT INTO event_schedule (event_id, start_hour, end_hour, description) VALUES (?, ?, ?, ?)");

    foreach ($descriptions as $i => $desc) {
        if ($desc !== '' && is_numeric($starts[$i]) && is_numeric($ends[$i])) {
            $insert->execute([
                $event_id,
                (int)$starts[$i],
                (int)$ends[$i],
                trim($desc)
            ]);
        }
    }

    header("Location: edit_event_schedule.php?event_id=" . urlencode($event_id));
    exit;
}

// Eventname abrufen
$eventStmt = $db->prepare("SELECT name FROM events WHERE id = ?");
$eventStmt->execute([$event_id]);
$eventData = $eventStmt->fetch(PDO::FETCH_ASSOC);

if (!$eventData || !isset($eventData['name'])) {
    echo "❌ Event konnte nicht geladen werden.";
    exit;
}

// Bestehende Einträge auslesen
$schedule = $db->prepare("SELECT * FROM event_schedule WHERE event_id = ? ORDER BY start_hour");
$schedule->execute([$event_id]);
$entries = $schedule->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Aktionen bearbeiten</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css" rel="stylesheet">
    <style>
        .action-table select,
        .action-table input[type="text"] {
    width: 100%;
    box-sizing: border-box;
        }
        .delete-btn {
            background-color: #d9534f;
            color: white;
            border: none;
            padding: 5px 8px;
            border-radius: 4px;
            cursor: pointer;
        }
        .delete-btn:hover {
            background-color: #c9302c;
        }
    </style>
</head>
<body>
    <h1><span class="mdi mdi-hammer-screwdriver"></span> Aktionen für „<?= htmlspecialchars($eventData['name']) ?>“</h1>

    <form method="POST">
        <table class="action-table">
            <thead>
                <tr>
                    <th>Von</th>
                    <th>Bis</th>
                    <th>Aktion</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="schedule-body">
                <?php
                function render_row($start = '', $end = '', $desc = '') {
                    echo '<tr>';
                    echo '<td><select name="start_hour[]">';
                    for ($h = 0; $h < 24; $h++) {
                        $label = sprintf('%02d:00', $h);
                        $selected = ($h == $start) ? 'selected' : '';
                        echo "<option value=\"$h\" $selected>$label</option>";
                    }
                    echo '</select></td>';

                    echo '<td><select name="end_hour[]">';
                    for ($h = 1; $h <= 24; $h++) {
                        $label = sprintf('%02d:00', $h % 24);
                        $selected = ($h == $end) ? 'selected' : '';
                        echo "<option value=\"$h\" $selected>$label</option>";
                    }
                    echo '</select></td>';

                    echo '<td><input type="text" name="description[]" value="' . htmlspecialchars($desc) . '" style="width:100%"></td>';
                    echo '<td><button type="button" class="delete-btn" onclick="deleteRow(this)"><span class="mdi mdi-delete-forever"></span></button></td>';
                    echo '</tr>';
                }

                if (count($entries) === 0) {
                    render_row();
                } else {
                    foreach ($entries as $entry) {
                        render_row($entry['start_hour'], $entry['end_hour'], $entry['description']);
                    }
                }
                ?>
            </tbody>
        </table>

        <br>
        <button type="button" onclick="addRow()"><span class="mdi mdi-plus"></span> Weitere Aktion hinzufügen</button>
        <br><br>
        <button type="submit"><span class="mdi mdi-content-save"></span> Speichern</button>
        <a href="index.php"><button type="button"><span class="mdi mdi-backspace-outline"></span> Zurück</button></a>
    </form>

    <script>
        function addRow() {
            const tbody = document.getElementById('schedule-body');
            const tr = document.createElement('tr');

            const td1 = document.createElement('td');
            const selectStart = document.createElement('select');
            selectStart.name = "start_hour[]";
            for (let h = 0; h < 24; h++) {
                const opt = document.createElement('option');
                opt.value = h;
                opt.text = ("0" + h).slice(-2) + ":00";
                selectStart.appendChild(opt);
            }
            td1.appendChild(selectStart);

            const td2 = document.createElement('td');
            const selectEnd = document.createElement('select');
            selectEnd.name = "end_hour[]";
            for (let h = 1; h <= 24; h++) {
                const opt = document.createElement('option');
                opt.value = h;
                opt.text = ("0" + (h % 24)).slice(-2) + ":00";
                selectEnd.appendChild(opt);
            }
            td2.appendChild(selectEnd);

            const td3 = document.createElement('td');
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'description[]';
            input.style.width = '100%';
            td3.appendChild(input);

            const td4 = document.createElement('td');
            const delBtn = document.createElement('button');
            delBtn.innerHTML = '<span class="mdi mdi-delete-forever"></span>';
            delBtn.className = 'delete-btn';
            delBtn.type = 'button';
            delBtn.onclick = function () {
                tr.remove();
            };
            td4.appendChild(delBtn);

            tr.appendChild(td1);
            tr.appendChild(td2);
            tr.appendChild(td3);
            tr.appendChild(td4);
            tbody.appendChild(tr);
        }

        function deleteRow(btn) {
            const row = btn.closest('tr');
            row.remove();
        }
    </script>
    <?php include 'footer.php'; ?>
</body>
</html>
