<?php
include 'db.php';

$token = $_GET['token'] ?? '';
if (!$token) {
    echo "<div class='error'>❌ Kein Token übergeben.</div>";
    exit;
}

// Event abrufen
$eventStmt = $db->prepare("SELECT id, name FROM events WHERE url_token = ?");
$eventStmt->execute([$token]);
$event = $eventStmt->fetch();

if (!$event) {
    echo "<div class='error'>❌ Ungültiger Token.</div>";
    exit;
}

$event_id = $event['id'];

// Shirtgrößen abrufen
$stmt = $db->prepare("
    SELECT DISTINCT name, shirt_size
    FROM signups
    WHERE event_id = ? AND shirt_size IS NOT NULL AND shirt_size != ''
    ORDER BY shirt_size, name
");
$stmt->execute([$event_id]);
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Optional: nach Größen zählen
$countBySize = [];
foreach ($entries as $entry) {
    $size = $entry['shirt_size'];
    $countBySize[$size] = ($countBySize[$size] ?? 0) + 1;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>T-Shirt-Größen – <?= htmlspecialchars($event['name']) ?></title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css" rel="stylesheet">
</head>
<body>
    <h1><span class="mdi mdi-tshirt-crew"> T-Shirt-Größen – <?= htmlspecialchars($event['name']) ?></h1>

    <?php if (count($entries) === 0): ?>
        <p><span class="mdi mdi-exclamation"></span> Es wurden noch keine T-Shirt-Größen erfasst.</p>
    <?php else: ?>
        <h2><span class="mdi mdi-file-chart"></span> Übersicht nach Größen:</h2>
        <ul>
            <?php foreach ($countBySize as $size => $count): ?>
                <li><strong><?= htmlspecialchars($size) ?>:</strong> <?= $count ?> Person(en)</li>
            <?php endforeach; ?>
        </ul>

        <h2><span class="mdi mdi-clipboard-account"></span> Teilnehmer mit Größenangabe:</h2>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>T-Shirt-Größe</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $entry): ?>
                    <tr>
                        <td><?= htmlspecialchars($entry['name']) ?></td>
                        <td><?= htmlspecialchars($entry['shirt_size']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <br>
    <a class="button" href="index.php"><span class="mdi mdi-backspace-outline"></span> Zurück zur Übersicht</a> <br>
    <?php include 'footer.php'; ?>
</body>
</html>
