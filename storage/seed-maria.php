<?php

$u = App\Models\User::firstOrCreate(
    ['email' => 'maria@medico.com'],
    [
        'name' => 'Dra. Maria Teste',
        'password' => bcrypt('senhaMaria123'),
        'email_verified_at' => now(),
        'specialty' => 'Clínica Geral',
    ],
);

$u->hospitalMemberships()->firstOrCreate(
    ['hospital_id' => 1, 'role' => 'medico'],
    ['active' => true],
);

App\Models\ShiftBoard::find(1)->doctors()->syncWithoutDetaching([$u->id]);

$u->forceFill(['email_verified_at' => now()])->save();

echo 'OK '.$u->id.PHP_EOL;
