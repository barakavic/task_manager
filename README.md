# Task Manager

This is a clean and simple Task Management API and Web application built with Laravel. It handles creating tasks, updating their status, preventing duplicate entries, and showing a daily summary report.

## Features Supported
- **Interactive GUI**: A modern Vanilla Javascript & Tailwind CSS frontend application communicating dynamically with the API natively.
- **RESTful Endpoints**: Full CRUD API endpoints with **dynamic filtering** (by status, priority, and date).
- **Rules Executed**: No duplicate task titles per date, progressive state-machine status enforcement (`pending` -> `in_progress` -> `done`), and restricted deletion.

## How to Run Locally using Docker (Recommended)
This requires only Docker to be installed on your machine.

1. Clone this repository.
2. Run `cp .env.example .env`.
3. If you don't have local PHP/Composer installed, run this initial setup command first:
   ```bash
   docker run --rm \
       -u "$(id -u):$(id -g)" \
       -v "$(pwd):/var/www/html" \
       -w /var/www/html \
       laravelsail/php83-composer:latest \
       composer install --ignore-platform-reqs
   ```
4. Start the application via Laravel Sail:
   ```bash
   ./vendor/bin/sail up -d
   ```
5. Generate the application encryption key:
   ```bash
   ./vendor/bin/sail artisan key:generate
   ```
6. Run the database migrations to set up MySQL:
   ```bash
   ./vendor/bin/sail artisan migrate
   ```

## How to use the Interactive GUI
Once the Docker or standard PHP server is successfully running:
1. Open your web browser.
2. Navigate to `http://localhost`.
3. You will be greeted by the natively built frontend. You can interactively create tasks, view lists mapped beautifully by priority, update statuses directly with buttons, generate dynamic reports, and instantly sort tasks by date, priority, or name!

## How to Run Locally using standard PHP/Composer
If you prefer to run it without Docker:
1. Clone this repository and run `composer install`.
2. Copy the `.env` file: `cp .env.example .env`
3. Update `.env` with your actual MySQL credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database_name
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```
4. Run migrations: `php artisan migrate`
5. Serve the application: `php artisan serve`

## How to Deploy Online (Railway)

### Deploying on Railway
Railway natively supports Dockerfiles and PHP applications via Nixpacks.
1. Connect your GitHub repository to Railway.
2. Add a `MySQL` database service globally in your Railway project.
3. In your web application service, set your Environment Variables using the MySQL instance internal details:
    - `DB_CONNECTION=mysql`
    - `DB_HOST=${{MySQL.MYSQLHOST}}`
    - `DB_PORT=${{MySQL.MYSQLPORT}}`
    - `DB_DATABASE=${{MySQL.MYSQLDATABASE}}`
    - `DB_USERNAME=${{MySQL.MYSQLUSER}}`
    - `DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}`
    - `APP_KEY` (generate one via `php artisan key:generate --show`)
4. Customize the build phase (if using Nixpacks, Laravel is auto-detected).
5. Specify the start command (or Nixpack does it automatically: `php artisan serve --host 0.0.0.0 --port $PORT`). Ensure `php artisan migrate --force` is run during deployment.

## OpenAPI Specification Documentation

```yaml
openapi: 3.0.0
info:
  title: Task Management API
  version: 1.0.0
  description: A REST API for managing tasks with prioritization, strict status progression, and daily reporting.
paths:
  /api/tasks:
    get:
      summary: Retrieve all tasks
      description: Lists tasks ordered by priority (high->low) then by due date. Can be filtered by status.
      parameters:
        - in: query
          name: status
          schema:
            type: string
            enum: [pending, in_progress, done]
          required: false
        - in: query
          name: priority
          schema:
            type: string
            enum: [high, medium, low]
          required: false
        - in: query
          name: due_date
          schema:
            type: string
            format: date
          required: false
      responses:
        '200':
          description: A list of tasks
    post:
      summary: Create a new task
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required:
                - title
                - due_date
                - priority
              properties:
                title:
                  type: string
                  example: "Complete Laravel project"
                due_date:
                  type: string
                  format: date
                  example: "2026-03-31"
                priority:
                  type: string
                  enum: [high, medium, low]
                  example: "high"
      responses:
        '201':
          description: Task created successfully
        '422':
          description: Validation error preventing creation (e.g. duplicating title on same date, past dates)
  /api/tasks/{id}/status:
    patch:
      summary: Update task status
      description: strictly enforces progression from pending -> in_progress -> done.
      parameters:
        - in: path
          name: id
          required: true
          schema:
            type: integer
      requestBody:
        required: true
        content:
          application/json:
            schema:
              type: object
              required:
                - status
              properties:
                status:
                  type: string
                  enum: [pending, in_progress, done]
      responses:
        '200':
          description: Task Status successfully updated
        '400':
          description: Task is already in this status
        '403':
          description: Invalid transition sequence
  /api/tasks/{id}:
    delete:
      summary: Delete a task
      description: Deletes a task uniquely if it is marked as 'done'.
      parameters:
        - in: path
          name: id
          required: true
          schema:
            type: integer
      responses:
        '204':
          description: Task deleted
        '403':
          description: Forbidden (Only done tasks can be deleted)
  /api/tasks/report:
    get:
      summary: Get daily tasks report
      parameters:
        - in: query
          name: date
          required: true
          schema:
            type: string
            format: date
            example: "2026-03-31"
      responses:
        '200':
          description: A structured report containing task counts dynamically grouped strictly by priority and status
```
