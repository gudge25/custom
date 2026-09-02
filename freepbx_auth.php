<?php
/**
 * Guard: require an authenticated FreePBX admin session before rendering
 * a custom page.
 *
 * FreePBX's own login sets $_SESSION['AMP_user'] on success and checks for
 * exactly that key to decide whether a request is authenticated (see
 * /var/www/html/admin/libraries/gui_auth.php). The session cookie path is
 * "/", so a plain session_start() here sees the same session - no need to
 * bootstrap FreePBX's own framework just to check login state.
 *
 * Deliberately dependency-free (no env()/db()) so both bootstrap.php
 * consumers and legacy inline-config pages can require this directly.
 */
function requireFreepbxAuth() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (isset($_SESSION['AMP_user'])) {
        return;
    }

    http_response_code(401);
    echo <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>PBX Login Required</title>
            <style>
                body {
                    margin: 0;
                    min-height: 100vh;
                    display: grid;
                    place-items: center;
                    background: radial-gradient(circle at top, #283c86 0, #0a0f1f 45%, #050814 100%);
                    color: #e5e7eb;
                    font-family: system-ui, -apple-system, BlinkMacSystemFont, "SF Pro Text", sans-serif;
                }
                .card {
                    width: min(420px, calc(100% - 40px));
                    background: rgba(15,23,42,0.9);
                    border: 1px solid rgba(148,163,184,0.1);
                    border-radius: 1rem;
                    box-shadow: 0 18px 40px rgba(0,0,0,0.55);
                    padding: 32px;
                    text-align: center;
                }
                h1 {
                    font-size: 18px;
                    font-weight: 600;
                    color: #f8fafc;
                    margin: 0 0 8px;
                }
                p {
                    font-size: 13px;
                    color: #94a3b8;
                    margin: 0 0 20px;
                }
                .btn {
                    display: inline-block;
                    border-radius: 0.75rem;
                    border: 1px solid rgba(56,189,248,0.4);
                    background: rgba(2,132,199,0.25);
                    color: #e0f2fe;
                    padding: 0.65rem 1.2rem;
                    font-weight: 600;
                    text-decoration: none;
                }
                .btn:hover {
                    background: rgba(3,105,161,0.45);
                }
            </style>
        </head>
        <body>
            <div class="card">
                <h1>🔒 PBX Login Required</h1>
                <p>Please log in to the PBX first to view this page.</p>
                <a class="btn" href="/admin/">Go to PBX Login</a>
            </div>
        </body>
        </html>
        HTML;
    exit;
}
