<?php

namespace App\Console\Commands;

use App\Jobs\ReadJsonFileJob;
use App\Models\PendingJobs;
use Illuminate\Console\Command;
use Carbon\Carbon;

class UpdateJobsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'updatejob';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This is to start jobs that failed';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        //check pending jobs that have not been completed and close to one hour and run them again
        $time = Carbon::now()->subMinutes(60)->toDateTimeString();
        $report = PendingJobs::where('status','!=','Completed')->where('updated_at', '<=',$time )->take(5)->get();
        if($report->isEmpty())
            exit();

        foreach ($report as $data) {
            ReadJsonFileJob::dispatch($data);
            usleep(500);
        }

    }
}
