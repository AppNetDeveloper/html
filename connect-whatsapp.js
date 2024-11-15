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
const P = require('pino');
const fs = require('fs');
const axios = require('axios');

// Configuración de Express
const app = express();
const port = 3005;

// Variable global para almacenar el código QR
let qrCode = null;

app.use(express.json()); // Middleware para procesar JSON en solicitudes

const logger = P({ timestamp: () => `,"time":"${new Date().toJSON()}"` }, P.destination('./wa-logs.txt'));
logger.level = 'trace';

// Configuración de Baileys y almacenamiento
const useStore = true;
const msgRetryCounterCache = new NodeCache();
const store = useStore ? makeInMemoryStore({ logger }) : undefined;

store?.readFromFile('./baileys_store_multi.json');
setInterval(() => {
    store?.writeToFile('./baileys_store_multi.json');
}, 10_000);

let sock;
const chatMessagesStore = {}; // Almacenamiento de mensajes

const startSock = async () => {
    const { state, saveCreds } = await useMultiFileAuthState('baileys_auth_info');
    const { version, isLatest } = await fetchLatestBaileysVersion();
    console.log(`Usando la versión de WhatsApp v${version.join('.')}, ¿Es la última?: ${isLatest}`);

    sock = makeWASocket({
        version,
        logger,
        printQRInTerminal: true,  // Siempre imprime el QR en la terminal
        auth: {
            creds: state.creds,
            keys: makeCacheableSignalKeyStore(state.keys, logger),
        },
        msgRetryCounterCache,
        getMessage: async (key) => {
            if (store) {
                const msg = await store.loadMessage(key.remoteJid, key.id);
                return msg?.message || undefined;
            }
            return {};
        },
    });

    store?.bind(sock.ev);

    sock.ev.process(async (events) => {
        if (events['connection.update']) {
            const update = events['connection.update'];
            const { connection, lastDisconnect, qr } = update;
            
            // Almacenar el código QR cuando se genera
            if (qr) {
                qrCode = qr;
                console.log('Nuevo código QR generado:', qr);
            }

            if (connection === 'close') {
                if ((lastDisconnect?.error instanceof Boom)?.output?.statusCode !== DisconnectReason.loggedOut) {
                    console.log('Intentando reconectar...');
                    await startSock();
                } else {
                    console.log('Conexión cerrada. Has sido desconectado.');
                }
            }
            console.log('Actualización de conexión', update);

            if (connection === 'open') {
                console.log('Conexión exitosa.');
                // Limpiar el código QR cuando la conexión es exitosa
                qrCode = null;

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
            }
        }

        if (events['creds.update']) {
            await saveCreds();
        }

        if (events['messages.upsert']) {
            const upsert = events['messages.upsert'];
            console.log('Evento "messages.upsert" capturado:', JSON.stringify(upsert, null, 2)); // Depuración inicial

            if (upsert.type === 'notify') {
                for (const msg of upsert.messages) {
                    const jid = msg.key.remoteJid;
                    console.log(`Mensaje recibido de ${jid}:`, msg); // Mensaje específico

                    if (!chatMessagesStore[jid]) {
                        chatMessagesStore[jid] = [];
                    }

                    chatMessagesStore[jid].push({
                        id: msg.key.id,
                        from: msg.key.fromMe ? 'me' : 'other',
                        text: msg.message?.conversation || msg.message?.extendedTextMessage?.text || null,
                        timestamp: msg.messageTimestamp,
                    });

                    console.log(`Mensajes almacenados para ${jid}:`, chatMessagesStore[jid]); // Confirmación de almacenamiento
                }
            }
        }
    });
};

// Endpoints de la API
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
            res.json({ 
                success: true,
                qr: qrCode,
                message: 'Código QR disponible'
            });
        } else {
            res.status(404).json({ 
                success: false,
                message: 'No hay código QR disponible. El dispositivo podría estar ya conectado o aún no se ha generado el código.'
            });
        }
    } catch (error) {
        console.error('Error al obtener el QR:', error);
        res.status(500).json({ 
            success: false,
            message: 'Error al obtener el código QR',
            error: error.message
        });
    }
});

// Endpoint para cerrar la sesión y limpiar el archivo de autenticación
app.post('/logout', async (req, res) => {
    try {
        if (sock) {
            // Eliminar el directorio de autenticación
            const authDir = './baileys_auth_info';
            if (fs.existsSync(authDir)) {
                fs.rmSync(authDir, { recursive: true, force: true });
            }
            // Cerrar sesión de WhatsApp
            await sock.logout();
            sock = null;
            // Limpiar el código QR al cerrar sesión
            qrCode = null;

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

app.get('/get-chats', async (req, res) => {
    try {
        const chats = store.chats.all().map(chat => ({
            jid: chat.id,
            name: chat.name || chat.id,
            lastMessage: chat.lastMessage?.message?.conversation || null,
            unreadCount: chat.unreadCount || 0,
        }));
        res.json({ chats });
    } catch (error) {
        console.error('Error al obtener conversaciones:', error);
        res.status(500).json({ error: 'Error al obtener las conversaciones' });
    }
});

app.get('/get-messages', async (req, res) => {
    const { jid } = req.query;
    try {
        if (!jid) {
            return res.status(400).json({ error: 'El parámetro "jid" es requerido' });
        }

        console.log(`Buscando mensajes para ${jid}. Almacenamiento completo:`, chatMessagesStore); // Verificación

        const messages = chatMessagesStore[jid] || [];

        res.json({ messages });
    } catch (error) {
        console.error('Error al obtener mensajes de la conversación:', error);
        res.status(500).json({ error: 'Error al obtener los mensajes de la conversación' });
    }
});

// Iniciar el servidor Express
app.listen(port, () => {
    console.log(`Servidor de API de WhatsApp escuchando en http://localhost:${port}`);
});

// Iniciar la conexión a WhatsApp
startSock();