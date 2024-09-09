@extends('layouts.admin')
@section('title', __('Agregar Nuevo Cliente'))

@section('content')
<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('customers.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="name">Nombre del Cliente</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="token_zerotier">Token ZeroTier</label>
                        <input type="text" name="token_zerotier" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-success">Guardar Cliente</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
