@extends('layouts.admin')
@section('title', $rfidReading->exists ? __('Editar Categoría RFID') : __('Añadir Nueva Categoría RFID'))
@section('content')
    <div class="container">
        <h1 class="mb-4">{{ $rfidReading->exists ? __('Editar Categoría RFID') : __('Añadir Nueva Categoría RFID') }}</h1>

        <form action="{{ $rfidReading->exists ? route('rfid.categories.update', $rfidReading->id) : route('rfid.categories.store') }}" method="POST">
            @csrf
            @if ($rfidReading->exists)
                @method('PUT')
            @endif
            <div class="form-group">
                <label for="name">{{ __('Nombre') }}</label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $rfidReading->name) }}" required>
            </div>

            <div class="form-group mt-3">
                <label for="epc">{{ __('EPC') }}</label>
                <input type="text" name="epc" id="epc" class="form-control" value="{{ old('epc', $rfidReading->epc) }}" required>
            </div>

            <input type="hidden" name="production_line_id" value="{{ $production_line_id }}">

            <button type="submit" class="btn btn-success mt-4">
                {{ $rfidReading->exists ? __('Actualizar') : __('Guardar') }}
            </button>
        </form>
    </div>
@endsection
