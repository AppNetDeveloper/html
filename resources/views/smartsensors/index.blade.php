@extends('layouts.admin')
@section('title', __('Smart Sensors'))
@section('content')
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <a href="{{ route('smartsensors.create', ['production_line_id' => $production_line_id]) }}" class="btn btn-primary mb-3">Añadir Nuevo Sensor</a>
                <div class="card">
                    <div class="card-body">
                        <table class="table table-bordered data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Tipo de Sensor</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sensors as $sensor)
                                <tr>
                                    <td>{{ $sensor->id }}</td>
                                    <td>{{ $sensor->name }}</td>
                                    <td>{{ $sensor->sensor_type }}</td>
                                    <td>
                                        <a href="{{ route('smartsensors.edit', $sensor->id) }}" class="btn btn-sm btn-primary">Editar</a>
                                        <form action="{{ route('smartsensors.destroy', $sensor->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro?')">Eliminar</button>
                                        </form>
                                        <a href="/live-sensor/live.html?token={{ $sensor->token }}" class="btn btn-sm btn-primary">Live View</a>
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
