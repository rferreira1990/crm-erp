@extends('layouts.admin')

@section('title', 'Editar Série')

@section('content')

<h2 class="mb-3">Editar Série</h2>

<form method="POST" action="{{ route('document-series.update', $documentSeries) }}">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-body row g-3">

            <div class="col-md-4">
                <label>Prefixo</label>
                <input name="prefix" class="form-control" value="{{ $documentSeries->prefix }}">
            </div>

            <div class="col-md-4">
                <label>Série</label>
                <input name="name" class="form-control" value="{{ $documentSeries->name }}">
            </div>

            <div class="col-md-4">
                <label>Ano</label>
                <input name="year" type="number" class="form-control" value="{{ $documentSeries->year }}">
            </div>

            <div class="col-md-4">
                <label>Ativa</label><br>
                <input type="checkbox" name="is_active" value="1" {{ $documentSeries->is_active ? 'checked' : '' }}>
            </div>

            <div class="card-footer text-end">
                <button class="btn btn-primary">Guardar</button>
            </div>
        </div>


    </div>

</form>

@endsection
