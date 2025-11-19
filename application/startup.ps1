# Taxi App Startup Script for Windows
# This script activates the virtual environment, loads environment variables, and starts the Django server

# Get the script directory
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path

# Navigate to application directory
Set-Location $scriptDir

Write-Host "Activating virtual environment..." -ForegroundColor Green
# Activate virtual environment
& "$scriptDir\venvpy\Scripts\Activate.ps1"

# Wait a moment for activation
Start-Sleep -Milliseconds 500

Write-Host "Loading environment variables..." -ForegroundColor Green
# Load environment variables
. "$scriptDir\load-env.ps1"

Write-Host "Starting Django development server..." -ForegroundColor Green
# Navigate to apps_taxi directory
Set-Location "$scriptDir\apps_taxi"

# Start Django development server
python manage.py runserver 0.0.0.0:8000

