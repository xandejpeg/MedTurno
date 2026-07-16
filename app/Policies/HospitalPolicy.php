<?php

namespace App\Policies;

use App\Models\Hospital;
use App\Models\User;

class HospitalPolicy
{
    public function view(User $user, Hospital $hospital): bool
    {
        return $user->isGestorOf($hospital);
    }

    public function update(User $user, Hospital $hospital): bool
    {
        return $user->isGestorOf($hospital);
    }
}
