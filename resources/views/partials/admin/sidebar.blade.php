<aside id="sidebar-left" class="sidebar-left">
    <div class="sidebar-header">
        <div class="sidebar-title">
            Navegação
        </div>

        <div class="sidebar-toggle d-none d-md-block"
             data-toggle-class="sidebar-left-collapsed"
             data-target="html"
             data-fire-event="sidebar-left-toggle">
            <i class="fas fa-bars" aria-label="Fechar menu lateral"></i>
        </div>
    </div>

    <div class="nano">
        <div class="nano-content">
            <nav id="menu" class="nav-main" role="navigation">
                <ul class="nav nav-main">

                    <li class="{{ request()->routeIs('dashboard') ? 'nav-active' : '' }}">
                        <a class="nav-link" href="{{ route('dashboard') }}">
                            <i class="bx bx-home-alt" aria-hidden="true"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    @can('customers.view')
                        <li class="{{ request()->routeIs('customers.*') ? 'nav-active' : '' }}">
                            <a class="nav-link" href="{{ route('customers.index') }}">
                                <i class="bx bx-user" aria-hidden="true"></i>
                                <span>Clientes</span>
                            </a>
                        </li>
                    @endcan

                    @can('jobs.view')
                        <li class="{{ request()->routeIs('jobs.*') ? 'nav-active' : '' }}">
                            <a class="nav-link" href="{{ route('jobs.index') }}">
                                <i class="bx bx-building-house" aria-hidden="true"></i>
                                <span>Obras</span>
                            </a>
                        </li>
                    @endcan

                    @can('budgets.view')
                        <li class="{{ request()->routeIs('budgets.*') ? 'nav-active' : '' }}">
                            <a class="nav-link" href="{{ route('budgets.index') }}">
                                <i class="bx bx-file" aria-hidden="true"></i>
                                <span>Orçamentos</span>
                            </a>
                        </li>
                    @endcan

                    @can('stock.view')
                        <li class="{{ request()->routeIs('stock.*') ? 'nav-active' : '' }}">
                            <a class="nav-link" href="{{ route('stock.index') }}">
                                <i class="bx bx-box" aria-hidden="true"></i>
                                <span>Stock</span>
                            </a>
                        </li>
                    @endcan

                    @can('users.view')
                        <li class="{{ request()->routeIs('users.*') ? 'nav-active' : '' }}">
                            <a class="nav-link" href="{{ route('users.index') }}">
                                <i class="bx bx-group" aria-hidden="true"></i>
                                <span>Utilizadores</span>
                            </a>
                        </li>
                    @endcan
                    @can('settings.manage')
                        <li>
                            <a href="{{ route('item-families.index') }}">
                                <i class="bx bx-category-alt"></i>
                                <span>Famílias de Artigos</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('brands.index') }}">
                                <i class="bx bx-purchase-tag-alt"></i>
                                <span>Marcas</span>
                            </a>
                        </li>
                          <li>
                            <a href="{{ route('units.index') }}">
                                <i class="bx bx-ruler"></i>
                                <span>Unidades</span>
                            </a>
                        </li>
                    @endcan

                </ul>
            </nav>
        </div>
    </div>
</aside>
