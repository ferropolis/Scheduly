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

$event_id = $event['id'];
$event_name = $event['name'];

$eventStart = new DateTime($event['start']);
$eventEnd   = new DateTime($event['end']);

$startHour = (int)$eventStart->format('H');
$endHour   = (int)$eventEnd->format('H');
if ((int)$eventEnd->format('i') > 0) $endHour++;
if ($eventStart->format('Y-m-d') === $eventEnd->format('Y-m-d') && $endHour <= $startHour) {
    $endHour = $startHour + 1;
}

$startDate = (new DateTime($event['start']))->setTime(0, 0, 0);
$endDate   = (new DateTime($event['end']))->setTime(0, 0, 0);
$period    = new DatePeriod($startDate, new DateInterval('P1D'), (clone $endDate)->modify('+1 day'));

$dates = [];
foreach ($period as $d) $dates[] = $d->format('Y-m-d');

$eventStartDate = $eventStart->format('Y-m-d');
$eventEndDate   = $eventEnd->format('Y-m-d');

$dayRanges = []; // date => ['start'=>int, 'end'=>int, 'hours'=>int[]]
foreach ($dates as $d) {
    if ($eventStartDate === $eventEndDate) {
        $dStart = $startHour;
        $dEnd   = $endHour;
    } elseif ($d === $eventStartDate) {
        $dStart = $startHour;
        $dEnd   = 24;
    } elseif ($d === $eventEndDate) {
        $dStart = 0;
        $dEnd   = $endHour; // letzter Tag: bis Endstunde
    } else {
        $dStart = 0;
        $dEnd   = 24;
    }
    if ($dEnd <= $dStart) continue;
    $dayRanges[$d] = [
        'start' => $dStart,
        'end'   => $dEnd,
        'hours' => range($dStart, $dEnd - 1)
    ];
}

$scheduleStmt = $db->prepare("SELECT * FROM event_schedule WHERE event_id = ? ORDER BY start_hour");
$scheduleStmt->execute([$event_id]);
$actions = $scheduleStmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("
    SELECT signups.name AS user, s.name AS station, shift_date, shift_start, shift_end
    FROM signups
    JOIN stations s ON s.id = signups.station_id
    WHERE signups.event_id = ? AND signups.participating = 1
    ORDER BY signups.name, shift_date, shift_start
");
$stmt->execute([$event_id]);
$shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stationColors = [];
$stationIndex = 0;
$rows = []; // $rows[date][user][hour] = ['text'=>..., 'class'=>...]

foreach ($shifts as $row) {
    $user    = $row['user'];
    $station = $row['station'];
    $date    = $row['shift_date'];

    if (!isset($dayRanges[$date])) continue;

    if (!isset($stationColors[$station])) {
        $stationColors[$station] = 'station-' . $stationIndex++;
    }

    $start = new DateTime($date . ' ' . $row['shift_start']);
    $end   = new DateTime($date . ' ' . $row['shift_end']);

    $hoursForDay = $dayRanges[$date]['hours'];

    if (!isset($rows[$date])) $rows[$date] = [];
    if (!isset($rows[$date][$user])) {
        $rows[$date][$user] = array_fill_keys($hoursForDay, ['text' => '', 'class' => '']);
    } else {
        foreach ($hoursForDay as $h) {
            if (!isset($rows[$date][$user][$h])) {
                $rows[$date][$user][$h] = ['text' => '', 'class' => ''];
            }
        }
    }

    foreach ($hoursForDay as $h) {
        $blockStart = (clone $start)->setTime($h, 0);
        $blockEnd   = (clone $blockStart)->modify('+1 hour');
        if ($start < $blockEnd && $end > $blockStart) {
            $rows[$date][$user][$h] = [
                'text'  => $station,
                'class' => $stationColors[$station]
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($event_name) ?> – Einsatzplan nach Benutzer</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css" rel="stylesheet">
    <style>
        .station-0 { background-color: #d1e7dd; }
        .station-1 { background-color: #cff4fc; }
        .station-2 { background-color: #f8d7da; }
        .station-3 { background-color: #fff3cd; }
        .station-4 { background-color: #e2e3e5; }
        .filled { font-weight: bold; }
        .sum-row { background-color: #f0f0f0; font-weight: bold; }
        .schedule-row td { background: #eef5ff; font-weight: bold; font-style: normal; font-size: 0.9em; }
    </style>
</head>
<body>
    <h1><?= htmlspecialchars($event_name) ?> – Einsatzplan nach Benutzer</h1>

    <?php if (!$isPrint): ?>
        <div class="button-bar">
            <div class="button-group-left">
                <a href="event.php?token=<?= urlencode($token) ?>"><button><i class="mdi mdi-account-plus"></i> Jetzt eintragen</button></a>
                <a href="view_by_station.php?token=<?= urlencode($token) ?>"><button><i class="mdi mdi-pin"></i> Nach Station anzeigen</button></a>
            </div>
            <div class="button-group-right">
                <a href="view_by_user.php?token=<?= urlencode($token) ?>&print=1"><button><i class="mdi mdi-printer"></i> Drucken</button></a>
            </div>
        </div>
    <?php else: ?>
        <script>window.print();</script>
    <?php endif; ?>

    <?php foreach ($dayRanges as $date => $range):
        $hoursForDay = $range['hours'];
        $usersForDay = $rows[$date] ?? [];
        if (count($hoursForDay) === 0) continue;
    ?>
        <h2><i class="mdi mdi-calendar-month-outline"></i> <?= (new DateTime($date))->format('d.m.Y') ?></h2>
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
                $sumRow = array_fill_keys($hoursForDay, 0);

                if (empty($usersForDay)) {
                    echo "<tr><td colspan='" . (count($hoursForDay) + 1) . "' style='text-align:center;color:#666;'>Keine Einträge</td></tr>";
                } else {
                    foreach ($usersForDay as $user => $userHours): ?>
                        <tr>
                            <td><?= htmlspecialchars($user) ?></td>
                            <?php foreach ($hoursForDay as $h):
                                $cell = $userHours[$h] ?? ['text'=>'','class'=>'']; ?>
                                <td class="<?= $cell['class'] ?><?= !empty($cell['text']) ? ' filled' : '' ?>"><?= $cell['text'] ?? '' ?></td>
                                <?php if (!empty($cell['text'])) $sumRow[$h]++; ?>
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
    <?php endforeach; ?>
<?php include 'footer.php'; ?>
</body>
</html>
