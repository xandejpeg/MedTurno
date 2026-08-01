<?php

use App\Models\Schedule;
use App\Models\User;

$s = Schedule::latest('id')->first();
echo "SCHEDULE {$s->id} {$s->year}-{$s->month} status={$s->status->value}\n";
$docs = User::whereIn('id', $s->shifts()->whereNotNull('user_id')->distinct()->pluck('user_id'))
    ->get(['id', 'name', 'email', 'phone']);
foreach ($docs as $d) {
    echo "{$d->id} | {$d->name} | {$d->email} | {$d->phone}\n";
}
