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

Copy the `.env.example` file to `.env` and update the values as needed.

```bash
cp .env.example .env
```

```env
# Database Configuration
DB_DATABASE_AUTH=ip_auth_service
DB_DATABASE_APP=ip_app_service
DB_USERNAME=db_user
DB_PASSWORD=change_this_password
DB_ROOT_PASSWORD=change_this_root_password
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
