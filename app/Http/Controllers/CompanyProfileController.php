<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompanyProfileRequest;
use App\Mail\TestCompanySmtpMail;
use App\Models\CompanyProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Throwable;

class CompanyProfileController extends Controller
{
    public function show()
    {
        $companyProfile = CompanyProfile::firstOrCreate(
            ['owner_id' => Auth::id()],
            ['country_code' => 'PT']
        );

        return view('company-profile.show', compact('companyProfile'));
    }

    public function edit()
    {
        $companyProfile = CompanyProfile::firstOrCreate(
            ['owner_id' => Auth::id()],
            ['country_code' => 'PT']
        );

        return view('company-profile.edit', compact('companyProfile'));
    }

    public function update(UpdateCompanyProfileRequest $request): RedirectResponse
    {
        $companyProfile = CompanyProfile::firstOrCreate(
            ['owner_id' => Auth::id()],
            ['country_code' => 'PT']
        );

        $data = $request->validated();

        if ($request->boolean('remove_logo')) {
            if ($companyProfile->logo_path) {
                Storage::disk('public')->delete($companyProfile->logo_path);
            }

            $data['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($companyProfile->logo_path) {
                Storage::disk('public')->delete($companyProfile->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->store('company-logos', 'public');
        }

        unset($data['logo'], $data['remove_logo']);

        $companyProfile->update($data);

        return redirect()
            ->route('company-profile.show')
            ->with('success', 'Dados da empresa atualizados com sucesso.');
    }

    public function sendTestEmail(Request $request): RedirectResponse
    {
        $companyProfile = CompanyProfile::firstOrCreate(
            ['owner_id' => Auth::id()],
            ['country_code' => 'PT']
        );

        $validator = Validator::make(
            $request->all(),
            [
                'test_recipient_email' => ['required', 'email', 'max:150'],
            ],
            [],
            [
                'test_recipient_email' => 'email de destino',
            ]
        );

        if ($validator->fails()) {
            return redirect()
                ->route('company-profile.edit')
                ->withErrors($validator, 'testEmail')
                ->withInput()
                ->with('open_test_email_card', true);
        }

        $requiredMailFields = [
            'mail_host' => $companyProfile->mail_host,
            'mail_port' => $companyProfile->mail_port,
            'mail_username' => $companyProfile->mail_username,
            'mail_password' => $companyProfile->mail_password,
            'mail_encryption' => $companyProfile->mail_encryption,
            'mail_from_address' => $companyProfile->mail_from_address,
            'mail_from_name' => $companyProfile->mail_from_name,
        ];

        foreach ($requiredMailFields as $field => $value) {
            if (empty($value)) {
                return redirect()
                    ->route('company-profile.edit')
                    ->with('error', 'Falta configurar o campo de email da empresa: ' . $field . '.')
                    ->withInput()
                    ->with('open_test_email_card', true);
            }
        }

        $recipientEmail = trim((string) $request->input('test_recipient_email'));

        try {
            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.transport' => 'smtp',
                'mail.mailers.smtp.host' => $companyProfile->mail_host,
                'mail.mailers.smtp.port' => (int) $companyProfile->mail_port,
                'mail.mailers.smtp.encryption' => $companyProfile->mail_encryption,
                'mail.mailers.smtp.username' => $companyProfile->mail_username,
                'mail.mailers.smtp.password' => $companyProfile->mail_password,
                'mail.from.address' => $companyProfile->mail_from_address,
                'mail.from.name' => $companyProfile->mail_from_name,
            ]);

            app('mail.manager')->forgetMailers();

            Mail::mailer('smtp')
                ->to($recipientEmail)
                ->send(new TestCompanySmtpMail($companyProfile));
        } catch (Throwable $exception) {
            return redirect()
                ->route('company-profile.edit')
                ->with('error', 'O email de teste falhou: ' . $exception->getMessage())
                ->withInput()
                ->with('open_test_email_card', true);
        }

        return redirect()
            ->route('company-profile.edit')
            ->with('success', 'Email de teste enviado com sucesso para ' . $recipientEmail . '.');
    }
}
