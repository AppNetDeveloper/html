// connect-whatsapp.js

const { Boom } = require('@hapi/boom');
const NodeCache = require('node-cache');
const makeWASocket = require('@whiskeysockets/baileys').default;
const {
    delay,
    DisconnectReason,
    fetchLatestBaileysVersion,
    makeCacheableSignalKeyStore,
    makeInMemoryStore,
    useMultiFileAuthState,
} = require('@whiskeysockets/baileys');
const express = require('express');
const fs = require('fs');
const path = require('path');
const P = require('pino');

const app = express();
const port = 3005;

app.use(express.json());
const logger = P({ timestamp: () => `,"time":"${new Date().toJSON()}"` }, P.destination('./wa-logs.txt'));
logger.level = 'trace';

const authFilePath = './baileys_auth_info';
const store = makeInMemoryStore({ logger });
store.readFromFile('./baileys_store_multi.json');
setInterval(() => {
    store.writeToFile('./baileys_store_multi.json');
}, 10_000);

let sock;
let qrCode = '';

const startSock = async () => {
    if (fs.existsSync(authFilePath)) {
        fs.rmSync(authFilePath, { recursive: true, force: true });
    }

    const { state, saveCreds } = await useMultiFileAuthState(authFilePath);
    const { version, isLatest } = await fetchLatestBaileysVersion();
    console.log(`Usando la versión de WhatsApp v${version.join('.')}, ¿Es la última?: ${isLatest}`);

    sock = makeWASocket({
        version,
        logger,
        printQRInTerminal: true,
        auth: {
            creds: state.creds,
            keys: makeCacheableSignalKeyStore(state.keys, logger),
        },
        msgRetryCounterCache: new NodeCache(),
        generateHighQualityLinkPreview: true,
        getMessage: async (key) => {
            const msg = await store.loadMessage(key.remoteJid, key.id);
            return msg?.message || undefined;
        },
    });

    store.bind(sock.ev);

    sock.ev.process(async (events) => {
        if (events['connection.update']) {
            const update = events['connection.update'];
            const { connection, lastDisconnect, qr } = update;

            if (qr) {
                qrCode = qr;
                console.log('QR generado:', qr);
                fs.writeFileSync(path.join(__dirname, 'whatsapp-qr.txt'), qr);
            }

            if (connection === 'close') {
                if ((lastDisconnect?.error instanceof Boom)?.output?.statusCode !== DisconnectReason.loggedOut) {
                    console.log('Intentando reconectar...');
                    await startSock();
                } else {
                    console.log('Conexión cerrada. Has sido desconectado.');
                }
            } else if (connection === 'open') {
                console.log('Conexión exitosa.');
                qrCode = ''; // Limpiar el QR después de conectarse exitosamente
            }

            console.log('Actualización de conexión', update);
        }

        if (events['creds.update']) {
            await saveCreds();
        }

        if (events['messages.upsert']) {
            const upsert = events['messages.upsert'];
            console.log('Recibido mensaje', JSON.stringify(upsert, undefined, 2));
        }
    });
};

// Endpoint para iniciar la conexión a WhatsApp manualmente
app.post('/start-whatsapp', async (req, res) => {
    try {
        await startSock();
        res.json({ message: 'Conexión a WhatsApp iniciada' });
    } catch (error) {
        console.error('Error al iniciar WhatsApp:', error);
        res.status(500).json({ error: 'Error al iniciar la conexión a WhatsApp' });
    }
});

// Endpoint para obtener el QR
app.get('/get-qr', (req, res) => {
    try {
        if (qrCode) {
            res.json({ qr: qrCode });
        } else {
            res.status(400).json({ error: 'No hay QR generado actualmente' });
        }
    } catch (error) {
        console.error('Error al obtener el QR:', error);
        res.status(500).json({ error: 'Error al obtener el QR' });
    }
});

// Endpoint para cerrar la sesión y limpiar el archivo de autenticación
app.post('/logout', async (req, res) => {
    try {
        if (sock) {
            await sock.logout();
            sock = null;

            // Eliminar el archivo de autenticación si existe
            if (fs.existsSync('./baileys_auth_info')) {
                fs.rmSync('./baileys_auth_info', { recursive: true, force: true });
            }

            console.log('Sesión cerrada y autenticación eliminada');
            res.json({ message: 'Sesión cerrada y autenticación eliminada' });
        } else {
            res.status(400).json({ error: 'No hay una sesión activa' });
        }
    } catch (error) {
        console.error('Error al cerrar sesión:', error);
        res.status(500).json({ error: 'Error al cerrar sesión' });
    }
});

// Endpoint para enviar mensajes
app.post('/send-message', async (req, res) => {
    const { jid, message } = req.body;
    try {
        if (!sock) {
            return res.status(400).json({ error: 'Conexión a WhatsApp no establecida' });
        }
        await sock.sendMessage(jid, { text: message });
        res.json({ message: 'Mensaje enviado correctamente' });
    } catch (error) {
        console.error('Error al enviar mensaje:', error);
        res.status(500).json({ error: 'Error al enviar el mensaje' });
    }
});

app.listen(port, () => {
    console.log(`Servidor de API de WhatsApp escuchando en http://localhost:${port}`);
});

// Iniciar la conexión a WhatsApp automáticamente al ejecutar el script
startSock();
