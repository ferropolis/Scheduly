<?php
include 'db.php';

$token = $_GET['token'] ?? '';
$isPrint = isset($_GET['print']) && $_GET['print'] == '1';

if (!$token) {
    echo "<div class='error'>❌ Kein Token übergeben.</div>";
    exit;
}

$eventStmt = $db->prepare("SELECT id, name, start, end FROM events WHERE url_token = ?");
$eventStmt->execute([$token]);
$event = $eventStmt->fetch();

if (!$event) {
    echo "<div class='error'>❌ Ungültiger Token.</div>";
    exit;
}

$event_id   = $event['id'];
$event_name = $event['name'];

// --- Event-Grenzen
$eventStart = new DateTime($event['start']);
$eventEnd   = new DateTime($event['end']);

$startHour  = (int)$eventStart->format('H');
$endHour    = (int)$eventEnd->format('H');
if ((int)$eventEnd->format('i') > 0) $endHour++;
if ($eventStart->format('Y-m-d') === $eventEnd->format('Y-m-d') && $endHour <= $startHour) {
    $endHour = $startHour + 1; // Minimal 1 Stunde bei eintägigen Events
}

// --- Datumsperiode
$startDate = new DateTime($event['start']);
$endDate   = new DateTime($event['end']);
$endDate->modify('+1 day');
$period = new DatePeriod($startDate, new DateInterval('P1D'), $endDate);
$dates = [];
foreach ($period as $d) $dates[] = $d->format('Y-m-d');

$eventStartDate = $eventStart->format('Y-m-d');
$eventEndDate   = $eventEnd->format('Y-m-d');

// --- Stundenraster je Tag berechnen
$dayRanges = []; // date => ['start'=>int, 'end'=>int, 'hours'=>int[]]
foreach ($dates as $d) {
    if ($eventStartDate === $eventEndDate) {
        // Eintägig
        $dStart = $startHour;
        $dEnd   = $endHour;
    } elseif ($d === $eventStartDate) {
        // Erster Tag
        $dStart = $startHour;
        $dEnd   = 24;
    } elseif ($d === $eventEndDate) {
        // Letzter Tag -> bis Endstunde (nicht 24/0)
        $dStart = 0;
        $dEnd   = $endHour;
    } else {
        // Zwischentage
        $dStart = 0;
        $dEnd   = 24;
    }
    if ($dEnd <= $dStart) continue; // Tage ohne Stunden überspringen
    $dayRanges[$d] = [
        'start' => $dStart,
        'end'   => $dEnd,
        'hours' => range($dStart, $dEnd - 1)
    ];
}

// --- Aktionen (für Aktionszeile)
$scheduleStmt = $db->prepare("SELECT * FROM event_schedule WHERE event_id = ? ORDER BY start_hour");
$scheduleStmt->execute([$event_id]);
$actions = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);

