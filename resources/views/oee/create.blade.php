@extends('layouts.admin')
@section('title', 'Crear Monitor OEE')
@section('content')
<div class="container">
    <h1>Crear Nuevo Monitor OEE</h1>
    <form action="{{ route('oee.store', ['production_line_id' => $production_line_id]) }}" method="POST">
        @csrf
        
        <div class="form-group">
            <label for="production_line_id">Línea de Producción</label>
            <select name="production_line_id" class="form-control" required>
                @foreach ($productionLines as $productionLine)
                    <option value="{{ $productionLine->id }}">{{ $productionLine->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="mqtt_topic">MQTT Topic</label>
            <input type="text" name="mqtt_topic" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="mqtt_topic2">MQTT Topic 2 (Opcional)</label>
            <input type="text" name="mqtt_topic2" class="form-control">
        </div>

        <div class="form-group">
            <label for="topic_oee">Topic OEE (Opcional)</label>
            <input type="text" name="topic_oee" class="form-control">
        </div>

        <div class="form-group">
            <label for="sensor_active">Sensor Activo</label>
            <select name="sensor_active" class="form-control" required>
                <option value="1">Sí</option>
                <option value="0">No</option>
            </select>
        </div>

        <div class="form-group">
            <label for="modbus_active">Modbus Activo</label>
            <select name="modbus_active" class="form-control" required>
                <option value="1">Sí</option>
                <option value="0">No</option>
            </select>
        </div>

        <div class="form-group">
            <label for="time_start_shift">Hora de Inicio del Turno (Opcional)</label>
            <input type="datetime-local" name="time_start_shift" class="form-control">
        </div>

        <button type="submit" class="btn btn-success">Crear Monitor OEE</button>
    </form>
</div>
@endsection
