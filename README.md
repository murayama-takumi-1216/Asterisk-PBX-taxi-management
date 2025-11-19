# Taxi Dispatch Management System

A comprehensive taxi dispatch and management system built with Django, integrated with Asterisk PBX for call handling and management. This system provides complete fleet management, driver coordination, service tracking, and real-time communication capabilities.

## 📋 Table of Contents

- [Features](#features)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Configuration](#configuration)
- [Running the Project](#running-the-project)
- [Development](#development)
- [Deployment](#deployment)
- [Project Structure](#project-structure)
- [API Documentation](#api-documentation)
- [Security Considerations](#security-considerations)
- [Troubleshooting](#troubleshooting)

## ✨ Features

### Core Functionality
- **Fleet Management**: Complete vehicle tracking and management
- **Driver Management**: Driver profiles, schedules, and performance tracking
- **Operator Dashboard**: Real-time dispatch and call management interface
- **Service Management**: Complete ride/service lifecycle tracking
- **Shift Management**: Turno (shift) scheduling and management
- **Client Management**: Customer profiles and frequent routes
- **Reporting System**: Comprehensive reports and analytics
- **Maintenance Tracking**: Vehicle maintenance scheduling and records

### Technical Features
- **RESTful API**: Full REST API with Django REST Framework
- **Asterisk Integration**: ARI (Asterisk REST Interface) and AMI (Asterisk Manager Interface)
- **WebSocket Support**: Real-time communication capabilities
- **Authentication**: Django Allauth with Multi-Factor Authentication (MFA)
- **Admin Interface**: Django admin panel for system management
- **Database Management Tools**: phpMyAdmin and PostgreSQL admin interfaces

## 🔧 Prerequisites

### System Requirements
- **Operating System**: Windows 10 or higher
- **Python**: 3.12 or higher
- **PostgreSQL**: 12+ (for main database)
- **Redis**: 6.0+ (for caching, optional - can use local memory cache)
- **Node.js**: 16+ (for frontend assets, optional)
- **Asterisk**: 18+ (for telephony integration, optional)

### Software Dependencies
- PostgreSQL server and client libraries
- Redis server (optional - Windows port available or use WSL)
- Git for Windows
- Virtual environment support (venv - included with Python 3.12+)
- Visual C++ Build Tools (for compiling some Python packages)

### Windows-Specific Requirements
- **PowerShell**: 5.1+ (included with Windows 10) or PowerShell 7+
- **Windows Terminal** (recommended for better experience)
- **PostgreSQL**: Download from [postgresql.org](https://www.postgresql.org/download/windows/)
- **Redis for Windows**: Use [Memurai](https://www.memurai.com/) or run Redis in WSL2

### Optional
- IIS or Nginx for Windows (for production deployment)
- Windows Task Scheduler (for cron jobs)
- SSL certificate (for HTTPS)

## 📦 Installation

### 1. Navigate to Project Directory

```powershell
# Open PowerShell or Command Prompt
# Navigate to your project directory (adjust path as needed)
cd E:\workana\carlos project\www_backup\var\www

# Or if cloning from repository:
# git clone <repository-url> .
```

### 2. Set Up Python Virtual Environment

```powershell
# Navigate to application directory
cd application

# Create virtual environment
python -m venv venvpy

# Activate virtual environment
# In PowerShell:
.\venvpy\Scripts\Activate.ps1

# If you get an execution policy error, run:
# Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser

# In Command Prompt (CMD):
# venvpy\Scripts\activate.bat
```

### 3. Install Python Dependencies

```powershell
# Make sure virtual environment is activated
# You should see (venvpy) in your prompt

cd apps_taxi

# Upgrade pip
python -m pip install --upgrade pip

# Install dependencies
# For development:
pip install -r requirements\local.txt

# OR for production:
# pip install -r requirements\production.txt
```

### 4. Set Up PostgreSQL Database

#### Install PostgreSQL (if not already installed)
1. Download PostgreSQL from [postgresql.org/download/windows/](https://www.postgresql.org/download/windows/)
2. Run the installer and remember the postgres user password you set
3. PostgreSQL will be installed as a Windows service

#### Create Database and User

**Option 1: Using pgAdmin (GUI)**
1. Open pgAdmin 4 (installed with PostgreSQL)
2. Connect to your PostgreSQL server
3. Right-click "Databases" → "Create" → "Database"
4. Name: `taxi_test`
5. Right-click "Login/Group Roles" → "Create" → "Login/Group Role"
6. Name: `taxi`, Password: `your_secure_password`
7. Go to "Privileges" tab and grant all necessary permissions

**Option 2: Using Command Line (psql)**

```powershell
# Open Command Prompt or PowerShell
# Navigate to PostgreSQL bin directory (usually):
cd "C:\Program Files\PostgreSQL\15\bin"

# Connect to PostgreSQL (use your postgres password)
.\psql.exe -U postgres

# Or if PostgreSQL is in your PATH:
psql -U postgres
```

Then run these SQL commands:

```sql
CREATE DATABASE taxi_test;
CREATE USER taxi WITH PASSWORD 'your_secure_password';
ALTER ROLE taxi SET client_encoding TO 'utf8';
ALTER ROLE taxi SET default_transaction_isolation TO 'read committed';
ALTER ROLE taxi SET timezone TO 'America/New_York';
GRANT ALL PRIVILEGES ON DATABASE taxi_test TO taxi;
\q
```

### 5. Install and Configure Redis (Optional)

Redis is optional for development. Django can use local memory cache instead.

#### Option 1: Use Memurai (Redis-compatible for Windows)
1. Download from [memurai.com](https://www.memurai.com/get-memurai)
2. Install and start the service
3. Verify: `memurai-cli ping` (should return PONG)

#### Option 2: Use WSL2 (Windows Subsystem for Linux)
```powershell
# Install Redis in WSL2
wsl sudo apt-get update
wsl sudo apt-get install redis-server
wsl sudo service redis-server start

# Test connection
wsl redis-cli ping
```

#### Option 3: Skip Redis (Use Local Memory Cache)
For development, you can skip Redis. The local settings already use `LocMemCache` which doesn't require Redis.

**Note**: If you skip Redis, the application will work fine for development. Redis is mainly needed for production scaling and session storage.

### 6. Configure Environment Variables

#### Option 1: Create Environment File (Recommended for Development)

```powershell
cd application
# Copy the template file
Copy-Item env-txi.txt env-txi.local.txt

# Edit env-txi.local.txt with Notepad or your preferred editor
notepad env-txi.local.txt
```

Edit `env-txi.local.txt` with your configuration. For Windows, you'll need to convert the export commands to PowerShell format or use a batch file.

**Create a PowerShell script to load environment variables:**

Create `application\load-env.ps1`:

```powershell
# Load environment variables from env-txi.local.txt
$env:DJANGO_SETTINGS_MODULE="config.settings.local"
$env:DB_PASSWORD="123"
$env:DB_NAME="taxi_test"
$env:DB_USER="postgres"
$env:DB_PORT="5432"
$env:DB_HOST="localhost"

# Asterisk Configuration (if using)
$env:ASTERISK_HOST="http://your-asterisk-ip:8088/asterisk/ari"
$env:ASTERISK_USER="carlos"
$env:ASTERISK_PASSWORD="123"
$env:ASTERISK_AMI_HOST="75.99.146.30"
$env:ASTERISK_AMI_PORT="7777"
$env:ASTERISK_AMI_USER="/etc/asterisk/manager.conf"
$env:ASTERISK_AMI_PASSWORD="your_ami_password"
$env:APPLICATION_PATH=""

# Channel filters
$env:ASTERISK_AMI_FILTERCHANNEL_EX="DAHDI"
$env:ASTERISK_AMI_FILTERCHANNEL_IN="PJSIP"
```

#### Option 2: Set Windows Environment Variables (System-wide)

```powershell
# Set environment variables permanently (PowerShell as Administrator)
[System.Environment]::SetEnvironmentVariable('DJANGO_SETTINGS_MODULE', 'config.settings.local', 'User')
[System.Environment]::SetEnvironmentVariable('DB_PASSWORD', 'your_secure_password', 'User')
[System.Environment]::SetEnvironmentVariable('DB_NAME', 'taxi_test', 'User')
[System.Environment]::SetEnvironmentVariable('DB_USER', 'taxi', 'User')
[System.Environment]::SetEnvironmentVariable('DB_PORT', '5432', 'User')
[System.Environment]::SetEnvironmentVariable('DB_HOST', 'localhost', 'User')
```

**⚠️ Security Warning**: Never commit credentials to version control. Use environment variables or a secrets management system.

### 7. Run Database Migrations

**⚠️ IMPORTANT: You MUST activate the virtual environment first!**

```powershell
# Navigate to application directory
cd application

# Activate virtual environment (REQUIRED!)
.\venvpy\Scripts\Activate.ps1

# You should see (venvpy) in your prompt after activation

# Load environment variables
. .\load-env.ps1

# Navigate to apps_taxi directory
cd apps_taxi

# Now run migrations
python manage.py migrate
```

**If you get "ModuleNotFoundError: No module named 'django'":**
- Make sure you activated the virtual environment (you should see `(venvpy)` in your prompt)
- Verify activation: `python --version` should show Python from venvpy
- Try: `pip list` to see if Django is installed in the venv

### 8. Create Superuser

```powershell
# Make sure virtual environment is activated first!
cd application
.\venvpy\Scripts\Activate.ps1

# Load environment variables
. .\load-env.ps1

# Navigate to apps_taxi
cd apps_taxi

# Create superuser
python manage.py createsuperuser

# Follow the prompts to create your admin user
```

### 9. Collect Static Files

```powershell
# Make sure virtual environment is activated!
cd application
.\venvpy\Scripts\Activate.ps1

# Load environment variables
. .\load-env.ps1

# Navigate to apps_taxi
cd apps_taxi

# Collect all static files
python manage.py collectstatic --noinput
```

### 10. Load Initial Data (Optional)

```powershell
# Make sure virtual environment is activated!
cd application
.\venvpy\Scripts\Activate.ps1
. .\load-env.ps1
cd apps_taxi

# Load fixtures if available
python manage.py loaddata apps\core_conductor\fixtures\conductor.json
python manage.py loaddata apps\core_operador\fixtures\operador.json
python manage.py loaddata apps\core_vehiculo\fixtures\vehiculo.json

# Load all fixtures from a directory
python manage.py loaddata apps\core_maestras\fixtures\*.json
```

## ⚙️ Configuration

### Django Settings

The project uses environment-based settings:
- **Development**: `config.settings.local`
- **Production**: `config.settings.production`
- **Testing**: `config.settings.test`

Set the appropriate settings module via environment variable:

**PowerShell:**
```powershell
$env:DJANGO_SETTINGS_MODULE="config.settings.local"
```

**Command Prompt (CMD):**
```cmd
set DJANGO_SETTINGS_MODULE=config.settings.local
```

**Or use the load-env.ps1 script** (recommended)

### Database Configuration

Database settings are configured via environment variables:
- `DB_NAME`: Database name
- `DB_USER`: Database user
- `DB_PASSWORD`: Database password
- `DB_HOST`: Database host (default: localhost)
- `DB_PORT`: Database port (default: 5432)

### Asterisk Configuration

If you're using Asterisk integration, configure:
- `ASTERISK_HOST`: ARI endpoint URL
- `ASTERISK_USER`: ARI username
- `ASTERISK_PASSWORD`: ARI password
- `ASTERISK_AMI_HOST`: AMI server host
- `ASTERISK_AMI_PORT`: AMI server port
- `ASTERISK_AMI_USER`: AMI username
- `ASTERISK_AMI_PASSWORD`: AMI password

### Static and Media Files

- **Static files**: Collected to `apps_taxi/staticfiles/`
- **Media files**: Stored in `apps_taxi/apps/media/`
- In production, configure S3 or other storage backends

## 🚀 Running the Project

### Development Server

#### Option 1: Using PowerShell Script

Create `application\startup.ps1`:

```powershell
cd $PSScriptRoot
.\venvpy\Scripts\Activate.ps1
. .\load-env.ps1  # Load environment variables
cd apps_taxi
python manage.py runserver 0.0.0.0:8000
```

Then run:
```powershell
cd application
.\startup.ps1
```

#### Option 2: Manual Start

```powershell
# Navigate to application directory
cd application

# Activate virtual environment
.\venvpy\Scripts\Activate.ps1

# Load environment variables (if using load-env.ps1)
. .\load-env.ps1

# Navigate to apps_taxi
cd apps_taxi

# Start development server
python manage.py runserver 0.0.0.0:8000

# Or just use localhost:
python manage.py runserver
```

The application will be available at:
- **Web Interface**: http://localhost:8000
- **Admin Panel**: http://localhost:8000/admin/
- **API Documentation**: http://localhost:8000/api/docs/

### Production Server (Windows)

For production on Windows, you have several options:

#### Option 1: Using Waitress (Recommended for Windows)

Waitress is a production WSGI server that works well on Windows:

```powershell
# Install waitress
pip install waitress

# Run with waitress
cd application\apps_taxi
..\venvpy\Scripts\Activate.ps1
. ..\load-env.ps1

waitress-serve --host=0.0.0.0 --port=8000 config.wsgi:application
```

#### Option 2: Using Gunicorn with WSL2

If you need Gunicorn, run it in WSL2:

```bash
# In WSL2
cd /mnt/e/workana/carlos\ project/www_backup/var/www/application/apps_taxi
source ../venvpy/bin/activate
gunicorn config.wsgi:application --bind 0.0.0.0:8000 --workers 4
```

#### Option 3: Using Windows Service (NSSM)

Use NSSM (Non-Sucking Service Manager) to run as a Windows service:

1. Download NSSM from [nssm.cc](https://nssm.cc/download)
2. Extract and run:
```powershell
# Install service
.\nssm.exe install TaxiApp "C:\Python312\python.exe" "E:\workana\carlos project\www_backup\var\www\application\apps_taxi\manage.py runserver 0.0.0.0:8000"

# Set working directory
.\nssm.exe set TaxiApp AppDirectory "E:\workana\carlos project\www_backup\var\www\application\apps_taxi"

# Start service
.\nssm.exe start TaxiApp
```

### IIS Configuration (Production on Windows)

For production with IIS, use HttpPlatformHandler or wfastcgi:

1. Install IIS and HttpPlatformHandler
2. Configure web.config in your project root
3. Set up application pool
4. Configure static file handling

### Nginx for Windows (Alternative)

You can also use Nginx for Windows:

```nginx
server {
    listen 80;
    server_name your-domain.com;

    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /static/ {
        alias E:/workana/carlos project/www_backup/var/www/application/apps_taxi/staticfiles/;
    }

    location /media/ {
        alias E:/workana/carlos project/www_backup/var/www/application/apps_taxi/apps/media/;
    }
}
```

## 💻 Development

### Code Quality Tools

The project uses several code quality tools:

```powershell
# Make sure virtual environment is activated

# Linting with Ruff
ruff check .

# Format code
ruff format .

# Type checking with mypy
mypy .

# Template linting with djLint
djlint apps_taxi\apps\templates

# Run tests
pytest

# Coverage report
pytest --cov=apps --cov-report=html
```

### Pre-commit Hooks

Install pre-commit hooks:

```powershell
pip install pre-commit
pre-commit install
```

### Running Tests

```powershell
# Run all tests
pytest

# Run with coverage
pytest --cov=apps --cov-report=term-missing

# Run specific test file
pytest apps\users\tests\test_models.py
```

### Database Migrations

```powershell
# Create migrations
python manage.py makemigrations

# Apply migrations
python manage.py migrate

# Show migration status
python manage.py showmigrations
```

### Django Shell

```powershell
python manage.py shell
```

### Creating New Apps

```powershell
cd apps_taxi\apps
python ..\manage.py startapp your_app_name
```

## 📁 Project Structure

```
E:\workana\carlos project\www_backup\var\www\
├── application/                    # Main application directory
│   ├── apps_taxi/                  # Django taxi management system
│   │   ├── apps/                   # Application modules
│   │   │   ├── common/             # Common utilities and integrations
│   │   │   ├── core_app/           # Core application views
│   │   │   ├── core_cliente/       # Client management
│   │   │   ├── core_conductor/     # Driver management
│   │   │   ├── core_operador/      # Operator management
│   │   │   ├── core_servicio/      # Service/ride management
│   │   │   ├── core_turno/         # Shift management
│   │   │   ├── core_vehiculo/      # Vehicle management
│   │   │   ├── core_maestras/      # Master data
│   │   │   ├── core_app_reportes/  # Reporting system
│   │   │   ├── core_app_mantenimiento/ # Maintenance tracking
│   │   │   ├── users/              # User management
│   │   │   ├── webhook/            # Webhook handlers
│   │   │   ├── static/             # Static files (CSS, JS, images)
│   │   │   └── templates/          # HTML templates
│   │   ├── config/                 # Django configuration
│   │   │   ├── settings/           # Environment-specific settings
│   │   │   └── urls.py             # URL routing
│   │   ├── requirements/            # Python dependencies
│   │   │   ├── base.txt            # Base dependencies
│   │   │   ├── local.txt           # Development dependencies
│   │   │   └── production.txt      # Production dependencies
│   │   └── manage.py               # Django management script
│   ├── apps_socket/                 # WebSocket test applications
│   ├── cron/                        # Scheduled tasks
│   │   ├── cron_back.py            # Python cron job
│   │   └── cron_back.php           # PHP cron job
│   ├── venvpy/                      # Python virtual environment
│   ├── env-txi.txt                  # Environment variables template
│   ├── startup.sh                   # Startup script (Linux/WSL)
│   └── startup.ps1                  # Startup script (Windows PowerShell)
└── html/                            # Web-accessible applications
    ├── appdb/                       # phpMyAdmin (MySQL admin)
    ├── app-pgdb/                    # PostgreSQL admin interface
    ├── fop2/                        # Flash Operator Panel 2 (Asterisk)
    └── query/                       # Query tools
```

## 📚 API Documentation

### API Endpoints

The API is available at `/api/` and includes:

- **Authentication**: `/api/auth-token/`
- **Users**: `/api/users/`
- **API Schema**: `/api/schema/`
- **Interactive Docs**: `/api/docs/` (Swagger UI)

### API Authentication

Use token authentication:

**PowerShell (using Invoke-RestMethod):**
```powershell
# Get token
$body = @{
    username = "your_username"
    password = "your_password"
} | ConvertTo-Json

$response = Invoke-RestMethod -Uri "http://localhost:8000/api/auth-token/" `
    -Method Post `
    -ContentType "application/json" `
    -Body $body

$token = $response.token

# Use token
$headers = @{
    Authorization = "Token $token"
}
Invoke-RestMethod -Uri "http://localhost:8000/api/users/" -Headers $headers
```

**Using curl (if installed):**
```powershell
# Get token
curl.exe -X POST http://localhost:8000/api/auth-token/ `
  -H "Content-Type: application/json" `
  -d '{\"username\": \"your_username\", \"password\": \"your_password\"}'

# Use token
curl.exe -H "Authorization: Token your_token_here" `
  http://localhost:8000/api/users/
```

### API Documentation

Access interactive API documentation:
- **Swagger UI**: http://localhost:8000/api/docs/
- **ReDoc**: Available via DRF Spectacular

## 🔒 Security Considerations

### ⚠️ Important Security Notes

1. **Never commit credentials**: The `env-txi.txt` file contains example credentials. Always use secure, environment-specific configuration files.

2. **Change default passwords**: Update all default passwords before deploying to production.

3. **Use HTTPS**: Always use HTTPS in production. Configure SSL certificates.

4. **Secure Django settings**:
   - Set `DEBUG = False` in production
   - Use strong `SECRET_KEY`
   - Configure `ALLOWED_HOSTS` properly
   - Enable security middleware settings

5. **Database security**:
   - Use strong database passwords
   - Limit database user permissions
   - Use connection encryption

6. **Asterisk security**:
   - Use strong AMI/ARI passwords
   - Restrict AMI access by IP
   - Use HTTPS for ARI connections

7. **Environment variables**: Store sensitive data in environment variables, not in code.

### Production Security Checklist

- [ ] Change all default passwords
- [ ] Set `DEBUG = False`
- [ ] Configure `ALLOWED_HOSTS`
- [ ] Use strong `SECRET_KEY`
- [ ] Enable HTTPS/SSL
- [ ] Configure secure cookies
- [ ] Set up proper firewall rules
- [ ] Regular security updates
- [ ] Database backups
- [ ] Monitor logs for suspicious activity

## 🐛 Troubleshooting

### Common Issues

#### Database Connection Error

```powershell
# Check PostgreSQL service is running
Get-Service postgresql*

# Start PostgreSQL service if stopped
Start-Service postgresql-x64-15  # Adjust version number as needed

# Check database exists (using psql)
# Navigate to PostgreSQL bin directory or add to PATH
& "C:\Program Files\PostgreSQL\15\bin\psql.exe" -U postgres -l

# Or use pgAdmin GUI to verify database exists

# Verify credentials in load-env.ps1 or environment variables
```

#### Redis Connection Error

```powershell
# If using Memurai, check service
Get-Service Memurai*

# Start Memurai if stopped
Start-Service Memurai

# Test connection
memurai-cli ping

# If using WSL2 Redis
wsl redis-cli ping

# Note: For development, you can skip Redis and use local memory cache
```

#### Port Already in Use

```powershell
# Find process using port 8000
netstat -ano | findstr :8000

# Or using PowerShell
Get-NetTCPConnection -LocalPort 8000 | Select-Object OwningProcess

# Find process name
Get-Process -Id <PID>

# Kill the process
Stop-Process -Id <PID> -Force

# Or use different port
python manage.py runserver 0.0.0.0:8001
```

#### Migration Errors

```powershell
# Reset migrations (CAUTION: Only in development)
python manage.py migrate --fake-initial

# Show migration status
python manage.py showmigrations

# If you need to reset completely (DEVELOPMENT ONLY):
# python manage.py migrate --fake-initial
```

#### Static Files Not Loading

```powershell
# Recollect static files
python manage.py collectstatic --noinput --clear

# Check STATIC_ROOT and STATIC_URL in settings
# Verify paths use forward slashes or raw strings in settings
```

#### Asterisk Connection Issues

- Verify Asterisk is running and accessible
- Check firewall rules
- Verify credentials in environment variables
- Test ARI/AMI connectivity manually

### Getting Help

1. **Check Django console output**: The development server shows errors in the console
2. **Enable debug mode temporarily** (development only): Set `DEBUG = True` in `config/settings/local.py`
3. **Check Django debug toolbar** (if installed): Available at `http://localhost:8000/__debug__/`
4. **Review application logs**: Check console output or configure logging in settings
5. **Check Windows Event Viewer**: For service-related issues
6. **Use Django shell**: `python manage.py shell` to test database connections

## 📝 Additional Resources

### Documentation
- [Django Documentation](https://docs.djangoproject.com/)
- [Django REST Framework](https://www.django-rest-framework.org/)
- [Asterisk Documentation](https://docs.asterisk.org/)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)

### Related Applications
- **phpMyAdmin**: MySQL/MariaDB administration
- **PostgreSQL Admin**: Database management interface
- **FOP2**: Asterisk operator panel

## 📄 License

[Specify your license here]

## 👥 Contributors

[Add contributors here]

## 🙏 Acknowledgments

- Django framework
- Django REST Framework
- Asterisk project
- All open-source contributors

---

**Note**: This is a production system. Always test changes in a development environment before deploying to production.

