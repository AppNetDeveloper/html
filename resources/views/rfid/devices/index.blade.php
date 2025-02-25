@extends('layouts.admin')
@section('title', __('RFID Dispositivos'))
@section('content')
    <div class="container">
        <h1 class="mb-4">{{ __('Dispositivos RFID para la Línea de Producción') }} {{ $production_line_id }}</h1>

        <div class="mb-3">
            <a href="{{ route('rfid.devices.create', ['production_line_id' => $production_line_id]) }}" class="btn btn-primary">
                {{ __('Añadir Nuevo Dispositivo RFID') }}
            </a>
        </div>

        @if ($rfidDevices->isEmpty())
            <div class="alert alert-info">
                {{ __('No hay dispositivos RFID asociados a esta línea de producción.') }}
            </div>
        @else
            <div class="card">
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>{{ __('ID') }}</th>
                                <th>{{ __('Nombre') }}</th>
                                <th>{{ __('EPC') }}</th>
                                <th>{{ __('TID') }}</th>
                                <th>{{ __('Acciones') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rfidDevices as $device)
                                <tr>
                                    <td>{{ $device->id }}</td>
                                    <td>{{ $device->name }}</td>
                                    <td>{{ $device->epc }}</td>
                                    <td>{{ $device->tid }}</td>
                                    <td>
                                        <a href="{{ route('rfid.devices.edit', $device->id) }}" class="btn btn-sm btn-primary">
                                            {{ __('Editar') }}
                                        </a>
                                        <form action="{{ route('rfid.devices.destroy', $device->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('{{ __('¿Estás seguro de eliminar este dispositivo RFID?') }}')">
                                                {{ __('Eliminar') }}
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection
