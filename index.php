<?php

// Path to the directory to save the notes in, without trailing slash.
// Ideally this should be outside the document root to prevent direct file access.
$save_path = '_tmp';

// Maximum allowed note content size in bytes (1 MB).
$max_note_size = 1024 * 1024;

// Disable caching so clients always fetch the latest note content.
header('Cache-Control: no-store');

// Validate the note name: must be present, <= 64 chars, and alphanumeric/dash/underscore only.
// This prevents path traversal (e.g., "../etc/passwd") and other injection attacks.
if (!isset($_GET['note']) || strlen($_GET['note']) > 64 || !preg_match('/^[a-zA-Z0-9_-]+$/', $_GET['note'])) {

    // Generate a name with 5 cryptographically random characters from an unambiguous set.
    // Excludes visually similar characters (0, O, 1, l, I, etc.).
    $chars = '234579abcdefghjkmnpqrstwxyz';
    $name = '';
    for ($i = 0; $i < 5; $i++) {
        $name .= $chars[random_int(0, strlen($chars) - 1)];
    }
    header("Location: " . $name);
    die;
}

// Build the filesystem path for this note.
$path = $save_path . '/' . $_GET['note'];

// Handle POST requests: save or delete note content.
// NOTE: No CSRF protection is implemented. For public-facing deployments,
// consider adding Origin/Referer validation or a CSRF token.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Accept content from either form data ('text' field) or raw POST body.
    $text = isset($_POST['text']) ? $_POST['text'] : file_get_contents("php://input");

    // Enforce maximum content size to prevent disk exhaustion attacks.
    if (strlen($text) > $max_note_size) {
        http_response_code(413);
        die('Content too large');
    }

    // Write the note content to disk.
    $result = file_put_contents($path, $text);
    if ($result === false) {
        http_response_code(500);
        die('Failed to save note');
    }

    // If the submitted content is empty, remove the file to keep storage clean.
    if (!strlen($text)) {
        if (is_file($path)) {
            unlink($path);
        }
    }
    die;
}

// Serve raw text when explicitly requested via ?raw query parameter,
// or when the client identifies as curl or wget (CLI usage).
if (isset($_GET['raw']) || strpos($_SERVER['HTTP_USER_AGENT'], 'curl') === 0 || strpos($_SERVER['HTTP_USER_AGENT'], 'Wget') === 0) {
    if (is_file($path)) {
        header('Content-type: text/plain');
        readfile($path);
    } else {
        header('HTTP/1.0 404 Not Found');
    }
    die;
}
?><!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Note name is escaped to prevent XSS injection via crafted URLs -->
    <title><?php print htmlspecialchars($_GET['note'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="icon" href="favicon.ico" sizes="any">
    <link rel="icon" href="favicon.svg" type="image/svg+xml">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #333b4d;
            --text: #fff;
            --border: #495265;
            --header-bg: rgba(36, 38, 43, 0.8);
            --btn-bg: #24262b;
            --btn-border: #495265;
            --btn-hover: #333b4d;
            --btn-text: #fff;
            --toast-bg: #fff;
            --toast-text: #333;
            --font: 'Inter', system-ui, sans-serif;
            --content-font-size: 14px;
        }

        :root.theme-light {
            --bg: #ebeef1;
            --text: #333;
            --border: #ddd;
            --header-bg: rgba(255, 255, 255, 0.8);
            --btn-bg: #fff;
            --btn-border: #ccc;
            --btn-hover: #f0f0f0;
            --btn-text: #333;
            --toast-bg: #333;
            --toast-text: #fff;
        }

        body {
            margin: 0;
            background: var(--bg);
            font-family: var(--font);
            color: var(--text);
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
        }

        #header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 20px;
            background: var(--header-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
            z-index: 10;
        }

        .header-title {
            font-weight: 600;
            font-size: 14px;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: opacity 0.2s ease;
        }

        .header-title:hover {
            opacity: 0.7;
        }

        .header-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        button,
        select {
            font-family: var(--font);
            font-size: 11px;
            padding: 6px 12px;
            border: 1px solid var(--btn-border);
            border-radius: 6px;
            background: var(--btn-bg);
            color: var(--btn-text);
            cursor: pointer;
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
            outline: none;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        button:hover,
        select:hover {
            background-color: var(--btn-hover);
        }

        select {
            appearance: none;
            padding-right: 28px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23ccc' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 8px center;
        }

        :root.theme-light select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23888' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        }

        .container {
            flex: 1;
            position: relative;
            padding: 8px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }

        #content {
            margin: 0;
            padding: 20px;
            overflow-y: auto;
            resize: none;
            width: 100%;
            flex: 1;
            box-sizing: border-box;
            border: 1px solid var(--border);
            border-radius: 8px;
            outline: none;
            background: var(--btn-bg);
            color: var(--text);
            font-family: monospace;
            font-size: var(--content-font-size);
            line-height: 1.5;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.02);
            transition: border-color 0.2s ease;
        }

        #content:focus {
            border-color: #888;
        }

        #printable {
            display: none;
        }

        #toast {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: var(--toast-bg);
            color: var(--toast-text);
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 1000;
        }

        #toast.show {
            transform: translateX(-50%) translateY(0);
        }

        @media print {

            #header,
            .container {
                display: none;
            }

            #printable {
                display: block;
                white-space: pre-wrap;
                word-break: break-word;
            }
        }

        @media (max-width: 600px) {
            .header-actions {
                flex-wrap: wrap;
            }

            #header {
                padding: 8px 12px;
            }
        }

        .settings-menu-container {
            position: relative;
            display: flex;
            align-items: center;
        }

        .settings-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 8px;
            background: var(--btn-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px;
            min-width: 170px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 100;
        }

        .settings-dropdown.show {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .settings-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: var(--text);
            font-weight: 500;
        }

        .text-size-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .text-size-controls button {
            padding: 2px 8px;
            font-size: 14px;
            width: 24px;
            justify-content: center;
        }
    </style>
