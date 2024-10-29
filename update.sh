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


# Definir el usuario y el comando que permitiremos sin contraseña
USER="www-data"
COMMAND="/usr/bin/supervisorctl restart all"

# Verificar si la línea ya está en sudoers
if sudo grep -Fxq "$USER ALL=(ALL) NOPASSWD: $COMMAND" /etc/sudoers
then
    echo "La entrada ya está en sudoers. No se requiere ninguna acción."
else
    # Añadir la entrada a sudoers
    echo "Añadiendo la entrada a sudoers..."
    echo "$USER ALL=(ALL) NOPASSWD: $COMMAND" | sudo EDITOR='tee -a' visudo
    echo "Entrada añadida correctamente a sudoers."
fi


echo "SHIFT_TIME=08:00:00" >> .env
echo "PRODUCTION_MIN_TIME=3" >> .env
echo "PRODUCTION_MAX_TIME=5" >> .env

echo "EXTERNAL_API_QUEUE_MODEL=dataToSend3" >> .env
echo "EXTERNAL_API_QUEUE_TYPE=put" >> .env
echo "USE_CURL=true" >> .env


sudo supervisorctl stop all
rm -rf /etc/supervisor/conf.d/*
cp laravel*.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart all