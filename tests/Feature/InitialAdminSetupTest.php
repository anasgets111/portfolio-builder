<?php

use App\Filament\Pages\Auth\InitialAdminRegistration;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('shows administrator setup when accessing an empty panel', function () {
    $this->followingRedirects()
        ->get('/admin')
        ->assertSuccessful()
        ->assertSee('Create your administrator account');
});

it('creates and signs in the initial verified administrator', function () {
    Livewire::test(InitialAdminRegistration::class)
        ->fillForm([
            'name' => 'Portfolio Administrator',
            'email' => 'admin@example.com',
            'password' => 'correct horse battery staple',
            'passwordConfirmation' => 'correct horse battery staple',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    $administrator = User::query()->sole();

    expect($administrator->email_verified_at)->not->toBeNull()
        ->and(Hash::check('correct horse battery staple', $administrator->password))->toBeTrue();

    $this->assertAuthenticatedAs($administrator);
});

it('disables setup once a verified administrator exists', function () {
    User::factory()->create();

    $this->get('/admin/setup')
        ->assertRedirect('/admin/login');
});

it('keeps setup available when only unverified users exist', function () {
    User::factory()->unverified()->create();

    $this->get('/admin/setup')
        ->assertSuccessful()
        ->assertSee('Create your administrator account');
});
