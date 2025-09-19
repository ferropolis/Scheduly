<?php
session_start();

define('MAX_FAILED_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 300); // Sekunden (5 Minuten)
define('BRUTEFORCE_LOG', __DIR__.'/bruteforce_log.json');

// === USER LOGIN DATEN ===
$valid_user = 'admin';
$valid_pass_hash = '$2y$10$Ap6OD9iQwFn/6xyVSyR.huKoGU4MPNOu7nbKz8VkW72OjGEamxHca';

// === BRUTEFORCE-SCHUTZ ===
$ip_address = $_SERVER['REMOTE_ADDR'];
$brute_data = file_exists(BRUTEFORCE_LOG) ? json_decode(file_get_contents(BRUTEFORCE_LOG), true) : [];

$failed_attempts = $brute_data[$ip_address]['failed_attempts'] ?? 0;
$lockout_time = $brute_data[$ip_address]['lockout_time'] ?? null;

$locked_out = ($lockout_time && time() < $lockout_time);

// === CSRF Token generieren ===
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// === LOGIN-VERARBEITUNG ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "⚠ Ungültiger Anfrage-Token.";
    } elseif ($locked_out) {
        $remaining = $lockout_time - time();
        $error = "⏳ Zu viele Fehlversuche. Bitte in {$remaining} Sekunden erneut versuchen.";
    } else {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if ($username === $valid_user && password_verify($password, $valid_pass_hash)) {
            $_SESSION['logged_in'] = true;
            $brute_data[$ip_address] = ['failed_attempts' => 0, 'lockout_time' => null];
            file_put_contents(BRUTEFORCE_LOG, json_encode($brute_data, JSON_PRETTY_PRINT));
            header('Location: index.php');
            exit;
        } else {
            $failed_attempts++;
            if ($failed_attempts >= MAX_FAILED_ATTEMPTS) {
                $brute_data[$ip_address] = [
                    'failed_attempts' => $failed_attempts,
                    'lockout_time' => time() + LOCKOUT_DURATION
                ];
                $error = "🚫 Zu viele Fehlversuche. Login für ".(LOCKOUT_DURATION/60)." Minuten gesperrt.";
            } else {
                $brute_data[$ip_address] = ['failed_attempts' => $failed_attempts, 'lockout_time' => null];
                $remaining_attempts = MAX_FAILED_ATTEMPTS - $failed_attempts;
                $error = "❌ Benutzername oder Passwort falsch. Noch {$remaining_attempts} Versuch(e).";
            }
            file_put_contents(BRUTEFORCE_LOG, json_encode($brute_data, JSON_PRETTY_PRINT));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css" rel="stylesheet">
    <style>
        body, html {
            margin: 0;
            background: #f5f5f5;
            font-family: sans-serif;
        }

        .main {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            padding: 40px 20px;
        }

        h1 {
            margin-bottom: 20px;
            font-size: 1.8em;
        }

        form {
            max-width: 500px;
            width: 100%;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .error {
            background: #ffe0e0;
            padding: 12px;
            margin-bottom: 12px;
            border-radius: 5px;
            color: #a00;
        }

        .disabled {
            opacity: 0.6;
            pointer-events: none;
        }

        button {
            display: block;
            margin: 0 auto;
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            background-color: #007bff;
            color: white;
            font-size: 1em;
            cursor: pointer;
        }

        button:disabled {
            background-color: #aaa;
            cursor: not-allowed;
        }

.footer {
    max-width: 500px;
    width: 100%;
    margin: 20px auto 0 auto;
    font-size: 0.8em;
    color: #777;
    text-align: center;
}
    </style>
</head>
<body>
    <div class="main">
        <h1><span class="mdi mdi-lock"></span> Admin-Login</h1>
        <form method="POST"<?= $locked_out ? ' class="disabled"' : '' ?>>
            <?php if (isset($error)): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <label for="username">Benutzername:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Passwort:</label>
            <input type="password" id="password" name="password" required>

            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <button type="submit"<?= $locked_out ? ' disabled' : '' ?>>Anmelden</button>
        </form>

        <div class="footer">
            &copy; <?= date("Y") ?> Christian Lattemann – Zeitplaner. Alle Rechte vorbehalten.<br>
            Icons © Material Design Icons – MIT License
        </div>
    </div>
</body>
</html>
