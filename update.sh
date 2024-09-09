sudo cd /var/www/html
sudo supervisorctl stop all
npm update
composer update
rm -rf /etc/supervisor/conf.d/*
cp laravel*.conf /etc/supervisor/conf.d/
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl restart all

# Añadir las credenciales SFTP al archivo .env si no existen
ENV_FILE="/var/www/html/.env"

if ! grep -q "SFTP_HOST" "$ENV_FILE"; then
    echo -e "\n# SFTP Configuration" >> "$ENV_FILE"
    echo "SFTP_HOST=127.0.0.1" >> "$ENV_FILE"
    echo "SFTP_USERNAME=root" >> "$ENV_FILE"
    echo "SFTP_PASSWORD=lss281613858715" >> "$ENV_FILE"
    # Si prefieres usar clave privada en lugar de contraseña, comenta o descomenta las líneas siguientes
    # echo "SFTP_PRIVATE_KEY=/path/to/privatekey" >> "$ENV_FILE"
    echo "SFTP_ROOT=/home" >> "$ENV_FILE"
    echo "SFTP_PORT=22" >> "$ENV_FILE"
fi