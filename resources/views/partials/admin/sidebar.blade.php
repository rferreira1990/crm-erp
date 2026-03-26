@php
    $menuItems = config('admin_menu', []);

    $user = auth()->user();

    $isItemVisible = function (array $item) use (&$isItemVisible, $user): bool {
        if (!empty($item['permission']) && (!$user || !$user->can($item['permission']))) {
            return false;
        }

        if (!empty($item['children']) && is_array($item['children'])) {
            foreach ($item['children'] as $child) {
                if ($isItemVisible($child)) {
                    return true;
                }
            }

            return false;
        }

        return true;
    };

    $isItemActive = function (array $item) use (&$isItemActive): bool {
        $activeRoutes = $item['active'] ?? [];

        if (!empty($item['route'])) {
            $activeRoutes[] = $item['route'];
        }

        foreach ($activeRoutes as $routeName) {
            if (request()->routeIs($routeName)) {
                return true;
            }
        }

        if (!empty($item['children']) && is_array($item['children'])) {
            foreach ($item['children'] as $child) {
                if ($isItemActive($child)) {
                    return true;
                }
            }
        }

        return false;
    };
@endphp

<aside id="sidebar-left" class="sidebar-left">
    <div class="sidebar-header">
        <div class="sidebar-title">Navegação</div>

        <div class="sidebar-toggle d-none d-md-block" data-toggle-class="sidebar-left-collapsed" data-target="html" data-fire-event="sidebar-left-toggle">
            <i class="fas fa-bars" aria-label="Alternar menu"></i>
        </div>
    </div>

    <div class="nano">
        <div class="nano-content">
            <nav id="menu" class="nav-main" role="navigation">
                <ul class="nav nav-main">
                    @foreach ($menuItems as $item)
                        @if ($isItemVisible($item))
                            @include('partials.admin.sidebar-item', ['item' => $item, 'level' => 0])
                        @endif
                    @endforeach
                </ul>
            </nav>
        </div>
    </div>
</aside>
