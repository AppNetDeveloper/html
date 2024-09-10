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
            <label for="mqtt_topic_1">MQTT Topic 1</label>
            <input type="text" name="mqtt_topic_1" class="form-control" value="{{ $sensor->mqtt_topic_1 }}" required>
        </div>

        <div class="form-group">
            <label for="function_model_0">Función Modelo 0</label>
            <input type="text" name="function_model_0" class="form-control" value="{{ $sensor->function_model_0 }}" required>
        </div>

        <div class="form-group">
            <label for="function_model_1">Función Modelo 1</label>
            <input type="text" name="function_model_1" class="form-control" value="{{ $sensor->function_model_1 }}" required>
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

        <button type="submit" class="btn btn-success">Actualizar Sensor</button>
    </form>
</div>
@endsection
