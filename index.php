<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

include 'db.php';

// Events laden
$stmt = $db->query("SELECT id, name, start, end, url_token FROM events ORDER BY id DESC");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Zeitplaner – Veranstaltungen</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css" rel="stylesheet">
</head>
<body>
    <h1><span class="mdi mdi-timetable"></span> Zeitplaner – Veranstaltungen</h1>

    <div style="margin-bottom: 20px;">
        <a class="button button-create" href="create_event.php"><span class="mdi mdi-plus"></span> Neue Veranstaltung anlegen</a>
        <a class="button" href="logout.php"><span class="mdi mdi-logout"></span> Logout</a>
    </div>

    <?php if (count($events) === 0): ?>
        <p><span class="mdi mdi-exclamation"></span> Noch keine Veranstaltungen vorhanden.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Veranstaltung</th>
                    <th>Start</th>
					<th>Ende</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $event): ?>
                    <tr>
                        <td><?= $event['id'] ?></td>
                        <td><?= htmlspecialchars($event['name']) ?></td>
                        <td><?= (new DateTime($event['start']))->format('d.m.Y H:i') ?></td>
						<td><?= (new DateTime($event['end']))->format('d.m.Y H:i') ?></td>
                        <td>
                            <div class="button-group">
                                <a class="button" href="event.php?token=<?= $event['url_token'] ?>"><span class="mdi mdi-note-edit"></span> Teilnahme</a>
                                <a class="button" href="view_by_user.php?token=<?= $event['url_token'] ?>"><span class="mdi mdi-account"></span> Benutzer</a>
                                <a class="button" href="view_by_station.php?token=<?= $event['url_token'] ?>"><span class="mdi mdi-ticket-account"></span> Station</a>
                                <a class="button button-schedule" href="add_station.php?token=<?= $event['url_token'] ?>"><span class="mdi mdi-plus"></span> Station hinzufügen</a>
                                <a class="button button-schedule" href="edit_event_schedule.php?token=<?= $event['url_token'] ?>"><span class="mdi mdi-cog"></span> Aktionen</a>
                                <a class="button button-edit" href="edit_event.php?id=<?= $event['id'] ?>"><span class="mdi mdi-pencil"></span> Bearbeiten</a>
                                <a class="button button-shirt" href="view_shirt_sizes.php?token=<?= $event['url_token'] ?>"><span class="mdi mdi-tshirt-crew"></span> Shirtgrößen</a>
                                <a class="button button-danger" href="delete_event.php?id=<?= $event['id'] ?>" onclick="return confirm('Diese Veranstaltung wirklich löschen?')"><span class="mdi mdi-delete-forever"></span> Löschen</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
<?php include 'footer.php'; ?>
</body>
</html>
