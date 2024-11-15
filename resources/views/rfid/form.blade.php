@extends('layouts.admin')
@section('title', $rfidAnt->exists ? __('Editar Antena RFID') : __('Añadir Nueva Antena RFID'))
@section('content')
    <div class="container">
        <h1 class="mb-4">{{ $rfidAnt->exists ? __('Editar Antena RFID') : __('Añadir Nueva Antena RFID') }}</h1>

        <form action="{{ $rfidAnt->exists ? route('rfid.update', $rfidAnt->id) : route('rfid.store') }}" method="POST">
            @csrf
            @if ($rfidAnt->exists)
                @method('PUT')
            @endif
            <div class="form-group">
                <label for="name">{{ __('Nombre') }}</label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $rfidAnt->name) }}" required>
            </div>

            <div class="form-group mt-3">
                <label for="mqtt_topic">{{ __('MQTT Topic') }}</label>
                <input type="text" name="mqtt_topic" id="mqtt_topic" class="form-control" value="{{ old('mqtt_topic', $rfidAnt->mqtt_topic) }}" required>
            </div>

            <div class="form-group mt-3">
                <label for="token">{{ __('Token') }}</label>
                <input type="text" name="token" id="token" class="form-control" value="{{ old('token', $rfidAnt->token) }}" required>
            </div>

            <input type="hidden" name="production_line_id" value="{{ $production_line_id }}">

            <button type="submit" class="btn btn-success mt-4">
                {{ $rfidAnt->exists ? __('Actualizar') : __('Guardar') }}
            </button>
        </form>
    </div>
@endsection
