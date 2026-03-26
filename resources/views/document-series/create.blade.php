@extends('layouts.admin')

@section('title', 'Nova Série')

@section('content')

<h2 class="mb-3">Nova Série</h2>

<form method="POST" action="{{ route('document-series.store') }}">
    @csrf

    <div class="card">
        <div class="card-body row g-3">

            <div class="col-md-4">
                <label>Tipo Documento</label>
                <select name="document_type" class="form-control">
                    <option value="budget">Orçamentos</option>
                </select>
            </div>

            <div class="col-md-4">
                <label>Prefixo</label>
                <input name="prefix" class="form-control" value="ORC">
            </div>

            <div class="col-md-4">
                <label>Série</label>
                <input name="name" class="form-control" placeholder="2026">
            </div>

            <div class="col-md-4">
                <label>Ano</label>
                <input name="year" type="number" class="form-control">
            </div>

            <div class="col-md-4">
                <label>Ativa</label>
                <input type="checkbox" name="is_active" value="1">
            </div>

        </div>

        <div class="card-footer text-end">
            <button class="btn btn-primary">Guardar</button>
        </div>
    </div>

</form>

@endsection
