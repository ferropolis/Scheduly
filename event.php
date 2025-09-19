<?php
include 'db.php';

$token = $_GET['token'] ?? '';

// Veranstaltung laden
$eventStmt = $db->prepare("SELECT * FROM events WHERE url_token = ?");
$eventStmt->execute([$token]);
$event = $eventStmt->fetch();

if (!$event) {
    echo "<div class='error'>❌ Veranstaltung nicht gefunden.</div>";
    exit;
}

$enable_tshirts = (bool)($event['enable_shirt_size'] ?? false);

// Stationen laden
$stationStmt = $db->prepare("SELECT * FROM stations WHERE event_id = ?");
$stationStmt->execute([$event['id']]);
$stations = $stationStmt->fetchAll();

// Event-Zeitfenster vorbereiten
$eventStart = new DateTime($event['start']);
$eventEnd = new DateTime($event['end']);
$startTimeOnly = $eventStart->format('H:i');
$endTimeOnly = $eventEnd->format('H:i');

// Gültige Datumswerte erzeugen
$validDates = [];
$period = new DatePeriod($eventStart, new DateInterval('P1D'), (clone $eventEnd)->modify('+1 second'));
foreach ($period as $date) {
    $validDates[] = $date->format('Y-m-d');
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($event['name']) ?> – Eintragen</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css" rel="stylesheet">
    <style>
input, select {
    width: 100%;
    padding: 8px;
    margin-top: 5px;
    box-sizing: border-box;
    border: 1px solid #ccc;
    border-radius: 4px;
}
        .optional-block {
            margin-top: 20px;
            border: 1px dashed #ccc;
            padding: 12px;
            background: #f9f9f9;
        }
    </style>
    <script>
        const startTime = '<?= $startTimeOnly ?>';
        const endTime = '<?= $endTimeOnly ?>';
        const firstDay = '<?= $eventStart->format('Y-m-d') ?>';
        const lastDay = '<?= $eventEnd->format('Y-m-d') ?>';

        function updateTimeOptions() {
            const date = document.getElementById('shift_date').value;
            let start = '00:00', end = '23:00';

            if (date === firstDay && date === lastDay) {
                start = startTime;
                end = endTime;
            } else if (date === firstDay) {
                start = startTime;
            } else if (date === lastDay) {
                end = endTime;
            }

            fetch(`generate_times.php?start=${start}&end=${end}`)
                .then(res => res.text())
                .then(options => {
                    document.getElementById('shift_start').innerHTML = options;
                    document.getElementById('shift_end').innerHTML = options;
                });
        }

        function toggleParticipationForm() {
            const participation = document.getElementById('participates').value;
            const detailForm = document.getElementById('full_participation_fields');
            detailForm.style.display = (participation === '1') ? 'block' : 'none';
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateTimeOptions();
            toggleParticipationForm();
        });
    </script>
</head>
<body>
    <h1><?= htmlspecialchars($event['name']) ?> – Teilnehmer-Eintragung</h1>

    <div class="description">
		<p>Schön, dass du dich einträgst! Gib an, ob du <strong>teilnehmen kannst</strong> oder <strong>leider nicht dabei</strong> bist.</p>
        <p>Aber wenn du dabei bist, bachte folgendes!</p>
        <ul>
            <li>Gib bitte deinen Namen an</li>
            <li>Wähle aus, an welcher <strong>Station</strong> du mitarbeiten möchtest</li>
            <li>Such dir ein <strong>Datum</strong> und eine passende <strong>Uhrzeit</strong></li>
			<li>Solltest du <strong>mehrere Personen</strong> eintragen wollen, so fülle das Formular gern mehrmals aus.</li>
        </ul>
        <p>Wenn du vorab schauen möchtest, wo noch Heldenpower gebraucht wird, kannst du gerne <a href="view_by_station.php?token=<?= urlencode($token) ?>">einen Blick in die aktuelle Planung werfen</a>.</p>
    </div>

    <form class="form-card" method="POST" action="signup.php">
        <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <label for="name"><i class="mdi mdi-account"></i> Dein Name:</label>
        <input type="text" name="name" id="name" required placeholder="z. B. Anna Ampel">
        <small style="display:block; margin-top:4px; color:gray;">
        Gib hier deinen vollständigen Vor- und Nachnamen ein
        </small>

        <label for="participates"><i class="mdi mdi-help-circle-outline"></i> Nimmst du teil?</label>
        <select name="participates" id="participates" onchange="toggleParticipationForm()" required>
            <option value="1">Ja, ich nehme teil</option>
            <option value="0">Nein, ich kann nicht</option>
        </select>

        <div id="full_participation_fields" class="optional-block">
            <label for="station_id"><span class="mdi mdi-ticket-account"></span> Station auswählen:</label>
            <select name="station_id" id="station_id">
                <?php foreach ($stations as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="shift_date"><i class="mdi mdi-calendar-month-outline"></i> Datum:</label>
            <select name="shift_date" id="shift_date" onchange="updateTimeOptions()">
                <?php foreach ($validDates as $d): ?>
                    <option value="<?= $d ?>"><?= (new DateTime($d))->format('d.m.Y') ?></option>
                <?php endforeach; ?>
            </select>

            <label for="shift_start"><i class="mdi mdi-clock-start"></i> Startzeit:</label>
            <select name="shift_start" id="shift_start"></select>

            <label for="shift_end"><i class="mdi mdi-clock-end"></i> Endzeit:</label>
            <select name="shift_end" id="shift_end"></select>

            <?php if ($enable_tshirts): ?>
                <label for="shirt_size"><span class="mdi mdi-tshirt-crew"> T-Shirt-Größe (optional):</label>
                <select name="shirt_size" id="shirt_size">
                    <option value="">– bitte wählen –</option>
                    <option>XS</option>
                    <option>S</option>
                    <option>M</option>
                    <option>L</option>
                    <option>XL</option>
                    <option>XXL</option>
                    <option>3XL</option>
                    <option>4XL</option>
                    <option>5XL</option>
                </select>
               <small style="display:block; margin-top:4px; color:gray;">
                Nur für Personen ohne Feuerwehr Essen Uniform relevant.
               </small>
            <?php endif; ?>
        </div>

        <br>
        <button type="submit"><span class="mdi mdi-content-save"></span> Jetzt eintragen</button>
    </form>
<?php include 'footer.php'; ?>
</body>
</html>
