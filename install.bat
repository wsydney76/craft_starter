@echo off
php craft install --interactive=0 --username=admin --password=password  --email=admin@starter.local  &&^
php craft migrate/up --interactive=0 &&^
php craft main/seed/create-posts 25
