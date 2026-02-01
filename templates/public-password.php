<?php
/**
 * Password challenge template for password-protected public shares.
 *
 * Shown when a share link requires a password before content is accessible.
 * Follows Nextcloud's own password-protected share pattern.
 */
style('intravox', 'main');

$shareToken = $_['shareToken'] ?? '';
$wrongPassword = $_['wrongPassword'] ?? false;
$actionUrl = $_['actionUrl'] ?? '';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password Required - IntraVox</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 90%;
        }
        .lock-icon {
            font-size: 48px;
            margin-bottom: 10px;
            color: #999;
        }
        h2 {
            font-size: 22px;
            margin: 10px 0 8px;
            color: #333;
        }
        p {
            color: #666;
            line-height: 1.5;
            margin: 0 0 24px;
            font-size: 14px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        input[type="password"] {
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }
        input[type="password"]:focus {
            border-color: #0082c9;
        }
        .error input[type="password"] {
            border-color: #e9322d;
        }
        button[type="submit"] {
            padding: 10px 14px;
            background: #0082c9;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        button[type="submit"]:hover {
            background: #006ba7;
        }
        .error-message {
            color: #e9322d;
            font-size: 13px;
            margin: -4px 0 0;
        }
        .logo {
            font-size: 18px;
            font-weight: bold;
            color: #0082c9;
            margin-top: 24px;
        }
    </style>
</head>
<body>
    <div class="container<?php if ($wrongPassword) echo ' error'; ?>">
        <div class="lock-icon">&#128274;</div>
        <h2>Password Required</h2>
        <p>This shared page is password protected. Enter the password to continue.</p>

        <form method="POST" action="<?php p($actionUrl); ?>">
            <input type="password"
                   name="password"
                   placeholder="Password"
                   autofocus
                   required
                   autocomplete="off">

            <?php if ($wrongPassword): ?>
                <p class="error-message">Incorrect password. Please try again.</p>
            <?php endif; ?>

            <button type="submit">Unlock</button>
        </form>

        <div class="logo">IntraVox</div>
    </div>
</body>
</html>
