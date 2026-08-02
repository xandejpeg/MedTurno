<?php

use App\Models\Hospital;
use Illuminate\Support\Str;

if (! function_exists('firstName')) {
    /**
     * Retorna apenas o primeiro nome de uma pessoa, para saudações de mensagens.
     */
    function firstName(?string $fullName): string
    {
        $name = trim((string) $fullName);

        if ($name === '') {
            return '';
        }

        $titles = ['dr', 'dr.', 'dra', 'dra.', 'sr', 'sr.', 'sra', 'sra.', 'prof', 'prof.', 'ms', 'ms.'];

        $parts = preg_split('/\s+/', $name) ?: [];

        foreach ($parts as $part) {
            if (! in_array(Str::lower($part), $titles, true)) {
                return $part;
            }
        }

        return $parts[0] ?? '';
    }
}

if (! function_exists('currentHospital')) {
    /**
     * Hospital atualmente selecionado pelo gestor logado (persistido em session).
     * Retorna null se o usuário não gerencia nenhum hospital.
     */
    function currentHospital(): ?Hospital
    {
        $user = auth()->user();

        if ($user === null) {
            return null;
        }

        $selectedId = session('current_hospital_id');

        if ($selectedId !== null) {
            /** @var Hospital|null $hospital */
            $hospital = $user->managedHospitals()->whereKey($selectedId)->first();

            if ($hospital !== null) {
                return $hospital;
            }
        }

        /** @var Hospital|null $first */
        $first = $user->managedHospitals()->orderBy('name')->first();

        if ($first !== null) {
            session(['current_hospital_id' => $first->id]);
        }

        return $first;
    }
}
