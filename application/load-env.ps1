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
$env:ASTERISK_AMI_USER="carlos"
$env:ASTERISK_AMI_PASSWORD="123"
$env:APPLICATION_PATH=""

# Channel filters
$env:ASTERISK_AMI_FILTERCHANNEL_EX="DAHDI"
$env:ASTERISK_AMI_FILTERCHANNEL_IN="PJSIP"