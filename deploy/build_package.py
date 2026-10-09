"""Builds the zip you upload to a shared host (Hostinger, cPanel...).

Run it from the project root, after preparing the code:

    composer install --no-dev --optimize-autoloader
    npm ci && npm run build
    php artisan livewire:publish --assets
    python deploy/build_package.py

The zip contains a ready-to-install copy: a fresh .env (new APP_KEY, APP_INSTALLED=false)
and an .htaccess that sends every request to public/. After uploading it, open the
site: the wizard at /instalar asks for the database and the administrator.
See docs/despliegue-hostinger.md.
"""
import argparse
import base64
import os
import secrets
import sys
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent

INCLUDE = ["app", "bootstrap", "config", "database", "public", "resources", "routes", "storage", "vendor", "artisan", "composer.json", "composer.lock"]

# Paths left out (relative, forward slashes), as prefixes.
EXCLUDE_PREFIXES = [
    "public/hot", "public/storage", "public/media",
    "storage/logs/", "storage/framework/cache/data/", "storage/framework/sessions/", "storage/framework/views/",
    "storage/framework/testing/", "storage/app/private/", "storage/app/public/", "storage/pail",
]
EXCLUDE_NAMES = {".DS_Store", "Thumbs.db", ".phpunit.result.cache"}

ENV = """APP_NAME="{name}"
APP_ENV=production
APP_KEY=base64:{key}
APP_DEBUG=false
APP_URL=
APP_INSTALLED=false
BUSINESS_TIMEZONE=America/Santo_Domingo
APP_LOCALE=es
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
APP_MAINTENANCE_DRIVER=file
BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
CACHE_STORE=file

# Photos (vehicles, logo, signed contracts) are stored here, relative to the project.
MEDIA_ROOT=public/media

MAIL_MAILER=log
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${{APP_NAME}}"

VITE_APP_NAME="${{APP_NAME}}"
"""

# Send every request to public/ and keep source, .env and dotfiles private.
HTACCESS = """<IfModule mod_rewrite.c>
    RewriteEngine On

    # Let the host's SSL validation through.
    RewriteRule ^\\.well-known/ - [L]

    # Never serve private files.
    RewriteRule ^\\.(env|git) - [F,L]

    # The site lives in public/.
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
"""


def wanted(rel: str) -> bool:
    if any(rel == p.rstrip("/") or rel.startswith(p if p.endswith("/") else p + "/") for p in EXCLUDE_PREFIXES):
        # Keep the .gitkeep / .gitignore placeholders so the folders exist on the server.
        return rel.endswith((".gitignore", ".gitkeep"))
    if rel.startswith("bootstrap/cache/") and rel.endswith(".php"):
        return False  # regenerated on the server
    return Path(rel).name not in EXCLUDE_NAMES


def add_file(zf: zipfile.ZipFile, path: Path, arcname: str) -> None:
    info = zipfile.ZipInfo(arcname, (2026, 1, 1, 0, 0, 0))
    info.compress_type = zipfile.ZIP_DEFLATED
    info.create_system = 3  # Unix, so the permission bits are honoured
    info.external_attr = 0o100644 << 16
    zf.writestr(info, path.read_bytes())


def main() -> int:
    parser = argparse.ArgumentParser(description="Build the upload zip for a shared host.")
    parser.add_argument("--name", default="Rent Car System", help="business name written to APP_NAME")
    parser.add_argument("--out", default=str(ROOT / "build" / "rent-car-system.zip"), help="output zip path")
    args = parser.parse_args()

    if not (ROOT / "vendor" / "autoload.php").exists() or not (ROOT / "public" / "build" / "manifest.json").exists():
        print("Falta vendor/ o public/build/. Ejecuta antes: composer install --no-dev -o && npm ci && npm run build", file=sys.stderr)
        return 1
    if not (ROOT / "public" / "vendor" / "livewire").exists():
        print("Falta public/vendor/livewire. Ejecuta antes: php artisan livewire:publish --assets", file=sys.stderr)
        return 1

    out = Path(args.out)
    out.parent.mkdir(parents=True, exist_ok=True)
    key = base64.b64encode(secrets.token_bytes(32)).decode()

    files = []
    for entry in INCLUDE:
        base = ROOT / entry
        if base.is_file():
            files.append(entry)
            continue
        for dirpath, dirnames, filenames in os.walk(base, followlinks=False):
            dirnames[:] = [d for d in dirnames if d not in (".git", "node_modules") and not (Path(dirpath) / d).is_symlink()]
            for name in filenames:
                path = Path(dirpath) / name
                rel = path.relative_to(ROOT).as_posix()
                if not path.is_symlink() and wanted(rel):
                    files.append(rel)

    dirs = sorted({"/".join(f.split("/")[:i]) for f in files for i in range(1, len(f.split("/")))})
    dirs.append("public/media")

    with zipfile.ZipFile(out, "w", zipfile.ZIP_DEFLATED, compresslevel=6) as zf:
        for d in dict.fromkeys(dirs):  # folders first, 755
            info = zipfile.ZipInfo(d + "/", (2026, 1, 1, 0, 0, 0))
            info.create_system = 3
            info.external_attr = (0o40755 << 16) | 0x10
            zf.writestr(info, b"")
        for rel in files:
            add_file(zf, ROOT / rel, rel)
        for name, content in ((".env", ENV.format(name=args.name.replace('"', ""), key=key)), (".htaccess", HTACCESS)):
            info = zipfile.ZipInfo(name, (2026, 1, 1, 0, 0, 0))
            info.create_system = 3
            info.external_attr = 0o100644 << 16
            zf.writestr(info, content)
        zf.writestr(zipfile.ZipInfo("public/media/.gitkeep", (2026, 1, 1, 0, 0, 0)), b"")

    print(f"{len(files)} archivos -> {out} ({out.stat().st_size / 1024 / 1024:.1f} MB)")
    return 0


if __name__ == "__main__":
    sys.exit(main())
