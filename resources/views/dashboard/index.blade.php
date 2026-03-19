@extends('layouts.admin')

@section('title', 'Dashboard')

@section('page_header')
    <header class="page-header">
        <h2>Dashboard</h2>

        <div class="right-wrapper text-end">
            <ol class="breadcrumbs">
                <li>
                    <a href="{{ route('dashboard') }}">
                        <i class="bx bx-home-alt"></i>
                    </a>
                </li>
                <li><span>Painel</span></li>
            </ol>
        </div>
    </header>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-3">
            <section class="card card-featured-left card-featured-primary">
                <div class="card-body">
                    <div class="widget-summary">
                        <div class="widget-summary-col widget-summary-col-icon">
                            <div class="summary-icon bg-primary">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="widget-summary-col">
                            <div class="summary">
                                <h4 class="title">Clientes</h4>
                                <div class="info">
                                    <strong class="amount">0</strong>
                                </div>
                            </div>
                            <div class="summary-footer">
                                <span class="text-muted">Módulo ainda por ligar</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <section class="card card-featured-left card-featured-secondary">
                <div class="card-body">
                    <div class="widget-summary">
                        <div class="widget-summary-col widget-summary-col-icon">
                            <div class="summary-icon bg-secondary">
                                <i class="fas fa-briefcase"></i>
                            </div>
                        </div>
                        <div class="widget-summary-col">
                            <div class="summary">
                                <h4 class="title">Obras</h4>
                                <div class="info">
                                    <strong class="amount">0</strong>
                                </div>
                            </div>
                            <div class="summary-footer">
                                <span class="text-muted">Módulo ainda por ligar</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <section class="card card-featured-left card-featured-tertiary">
                <div class="card-body">
                    <div class="widget-summary">
                        <div class="widget-summary-col widget-summary-col-icon">
                            <div class="summary-icon bg-tertiary">
                                <i class="fas fa-file-invoice"></i>
                            </div>
                        </div>
                        <div class="widget-summary-col">
                            <div class="summary">
                                <h4 class="title">Orçamentos</h4>
                                <div class="info">
                                    <strong class="amount">0</strong>
                                </div>
                            </div>
                            <div class="summary-footer">
                                <span class="text-muted">Módulo ainda por ligar</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <section class="card card-featured-left card-featured-quaternary">
                <div class="card-body">
                    <div class="widget-summary">
                        <div class="widget-summary-col widget-summary-col-icon">
                            <div class="summary-icon bg-quaternary">
                                <i class="fas fa-boxes"></i>
                            </div>
                        </div>
                        <div class="widget-summary-col">
                            <div class="summary">
                                <h4 class="title">Stock</h4>
                                <div class="info">
                                    <strong class="amount">0</strong>
                                </div>
                            </div>
                            <div class="summary-footer">
                                <span class="text-muted">Módulo ainda por ligar</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="row pt-3">
        <div class="col-12">
            <section class="card">
                <header class="card-header">
                    <h2 class="card-title">Bem-vindo</h2>
                </header>
                <div class="card-body">
                    <p class="mb-0">
                        O layout base do Porto Admin já está integrado no Laravel e pronto para receber os módulos reais do sistema.
                    </p>
                </div>
            </section>
        </div>
    </div>
@endsection
