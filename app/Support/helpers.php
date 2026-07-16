<?php

use App\Models\Hospital;

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
