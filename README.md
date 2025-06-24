# IP Management System

A microservices-based IP address management system with audit logging capabilities.

## Prerequisites

- Docker & Docker Compose
- Git

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/SayefEshan/ip-management-system.git
cd ip-management-system
```

### 2. Main Environment File

Copy the main `.env.example` file to `.env`:

```bash
cp .env.example .env
```

The main `.env` file contains database configuration:
Configure db credentials in `.env`:

```env
# Database Configuration
DB_DATABASE_AUTH=ip_auth_service
DB_DATABASE_APP=ip_app_service
DB_USERNAME=db_user
DB_PASSWORD=change_this_password
DB_ROOT_PASSWORD=change_this_root_password
```

### 3. Build the Services

```bash
docker compose up -d --build
```

This will start:

- Gateway service (port 8000)
- Auth service (internal)
- App service (internal)
- Frontend (port 3000)
- Auth Database (MySQL)
- App Database (MySQL)

### 4. Generate Secure Keys

**Generate JWT Secret Key** (CRITICAL for security):

```bash
openssl rand -base64 64
```

Copy the generated key to the `JWT_SECRET_KEY` variable in the `auth-service/.env` file.

**Generate Laravel APP_KEY** for each service:

#### Generate APP_KEY for auth service

```bash
docker compose exec auth-service php artisan key:generate
```

#### Generate APP_KEY for app service

```bash
docker compose exec app-service php artisan key:generate
```

#### Generate APP_KEY for gateway service

```bash
docker compose exec gateway php artisan key:generate
```

### 5. Run Database Migrations with Seeders

```bash
docker compose exec auth-service php artisan migrate --seed
```

```bash
docker compose exec app-service php artisan migrate --seed
```

### 6. Access the Application

Open your browser and navigate to:

```bash
http://localhost:3000
```

The API can be accessed at:

```bash
http://localhost:8000/api
```

### 7. Test Accounts

You can use the following test accounts to log in:

- **Admin User**
  - Email: `admin@ad-group.com.au`
  - Password: `admin123`
- **Regular User**
  - Email: `sayef@ad-group.com.au`
  - Password: `password123`

### 8. Architecture Overview

The system is built using a microservices architecture with the following components:

- **Gateway Service**: Acts as the entry point for all requests, routing them to the appropriate service.
- **Auth Service**: Handles user authentication and authorization.
- **App Service**: Manages IP address allocations and related operations.
- **Frontend**: A React-based user interface for interacting with the system.
- **Databases**: Two MySQL databases for the auth and app services.

### 9. API Documentation and Testing

The system includes a comprehensive Postman collection for testing all API endpoints.

#### Importing the Postman Collection

1. Download and install [Postman](https://www.postman.com/downloads/) if you haven't already
2. Open Postman and click on "Import" in the top left corner
3. Choose "File" and select the [`ip-management-API.postman_collection.json`](./ip-management-API.postman_collection.json) file from the project root
4. Click "Import" to add the collection to your Postman workspace
