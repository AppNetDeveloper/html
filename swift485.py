import time
from pymodbus.client import ModbusSerialClient
import paho.mqtt.client as mqtt
import json
import threading

# Crear un bloqueo para evitar conflictos en el uso del cliente Modbus y un evento para pausar/continuar la lectura de peso
modbus_lock = threading.Lock()
pause_event = threading.Event()

# Configuración del cliente Modbus
client = ModbusSerialClient(
    port='/dev/ttyUSB0',  # Cambia al puerto adecuado en Linux
    baudrate=9600,
    timeout=1,
    stopbits=1,
    bytesize=8,
    parity='N'
)

# Configuración del cliente MQTT
mqtt_broker = "localhost"  # IP de tu broker MQTT
mqtt_base_topic = "sensorica/bascula/peso"  # Tópico base para las básculas
mqtt_dosificador_topic = "sensorica/bascula/dosifica"  # Tópico base para dosificación
mqtt_zero_topic = "sensorica/bascula/zero"  # Tópico para hacer cero
mqtt_tara_topic = "sensorica/bascula/tara"  # Tópico para hacer y leer la tara

def on_connect(mqtt_client, userdata, flags, rc):
    if rc == 0:
        print("Conexión MQTT exitosa.")
        mqtt_client.subscribe(f"{mqtt_dosificador_topic}/#")  # Escuchar todos los sub-tópicos de dosificación
        mqtt_client.subscribe(f"{mqtt_zero_topic}/#")  # Escuchar todos los sub-tópicos de cero
        mqtt_client.subscribe(f"{mqtt_tara_topic}/#")  # Escuchar todos los sub-tópicos de tara
    else:
        print(f"Conexión MQTT fallida con código {rc}")

def on_message(mqtt_client, userdata, message):
    topic = message.topic
    payload = json.loads(message.payload.decode('utf-8'))
    print(f"Mensaje recibido en tópico {topic}: {payload}")

    # Pausar la lectura de pesos cuando recibes un comando
    pause_event.clear()  # Detener temporalmente la lectura

    # Extraer la dirección del Modbus del tópico MQTT (último número del tópico)
    try:
        direccion_modbus = int(topic.split('/')[-1])
    except ValueError:
        print(f"Error: La dirección Modbus en el tópico {topic} no es válida.")
        pause_event.set()  # Reanudar la lectura
        return

    # Verificar que la dirección esté dentro del rango permitido (2 a 7)
    if 2 <= direccion_modbus <= 7:
        # Si el tópico es para dosificación
        if mqtt_dosificador_topic in topic and 'value' in payload:
            iniciar_dosificacion(direccion_modbus, payload['value'])
        # Si el tópico es para hacer cero
        elif mqtt_zero_topic in topic and 'value' in payload and payload['value'] is True:
            hacer_cero(direccion_modbus)
        # Si el tópico es para hacer tara
        elif mqtt_tara_topic in topic and 'value' in payload and payload['value'] is True:
            hacer_tara(direccion_modbus)
        # Si el tópico es para leer el valor de la tara
        elif mqtt_tara_topic in topic and 'read' in payload and payload['read'] is True:
            valor_tara = leer_valor_tara(direccion_modbus)
            if valor_tara is not None:
                mqtt_topic = f"{mqtt_tara_topic}/{direccion_modbus}"
                payload_tara = json.dumps({"tara": valor_tara})
                mqtt_client.publish(mqtt_topic, payload_tara)
                print(f"Publicado en MQTT - Tópico: {mqtt_topic}, Payload: {payload_tara}")
    else:
        print(f"Error: Dirección Modbus fuera del rango permitido (2-7). Dirección recibida: {direccion_modbus}")

    # Reanudar la lectura después de ejecutar el comando
    pause_event.set()

# Función para iniciar dosificación en la dirección Modbus dada con un valor de peso objetivo
def iniciar_dosificacion(direccion_modbus, peso_objetivo):
    with modbus_lock:  # Asegura que solo un hilo acceda al cliente Modbus a la vez
        try:
            print(f"Iniciando dosificación en dirección Modbus {direccion_modbus} con {peso_objetivo / 10.0} kg.")
            
            # Escribir el valor de peso en los registros 1001 (parte alta) y 1002 (parte baja)
            client.write_registers(1001, [peso_objetivo >> 16, peso_objetivo & 0xFFFF], slave=direccion_modbus)
            
            # Enviar el comando para iniciar la dosificación (código 13) en el registro 1000
            client.write_register(1000, 13, slave=direccion_modbus)

            print(f"Dosificación de {peso_objetivo / 10.0} kg iniciada en dirección Modbus {direccion_modbus}.")
        except Exception as e:
            print(f"Error al realizar la dosificación: {e}")

