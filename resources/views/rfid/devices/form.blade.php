@extends('layouts.admin')
@section('title', $rfidDevice->exists ? __('Editar Dispositivo RFID') : __('Añadir Nuevo Dispositivo RFID'))
@section('content')
    <div class="container">
        <h1 class="mb-4">{{ $rfidDevice->exists ? __('Editar Dispositivo RFID') : __('Añadir Nuevo Dispositivo RFID') }}</h1>

        <form action="{{ $rfidDevice->exists ? route('rfid.devices.update', $rfidDevice->id) : route('rfid.devices.store') }}" method="POST">
            @csrf
            @if ($rfidDevice->exists)
                @method('PUT')
            @endif

            <!-- Campos Básicos -->
            <div class="form-group">
                <label for="name">{{ __('Nombre') }}</label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $rfidDevice->name) }}" required>
            </div>

            <div class="form-group mt-3">
                <label for="token">{{ __('Token') }}</label>
                <input type="text" name="token" id="token" class="form-control" value="{{ old('token', $rfidDevice->token) }}" required>
            </div>

            <!-- Claves Foráneas -->
            <div class="form-group mt-3">
                <label for="rfid_reading_id">{{ __('Categoría RFID') }}</label>
                <select name="rfid_reading_id" id="rfid_reading_id" class="form-control" required>
                    @foreach ($rfidReadings as $reading)
                        <option value="{{ $reading->id }}" {{ old('rfid_reading_id', $rfidDevice->rfid_reading_id) == $reading->id ? 'selected' : '' }}>
                            {{ $reading->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group mt-3">
                <label for="rfid_ant_id">{{ __('Antena RFID') }}</label>
                <select name="rfid_ant_id" id="rfid_ant_id" class="form-control" required>
                    @foreach ($rfidAnts as $antenna)
                        <option value="{{ $antenna->id }}" {{ old('rfid_ant_id', $rfidDevice->rfid_ant_id) == $antenna->id ? 'selected' : '' }}>
                            {{ $antenna->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Campos Específicos de RFID -->
            <div class="form-group mt-3">
                <label for="epc">{{ __('EPC') }}</label>
                <input type="text" name="epc" id="epc" class="form-control" value="{{ old('epc', $rfidDevice->epc) }}" required>
            </div>

            <div class="form-group mt-3">
                <label for="tid">{{ __('TID') }}</label>
                <input type="text" name="tid" id="tid" class="form-control" value="{{ old('tid', $rfidDevice->tid) }}" required>
            </div>

            <div class="form-group mt-3">
                <label for="serialno">{{ __('Número de Serie') }}</label>
                <input type="text" name="serialno" id="serialno" class="form-control" value="{{ old('serialno', $rfidDevice->serialno) }}">
            </div>

            <div class="form-group mt-3">
                <label for="rssi">{{ __('RSSI (Intensidad de Señal)') }}</label>
                <input type="number" name="rssi" id="rssi" class="form-control" value="{{ old('rssi', $rfidDevice->rssi) }}">
            </div>

            <div class="form-group mt-3">
                <label for="rfid_type">{{ __('Tipo de RFID') }}</label>
                <input type="number" name="rfid_type" id="rfid_type" class="form-control" value="{{ old('rfid_type', $rfidDevice->rfid_type) }}" required>
            </div>

            <!-- Contadores -->
            <div class="form-group mt-3">
                <label for="count_total">{{ __('Contador Total de Lecturas') }}</label>
                <input type="number" name="count_total" id="count_total" class="form-control" value="{{ old('count_total', $rfidDevice->count_total) }}">
            </div>

            <div class="form-group mt-3">
                <label for="count_total_0">{{ __('Contador Total de Lecturas Inactivas') }}</label>
                <input type="number" name="count_total_0" id="count_total_0" class="form-control" value="{{ old('count_total_0', $rfidDevice->count_total_0) }}">
            </div>

            <div class="form-group mt-3">
                <label for="count_total_1">{{ __('Contador Total de Lecturas Activas') }}</label>
                <input type="number" name="count_total_1" id="count_total_1" class="form-control" value="{{ old('count_total_1', $rfidDevice->count_total_1) }}">
            </div>

            <div class="form-group mt-3">
                <label for="count_shift_0">{{ __('Contador de Lecturas Inactivas por Turno') }}</label>
                <input type="number" name="count_shift_0" id="count_shift_0" class="form-control" value="{{ old('count_shift_0', $rfidDevice->count_shift_0) }}">
            </div>

            <div class="form-group mt-3">
                <label for="count_shift_1">{{ __('Contador de Lecturas Activas por Turno') }}</label>
                <input type="number" name="count_shift_1" id="count_shift_1" class="form-control" value="{{ old('count_shift_1', $rfidDevice->count_shift_1) }}">
            </div>

            <div class="form-group mt-3">
                <label for="count_order_0">{{ __('Contador de Lecturas Inactivas por Orden') }}</label>
                <input type="number" name="count_order_0" id="count_order_0" class="form-control" value="{{ old('count_order_0', $rfidDevice->count_order_0) }}">
            </div>

            <div class="form-group mt-3">
                <label for="count_order_1">{{ __('Contador de Lecturas Activas por Orden') }}</label>
                <input type="number" name="count_order_1" id="count_order_1" class="form-control" value="{{ old('count_order_1', $rfidDevice->count_order_1) }}">
            </div>

            <!-- Otros Campos -->
            <div class="form-group mt-3">
                <label for="mqtt_topic_1">{{ __('MQTT Tópico') }}</label>
                <input type="text" name="mqtt_topic_1" id="mqtt_topic_1" class="form-control" value="{{ old('mqtt_topic_1', $rfidDevice->mqtt_topic_1) }}">
            </div>

            <div class="form-group mt-3">
                <label for="function_model_0">{{ __('Función Modelo 0') }}</label>
                <input type="text" name="function_model_0" id="function_model_0" class="form-control" value="{{ old('function_model_0', $rfidDevice->function_model_0) }}">
            </div>

            <div class="form-group mt-3">
                <label for="function_model_1">{{ __('Función Modelo 1') }}</label>
                <input type="text" name="function_model_1" id="function_model_1" class="form-control" value="{{ old('function_model_1', $rfidDevice->function_model_1) }}">
            </div>

            <div class="form-group mt-3">
                <label for="unic_code_order">{{ __('Código Único de Orden') }}</label>
                <input type="text" name="unic_code_order" id="unic_code_order" class="form-control" value="{{ old('unic_code_order', $rfidDevice->unic_code_order) }}">
            </div>

            <div class="form-group mt-3">
                <label for="shift_type">{{ __('Tipo de Turno') }}</label>
                <input type="text" name="shift_type" id="shift_type" class="form-control" value="{{ old('shift_type', $rfidDevice->shift_type) }}">
            </div>

            <div class="form-group mt-3">
                <label for="event">{{ __('Evento') }}</label>
                <input type="text" name="event" id="event" class="form-control" value="{{ old('event', $rfidDevice->event) }}">
            </div>

            <div class="form-group mt-3">
                <label for="downtime_count">{{ __('Contador de Inactividad') }}</label>
                <input type="number" name="downtime_count" id="downtime_count" class="form-control" value="{{ old('downtime_count', $rfidDevice->downtime_count) }}">
            </div>

            <div class="form-group mt-3">
                <label for="optimal_production_time">{{ __('Tiempo Óptimo de Producción') }}</label>
                <input type="number" name="optimal_production_time" id="optimal_production_time" class="form-control" value="{{ old('optimal_production_time', $rfidDevice->optimal_production_time) }}">
            </div>

            <div class="form-group mt-3">
                <label for="reduced_speed_time_multiplier">{{ __('Multiplicador de Velocidad Reducida') }}</label>
                <input type="number" name="reduced_speed_time_multiplier" id="reduced_speed_time_multiplier" class="form-control" value="{{ old('reduced_speed_time_multiplier', $rfidDevice->reduced_speed_time_multiplier) }}">
            </div>

            <div class="form-group mt-3">
                <label for="send_alert">{{ __('Enviar Alerta') }}</label>
                <select name="send_alert" id="send_alert" class="form-control">
                    <option value="0" {{ old('send_alert', $rfidDevice->send_alert) == 0 ? 'selected' : '' }}>{{ __('No') }}</option>
                    <option value="1" {{ old('send_alert', $rfidDevice->send_alert) == 1 ? 'selected' : '' }}>{{ __('Sí') }}</option>
                </select>
            </div>

            <div class="form-group mt-3">
                <label for="search_out">{{ __('Buscar Salida de Perímetro') }}</label>
                <select name="search_out" id="search_out" class="form-control">
                    <option value="0" {{ old('search_out', $rfidDevice->search_out) == 0 ? 'selected' : '' }}>{{ __('No') }}</option>
                    <option value="1" {{ old('search_out', $rfidDevice->search_out) == 1 ? 'selected' : '' }}>{{ __('Sí') }}</option>
                </select>
            </div>

            <div class="form-group mt-3">
                <label for="last_ant_detect">{{ __('Última Antena Detectada') }}</label>
                <input type="text" name="last_ant_detect" id="last_ant_detect" class="form-control" value="{{ old('last_ant_detect', $rfidDevice->last_ant_detect) }}">
            </div>

            <div class="form-group mt-3">
                <label for="last_status_detect">{{ __('Último Estado Detectado') }}</label>
                <input type="text" name="last_status_detect" id="last_status_detect" class="form-control" value="{{ old('last_status_detect', $rfidDevice->last_status_detect) }}">
            </div>

            <input type="hidden" name="production_line_id" value="{{ $production_line_id }}">

            <button type="submit" class="btn btn-success mt-4">
                {{ $rfidDevice->exists ? __('Actualizar') : __('Guardar') }}
            </button>
        </form>
    </div>
@endsection
