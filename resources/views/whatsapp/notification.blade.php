@extends('layouts.admin')
@section('title', __('WhatsApp'))
@section('content')
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="mb-4">{{ __('Estado de WhatsApp') }}</h1>

                @if (session('status'))
                    <div class="alert alert-success">
                        {{ session('status') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Card del Estado -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">{{ __('Estado de Conexión') }}</h4>
                    </div>
                    <div class="card-body">
                        @if ($isLinked)
                            <h3 class="text-success">{{ __('WhatsApp ya vinculado') }}</h3>
                            <p>{{ __('Tu aplicación ya está conectada con WhatsApp.') }}</p>
                        @elseif ($qrCode)
                            <h3 class="text-danger">{{ __('Escanea el Código QR') }}</h3>
                            <p>{{ __('Por favor, escanea el siguiente código QR con tu aplicación de WhatsApp para conectarte.') }}</p>
                            <div class="text-center">
                                <img src="{{ $qrCode }}" alt="{{ __('Código QR de WhatsApp') }}" class="img-fluid mt-3">
                            </div>
                        @else
                            <h3 class="text-danger">{{ __('Error') }}</h3>
                            <p>{{ __('No se pudo obtener el estado de conexión. Intenta nuevamente más tarde.') }}</p>
                        @endif
                    </div>
                </div>

                <!-- Card del Botón Desconectar -->
                <div class="card mb-4">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0">{{ __('Desconectar WhatsApp') }}</h4>
                    </div>
                    <div class="card-body text-center">
                        <form action="{{ route('whatsapp.disconnect') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-lg">{{ __('Desconectar') }}</button>
                        </form>
                    </div>
                </div>

                <!-- Card del Formulario de Actualización del Número -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h4 class="mb-0">{{ __('Actualizar Número de Notificación') }}</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('whatsapp.updatePhone') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="phone_number" class="form-label">{{ __('Número de Notificación') }}</label>
                                <input type="text" name="phone_number" id="phone_number" class="form-control form-control-lg" value="{{ $phoneNumber }}" placeholder="{{ __('Ingrese el número de notificación') }}">
                            </div>
                            <button type="submit" class="btn btn-info btn-lg mt-3">{{ __('Actualizar Número') }}</button>
                        </form>
                    </div>
                </div>

                <!-- Card del Envío de Mensaje de Prueba -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">{{ __('Enviar Mensaje de Prueba') }}</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('whatsapp.sendTestMessage') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="test_phone_number" class="form-label">{{ __('Número de Teléfono') }}</label>
                                <input type="text" name="test_phone_number" id="test_phone_number" class="form-control" placeholder="{{ __('Ingrese el número de teléfono') }}">
                            </div>
                            <div class="form-group mt-3">
                                <label for="test_message" class="form-label">{{ __('Mensaje') }}</label>
                                <input type="text" name="test_message" id="test_message" class="form-control" placeholder="{{ __('Ingrese el mensaje de prueba') }}">
                            </div>
                            <button type="submit" class="btn btn-success btn-lg mt-3">{{ __('Enviar Mensaje') }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
