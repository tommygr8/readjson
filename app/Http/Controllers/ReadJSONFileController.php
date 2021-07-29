<?php

namespace App\Http\Controllers;

use App\Models\PendingJobs;
use Illuminate\Http\Request;
use App\Jobs\ReadJsonFileJob;
use Illuminate\Http\Response;

class ReadJSONFileController extends Controller
{

    function index(Request $request)
    {
            $log_path = time().".txt";
            $file_name = storage_path('app/challenge.json');
            $job_model = PendingJobs::create([
                'file_name' => $file_name,
                "log_path" => $log_path,

            ]);
            $result = $this->dispatch(new ReadJsonFileJob($job_model));

            $job_model->job_id = $result;
            $job_model->save();
            return $result;

    }



}
