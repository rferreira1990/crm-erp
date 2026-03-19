<header class="header">
    <div class="logo-container">
        <a href="{{ route('dashboard') }}" class="logo">
            <img src="{{ asset('porto/img/logo.png') }}" width="75" height="35" alt="CRM ERP" />
        </a>

        <div class="d-md-none toggle-sidebar-left"
             data-toggle-class="sidebar-left-opened"
             data-target="html"
             data-fire-event="sidebar-left-opened">
            <i class="fas fa-bars" aria-label="Abrir menu lateral"></i>
        </div>
    </div>

    <div class="header-right">

        {{-- Pesquisa global futura --}}
        <form action="#" class="search nav-form" method="GET">
            <div class="input-group">
                <input type="text" class="form-control" name="q" id="q" placeholder="Pesquisar...">
                <button class="btn btn-default" type="submit">
                    <i class="bx bx-search"></i>
                </button>
            </div>
        </form>

        <span class="separator"></span>

        <div id="userbox" class="userbox">
            <a href="#" data-bs-toggle="dropdown">
                <figure class="profile-picture">
                    <img src="{{ asset('porto/img/!logged-user.jpg') }}"
                         alt="{{ auth()->user()->name }}"
                         class="rounded-circle"
                         data-lock-picture="{{ asset('porto/img/!logged-user.jpg') }}" />
                </figure>

                <div class="profile-info" data-lock-name="{{ auth()->user()->name }}" data-lock-email="{{ auth()->user()->email }}">
                    <span class="name">{{ auth()->user()->name }}</span>
                    <span class="role">
                        {{ auth()->user()->getRoleNames()->first() ?? 'Utilizador' }}
                    </span>
                </div>

                <i class="fa custom-caret"></i>
            </a>

            <div class="dropdown-menu">
                <ul class="list-unstyled mb-2">
                    <li class="divider"></li>

                    <li>
                        <a role="menuitem" tabindex="-1" href="#">
                            <i class="bx bx-user-circle"></i> Perfil
                        </a>
                    </li>

                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="bx bx-power-off"></i> Terminar sessão
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</header>
