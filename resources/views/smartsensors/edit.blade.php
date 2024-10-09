@extends('layouts.admin')
@section('title', 'Editar Sensor')
@section('content')
<div class="container">
    <h1>Editar Sensor</h1>
    <form action="{{ route('smartsensors.update', $sensor->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="form-group">
            <label for="name">Nombre del Sensor</label>
            <input type="text" name="name" class="form-control" value="{{ $sensor->name }}" required>
        </div>

        <div class="form-group">
            <label for="barcoder_id">Barcoder</label>
            <select name="barcoder_id" class="form-control" required>
                @foreach ($barcoders as $barcoder)
                    <option value="{{ $barcoder->id }}" {{ $sensor->barcoder_id == $barcoder->id ? 'selected' : '' }}>{{ $barcoder->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="sensor_type">Tipo de Sensor</label>
            <select name="sensor_type" class="form-control" required>
                <option value="0" {{ $sensor->sensor_type == 0 ? 'selected' : '' }}>Conteo</option>
                <option value="1" {{ $sensor->sensor_type == 1 ? 'selected' : '' }}>Consumibles</option>
                <option value="2" {{ $sensor->sensor_type == 2 ? 'selected' : '' }}>Materia Prima</option>
                <option value="3" {{ $sensor->sensor_type == 3 ? 'selected' : '' }}>Avería en Proceso</option>
            </select>
        </div>

        <div class="form-group">
            <label for="optimal_production_time">Tiempo Óptimo de Producción</label>
            <input type="number" name="optimal_production_time" class="form-control" value="{{ $sensor->optimal_production_time }}">
        </div>

        <div class="form-group">
            <label for="reduced_speed_time_multiplier">Multiplicador de Velocidad Reducida</label>
            <input type="number" name="reduced_speed_time_multiplier" class="form-control" value="{{ $sensor->reduced_speed_time_multiplier }}">
        </div>

        <div class="form-group">
            <label for="json_api">JSON API</label>
            <textarea name="json_api" class="form-control" rows="3">{{ $sensor->json_api }}</textarea>
        </div>

        <div class="form-group">
            <label for="mqtt_topic_sensor">MQTT Topic Sensor</label>
            <input type="text" name="mqtt_topic_sensor" class="form-control" value="{{ $sensor->mqtt_topic_sensor }}" required>
        </div>


        <div class="form-group">
            <label for="mqtt_topic_1">MQTT Topic envio topflow tipo: c/cliente/pli/PLI03/sta/PLI03STA01/mac/PLI03STA01MAC03  Los demas los genera solo</label>
            <input type="text" name="mqtt_topic_1" class="form-control" value="{{ $sensor->mqtt_topic_1 }}" required>
        </div>

        <div class="form-group">
            <label for="function_model_0">Función Modelo 0</label>
            <select name="function_model_0" class="form-control" required>
                <option value="sendMqttValue0" {{ $sensor->function_model_0 == 'sendMqttValue0' ? 'selected' : '' }}>sendMqttValue0</option>
                <option value="none" {{ $sensor->function_model_0 == 'none' ? 'selected' : '' }}>none</option>
            </select>
        </div>

        <div class="form-group">
            <label for="function_model_1">Función Modelo 1</label>
            <select name="function_model_1" class="form-control" required>
                <option value="sendMqttValue1" {{ $sensor->function_model_1 == 'sendMqttValue1' ? 'selected' : '' }}>sendMqttValue1</option>
                <option value="none" {{ $sensor->function_model_1 == 'none' ? 'selected' : '' }}>none</option>
            </select>
        </div>

        <div class="form-group">
            <label for="count_total">Contador Total</label>
            <input type="number" name="count_total" class="form-control" value="{{ $sensor->count_total }}" min="0">
        </div>

        <div class="form-group">
            <label for="count_total_0">Contador Total 0</label>
            <input type="number" name="count_total_0" class="form-control" value="{{ $sensor->count_total_0 }}" min="0">
        </div>

        <div class="form-group">
            <label for="count_total_1">Contador Total 1</label>
            <input type="number" name="count_total_1" class="form-control" value="{{ $sensor->count_total_1 }}" min="0">
        </div>

        <div class="form-group">
            <label for="count_shift_0">Contador Turno 0</label>
            <input type="number" name="count_shift_0" class="form-control" value="{{ $sensor->count_shift_0 }}" min="0">
        </div>

        <div class="form-group">
            <label for="count_shift_1">Contador Turno 1</label>
            <input type="number" name="count_shift_1" class="form-control" value="{{ $sensor->count_shift_1 }}" min="0">
        </div>

        <div class="form-group">
            <label for="count_order_0">Contador Pedido 0</label>
            <input type="number" name="count_order_0" class="form-control" value="{{ $sensor->count_order_0 }}" min="0">
        </div>

        <div class="form-group">
            <label for="count_order_1">Contador Pedido 1</label>
            <input type="number" name="count_order_1" class="form-control" value="{{ $sensor->count_order_1 }}" min="0">
        </div>

        <div class="form-group">
            <label for="invers_sensors">Sensor Inverso</label>
            <select name="invers_sensors" class="form-control">
                <option value="0" {{ $sensor->invers_sensors == 0 ? 'selected' : '' }}>No</option>
                <option value="1" {{ $sensor->invers_sensors == 1 ? 'selected' : '' }}>Sí</option>
            </select>
        </div>

        <div class="form-group">
            <label for="downtime_count">Tiempo de Inactividad</label>
            <input type="number" name="downtime_count" class="form-control" value="{{ $sensor->downtime_count }}" min="0">
        </div>

        <div class="form-group">
            <label for="unic_code_order">Código Único de Orden</label>
            <input type="text" name="unic_code_order" class="form-control" value="{{ $sensor->unic_code_order }}">
        </div>

        <button type="submit" class="btn btn-success">Actualizar Sensor</button>
    </form>
</div>
@endsection

