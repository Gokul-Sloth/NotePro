# NotePro — Minimalist Web Notepad

A lightweight, self-hosted web notepad where every unique URL is its own note. No accounts, no databases — just fast, ephemeral text storage backed by flat files.

**Live Demo:** [notepro.gokul.it.com](http://notepro.gokul.it.com)

---

## Features

- Auto-saving content (polls every 1 second)
- Dark and light theme support with persistent preference
- Adjustable text size
- File download in multiple formats (txt, py, sh, json, md, sql, etc.)
- CLI-friendly: serves raw text to curl and wget clients
- Copy cURL and wget download commands from the UI
- Shareable links (click the header title to copy)
- Print-friendly stylesheet

---

## Quick Start with Docker Hub

The fastest way to get up and running:

```bash
docker pull gokulkrish29/notepro:latest
docker run -d -p 8080:80 --name notepro-app gokulkrish29/notepro:latest
```

Then open **http://localhost:8080** in your browser.

---

## Other Installation Methods

### Docker Compose (from source)

```bash
git clone <repository-url>
cd Notepro
docker-compose up -d --build
```

Available at `http://localhost:8080`.

### PHP Built-in Server

```bash
git clone <repository-url>
cd Notepro
php -S localhost:8080 router.php
```

### Apache

1. Enable required modules:

```bash
a2enmod rewrite headers
```

2. Allow `.htaccess` overrides in your site configuration:

```apache
<Directory /var/www/html>
    AllowOverride All
</Directory>
```

3. Copy the project files to your document root, then make the storage directory writable:

```bash
chown -R www-data:www-data _tmp
chmod 755 _tmp
```

See [How To Set Up mod_rewrite for Apache](https://www.digitalocean.com/community/tutorials/how-to-set-up-mod_rewrite-for-apache-on-ubuntu-14-04) for more details.

### Nginx

If the project is in the root directory:

```nginx
location / {
    rewrite ^/([a-zA-Z0-9_-]+)$ /index.php?note=$1;
}
```

If the project is in a subdirectory (e.g., `/notes`):

```nginx
location ~* ^/notes/([a-zA-Z0-9_-]+)$ {
    try_files $uri /notes/index.php?note=$1&$args;
}
```

---

## Configuration

All configuration is done within `index.php`:

| Variable         | Default           | Description                                                     |
|------------------|-------------------|-----------------------------------------------------------------|
| `$save_path`     | `_tmp`            | Directory where note files are stored. Should ideally be outside the document root. |
| `$max_note_size` | `1048576` (1 MB)  | Maximum allowed size per note in bytes.                         |

To change the Docker host port, edit `docker-compose.yml`:

```yaml
ports:
  - "8080:80"   # Change 8080 to your preferred port
```

---

## Usage

### Web Interface

1. Navigate to `http://localhost:8080` — you will be redirected to a randomly generated note.
2. Navigate to `http://localhost:8080/my-note` to create or open a note named `my-note`.
3. Start typing. Content is auto-saved to the server.
4. Use the header controls to download, copy CLI commands, or adjust settings.

Note names must be alphanumeric (plus hyphens and underscores) and at most 64 characters long.

### CLI

Retrieve a note and save it to a local file:

```bash
curl http://localhost:8080/test > test.txt
```

Save specific text to a note:

```bash
curl http://localhost:8080/test -d 'hello,

welcome to my pad!
'
```

Save the content of a local file to a note:

```bash
cat /etc/hosts | curl http://localhost:8080/hosts --data-binary @-
```

Retrieve raw content explicitly (useful when the User-Agent is not curl/wget):

```
http://localhost:8080/my-note?raw
```

---

## Project Structure

```
Notepro/
├── index.php           # Main application (PHP backend + HTML/CSS/JS frontend)
├── router.php          # URL rewriting for PHP built-in server
├── .htaccess           # Apache rewrite rules and security headers
├── Dockerfile          # Docker image definition (php:8.2-apache)
├── docker-compose.yml  # Docker Compose service configuration
├── favicon.ico         # Favicon (ICO format)
├── favicon.svg         # Favicon (SVG format)
└── _tmp/               # Note storage directory (auto-created)
```

---

## Requirements

- PHP 8.0 or higher
- Apache with `mod_rewrite` and `mod_headers`, or Nginx with URL rewriting, or PHP built-in server
- Docker and Docker Compose (optional, for containerized deployment)

No external PHP libraries or Composer dependencies are required.

---

## Known Limitations

- No authentication or authorization. Anyone with the URL can read and overwrite a note.
- No CSRF protection on the POST endpoint. For public deployments, consider adding Origin header validation.
- The `_tmp` storage directory is inside the document root. For production, move it outside the web root or add access restrictions.
- The auto-save mechanism uses polling (1-second interval) rather than event-driven debouncing, which generates constant network traffic even when idle.
- Note name generation uses a 5-character space from a 27-character alphabet (~14.3 million combinations). This is not suitable for sensitive data.

---

---

## Acknowledgments

This project is a heavily modified and modernized version of the original [minimalist-web-notepad](https://github.com/pereorga/minimalist-web-notepad) by Pere Orga and contributors.

---

## License

Licensed under the Apache License, Version 2.0.
You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
