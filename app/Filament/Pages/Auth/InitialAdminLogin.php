<?php

namespace App\Filament\Pages\Auth;

use App\Models\User;
use Filament\Auth\Pages\Login;
use Illuminate\Contracts\Support\Htmlable;

class InitialAdminLogin extends Login
{
    public function mount(): void
    {
        if (! User::hasVerifiedAdministrator()) {
            redirect()->route('filament.admin.auth.register');

            return;
        }

        parent::mount();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return null;
    }
}
