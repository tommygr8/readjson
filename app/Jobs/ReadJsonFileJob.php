<?php

namespace App\Jobs;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;


class ReadJsonFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $file_name;
    private $resume_path;
    private $resume_id = 0;
    private $job_model;

    public function __construct($job_model)
    {
        $this->file_name = $job_model->file_name;
        $this->resume_path = $job_model->log_path;
        $this->job_model = $job_model;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $this->job_model->status = "Processing";
        $this->job_model->save();
        $contents = \File::get( $this->file_name);

        $chunks = array_chunk(json_decode($contents), 1000);

        $this->resume_id = (int) $this->getResumeId();
        $i =0;
        foreach($chunks as  $chunk) {
            foreach ($chunk as $data) {
                if($i >= $this->resume_id) {

                    $age =0;
                    try {
                        $age = Carbon::parse($data->date_of_birth)->age;

                    } catch (InvalidFormatException $e) {

                        $aga = Carbon::createFromFormat('d/m/Y', $data->date_of_birth)->age;
                    }

                    if(empty($age) || ( $age >= 18 && $age >= 65 )) {


                        Report::create(
                            [
                                'name' => $data->name,
                                'email' => $data->email,
                                'address' => $data->address,
                                'checked' => $data->checked,
                                'description' => $data->description,
                                'interest' => $data->interest,
                                'account' => $data->account,
                                'date_of_birth' => $data->date_of_birth,
                                'age' => $age,
                                'credit_card' => json_encode($data->credit_card),
                            ]
                        );
                    }
                    $this->resume_id++;
                    $this->logResumeId($this->resume_id);

                }

                $i++;

            }
        }

        $this->job_model->status = "Completed";
        $this->job_model->save();

    }


    function getResumeId()
    {
        if (Storage::exists($this->resume_path)) {
            $contents = Storage::get($this->resume_path);
            $id = (int)trim($contents);
        } else {
            $id = 0;
            $this->logResumeId($id);
        }
        return $id;
    }

    function logResumeId($resumeid)
    {
        Storage::put($this->resume_path, $resumeid);
    }
}
