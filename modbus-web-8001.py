import threading
import time
import json
import paho.mqtt.client as mqtt
from pymodbus.client import ModbusTcpClient

# --- Configuración ---

# Modbus
modbus_host = "192.168.1.117"
modbus_port = 502

# Define manualmente las combinaciones de unit_id y address
modbus_configurations = [
    {"unit_id": 1, "address": 300},
    {"unit_id": 1, "address": 301},  # bascula cinta 
    {"unit_id": 1, "address": 120},
    {"unit_id": 1, "address": 121},
    {"unit_id": 1, "address": 220},
    {"unit_id": 1, "address": 200},
    {"unit_id": 1, "address": 221},
    {"unit_id": 1, "address": 201},
    {"unit_id": 1, "address": 100},
    {"unit_id": 1, "address": 101},
    {"unit_id": 1, "address": 310},
    {"unit_id": 1, "address": 311},  # peso dinamico de 301
    {"unit_id": 1, "address": 350},  # bascula fija fuera?
    {"unit_id": 1, "address": 351},  # bascula fija fuera
    # Añade más configuraciones aquí según sea necesario
]

# MQTT
mqtt_broker = "172.25.30.240"
mqtt_port = 1883
mqtt_topic_base = "modbus_data/diacaproduct"  # Base topic

# Shared data
lecturas = {}
lock = threading.Lock()  # Create a lock to synchronize access to lecturas

# --- Funciones ---

def on_mqtt_connect(client, userdata, flags, rc):
    if rc == 0:
        print("Connected to MQTT broker successfully")
    else:
        print(f"Failed to connect to MQTT broker, return code {rc}")

def connect_mqtt():
    client_mqtt = mqtt.Client()
    client_mqtt.on_connect = on_mqtt_connect
    while True:
        try:
            client_mqtt.connect(mqtt_broker, mqtt_port)
            client_mqtt.loop_start()
            print("MQTT connected")
            return client_mqtt
        except Exception as e:
            print(f"Error connecting to MQTT: {e}. Retrying in 5 seconds...")
            time.sleep(5)

def connect_modbus():
    client_modbus = ModbusTcpClient(modbus_host, port=modbus_port)
    while True:
        try:
            if client_modbus.connect():
                print("Modbus connected")
                return client_modbus
            else:
                raise Exception("Modbus connection failed")
        except Exception as e:
            print(f"Error connecting to Modbus: {e}. Retrying in 5 seconds...")
            time.sleep(5)

def read_modbus_and_publish():
    global lecturas
    client_modbus = connect_modbus()
    client_mqtt = connect_mqtt()

    while True:
        try:
            with lock:  # Acquire the lock before updating lecturas
                for config in modbus_configurations:
                    unit_id = config['unit_id']
                    address = config['address']

                    result = client_modbus.read_holding_registers(address, 1)
                    if not result.isError():
                        value = result.registers[0]
                        key = f"Unidad {unit_id}, Direccion {address}"

                        # Guardar la lectura
                        lecturas[key] = value

                        # Publicar en MQTT
                        topic = f"{mqtt_topic_base}/Unidad{unit_id}/{address}"
                        payload = json.dumps({"value": value})
                        client_mqtt.publish(topic, payload)
                        print(f"Published to MQTT: {topic} - {payload}")

                    else:
                        print(f"Error al leer la Unidad {unit_id} en la Direccion {address}")
            time.sleep(0.3)  # Pausa de 0.3 segundos entre lecturas
        except Exception as e:
            print(f"Error in Modbus loop: {e}. Reconnecting...")
            client_modbus = connect_modbus()
            client_mqtt = connect_mqtt()

# --- Main ---

if __name__ == '__main__':
    # Start the Modbus reading and MQTT publishing in a separate thread
    threading.Thread(target=read_modbus_and_publish, daemon=True).start()

    # El código ahora solo se ejecuta en segundo plano para Modbus y MQTT
    while True:
        time.sleep(1)  # Mantiene el hilo principal activo
