from flask import Flask, request, jsonify
from flask_cors import CORS  # Importar CORS
import barcode
from barcode.writer import ImageWriter
import subprocess
import os

app = Flask(__name__)
CORS(app)  # Habilitar CORS para todos los dominios

@app.route('/print', methods=['POST'])
def print_barcode():
    data = request.get_json()
    barcode_data = data.get('barcode')
    printer_name = data.get('printer')

    if not barcode_data or not printer_name:
        return jsonify({'error': 'Faltan datos'}), 400

    # Generar código de barras
    barcode_path = '/tmp/barcode'
    try:
        code128 = barcode.get_barcode_class('code128')
        code = code128(barcode_data, writer=ImageWriter())
        code.save(barcode_path)
    except Exception as e:
        return jsonify({'error': f'No se pudo crear el archivo del código de barras: {str(e)}'}), 500

    # Imprimir código de barras
    print_command = f'lp -d "{printer_name}" {barcode_path}.png'
    try:
        result = subprocess.run(print_command, shell=True, check=True, text=True, capture_output=True)
        return jsonify({'message': 'Código de barras enviado a imprimir'}), 200
    except subprocess.CalledProcessError as e:
        return jsonify({'error': f'Error al imprimir el código de barras: {e.stderr}'}), 500
    finally:
        # Limpiar archivo temporal
        if os.path.exists(barcode_path + '.png'):
            os.remove(barcode_path + '.png')

@app.route('/api.json', methods=['GET'])
def get_initial_data():
    try:
        # Devuelve datos iniciales de ejemplo
        return jsonify({'ultimoPeso': 120.00, 'codigoBarra': '123456789012'})
    except Exception as e:
        return jsonify({'status': 'error', 'message': str(e)}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5001, debug=True)
