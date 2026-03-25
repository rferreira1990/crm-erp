<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompanyProfileRequest;
use App\Models\CompanyProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

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
            $data['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('company-logos', 'public');
        }

        unset($data['logo'], $data['remove_logo']);

        $companyProfile->update($data);

        return redirect()
            ->route('company-profile.show')
            ->with('success', 'Dados da empresa atualizados com sucesso.');
    }
}
