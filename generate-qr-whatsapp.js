const { Boom } = require('@hapi/boom');
const makeWASocket = require('@whiskeysockets/baileys').default;
const { fetchLatestBaileysVersion, useMultiFileAuthState, DisconnectReason } = require('@whiskeysockets/baileys');
const P = require('pino');
const axios = require('axios');

// Configura el logger
const logger = P({ timestamp: () => `,"time":"${new Date().toJSON()}"` }, P.destination('./wa-logs.txt'));
logger.level = 'trace';

const startSock = async () => {
    // Obtener la versión de Baileys
    const { version, isLatest } = await fetchLatestBaileysVersion();
    console.log(`Usando la versión de WhatsApp v${version.join('.')}, ¿Es la última?: ${isLatest}`);

    // Usar el estado de autenticación
    const { state, saveCreds } = await useMultiFileAuthState('baileys_auth_info');
    console.log('Estado de las credenciales:', state);

    const sock = makeWASocket({
        version,
        logger,
        printQRInTerminal: true,
        auth: state,
    });

    sock.ev.process(async (events) => {
        if (events['connection.update']) {
            const update = events['connection.update'];
            const { connection, lastDisconnect, qr } = update;

            if (qr) {
                console.log('QR generado:', qr);
            }

            if (connection === 'close') {
                if ((lastDisconnect?.error instanceof Boom)?.output?.statusCode !== DisconnectReason.loggedOut) {
                    console.log('Conexión cerrada. Intentando reconectar...');
                    startSock();
                } else {
                    console.log('Conexión cerrada. Has sido desconectado.');
                }
            }

            if (connection === 'open') {
                console.log('Conexión exitosa.');

                // Extraer solo los datos esenciales de `creds` para almacenar
                const { registrationId, advSecretKey, me } = sock.authState.creds;
                const filteredCreds = {
                    registrationId,
                    advSecretKey,
                    me,
                };

                // Si `keys` está vacío, podemos omitirlo o definir algún valor según sea necesario
                const filteredKeys = {};  // Ajusta según tus necesidades, aquí asumimos que está vacío

                try {
                    const response = await axios.post('http://localhost/api/whatsapp-credentials', {
                        creds: JSON.stringify(filteredCreds), // Solo los campos filtrados
                        keys: JSON.stringify(filteredKeys)    // Solo un objeto vacío u otros valores necesarios
                    });
                    console.log('Datos enviados a la API. Respuesta:', response.data);
                } catch (error) {
                    console.error('Error al enviar los datos a la API:', error.response ? error.response.data : error.message);
                }

                console.log('Cerrando conexión...');
                await sock.logout();
                process.exit(0);
            }
        }

        if (events['creds.update']) {
            await saveCreds();
        }
    });
};

startSock();
