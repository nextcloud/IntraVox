<?php
/**
 * "Page not found" template for public access.
 *
 * This template is shown when:
 * - The token is invalid or expired
 * - Public sharing is disabled
 * - The page doesn't exist or is not publicly accessible
 *
 * The message is intentionally generic to prevent information leakage.
 */
style('intravox', 'main');
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page Not Found - IntraVox</title>
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
        }
        h1 {
            font-size: 72px;
            margin: 0;
            color: #999;
        }
        h2 {
            font-size: 24px;
            margin: 20px 0 10px;
            color: #333;
        }
        p {
            color: #666;
            line-height: 1.5;
        }
        .logo {
            font-size: 18px;
            font-weight: bold;
            color: #0082c9;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>404</h1>
        <h2>Page Not Found</h2>
        <p>The page you're looking for doesn't exist or is no longer publicly accessible.</p>
        <div class="logo">IntraVox</div>
    </div>
</body>
</html>
