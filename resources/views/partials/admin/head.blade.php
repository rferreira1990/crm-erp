<meta charset="UTF-8">

<title>@yield('title', 'CRM ERP')</title>

<meta name="keywords" content="CRM ERP, eletricidade, obras">
<meta name="description" content="Sistema interno CRM/ERP">
<meta name="author" content="Ricardo">

<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />

<link href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700,800|Shadows+Into+Light" rel="stylesheet" type="text/css">

{{-- Vendor CSS --}}
<link rel="stylesheet" href="{{ asset('porto/vendor/bootstrap/css/bootstrap.css') }}">
<link rel="stylesheet" href="{{ asset('porto/vendor/animate/animate.compat.css') }}">
<link rel="stylesheet" href="{{ asset('porto/vendor/font-awesome/css/all.min.css') }}">
<link rel="stylesheet" href="{{ asset('porto/vendor/boxicons/css/boxicons.min.css') }}">
<link rel="stylesheet" href="{{ asset('porto/vendor/magnific-popup/magnific-popup.css') }}">
<link rel="stylesheet" href="{{ asset('porto/vendor/bootstrap-datepicker/css/bootstrap-datepicker3.css') }}">
<link rel="stylesheet" href="{{ asset('porto/vendor/jquery-ui/jquery-ui.css') }}">
<link rel="stylesheet" href="{{ asset('porto/vendor/jquery-ui/jquery-ui.theme.css') }}">
<link rel="stylesheet" href="{{ asset('porto/vendor/bootstrap-multiselect/css/bootstrap-multiselect.css') }}">
<link rel="stylesheet" href="{{ asset('porto/vendor/morris/morris.css') }}">

{{-- Theme CSS --}}
<link rel="stylesheet" href="{{ asset('porto/css/theme.css') }}">

{{-- Skin CSS --}}
<link rel="stylesheet" href="{{ asset('porto/css/skins/default.css') }}">

{{-- Custom CSS --}}
<link rel="stylesheet" href="{{ asset('porto/css/custom.css') }}">

{{-- Espaço para CSS extra por página --}}
@stack('styles')

<script src="{{ asset('porto/vendor/modernizr/modernizr.js') }}"></script>
