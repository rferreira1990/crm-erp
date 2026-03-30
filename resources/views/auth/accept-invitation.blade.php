<x-guest-layout>
    @if (! $isValidInvitation)
        <div class="text-sm text-gray-700">
            Este convite e invalido, expirou ou ja foi utilizado.
        </div>

        <div class="mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                Ir para login
            </a>
        </div>
    @else
        <form method="POST" action="{{ route('invitations.complete', $invitation) }}">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" class="block mt-1 w-full bg-gray-100" type="email" :value="$invitation->email" disabled />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                <x-input-error :messages="$errors->get('token')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="name" :value="__('Nome')" />
                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name', $invitation->invitee_name)" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input id="password" class="block mt-1 w-full"
                                type="password"
                                name="password"
                                required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mt-4">
                <x-input-label for="password_confirmation" :value="__('Confirmar Password')" />
                <x-text-input id="password_confirmation" class="block mt-1 w-full"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-primary-button class="ms-3">
                    Concluir registo
                </x-primary-button>
            </div>
        </form>
    @endif
</x-guest-layout>
