@extends('layouts.admin')
@section('title', __('RFID Reader'))
@section('content')
    <div class="container">
        <h1 class="mb-4">{{ __('Antenas RFID para la Línea de Producción') }} {{ $production_line_id }}</h1>

        <div class="mb-3">
            <a href="{{ route('rfid.create', ['production_line_id' => $production_line_id]) }}" class="btn btn-primary">
                {{ __('Añadir Nueva Antena RFID') }}
            </a>
        </div>

        @if ($rfidAnts->isEmpty())
            <div class="alert alert-info">
                {{ __('No hay antenas RFID asociadas a esta línea de producción.') }}
            </div>
        @else
            <div class="card">
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>{{ __('ID') }}</th>
                                <th>{{ __('Nombre') }}</th>
                                <th>{{ __('MQTT Topic') }}</th>
                                <th>{{ __('Token') }}</th>
                                <th>{{ __('Acciones') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rfidAnts as $rfidAnt)
                                <tr>
                                    <td>{{ $rfidAnt->id }}</td>
                                    <td>{{ $rfidAnt->name }}</td>
                                    <td>{{ $rfidAnt->mqtt_topic }}</td>
                                    <td>{{ $rfidAnt->token }}</td>
                                    <td>
                                        <a href="{{ route('rfid.edit', $rfidAnt->id) }}" class="btn btn-sm btn-primary">
                                            {{ __('Editar') }}
                                        </a>
                                        <form action="{{ route('rfid.destroy', $rfidAnt->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('{{ __('¿Estás seguro de eliminar esta antena RFID?') }}')">
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
