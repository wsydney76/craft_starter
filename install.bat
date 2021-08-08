@echo off
php craft install --interactive=0 --username=admin --password=password  --email=admin@starter.local  &&^
php craft index-assets/all  &&^
php craft migrate/up --interactive=0
