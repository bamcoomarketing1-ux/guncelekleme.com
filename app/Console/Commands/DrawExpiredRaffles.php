<?php

namespace App\Console\Commands;

use App\Services\RaffleService;
use Illuminate\Console\Command;

class DrawExpiredRaffles extends Command
{
    protected $signature = 'raffles:draw-expired';

    protected $description = 'Süresi dolmuş çekilişlerde kazanan seç';

    public function handle(RaffleService $raffles): int
    {
        $count = $raffles->drawExpired();
        $this->info("{$count} çekiliş sonuçlandırıldı.");

        return self::SUCCESS;
    }
}
