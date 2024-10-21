@extends('layouts.admin')
@section('title', __('Modbuses'))
@section('content')
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <a href="{{ route('modbuses.create', ['production_line_id' => $production_line_id]) }}" class="btn btn-primary mb-3">Añadir Nuevo Modbus</a>
                <div class="card">
                    <div class="card-body">
                        <table class="table table-bordered data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Modelo</th>
                                    <th>Último Valor</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($modbuses as $modbus)
                                <tr>
                                    <td>{{ $modbus->id }}</td>
                                    <td>{{ $modbus->name }}</td>
                                    <td>{{ $modbus->model_name }}</td>
                                    <td>{{ $modbus->last_value }}</td>
                                    <td>
                                        <a href="{{ route('modbuses.edit', $modbus->id) }}" class="btn btn-sm btn-primary">Editar</a>
                                        <form action="{{ route('modbuses.destroy', ['production_line_id' => $production_line_id, 'modbus' => $modbus->id]) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro?')">Eliminar</button>
                                        </form>

                                        
                                        <a href="/live-weight/live.html?token={{ $modbus->token }}" class="btn btn-sm btn-primary">Live View</a>
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
