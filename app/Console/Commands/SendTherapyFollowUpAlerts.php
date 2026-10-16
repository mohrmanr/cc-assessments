<?php

namespace App\Console\Commands;

use App\Services\TherapyFollowUpAlerts;
use Illuminate\Console\Command;

class SendTherapyFollowUpAlerts extends Command
{
    protected $signature = "therapy:send-follow-up-alerts";

    protected $description = "Email case managers when a 3-month, 6-month, or 1-year follow-up is due.";

    public function handle(TherapyFollowUpAlerts $alerts): int
    {
        $count = $alerts->sendDue();
        $this->info("Sent {$count} follow-up alert(s).");

        return self::SUCCESS;
    }
}
