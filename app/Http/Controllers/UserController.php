<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $search = trim((string) $request->input('search'));
        $status = trim((string) $request->input('status'));
        $role = trim((string) $request->input('role'));

        $users = User::query()
            ->with(['roles:id,name', 'permissions:id,name'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, ['active', 'inactive'], true), function ($query) use ($status) {
                $query->where('is_active', $status === 'active');
            })
            ->when($role !== '', function ($query) use ($role) {
                $query->whereHas('roles', function ($roleQuery) use ($role) {
                    $roleQuery->where('name', $role);
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->pluck('name');

        return view('users.index', compact('users', 'roles', 'search', 'status', 'role'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create', [
            'user' => new User(),
            'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->get(),
            'permissions' => Permission::query()->where('guard_name', 'web')->orderBy('name')->get(),
            'assignedRoles' => collect(),
            'directPermissions' => collect(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        $user->syncRoles($validated['roles']);
        $user->syncPermissions($validated['permissions'] ?? []);

        return redirect()
            ->route('users.show', $user)
            ->with('success', 'Utilizador criado com sucesso.');
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        $user->load(['roles:id,name', 'permissions:id,name']);

        return view('users.show', compact('user'));
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $user->load(['roles:id,name', 'permissions:id,name']);

        return view('users.edit', [
            'user' => $user,
            'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->get(),
            'permissions' => Permission::query()->where('guard_name', 'web')->orderBy('name')->get(),
            'assignedRoles' => $user->roles->pluck('name'),
            'directPermissions' => $user->permissions->pluck('name'),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validated();
        $selectedRoles = collect($validated['roles']);
        $isActive = (bool) ($validated['is_active'] ?? true);
        $authUser = $request->user();

        if ($authUser->id === $user->id) {
            if (! $isActive) {
                return back()
                    ->withErrors(['is_active' => 'Nao podes desativar a tua propria conta.'])
                    ->withInput();
            }

            if ($user->hasRole('admin') && ! $selectedRoles->contains('admin')) {
                return back()
                    ->withErrors(['roles' => 'Nao podes remover o role admin da tua propria conta.'])
                    ->withInput();
            }
        }

        if ($user->hasRole('admin') && ! $selectedRoles->contains('admin') && ! $this->hasAnotherActiveAdmin($user->id)) {
            return back()
                ->withErrors(['roles' => 'Tem de existir pelo menos um utilizador admin ativo.'])
                ->withInput();
        }

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_active' => $isActive,
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        $user->syncRoles($validated['roles']);
        $user->syncPermissions($validated['permissions'] ?? []);

        return redirect()
            ->route('users.show', $user)
            ->with('success', 'Utilizador atualizado com sucesso.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ((int) auth()->id() === (int) $user->id) {
            return back()->with('error', 'Nao podes apagar a tua propria conta.');
        }

        if ($user->hasRole('admin') && ! $this->hasAnotherActiveAdmin($user->id)) {
            return back()->with('error', 'Tem de existir pelo menos um utilizador admin ativo.');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Utilizador removido com sucesso.');
    }

    private function hasAnotherActiveAdmin(int $exceptUserId): bool
    {
        return User::query()
            ->role('admin')
            ->where('is_active', true)
            ->whereKeyNot($exceptUserId)
            ->exists();
    }
}
