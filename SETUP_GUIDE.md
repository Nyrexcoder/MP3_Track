# MP3 Player Backend API

This is a Laravel-based backend for an MP3 player application, using Docker (Sail), MySQL, and MinIO for file storage.

## Prerequisites
- Docker Desktop installed and running.
- PHP and Composer (for initial setup).

## Getting Started

Since you are on Windows and if you don't have WSL2 installed, you can use `docker compose` directly instead of `sail`.

1. **Start the Docker environment**:
   If you have WSL2:
   ```bash
   ./vendor/bin/sail up -d
   ```
   If you don't have WSL2 (Recommended for you):
   ```bash
   docker compose up -d
   ```

2. **Run Migrations**:
   If using Sail:
   ```bash
   ./vendor/bin/sail artisan migrate
   ```
   If using Docker Compose:
   ```bash
   docker compose exec laravel.test php artisan migrate
   ```

3. **Create the MinIO Bucket**:
   Access the MinIO console at [http://localhost:8900](http://localhost:8900) (User: `sail`, Password: `password`) and create a bucket named `mp3-files`.
   - **phpMyAdmin**: [http://localhost:8081](http://localhost:8081) (User: `sail`, Password: `password`)

## API Endpoints

- `GET /api/mp3s`: List all MP3 files (with temporary stream URLs).
- `POST /api/mp3s`: Upload a new MP3 file.
  - Parameters: `file` (MP3 file), `title` (string), `artist` (string, optional).
- `GET /api/mp3s/{id}`: Get details and stream URL for a specific file.
- `DELETE /api/mp3s/{id}`: Delete an MP3 file.

## Default Login Credentials
Registration is disabled. Use these credentials to log in:
- **Email**: `admin@example.com`
- **Password**: `password`

## UI Features
- **Dashboard**: [http://localhost](http://localhost)
- **phpMyAdmin**: [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/) (User: `sail`, Password: `password`)
- **MinIO Console**: [http://localhost/minio-console/](http://localhost/minio-console/) (User: `sail`, Password: `password`)
The project is pre-configured in `.env` to work with the Docker services:
- **MySQL**: `DB_HOST=mysql`, `DB_DATABASE=mp3_player`, `DB_USERNAME=sail`, `DB_PASSWORD=password`
- **MinIO**: `AWS_ENDPOINT=http://minio:9000`, `AWS_BUCKET=mp3-files`
