<?php

namespace App\Services;

use App\Models\BalanceTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BalanceService
{
    public function adjust(User $user, float $amount, string $type, ?string $reference = null): User
    {
        return DB::transaction(function () use ($user, $amount, $type, $reference) {
            $user = User::lockForUpdate()->find($user->id);
            $user->balance = bcadd((string) $user->balance, (string) $amount, 2);
            $user->save();

            BalanceTransaction::create([
                'user_id' => $user->id,
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $user->balance,
                'reference' => $reference,
            ]);

            return $user->fresh();
        });
    }

    public function debit(User $user, float $amount, string $type, ?string $reference = null): User
    {
        if ((float) $user->balance < $amount) {
            throw new \RuntimeException('Yetersiz bakiye.');
        }
        return $this->adjust($user, -$amount, $type, $reference);
    }
}
