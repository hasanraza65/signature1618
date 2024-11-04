<?php

namespace App\Jobs;

use App\Models\UserRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use Auth;

class ProcessFileUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $request;
    protected $userRequest;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($request, $userRequest)
    {
        $this->request = $request;
        $this->userRequest = $userRequest;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Store file
        $filePath = $this->storeFile($this->request->file('file'), 'files');
        $originalFileName = $this->request->file('file')->getClientOriginalName();
        $thumbnailPath = $this->storeFile($this->request->file('thumbnail'), 'thumbnails');

        // Update the user request with file data
        $this->userRequest->file = $filePath;
        $this->userRequest->file_name = $originalFileName;
        $this->userRequest->thumbnail = $thumbnailPath;
        $this->userRequest->sent_date = Carbon::now();
        $this->userRequest->save();

        // Log activity
        $userName = getUserName($this->request);
        $this->addRequestLog("new_request", "Signature request processed", $userName, $this->userRequest->id);
    }

    protected function storeFile($file, $directory)
    {
        // Logic for storing files
        return $file->store($directory);
    }

    protected function addRequestLog($type, $description, $userName, $requestId)
    {
        // Add log functionality here
    }
}