</head>

<body>

    <div id="header">
        <div class="header-title" id="btnCopyLink" title="Click to copy link">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
                <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
            <?php print htmlspecialchars($_GET['note'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="header-actions">
            <select id="downloadExt">
                <option value="txt">.txt</option>
                <option value="sh">.sh</option>
                <option value="bash">.bash</option>
                <option value="ps1">.ps1</option>
                <option value="bat">.bat</option>
                <option value="cmd">.cmd</option>
                <option value="py">.py</option>
                <option value="md">.md</option>
                <option value="sql">.sql</option>
                <option value="sqlite">.sqlite</option>
                <option value="ipynb">.ipynb</option>
                <option value="pyi">.pyi</option>
                <option value="go">.go</option>
                <option value="yml">.yml</option>
                <option value="yaml">.yaml</option>
                <option value="ini">.ini</option>
                <option value="mjs">.mjs</option>
                <option value="jsx">.jsx</option>
                <option value="tsx">.tsx</option>
                <option value="db">.db</option>
                <option value="json">.json</option>
                <option value="js">.js</option>
                <option value="ts">.ts</option>
                <option value="html">.html</option>
                <option value="css">.css</option>
                <option value="xml">.xml</option>
            </select>
            <button id="btnDownload">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="7 10 12 15 17 10"></polyline>
                    <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Download
            </button>
            <button id="btnCopyCurl">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="4 17 10 11 4 5"></polyline>
                    <line x1="12" y1="19" x2="20" y2="19"></line>
                </svg>
                cURL
            </button>
            <button id="btnCopyWget">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="4 17 10 11 4 5"></polyline>
                    <line x1="12" y1="19" x2="20" y2="19"></line>
                </svg>
                Wget
            </button>
            <div class="settings-menu-container">
                <button id="btnSettings" title="Settings" style="padding: 6px 8px;">
                    <svg fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path
                            d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z">
                        </path>
                    </svg>
                </button>
                <div id="settingsDropdown" class="settings-dropdown">
                    <div class="settings-item">
                        <span>Theme</span>
                        <select id="themeSelect">
                            <option value="dark">Dark</option>
                            <option value="light">Light</option>
                        </select>
                    </div>
                    <div class="settings-item">
                        <span>Text Size</span>
                        <div class="text-size-controls">
                            <button id="btnTextDec">-</button>
                            <span id="textSizeDisplay">14px</span>
                            <button id="btnTextInc">+</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <textarea id="content" spellcheck="false"><?php
        if (is_file($path)) {
            print htmlspecialchars(file_get_contents($path), ENT_QUOTES, 'UTF-8');
        }
        ?></textarea>
    </div>
    <pre id="printable"></pre>
    <div id="toast"></div>
    <script>
        // ── Settings Initialization ──
        // Retrieve persisted user preferences from localStorage, with defaults.
        var currentTheme = localStorage.getItem('theme') || 'dark';
        var currentFontSize = parseInt(localStorage.getItem('fontSize')) || 14;
        var toastTimeout; // Tracks the active toast timer to prevent overlap

        /**
         * Apply a theme by toggling the 'theme-light' class on <html>.
         * @param {string} theme - 'dark' or 'light'
         */
        function applyTheme(theme) {
            if (theme === 'light') {
                document.documentElement.classList.add('theme-light');
            } else {
                document.documentElement.classList.remove('theme-light');
            }
            document.getElementById('themeSelect').value = theme;
        }

        /**
         * Apply a font size to the content textarea via CSS custom property.
         * @param {number} size - Font size in pixels
         */
        function applyFontSize(size) {
            document.documentElement.style.setProperty('--content-font-size', size + 'px');
            document.getElementById('textSizeDisplay').textContent = size + 'px';
        }

        applyTheme(currentTheme);
        applyFontSize(currentFontSize);

        /**
         * Display a temporary toast notification at the bottom of the screen.
         * Clears any existing toast timeout to prevent premature dismissal on rapid calls.
         * @param {string} msg - Message to display
         */
        function showToast(msg) {
            var toast = document.getElementById('toast');
            toast.textContent = msg;
            toast.classList.add('show');
            clearTimeout(toastTimeout);
            toastTimeout = setTimeout(function () { toast.classList.remove('show'); }, 2500);
        }

        // ── Header Actions ──

        // Copy the current page URL to clipboard when the header title is clicked.
        document.getElementById('btnCopyLink').onclick = function () {
            navigator.clipboard.writeText(window.location.href).then(function () {
                showToast('Link copied to clipboard!');
            }).catch(function () {
                showToast('Failed to copy link');
            });
        };

        // Build and copy a cURL download command for the current note.
        document.getElementById('btnCopyCurl').onclick = function () {
            var url = window.location.href;
            var ext = document.getElementById('downloadExt').value;
            var filename = "<?php print addslashes(htmlspecialchars($_GET['note'], ENT_QUOTES, 'UTF-8')); ?>." + ext;
            var cmd = 'curl "' + url + '" -o "' + filename + '"';
            navigator.clipboard.writeText(cmd).then(function () {
                showToast('cURL command copied!');
            }).catch(function () {
                showToast('Failed to copy command');
            });
        };

        // Build and copy a wget download command for the current note.
        document.getElementById('btnCopyWget').onclick = function () {
            var url = window.location.href;
            var ext = document.getElementById('downloadExt').value;
            var filename = "<?php print addslashes(htmlspecialchars($_GET['note'], ENT_QUOTES, 'UTF-8')); ?>." + ext;
            var cmd = 'wget "' + url + '" -O "' + filename + '"';
            navigator.clipboard.writeText(cmd).then(function () {
                showToast('Wget command copied!');
            }).catch(function () {
                showToast('Failed to copy command');
            });
        };

        // Download the current note content as a file with the selected extension.
        // Creates a temporary blob URL, triggers the download, then cleans up.
        document.getElementById('btnDownload').onclick = function () {
            var ext = document.getElementById('downloadExt').value;
            var filename = "<?php print addslashes(htmlspecialchars($_GET['note'], ENT_QUOTES, 'UTF-8')); ?>." + ext;
            var text = document.getElementById('content').value;
            var blob = new Blob([text], { type: 'text/plain' });
            var a = document.createElement('a');
            a.href = window.URL.createObjectURL(blob);
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            // Revoke the blob URL to free memory (prevents accumulation on repeated downloads).
            window.URL.revokeObjectURL(a.href);
        };

        // ── Settings Panel ──

        // Toggle the settings dropdown visibility.
        document.getElementById('btnSettings').onclick = function () {
            document.getElementById('settingsDropdown').classList.toggle('show');
        };

        // Persist and apply theme when the user changes the select.
        document.getElementById('themeSelect').onchange = function (e) {
            var theme = e.target.value;
            localStorage.setItem('theme', theme);
            applyTheme(theme);
        };

        // Increase font size (capped at 40px to prevent layout breakage).
        document.getElementById('btnTextInc').onclick = function () {
            if (currentFontSize < 40) {
                currentFontSize += 2;
                localStorage.setItem('fontSize', currentFontSize);
                applyFontSize(currentFontSize);
            }
        };

        // Decrease font size (minimum 8px for readability).
        document.getElementById('btnTextDec').onclick = function () {
            if (currentFontSize > 8) {
                currentFontSize -= 2;
                localStorage.setItem('fontSize', currentFontSize);
                applyFontSize(currentFontSize);
            }
        };

        // Close the settings dropdown when clicking anywhere outside of it.
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.settings-menu-container')) {
                var dropdown = document.getElementById('settingsDropdown');
                if (dropdown && dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        });

        // ── Auto-Save Logic ──
        // Polls every 1 second, comparing the textarea value against the last saved
        // content. If different, sends a POST request to persist the change.
        // NOTE: For high-traffic deployments, consider replacing this polling loop
        // with a debounced input event listener to reduce unnecessary requests.

        /**
         * Upload the textarea content to the server if it has changed.
         * Schedules the next check after the request completes (or on error).
         */
        function uploadContent() {
            if (content !== textarea.value) {
                var temp = textarea.value;
                var request = new XMLHttpRequest();
                request.open('POST', window.location.href, true);
                request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
                request.onload = function () {
                    if (request.readyState === 4) {
                        content = temp;
                        setTimeout(uploadContent, 1000);
                    }
                }
                request.onerror = function () {
                    setTimeout(uploadContent, 1000);
                }
                request.send('text=' + encodeURIComponent(temp));

                // Update the hidden printable element (used for print stylesheets).
                printable.textContent = temp;
            }
            else {
                setTimeout(uploadContent, 1000);
            }
        }

        // Cache DOM references used by the auto-save loop.
        var textarea = document.getElementById('content');
        var printable = document.getElementById('printable');
        var content = textarea.value;

        // Initialize the printable element and start auto-save.
        printable.textContent = content;
        textarea.focus();
        uploadContent();
    </script>
</body>

</html>