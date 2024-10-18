@extends('layouts.admin')
@section('title', __('Barcodes'))
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('productionlines.index', ['customer_id' => request()->route('production_line_id')]) }}">{{ __('Production Lines') }}</a></li>
        <li class="breadcrumb-item">{{ __('Barcodes') }}</li>
    </ul>
@endsection
@section('content')
    <div class="row">
        <div class="mb-3">
            <a href="{{ route('barcodes.create', ['production_line_id' => $production_line_id]) }}" class="btn btn-primary">Añadir Nuevo Barcode</a>
            <a href="{{ route('logs.view') }}" class="btn btn-secondary">Ver Logs</a> <!-- Botón para ver los logs -->
        </div>
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive py-5 pb-4 dropdown_2">
                        <div class="container-fluid">
                            <table class="table table-bordered data-table" id="barcodes-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Token</th>
                                        <th>Machine ID</th>
                                        <th>OPE ID</th>
                                        <th>Last Barcode</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style')
    @include('layouts.includes.datatable_css')
    <style>
        .table-responsive {
            overflow-x: auto; /* Asegura que haya un scroll horizontal cuando sea necesario */
            -webkit-overflow-scrolling: touch; /* Mejora del scroll para dispositivos móviles */
            white-space: nowrap; /* Evita el wrap de las celdas */
        }
    </style>
@endpush

@push('scripts')
    @include('layouts.includes.datatable_js')
    <script type="text/javascript">
        $(function () {
            var production_line_id = "{{ $production_line_id }}";
            $('.data-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "/productionlines/" + production_line_id + "/barcodesjson",
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'name', name: 'name'},
                    {data: 'token', name: 'token'},
                    {data: 'machine_id', name: 'machine_id'},
                    {data: 'ope_id', name: 'ope_id'},
                    {data: 'last_barcode', name: 'last_barcode'},
                    
                    {data: 'action', name: 'action', orderable: false, searchable: false},
                ],
                scrollX: true, 
                autoWidth: false,
                columnDefs: [
                    { "targets": [0], "visible": true, "searchable": true, "sortable": true },
                    { "targets": [1], "visible": true, "searchable": true, "sortable": true },
                    { "targets": [5], "render": function (data, type, full, meta) {
                        return data;
                    }},
                ],
                scrollX: true,       // Activa el scroll horizontal
                scrollY: '400px',    // Altura del área de visualización vertical
                scrollCollapse: true, // Ajusta la altura si hay menos filas
            });
        });
    </script>
@endpush
