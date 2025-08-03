<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScheduleDisruption;
use App\Services\ScheduleDisruptionService;

class ProcessExpiredDisruptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'disruptions:process-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process expired schedule disruptions and execute majority decisions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $service = new ScheduleDisruptionService();
        
        // Get all pending disruptions past their deadline
        $expiredDisruptions = ScheduleDisruption::where('status', 'pending')
            ->where('response_deadline', '<', now())
            ->get();

        $processed = 0;
        
        foreach ($expiredDisruptions as $disruption) {
            $this->info("Processing disruption #{$disruption->id} for schedule: {$disruption->schedule->title}");
            
            if ($service->processResponses($disruption)) {
                $processed++;
                $this->info("✓ Processed disruption #{$disruption->id}");
            } else {
                $this->warn("✗ Failed to process disruption #{$disruption->id}");
            }
        }

        $this->info("Processed {$processed} expired disruptions out of {$expiredDisruptions->count()} total.");
        
        return 0;
    }
}
