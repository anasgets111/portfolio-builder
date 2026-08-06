<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Filament\Auth\Pages\Register;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class InitialAdminRegistration extends Register
{
    public function mount(): void
    {
        if (User::hasVerifiedAdministrator()) {
            redirect()->route('filament.admin.auth.login');

            return;
        }

        parent::mount();
    }

    public function register(): ?RegistrationResponse
    {
        $lock = Cache::lock('initial-admin-registration', 10);

        if (! $lock->get()) {
            throw ValidationException::withMessages([
                'data.email' => 'Another administrator setup request is in progress.',
            ]);
        }

        try {
            return parent::register();
        } finally {
            $lock->release();
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRegistration(array $data): Model
    {
        if (User::hasVerifiedAdministrator()) {
            throw ValidationException::withMessages([
                'data.email' => 'An administrator has already been created.',
            ]);
        }

        $user = new User($data);
        $user->email_verified_at = Carbon::now();
        $user->save();

        return $user;
    }

    public function getTitle(): string|Htmlable
    {
        return 'Set up your portfolio';
    }

    public function getHeading(): string|Htmlable|null
    {
        return 'Create your administrator account';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'This one-time form is disabled as soon as the administrator account is created.';
    }
}
