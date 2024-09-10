@extends('layouts.admin')
@section('title', __('Sensors'))
@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('productionlines.index', ['customer_id' => request()->route('id')]) }}">{{ __('Production Lines') }}</a></li>
        <li class="breadcrumb-item">{{ __('Sensors') }}</li>
    </ul>
@endsection
@section('content')
    <div class="container">
        <div class="row">
            <!-- Card para Barcoders -->
            <div class="col-md-4">
                <div class="card">
                    <img src="/necesarios/Designer.jpeg" class="card-img-top" alt="Barcoders">
                    <div class="card-body">
                        <h5 class="card-title">Barcoders</h5>
                        <p class="card-text">Administrar listado de Lectores.</p>
                        <a href="{{ route('barcodes.index', ['production_line_id' => request()->route('id')]) }}" class="btn btn-primary">Ver más</a>
                    </div>
                </div>
            </div>
            <!-- Fin del card -->

            <!-- Card para Modbuses -->
            <div class="col-md-4">
                <div class="card">
                    <img src="/necesarios/basculas.webp" class="card-img-top" alt="Modbuses">
                    <div class="card-body">
                        <h5 class="card-title">Basculas</h5>
                        <p class="card-text">Administrar listado de Basculas.</p>
                        <a href="{{ route('modbuses.index', ['production_line_id' => request()->route('id')]) }}" class="btn btn-primary">Ver más</a>
                    </div>
                </div>
            </div>
            <!-- Fin del card -->

           <!-- Card para Smart Sensors -->
<div class="col-md-4">
    <div class="card">
        <img src="/necesarios/sensors.webp" class="card-img-top" alt="Smart Sensors">
        <div class="card-body">
            <h5 class="card-title">Sensorica</h5>
            <p class="card-text">Administrar listado de Sensorica.</p>
            <a href="{{ route('smartsensors.index', ['production_line_id' => request()->route('id')]) }}" class="btn btn-primary">Ver más</a>
        </div>
    </div>
</div>
<!-- Fin del card -->


        </div>
    </div>
@endsection
