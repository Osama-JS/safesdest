<?php

namespace App\Jobs;

use App\Services\FirebaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendFirebaseNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $method;
    protected $args;

    public function __construct($method, array $args = [])
    {
        $this->method = $method;
        $this->args = $args;

        // Push this job to the 'noti' queue
        $this->onQueue('noti');
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(FirebaseService $firebaseService)
    {
        Log::info("Job SendFirebaseNotification starting for method: {$this->method}");

        try {
            if (method_exists($firebaseService, $this->method)) {
                call_user_func_array([$firebaseService, $this->method], $this->args);
            } else {
                Log::error("Method {$this->method} not found in FirebaseService");
            }
        } catch (\Exception $e) {
            Log::error("Job SendFirebaseNotification failed: " . $e->getMessage());
            $this->fail($e);
        }
    }
}
