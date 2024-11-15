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

            
            <!-- Card para Monitor OEE -->
            <div class="col-md-4">
                <div class="card">
                    <img src="/necesarios/oee.webp" class="card-img-top" alt="Monitor OEE">
                    <div class="card-body">
                        <h5 class="card-title">Monitor OEE</h5>
                        <p class="card-text">Administrar listado de Monitor OEE.</p>
                        <a href="{{ route('oee.index', ['production_line_id' => request()->route('id')]) }}" class="btn btn-primary">Ver más</a>
                    </div>
                </div>
            </div>
            <!-- Fin del card -->

            <!-- Botón para WhatsApp -->
            <div class="col-md-4">
                <div class="card">
                    <img src="/necesarios/whatsapp.webp" class="card-img-top" alt="WhatsApp">
                    <div class="card-body">
                        <h5 class="card-title">WhatsApp</h5>
                        <p class="card-text">Enviar notificaciones a través de WhatsApp.</p>
                        <a href="{{ route('whatsapp.notifications') }}" class="btn btn-success">Enviar Notificación</a>
                    </div>
                </div>
            </div>
            <!-- Fin del botón para WhatsApp -->

            <!-- Card para RFID Reader o Antenas -->
            <div class="col-md-4">
                <div class="card">
                    <img src="/necesarios/rfid.webp" class="card-img-top" alt="RFID Reader">
                    <div class="card-body">
                        <h5 class="card-title">RFID Reader</h5>
                        <p class="card-text">Administrar listado de Antenas RFID.</p>
                        <a href="{{ route('rfid.index', ['production_line_id' => request()->route('id')]) }}" class="btn btn-warning">Ver más</a>
                    </div>
                </div>
            </div>
            <!-- Fin del card para RFID Reader -->
            <!-- Card para Categorías RFID -->
            <div class="col-md-4">
                <div class="card">
                    <img src="/necesarios/rfid_categories.webp" class="card-img-top" alt="RFID Categorías">
                    <div class="card-body">
                        <h5 class="card-title">RFID Categorías</h5>
                        <p class="card-text">Administrar categorías y lecturas de RFID.</p>
                        <a href="{{ route('rfid.categories.index', ['production_line_id' => request()->route('id')]) }}" class="btn btn-info">Ver más</a>
                    </div>
                </div>
            </div>
            <!-- Fin del card para Categorías RFID -->
            <!-- Card para Dispositivos RFID -->
            <div class="col-md-4">
                <div class="card">
                    <img src="/necesarios/rfid_devices.webp" class="card-img-top" alt="RFID Devices">
                    <div class="card-body">
                        <h5 class="card-title">RFID Dispositivos</h5>
                        <p class="card-text">Administrar dispositivos y detalles de RFID.</p>
                        <a href="{{ route('rfid.devices.index', ['production_line_id' => request()->route('id')]) }}" class="btn btn-warning">Ver más</a>
                    </div>
                </div>
            </div>
            <!-- Fin del card para Dispositivos RFID -->


        </div>
    </div>
@endsection
