<?php
declare(strict_types=1);

// Public, development-only browser consent page. Never reads session cookies,
// signs a user in, transfers tokens, or changes Nextcloud security settings.
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'unsafe-inline'; base-uri 'none'; form-action 'none'; frame-ancestors https://replit.com");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connect your Preview session</title>
    <style>
        :root { color-scheme: light dark; font: 16px/1.6 system-ui, sans-serif; }
        body { margin: 0; min-height: 100dvh; display: grid; place-items: center; background: #1e0824; color: #f8fafc; }
        main { max-width: 36rem; padding: clamp(1.5rem, 5vw, 3rem); }
        h1 { line-height: 1.2; font-size: clamp(1.7rem, 4vw, 2.3rem); }
        p { color: #e2dce8; }
        button, a { font: inherit; }
        button { padding: .8rem 1.1rem; border: 1px solid #c084fc; border-radius: 10px; background: #7e22ce; color: #fff; cursor: pointer; }
        button:disabled { opacity: .6; cursor: default; }
        :focus-visible { outline: 3px solid #f0abfc; outline-offset: 4px; }
        a { color: #e9baff; }
        #status { min-height: 3.2rem; }
        .links { display: flex; flex-wrap: wrap; gap: 1rem; margin-top: 1rem; }
        small { display: block; margin-top: 1.5rem; color: #d2c4d8; }
    </style>
    <script src="/intravox-preview-storage.js" defer></script>
</head>
<body>
<main>
    <p>INTRAVOX · REPLIT PREVIEW</p>
    <h1>Use your existing sign-in here</h1>
    <p>Firefox may keep this embedded Preview separate from your signed-in tab.
        Allow this Preview to use this app’s existing cookies when your browser asks.</p>
    <button id="connect-session" type="button">Use existing sign-in in Preview</button>
    <p id="status" role="status" aria-live="polite">Checking browser support…</p>
    <div class="links">
        <a id="preview-login" href="/index.php/login?preview_storage=1">Sign in inside Preview</a>
        <a href="/index.php/login?preview_storage=1" target="_blank" rel="noopener noreferrer">Open sign-in tab</a>
    </div>
    <small>No password or cookie is copied. Nextcloud still verifies your session.
        If your browser or Replit blocks storage access, this page cannot override that policy.</small>
    <noscript><p>JavaScript is required to request browser permission. Use the sign-in links above instead.</p></noscript>
</main>
</body>
</html>
