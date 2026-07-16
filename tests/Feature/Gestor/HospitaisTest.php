<?php

use App\Enums\Role;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Volt\Volt;

function gestorWithHospital(string $hospitalName = 'Hospital Teste'): array
{
    $user = User::factory()->create();
    $hospital = Hospital::factory()->create(['name' => $hospitalName]);
    $user->hospitalMemberships()->create([
        'hospital_id' => $hospital->id,
        'role' => Role::Gestor,
    ]);

    return [$user, $hospital];
}

test('gestor sees only his own hospitals', function () {
    [$gestorA, $hospitalA] = gestorWithHospital('Hospital do Gestor A');
    [$gestorB, $hospitalB] = gestorWithHospital('Hospital do Gestor B');

    $this->actingAs($gestorA);

    Volt::test('pages.gestor.hospitais')
        ->assertSee('Hospital do Gestor A')
        ->assertDontSee('Hospital do Gestor B');
});

test('gestor cannot edit a hospital he does not manage', function () {
    [$gestorA] = gestorWithHospital();
    [, $hospitalB] = gestorWithHospital('Hospital Alheio');

    $this->actingAs($gestorA);

    Volt::test('pages.gestor.hospitais')
        ->call('edit', $hospitalB->id);
})->throws(ModelNotFoundException::class);

test('gestor cannot select a hospital he does not manage', function () {
    [$gestorA, $hospitalA] = gestorWithHospital();
    [, $hospitalB] = gestorWithHospital('Hospital Alheio');

    $this->actingAs($gestorA);

    Volt::test('layout.navigation')
        ->call('selectHospital', $hospitalB->id);

    expect(session('current_hospital_id'))->not->toBe($hospitalB->id);
});

test('gestor can create a hospital and becomes its gestor', function () {
    [$gestor] = gestorWithHospital();

    $this->actingAs($gestor);

    Volt::test('pages.gestor.hospitais')
        ->call('create')
        ->set('name', 'Hospital Novo')
        ->call('save');

    $hospital = Hospital::where('name', 'Hospital Novo')->first();

    expect($hospital)->not->toBeNull()
        ->and($gestor->isGestorOf($hospital))->toBeTrue();
});

test('gestor can update his own hospital', function () {
    [$gestor, $hospital] = gestorWithHospital('Nome Antigo');

    $this->actingAs($gestor);

    Volt::test('pages.gestor.hospitais')
        ->call('edit', $hospital->id)
        ->set('name', 'Nome Novo')
        ->call('save');

    expect($hospital->fresh()->name)->toBe('Nome Novo');
});

test('current hospital defaults to the first managed hospital', function () {
    [$gestor, $hospital] = gestorWithHospital('Hospital Único');

    $this->actingAs($gestor);

    expect(currentHospital()->id)->toBe($hospital->id)
        ->and(session('current_hospital_id'))->toBe($hospital->id);
});

test('switching hospital persists in session', function () {
    [$gestor, $hospitalA] = gestorWithHospital('Hospital A');
    $hospitalB = Hospital::factory()->create(['name' => 'Hospital B']);
    $gestor->hospitalMemberships()->create([
        'hospital_id' => $hospitalB->id,
        'role' => Role::Gestor,
    ]);

    $this->actingAs($gestor);

    Volt::test('layout.navigation')->call('selectHospital', $hospitalB->id);

    expect(session('current_hospital_id'))->toBe($hospitalB->id)
        ->and(currentHospital()->id)->toBe($hospitalB->id);
});

test('guests cannot access the hospitals page', function () {
    $this->get('/gestor/hospitais')->assertRedirect('/login');
});
