@extends('layouts.admin')

@section('title', __('Edit Barcode'))

@section('breadcrumb')
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('customers.index') }}">{{ __('Customers') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('productionlines.index', ['customer_id' => request()->route('id')]) }}">{{ __('Production Lines') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ route('productionlines.barcodes.index', ['production_line_id' => $barcode->production_line_id]) }}">{{ __('Barcodes') }}</a></li>
        <li class="breadcrumb-item">{{ __('Edit Barcode') }}</li>
    </ul>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">{{ __('Edit Barcode') }}</h4>
                    <form action="{{ route('productionlines.barcodes.update', $barcode->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="name">{{ __('Name') }}</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ $barcode->name }}" required>
                        </div>

                        <div class="form-group">
                            <label for="token">{{ __('Token') }}</label>
                            <input type="text" class="form-control" id="token" name="token" value="{{ $barcode->token }}" required>
                        </div>

                        <div class="form-group">
                            <label for="mqtt_topic_barcodes">{{ __('MQTT Topic Barcodes') }}</label>
                            <input type="text" class="form-control" id="mqtt_topic_barcodes" name="mqtt_topic_barcodes" value="{{ $barcode->mqtt_topic_barcodes }}">
                        </div>

                        <div class="form-group">
                            <label for="mqtt_topic_orders">{{ __('MQTT Topic Orders') }}</label>
                            <input type="text" class="form-control" id="mqtt_topic_orders" name="mqtt_topic_orders" value="{{ $barcode->mqtt_topic_orders }}">
                        </div>

                        <div class="form-group">
                            <label for="mqtt_topic_finish">{{ __('MQTT Topic Finish') }}</label>
                            <input type="text" class="form-control" id="mqtt_topic_finish" name="mqtt_topic_finish" value="{{ $barcode->mqtt_topic_finish }}">
                        </div>

                        <div class="form-group">
                            <label for="mqtt_topic_pause">{{ __('MQTT Topic Pause') }}</label>
                            <input type="text" class="form-control" id="mqtt_topic_pause" name="mqtt_topic_pause" value="{{ $barcode->mqtt_topic_pause }}">
                        </div>

                        <div class="form-group">
                            <label for="machine_id">{{ __('Machine ID') }}</label>
                            <input type="text" class="form-control" id="machine_id" name="machine_id" value="{{ $barcode->machine_id }}">
                        </div>

                        <div class="form-group">
                            <label for="ope_id">{{ __('OPE ID') }}</label>
                            <input type="text" class="form-control" id="ope_id" name="ope_id" value="{{ $barcode->ope_id }}">
                        </div>

                        <div class="form-group">
                            <label for="order_notice">{{ __('Order Notice') }}</label>
                            <input type="text" class="form-control" id="order_notice" name="order_notice" value="{{ $barcode->order_notice }}">
                        </div>

                        <div class="form-group">
                            <label for="last_barcode">{{ __('Last Barcode') }}</label>
                            <input type="text" class="form-control" id="last_barcode" name="last_barcode" value="{{ $barcode->last_barcode }}">
                        </div>

                        <div class="form-group">
                            <label for="ip_zerotier">{{ __('IP Zerotier') }}</label>
                            <input type="text" class="form-control" id="ip_zerotier" name="ip_zerotier" value="{{ $barcode->ip_zerotier }}">
                        </div>

                        <div class="form-group">
                            <label for="user_ssh">{{ __('User SSH') }}</label>
                            <input type="text" class="form-control" id="user_ssh" name="user_ssh" value="{{ $barcode->user_ssh }}">
                        </div>

                        <div class="form-group">
                            <label for="port_ssh">{{ __('Port SSH') }}</label>
                            <input type="text" class="form-control" id="port_ssh" name="port_ssh" value="{{ $barcode->port_ssh }}">
                        </div>

                        <div class="form-group">
                            <label for="user_ssh_password">{{ __('User SSH Password') }}</label>
                            <input type="text" class="form-control" id="user_ssh_password" name="user_ssh_password" value="{{ $barcode->user_ssh_password }}">
                        </div>

                        <div class="form-group">
                            <label for="ip_barcoder">{{ __('IP Barcoder') }}</label>
                            <input type="text" class="form-control" id="ip_barcoder" name="ip_barcoder" value="{{ $barcode->ip_barcoder }}">
                        </div>

                        <div class="form-group">
                            <label for="port_barcoder">{{ __('Port Barcoder') }}</label>
                            <input type="text" class="form-control" id="port_barcoder" name="port_barcoder" value="{{ $barcode->port_barcoder }}">
                        </div>

                        <div class="form-group">
                            <label for="conexion_type">{{ __('Conexion Type') }}</label>
                            <input type="text" class="form-control" id="conexion_type" name="conexion_type" value="{{ $barcode->conexion_type }}">
                        </div>

                        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                        <a href="{{ route('productionlines.barcodes.index', ['production_line_id' => $barcode->production_line_id]) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
