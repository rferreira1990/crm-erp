<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\InviteUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Mail\UserInvitationMail;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Throwable;

class UserController extends Controller
{
    public const INVITATION_EXPIRY_HOURS = 48;

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
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('job_title', 'like', "%{$search}%");
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
            'roles' => Role::query()->where('guard_name', 'web')->orderBy('name')->get(),
            'permissions' => Permission::query()->where('guard_name', 'web')->orderBy('name')->get(),
            'invitationExpiryHours' => self::INVITATION_EXPIRY_HOURS,
        ]);
    }

    public function store(InviteUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validated();
        $rawToken = Str::random(64);

        $invitation = DB::transaction(function () use ($validated, $rawToken) {
            UserInvitation::query()
                ->pending()
                ->where('email', $validated['email'])
                ->update(['revoked_at' => now()]);

            return UserInvitation::create([
                'invited_by' => auth()->id(),
                'invitee_name' => $validated['invitee_name'] ?? null,
                'email' => $validated['email'],
                'token_hash' => hash('sha256', $rawToken),
                'role_names' => array_values($validated['roles']),
                'permission_names' => array_values($validated['permissions'] ?? []),
                'expires_at' => now()->addHours(self::INVITATION_EXPIRY_HOURS),
            ]);
        });

        $invitationUrl = URL::temporarySignedRoute(
            'invitations.accept',
            $invitation->expires_at,
            [
                'invitation' => $invitation->id,
                'token' => $rawToken,
            ]
        );

        try {
            Mail::to($invitation->email)->send(new UserInvitationMail(
                invitation: $invitation,
                invitationUrl: $invitationUrl,
            ));
        } catch (Throwable $exception) {
            report($exception);
            $invitation->delete();

            return back()
                ->withInput()
                ->with('error', 'Nao foi possivel enviar o convite por email. Verifica a configuracao de email e tenta novamente.');
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'Convite enviado com sucesso para ' . $invitation->email . '.');
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

        if ($user->hasRole('admin') && ! $isActive && ! $this->hasAnotherActiveAdmin($user->id)) {
            return back()
                ->withErrors(['is_active' => 'Tem de existir pelo menos um utilizador admin ativo.'])
                ->withInput();
        }

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'job_title' => $validated['job_title'] ?? null,
            'hourly_cost' => (float) ($validated['hourly_cost'] ?? 0),
            'hourly_sale_price' => $validated['hourly_sale_price'] ?? null,
            'is_labor_enabled' => (bool) ($validated['is_labor_enabled'] ?? true),
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

    public function sendPasswordReset(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        if (! $request->user()?->hasRole('admin')) {
            abort(403);
        }

        if (! $user->is_active) {
            return back()->with('error', 'Nao e possivel enviar reset para um utilizador inativo.');
        }

        $status = Password::sendResetLink([
            'email' => $user->email,
        ]);

        if ($status !== Password::RESET_LINK_SENT) {
            return back()->with('error', 'Nao foi possivel enviar o email de reset de password.');
        }

        return back()->with('success', 'Link de reset de password enviado para ' . $user->email . '.');
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
