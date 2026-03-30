<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompleteInvitationRequest;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InvitationAcceptanceController extends Controller
{
    public function create(Request $request, UserInvitation $invitation): View
    {
        $token = trim((string) $request->query('token', ''));
        $isValid = $this->isValidInvitation($invitation, $token);

        return view('auth.accept-invitation', [
            'invitation' => $invitation,
            'token' => $token,
            'isValidInvitation' => $isValid,
        ]);
    }

    public function store(CompleteInvitationRequest $request, UserInvitation $invitation): RedirectResponse
    {
        $token = $request->validated('token');

        if (! $this->isValidInvitation($invitation, $token)) {
            return back()
                ->withErrors(['token' => 'Este convite e invalido, expirou ou ja foi utilizado.'])
                ->withInput($request->except(['password', 'password_confirmation']));
        }

        if (User::query()->where('email', $invitation->email)->exists()) {
            return back()
                ->withErrors(['email' => 'Ja existe uma conta associada a este email.'])
                ->withInput($request->except(['password', 'password_confirmation']));
        }

        $user = DB::transaction(function () use ($request, $invitation) {
            $user = User::create([
                'name' => $request->validated('name'),
                'email' => $invitation->email,
                'password' => $request->validated('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]);

            $user->syncRoles($invitation->role_names ?? []);
            $user->syncPermissions($invitation->permission_names ?? []);

            $invitation->update([
                'accepted_at' => now(),
                'created_user_id' => $user->id,
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Conta criada com sucesso. Bem-vindo!');
    }

    private function isValidInvitation(UserInvitation $invitation, string $token): bool
    {
        if ($token === '' || strlen($token) !== 64) {
            return false;
        }

        if ($invitation->accepted_at !== null || $invitation->revoked_at !== null || $invitation->isExpired()) {
            return false;
        }

        return hash_equals($invitation->token_hash, hash('sha256', $token));
    }
}
