sudo cd /var/www/html
sudo supervisorctl stop all
npm update
composer update
rm -rf /etc/supervisor/conf.d/*
cp laravel*.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart all

php artisan migrate
