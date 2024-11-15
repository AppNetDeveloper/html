@extends('layouts.admin')

@section('title', __('Logs'))

@section('content')
    <div class="container">
        <h1>Logs del Sistema</h1>
        <pre style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; max-height: 500px; overflow-y: auto;">
            {{ $logs }}
        </pre>

    </div>
@endsection
