<?php

namespace Tests\Feature\Users;

use App\Mail\UserInvitationMail;
use App\Models\User;
use App\Models\UserInvitation;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class UserInvitationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_admin_can_send_user_invitation_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'invitee_name' => 'Novo Colaborador',
            'email' => 'novo.colaborador@example.com',
            'roles' => ['funcionario'],
            'permissions' => [],
        ]);

        $response->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('user_invitations', [
            'invitee_name' => 'Novo Colaborador',
            'email' => 'novo.colaborador@example.com',
            'invited_by' => $admin->id,
        ]);

        Mail::assertSent(UserInvitationMail::class, function (UserInvitationMail $mail) {
            return $mail->hasTo('novo.colaborador@example.com');
        });

        $this->assertDatabaseMissing('users', [
            'email' => 'novo.colaborador@example.com',
        ]);
    }

    public function test_invited_user_can_complete_registration_with_valid_token(): void
    {
        $token = str_repeat('a', 64);

        $invitation = UserInvitation::create([
            'invited_by' => User::factory()->create()->id,
            'invitee_name' => 'Utilizador Convidado',
            'email' => 'convidado@example.com',
            'token_hash' => hash('sha256', $token),
            'role_names' => ['funcionario'],
            'permission_names' => [],
            'expires_at' => now()->addHours(2),
        ]);

        $signedUrl = URL::temporarySignedRoute('invitations.accept', $invitation->expires_at, [
            'invitation' => $invitation->id,
            'token' => $token,
        ]);

        $this->get($signedUrl)
            ->assertOk()
            ->assertSee('Concluir registo');

        $response = $this->post(route('invitations.complete', $invitation), [
            'name' => 'Utilizador Final',
            'token' => $token,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'convidado@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('Utilizador Final', $user->name);
        $this->assertTrue($user->hasRole('funcionario'));

        $invitation->refresh();
        $this->assertNotNull($invitation->accepted_at);
        $this->assertSame($user->id, $invitation->created_user_id);
    }

    public function test_invitation_can_not_be_completed_with_invalid_token(): void
    {
        $invitation = UserInvitation::create([
            'invited_by' => User::factory()->create()->id,
            'invitee_name' => 'Utilizador Convidado',
            'email' => 'convite.invalido@example.com',
            'token_hash' => hash('sha256', str_repeat('b', 64)),
            'role_names' => ['funcionario'],
            'permission_names' => [],
            'expires_at' => now()->addHours(2),
        ]);

        $response = $this->post(route('invitations.complete', $invitation), [
            'name' => 'Utilizador Final',
            'token' => str_repeat('c', 64),
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors('token');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', [
            'email' => 'convite.invalido@example.com',
        ]);
    }
}
