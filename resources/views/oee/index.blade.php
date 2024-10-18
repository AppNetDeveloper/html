@extends('layouts.admin')
@section('title', __('Monitor OEE'))
@section('content')
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <a href="{{ route('oee.create', ['production_line_id' => $production_line_id]) }}" class="btn btn-primary mb-3">Añadir Nuevo Monitor OEE</a>
                <div class="card">
                    <div class="card-body">
                        <table class="table table-bordered data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ID Línea de Producción</th>
                                    <th>MQTT Topic</th>
                                    <th>MQTT Topic 2</th>
                                    <th>Sensor Activo</th>
                                    <th>Modbus Activo</th>
                                    <th>OEE Topic</th>
                                    <th>Hora de Inicio del Turno</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($monitorOees as $monitorOee)
                                <tr>
                                    <td>{{ $monitorOee->id }}</td>
                                    <td>{{ $monitorOee->production_line_id }}</td>
                                    <td>{{ $monitorOee->mqtt_topic }}</td>
                                    <td>{{ $monitorOee->mqtt_topic2 }}</td>
                                    <td>{{ $monitorOee->sensor_active ? 'Activo' : 'Inactivo' }}</td>
                                    <td>{{ $monitorOee->modbus_active ? 'Activo' : 'Inactivo' }}</td>
                                    <td>{{ $monitorOee->topic_oee }}</td>
                                    <td>{{ $monitorOee->time_start_shift }}</td>
                                    <td>
                                        <a href="{{ route('oee.edit', $monitorOee->id) }}" class="btn btn-sm btn-primary">Editar</a>
                                        <form action="{{ route('oee.destroy', $monitorOee->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro?')">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
