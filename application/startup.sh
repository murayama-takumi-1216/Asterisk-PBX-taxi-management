#!/bin/bash
cd /var/www/application
source venvpy/bin/activate
sleep .5
source env-txi.txt
cd apps_taxi
python manage.py runserver 0:8000
