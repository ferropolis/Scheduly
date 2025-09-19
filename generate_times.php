<?php
$start = $_GET['start'] ?? '00:00';
$end = $_GET['end'] ?? '23:00';

$startTime = strtotime($start);
$endTime = strtotime($end);

// nur volle Stunden generieren
for ($t = $startTime; $t <= $endTime; $t += 3600) {
    $timeStr = date("H:i", $t);
    echo "<option value=\"$timeStr\">$timeStr</option>\n";
}
