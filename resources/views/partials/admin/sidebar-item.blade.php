@php
    $user = auth()->user();

    $isItemVisible = function (array $menuItem) use (&$isItemVisible, $user): bool {
        if (!empty($menuItem['permission']) && (!$user || !$user->can($menuItem['permission']))) {
            return false;
        }

        if (!empty($menuItem['children']) && is_array($menuItem['children'])) {
            foreach ($menuItem['children'] as $childItem) {
                if ($isItemVisible($childItem)) {
                    return true;
                }
            }

            return false;
        }

        return true;
    };

    $isItemActive = function (array $menuItem) use (&$isItemActive): bool {
        $activeRoutes = $menuItem['active'] ?? [];

        if (!empty($menuItem['route'])) {
            $activeRoutes[] = $menuItem['route'];
        }

        foreach ($activeRoutes as $routeName) {
            if (request()->routeIs($routeName)) {
                return true;
            }
        }

        if (!empty($menuItem['children']) && is_array($menuItem['children'])) {
            foreach ($menuItem['children'] as $childItem) {
                if ($isItemActive($childItem)) {
                    return true;
                }
            }
        }

        return false;
    };

    $hasChildren = !empty($item['children']) && is_array($item['children']);
    $isActive = $isItemActive($item);
    $iconClass = $item['icon'] ?? 'bx bx-circle';
@endphp

@if ($hasChildren)
    @php
        $visibleChildren = collect($item['children'])
            ->filter(fn (array $child) => $isItemVisible($child))
            ->values();
    @endphp

    @if ($visibleChildren->isNotEmpty())
        <li class="nav-parent {{ $isActive ? 'nav-expanded nav-active' : '' }}">
            <a class="nav-link" href="#">
                <i class="{{ $iconClass }}" aria-hidden="true"></i>
                <span>{{ $item['label'] }}</span>
            </a>

            <ul class="nav nav-children">
                @foreach ($visibleChildren as $child)
                    @include('partials.admin.sidebar-item', ['item' => $child, 'level' => ($level ?? 0) + 1])
                @endforeach
            </ul>
        </li>
    @endif
@else
    <li class="{{ $isActive ? 'nav-active' : '' }}">
        <a class="nav-link" href="{{ route($item['route']) }}">
            <i class="{{ $iconClass }}" aria-hidden="true"></i>
            <span>{{ $item['label'] }}</span>
        </a>
    </li>
@endif
