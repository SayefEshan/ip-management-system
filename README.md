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

### 2. Setup Environment Variables

**IMPORTANT**: Each service requires its own `.env` file. The repository only includes `.env.example` files.

#### 2 Main Environment File

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
docker compose build
```

#### 4 Generate Secure Keys

**Generate JWT Secret Key** (CRITICAL for security):

```bash
openssl rand -base64 64
```

Copy the generated key to the `JWT_SECRET_KEY` variable in the `auth-service/.env` file.

**Generate Laravel APP_KEY** for each service:

```bash
# Generate APP_KEY for auth service
docker compose exec auth-service php artisan key:generate

# Generate APP_KEY for app service
docker compose exec app-service php artisan key:generate

# Generate APP_KEY for gateway service
docker compose exec gateway php artisan key:generate
```

### 5. Start the Services

```bash
docker compose up -d
```

This will start:

- Gateway service (port 8000)
- Auth service (internal)
- App service (internal)
- Frontend (port 3000)
- Auth Database (MySQL)
- App Database (MySQL)

### 6. Run Database Migrations

```bash
docker compose exec auth-service php artisan migrate --seed
docker compose exec app-service php artisan migrate --seed
```

### 7. Access the Application

Open your browser and navigate to:

```bash
http://localhost:3000
```

The API can be accessed at:

```bash
http://localhost:8000/api
```

### 8. Test Accounts

You can use the following test accounts to log in:

- **Admin User**
  - Email: `admin@ad-group.com.au`
  - Password: `admin123`
- **Regular User**
  - Email: `sayef@ad-group.com.au`
  - Password: `password123`

```

## Architecture

The system uses a microservices architecture:

- **Frontend**: React.js application served by Nginx
- **Gateway**: Laravel API gateway for routing and authentication
- **Auth Service**: Handles user authentication and JWT tokens
- **App Service**: Manages IP addresses and audit logs
- **Databases**: Separate MySQL instances for auth and app data
- **Docker**: All services are containerized for easy deployment and scaling
```