// --- Schichten laden (nur teilnehmende)
$stmt = $db->prepare("
    SELECT s.name AS station, signups.name AS user, shift_date, shift_start, shift_end
    FROM signups
    JOIN stations s ON s.id = signups.station_id
    WHERE signups.event_id = ? AND signups.participating = 1
    ORDER BY s.name, shift_date, shift_start
");
$stmt->execute([$event_id]);
$shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Struktur: $rows[station][date][user][hour] = username|'' (für Anzeige)
$rows = [];
foreach ($shifts as $row) {
    $station = $row['station'];
    $user    = $row['user'];
    $date    = $row['shift_date'];

    // Nur Tage berücksichtigen, die im berechneten Bereich liegen
    if (!isset($dayRanges[$date])) continue;

    $start = new DateTime($date . ' ' . $row['shift_start']);
    $end   = new DateTime($date . ' ' . $row['shift_end']);

    $hoursForDay = $dayRanges[$date]['hours'];

    if (!isset($rows[$station])) $rows[$station] = [];
    if (!isset($rows[$station][$date])) $rows[$station][$date] = [];
    if (!isset($rows[$station][$date][$user])) {
        $rows[$station][$date][$user] = array_fill_keys($hoursForDay, '');
    } else {
        // Sicherstellen, dass Keys für diesen Tag existieren
        foreach ($hoursForDay as $h) {
            if (!isset($rows[$station][$date][$user][$h])) {
                $rows[$station][$date][$user][$h] = '';
            }
        }
    }

    foreach ($hoursForDay as $h) {
        $blockStart = (clone $start)->setTime($h, 0);
        $blockEnd   = (clone $blockStart)->modify('+1 hour');
        if ($start < $blockEnd && $end > $blockStart) {
            $rows[$station][$date][$user][$h] = htmlspecialchars($user);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($event_name) ?> – Station-Zeitplan</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css" rel="stylesheet">
    <style>
        .sum-row { background-color: #f0f0f0; font-weight: bold; }
        .schedule-row td { background: #eef5ff; font-weight: bold; font-style: normal; font-size: 0.9em; }
        .subheading { margin: 0.25rem 0 0.5rem; color: #444; font-weight: 600; }
    </style>
</head>
<body>
    <h1><?= htmlspecialchars($event_name) ?> – Station-Zeitplan</h1>

    <?php if (!$isPrint): ?>
        <div class="button-bar">
            <div class="button-group-left">
                <a href="event.php?token=<?= urlencode($token) ?>"><button><i class="mdi mdi-account-plus"></i> Jetzt eintragen</button></a>
                <a href="view_by_user.php?token=<?= urlencode($token) ?>"><button><i class="mdi mdi-clipboard-account-outline"></i> Einsatzplan nach Benutzer</button></a>
            </div>
            <div class="button-group-right">
                <a href="view_by_station.php?token=<?= urlencode($token) ?>&print=1"><button><i class="mdi mdi-printer"></i> Drucken</button></a>
            </div>
        </div>
    <?php else: ?>
        <script>window.print();</script>
    <?php endif; ?>

    <?php foreach ($rows as $station => $datesData): ?>
        <h2><i class="mdi mdi-pin"></i> <?= htmlspecialchars($station) ?></h2>

        <?php foreach ($datesData as $date => $users): ?>
            <?php if (isset($dayRanges[$date])): 
                $range = $dayRanges[$date];
                $hoursForDay = $range['hours'];
            ?>
                <?php if (count($datesData) > 1 || count($dayRanges) > 1): ?>
                    <h3 class="subheading">
                        <i class="mdi mdi-calendar-month-outline"></i>
                        <?= (new DateTime($date))->format('d.m.Y') ?>
                    </h3>
                <?php endif; ?>

                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <?php foreach ($hoursForDay as $h): ?>
                                <th><?= sprintf('%02d–%02d Uhr', $h, ($h + 1) % 24) ?></th>
                            <?php endforeach; ?>
                        </tr>
                        <?php if (count($actions) > 0): ?>
                        <tr class="schedule-row">
                            <td><i class="mdi mdi-tools"></i> Aktion</td>
                            <?php
                            // Aktionen auf Tagesfenster clippen
                            $current = $range['start'];
                            foreach ($actions as $entry) {
                                $aStart = max((int)$entry['start_hour'], $range['start']);
                                $aEnd   = min((int)$entry['end_hour'],   $range['end']);
                                if ($aEnd <= $aStart) continue;

                                if ($aStart > $current) {
                                    echo "<td colspan='" . ($aStart - $current) . "'></td>";
                                }
                                echo "<td colspan='" . ($aEnd - $aStart) . "'>" . htmlspecialchars($entry['description']) . "</td>";
                                $current = $aEnd;
                            }
                            if ($current < $range['end']) {
                                echo "<td colspan='" . ($range['end'] - $current) . "'></td>";
                            }
                            ?>
                        </tr>
                        <?php endif; ?>
                    </thead>
                    <tbody>
                        <?php
                        // Summenzeile
                        $sumRow = array_fill_keys($hoursForDay, 0);

                        if (empty($users)) {
                            echo "<tr><td colspan='" . (count($hoursForDay) + 1) . "' style='text-align:center;color:#666;'>Keine Einträge</td></tr>";
                        } else {
                            foreach ($users as $user => $userHours): ?>
                                <tr>
                                    <td><?= htmlspecialchars($user) ?></td>
                                    <?php foreach ($hoursForDay as $h):
                                        $val = $userHours[$h] ?? '';
                                        ?>
                                        <td class="<?= $val ? 'filled' : '' ?>"><?= $val ?></td>
                                        <?php if ($val) $sumRow[$h]++; ?>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach;
                        } ?>
                        <tr class="sum-row">
                            <td><i class="mdi mdi-account-group"></i> Summe</td>
                            <?php foreach ($hoursForDay as $h): ?>
                                <td><?= $sumRow[$h] ?: '' ?></td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endforeach; ?>

<?php include 'footer.php'; ?>
</body>
</html>