# Función para hacer "cero" (tarar) en la dirección Modbus dada
def hacer_cero(direccion_modbus):
    with modbus_lock:  # Asegura que solo un hilo acceda al cliente Modbus a la vez
        try:
            print(f"Haciendo cero en dirección Modbus {direccion_modbus}.")
            
            # Enviar el comando para hacer cero (asumimos que el comando es el código 1 en el registro 1000)
            client.write_register(1000, 1, slave=direccion_modbus)

            print(f"Cero realizado en dirección Modbus {direccion_modbus}.")
        except Exception as e:
            print(f"Error al realizar el cero: {e}")

# Función para hacer "tara" en la dirección Modbus dada
def hacer_tara(direccion_modbus):
    with modbus_lock:  # Asegura que solo un hilo acceda al cliente Modbus a la vez
        try:
            print(f"Haciendo tara en dirección Modbus {direccion_modbus}.")
            
            # Enviar el comando para hacer tara (asumimos que el comando es el código 2 en el registro 1000)
            client.write_register(1000, 2, slave=direccion_modbus)

            print(f"Tara realizada en dirección Modbus {direccion_modbus}.")
        except Exception as e:
            print(f"Error al realizar la tara: {e}")

# Función para leer el valor de tara
def leer_valor_tara(direccion_modbus):
    with modbus_lock:  # Usar el mismo bloqueo para evitar conflicto con dosificación o cero
        try:
            print(f"Leyendo valor de tara en dirección Modbus {direccion_modbus}.")
            
            # Leer el valor de la tara, asumimos que el valor está en un registro específico, por ejemplo 1003
            response = client.read_holding_registers(1003, 2, slave=direccion_modbus)
            
            if response.isError():
                print(f"Error al leer el valor de la tara en dirección {direccion_modbus}.")
            else:
                # Combinar los dos registros para formar un valor de 32 bits (valor de tara)
                valor_tara = (response.registers[0] << 16) + response.registers[1]
                valor_tara /= 10.0  # Ajustar factor de conversión si es necesario
                print(f"Valor de tara leído en la dirección {direccion_modbus}: {valor_tara} kg.")
                return valor_tara
        except Exception as e:
            print(f"Error al leer el valor de tara: {e}")
            return None

# Escaneo y lectura del peso de las básculas desde Input Registers
def escanear_y_leer_peso():
    # Mantener la conexión Modbus abierta durante todo el ciclo de vida
    if client.connect():
        print("Conexión Modbus exitosa")
        while True:
            # Esperar que no haya comandos en ejecución antes de seguir con la lectura
            pause_event.wait()

            with modbus_lock:  # Usar el mismo bloqueo para evitar conflicto con dosificación
                try:
                    registro_peso_base = 9  # 30010 - 30001 = 9

                    for direccion in range(2, 7):  # Escaneo de direcciones
                        try:
                            response = client.read_input_registers(registro_peso_base, 2, slave=direccion)  # Lectura de 2 registros
                            if response.isError():
                                print(f"Sin respuesta en la dirección: {direccion}")
                            else:
                                # Combinar los dos registros para formar un valor de 32 bits (peso neto)
                                peso_neto = (response.registers[0] << 16) + response.registers[1]
                                peso_neto /= 10.0  # Ajustar factor de conversión
                                
                                # Publicar los datos a un tópico MQTT único para cada dirección
                                mqtt_topic = f"{mqtt_base_topic}/{direccion}"
                                payload = json.dumps({"value": peso_neto})
                                mqtt_client.publish(mqtt_topic, payload)
                        except Exception as e:
                            print(f"Error al escanear la dirección {direccion}: {e}")

                    time.sleep(0.1)  # Añadir un retardo para evitar lecturas continuas y sobrecarga
                except Exception as e:
                    print(f"Error general en el proceso: {e}. Reintentando en 5 segundos...")
                    time.sleep(5)
    else:
        print("Error al conectar con el puerto Modbus. Reintentando en 5 segundos...")
        time.sleep(5)

if __name__ == "__main__":
    # Configuración del cliente MQTT
    mqtt_client = mqtt.Client()
    mqtt_client.on_connect = on_connect
    mqtt_client.on_message = on_message

    # Intento de conexión MQTT
    try:
        mqtt_client.connect(mqtt_broker)
        mqtt_client.loop_start()  # Inicia el bucle de mensajes MQTT
    except Exception as e:
        print(f"Error al conectar con el broker MQTT: {e}. Reintentando en 5 segundos...")
        time.sleep(5)
    
    # Activar el evento para que empiece la lectura
    pause_event.set()

    # Iniciar el proceso de escaneo y lectura de pesos
    escanear_y_leer_peso()
