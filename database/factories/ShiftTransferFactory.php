<?php

namespace Database\Factories;

use App\Enums\TransferStatus;
use App\Enums\TransferType;
use App\Models\Shift;
use App\Models\ShiftTransfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShiftTransfer>
 */
class ShiftTransferFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shift_id' => Shift::factory(),
            'type' => TransferType::Direta,
            'from_user_id' => User::factory(),
            'to_user_id' => User::factory(),
            'reason' => null,
            'status' => TransferStatus::AguardandoReceptor,
        ];
    }
}
