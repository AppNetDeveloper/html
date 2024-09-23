import threading
import time
import json
import paho.mqtt.client as mqtt
from pymodbus.client import ModbusTcpClient

# --- Configuración ---

# Modbus
modbus_host = "172.18.24.5"
modbus_port = 502

# Define manualmente las combinaciones de unit_id y address
modbus_configurations = [
    {"unit_id": 1, "address": 2, "check_address": 311},  # bascula cinta con check en 311
    {"unit_id": 1, "address": 9},
    # Añade más configuraciones aquí según sea necesario
]

# MQTT
mqtt_broker = "127.0.0.1"
mqtt_port = 1883
mqtt_topic_base = "modbus_data/ajos"  # Base topic

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

                    # Leer el valor principal
                    result = client_modbus.read_holding_registers(address, 1)
                    if not result.isError():
                        value = result.registers[0]
                        key = f"Unidad {unit_id}, Direccion {address}"

                        # Guardar la lectura del valor principal
                        lecturas[key] = value

                        # Crear payload para MQTT
                        payload = {"value": value}

                        # Si existe una check_address, leer también ese valor
                        if 'check_address' in config:
                            check_address = config['check_address']
                            result_check = client_modbus.read_holding_registers(check_address, 1)
                            if not result_check.isError():
                                check_value = result_check.registers[0]
                                payload['check'] = check_value
                                print(f"Unidad {unit_id}, Direccion {address}, Check {check_address}: {check_value}")
                            else:
                                print(f"Error al leer check_address {check_address} para la Unidad {unit_id}")
                        
                        # Publicar en MQTT
                        topic = f"{mqtt_topic_base}/Unidad{unit_id}/{address}"
                        client_mqtt.publish(topic, json.dumps(payload))
                        print(f"Published to MQTT: {topic} - {json.dumps(payload)}")

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
