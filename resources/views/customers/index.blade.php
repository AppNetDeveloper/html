@extends('layouts.admin')
@section('title', __('Customers'))
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item">{{ __('Customers') }}
        </li>
    </ul>
@endsection
@section('content')
    <div class="row">
        <div class="col-lg-12">
        <div class="mb-3">
            <a href="{{ route('customers.create') }}" class="btn btn-primary">Agregar nuevo customer</a>
        </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive py-5 pb-4 dropdown_2">
                        <div class="container-fluid">
                            <table class="table table-bordered data-table">
                                <!-- Encabezados de las columnas aquí -->
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Token ZeroTier</th>
                                        <th>Created At</th>
                                        <th>Updated At</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <!-- Cuerpo de la tabla se llenará automáticamente -->
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.5/css/dataTables.dataTables.min.css">
@endpush

@push('scripts')
    <script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.5/js/dataTables.min.js"></script>
    
    <script type="text/javascript">
        $(function () {
            $('.data-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                scrollX: true,
                ajax: "/customers/getCustomers",
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'name', name: 'name'},
                    {data: 'token_zerotier', name: 'token_zerotier'},
                    {data: 'created_at', name: 'created_at', render: function (data, type, row) {
                        return moment(data).format('DD-MM-YYYY HH:mm:ss');
                    }},
                    {data: 'updated_at', name: 'updated_at', render: function (data, type, row) {
                        return moment(data).format('DD-MM-YYYY HH:mm:ss');
                    }},
                    {data: 'action', name: 'action', orderable: false, searchable: false},
                ],
                "columnDefs": [
                    { "targets": [0], "visible": true, "searchable": true, "sortable": true },
                    { "targets": [5], "render": function (data, type, full, meta) {
                        return data;
                    }},
                ],
            });
        });
    </script>
@endpush