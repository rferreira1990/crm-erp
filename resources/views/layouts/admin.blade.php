<!doctype html>
<html class="fixed" lang="pt">
<head>
    @include('partials.admin.head')
</head>
<body>
<section class="body">

    {{-- Header principal --}}
    @include('partials.admin.header')

    <div class="inner-wrapper">

        {{-- Sidebar esquerda --}}
        @include('partials.admin.sidebar')

        {{-- Conteúdo principal --}}
        <section role="main" class="content-body">
            @yield('page_header')

            {{-- Conteúdo da página --}}
            @yield('content')
        </section>
    </div>

    {{-- Sidebar direita desativada para já.
         Podemos recuperar depois se realmente fizer sentido. --}}
    {{-- @include('partials.admin.right-sidebar') --}}

</section>

@include('partials.admin.scripts')
</body>
</html>
