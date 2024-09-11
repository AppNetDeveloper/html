sudo cd /var/www/html
sudo supervisorctl stop all
git add .
git commit -m "Guardando cambios locales antes de rebase"
git pull --rebase origin main
# (resuelve conflictos si los hay)
git rebase --continue
git push origin main

php artisan migrate

npm update
composer update
rm -rf /etc/supervisor/conf.d/*
cp laravel*.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart all