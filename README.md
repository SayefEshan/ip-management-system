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

#### 2.1 Main Environment File

Copy the main `.env.example` file to `.env`:

```bash
cp .env.example .env
```

The main `.env` file contains database configuration:

```env
# Database Configuration
DB_DATABASE_AUTH=ip_auth_service
DB_DATABASE_APP=ip_app_service
DB_USERNAME=db_user
DB_PASSWORD=change_this_password
DB_ROOT_PASSWORD=change_this_root_password
```

#### 2.2 Auth Service Environment

Copy and configure the auth service environment:

```bash
cp auth-service/.env.example auth-service/.env
```

Update `auth-service/.env` with the following **REQUIRED** configurations:

```env
# Application
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_URL=http://auth-service
AUTH_SERVICE_URL=http://auth-service
APP_SERVICE_URL=http://app-service

# JWT Secret Key (REQUIRED for authentication)
JWT_SECRET_KEY=YOUR_SECURE_JWT_SECRET_HERE

# Database
DB_CONNECTION=mysql
DB_HOST=auth-db
DB_PORT=3306
DB_DATABASE=ip_auth_service
DB_USERNAME=db_user
DB_PASSWORD=change_this_password
```

#### 2.3 App Service Environment

Copy and configure the app service environment:

```bash
cp app-service/.env.example app-service/.env
```

Update `app-service/.env` with:

```env
# Application
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_URL=http://app-service
AUTH_SERVICE_URL=http://auth-service
APP_SERVICE_URL=http://app-service

# Database
DB_CONNECTION=mysql
DB_HOST=app-db
DB_PORT=3306
DB_DATABASE=ip_app_service
DB_USERNAME=db_user
DB_PASSWORD=change_this_password
```

#### 2.4 Gateway Service Environment

Copy and configure the gateway service environment:

```bash
cp gateway/.env.example gateway/.env
```

Update `gateway/.env` with:

```env
# Application
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_URL=http://gateway
AUTH_SERVICE_URL=http://auth-service
APP_SERVICE_URL=http://app-service
```

#### 2.5 Generate Secure Keys

**Generate JWT Secret Key** (CRITICAL for security):

```bash
Using OpenSSL
openssl rand -base64 64
```

**Generate Laravel APP_KEY** for each service:

```bash
# Generate APP_KEY for auth service
docker exec auth-service php artisan key:generate --show

# Generate APP_KEY for app service
docker exec app-service php artisan key:generate --show

# Generate APP_KEY for gateway service
docker exec gateway-service php artisan key:generate --show
```

### 3. Build and Start the Services

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

### 4. Run Database Migrations

```bash
docker compose exec auth-service php artisan migrate --seed
docker compose exec app-service php artisan migrate --seed
```

### 5. Access the Application

Open your browser and navigate to:

```bash
http://localhost:3000
```

The API can be accessed at:

```bash
http://localhost:8000/api
```

### 6. Test Accounts

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
