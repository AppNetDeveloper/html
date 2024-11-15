@extends('layouts.admin')
@section('title', __('RFID Categorías'))
@section('content')
    <div class="container">
        <h1 class="mb-4">{{ __('Categorías RFID para la Línea de Producción') }} {{ $production_line_id }}</h1>

        <div class="mb-3">
            <a href="{{ route('rfid.categories.create', ['production_line_id' => $production_line_id]) }}" class="btn btn-primary">
                {{ __('Añadir Nueva Categoría RFID') }}
            </a>
        </div>

        @if ($categories->isEmpty())
            <div class="alert alert-info">
                {{ __('No hay categorías RFID asociadas a esta línea de producción.') }}
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
                                <th>{{ __('Acciones') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $category)
                                <tr>
                                    <td>{{ $category->id }}</td>
                                    <td>{{ $category->name }}</td>
                                    <td>{{ $category->epc }}</td>
                                    <td>
                                        <a href="{{ route('rfid.categories.edit', $category->id) }}" class="btn btn-sm btn-primary">
                                            {{ __('Editar') }}
                                        </a>
                                        <form action="{{ route('rfid.categories.destroy', $category->id) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('{{ __('¿Estás seguro de eliminar esta categoría RFID?') }}')">
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
