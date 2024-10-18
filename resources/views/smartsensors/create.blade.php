@extends('layouts.admin')
@section('title', 'Crear Sensor')
@section('content')
<div class="container">
    <h1>Crear Nuevo Sensor</h1>
    <form action="{{ route('smartsensors.store', ['production_line_id' => $production_line_id]) }}" method="POST">
        @csrf
        
        <div class="form-group">
            <label for="name">Nombre del Sensor</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="barcoder_id">Barcoder</label>
            <select name="barcoder_id" class="form-control" required>
                @foreach ($barcoders as $barcoder)
                    <option value="{{ $barcoder->id }}">{{ $barcoder->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="sensor_type">Tipo de Sensor</label>
            <select name="sensor_type" class="form-control" required>
                <option value="0">Conteo</option>
                <option value="1">Consumibles</option>
                <option value="2">Materia Prima</option>
                <option value="3">Avería en Proceso</option>
            </select>
        </div>

        <div class="form-group">
            <label for="optimal_production_time">Tiempo Óptimo de Producción</label>
            <input type="number" name="optimal_production_time" class="form-control" min="0">
        </div>

        <div class="form-group">
            <label for="reduced_speed_time_multiplier">Multiplicador de Velocidad Reducida</label>
            <input type="number" name="reduced_speed_time_multiplier" class="form-control" min="0">
        </div>

        <div class="form-group">
            <label for="json_api">JSON API</label>
            <textarea name="json_api" class="form-control" rows="3"></textarea>
        </div>

        <div class="form-group">
            <label for="mqtt_topic_sensor">MQTT Topic Sensor</label>
            <input type="text" name="mqtt_topic_sensor" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="mqtt_topic_1">MQTT Topic Envío (Ejemplo: c/cliente/pli/PLI03/sta/PLI03STA01/mac/PLI03STA01MAC03)</label>
            <input type="text" name="mqtt_topic_1" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="function_model_0">Función Modelo 0</label>
            <select name="function_model_0" class="form-control" required>
                <option value="sendMqttValue0">sendMqttValue0</option>
                <option value="none">none</option>
            </select>
        </div>

        <div class="form-group">
            <label for="function_model_1">Función Modelo 1</label>
            <select name="function_model_1" class="form-control" required>
                <option value="sendMqttValue1">sendMqttValue1</option>
                <option value="none">none</option>
            </select>
        </div>

        <div class="form-group">
            <label for="count_total">Contador Total</label>
            <input type="number" name="count_total" class="form-control" value="0" min="0">
        </div>

        <div class="form-group">
            <label for="count_total_0">Contador Total 0</label>
            <input type="number" name="count_total_0" class="form-control" value="0" min="0">
        </div>

        <div class="form-group">
            <label for="count_total_1">Contador Total 1</label>
            <input type="number" name="count_total_1" class="form-control" value="0" min="0">
        </div>

        <div class="form-group">
            <label for="count_shift_0">Contador Turno 0</label>
            <input type="number" name="count_shift_0" class="form-control" value="0" min="0">
        </div>

        <div class="form-group">
            <label for="count_shift_1">Contador Turno 1</label>
            <input type="number" name="count_shift_1" class="form-control" value="0" min="0">
        </div>

        <div class="form-group">
            <label for="count_order_0">Contador Pedido 0</label>
            <input type="number" name="count_order_0" class="form-control" value="0" min="0">
        </div>

        <div class="form-group">
            <label for="count_order_1">Contador Pedido 1</label>
            <input type="number" name="count_order_1" class="form-control" value="0" min="0">
        </div>

        <div class="form-group">
            <label for="invers_sensors">Sensor Inverso</label>
            <select name="invers_sensors" class="form-control">
                <option value="0">No</option>
                <option value="1">Sí</option>
            </select>
        </div>

        <div class="form-group">
            <label for="downtime_count">Tiempo de Inactividad</label>
            <input type="number" name="downtime_count" class="form-control" value="0" min="0">
        </div>

        <div class="form-group">
            <label for="unic_code_order">Código Único de Orden</label>
            <input type="text" name="unic_code_order" class="form-control">
        </div>

        <button type="submit" class="btn btn-success">Crear Sensor</button>
    </form>
</div>
@endsection
