<?php
namespace My\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use My\Auth\Http\Controllers\MainNotificationController;

class RegisterEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = $this->data;
        try {
            $verify_code = rand(100000, 999999);

            Cache::store('database')->put($data['email'], [
                'name' => $data['name'],
                'surname' => $data['surname'],
                'verify_code' => $verify_code,
                'password' => $data['password'],
            ], 600);

            $sms = new MainNotificationController();

            $message = [
                'title' => 'Programmer UZ',
                'name' => $data['name'],
                'verify_code' => $verify_code,
            ];

            echo "mail is starting to send \n";
            $sms->mail($data['email'], $message, 'mail');
            echo "success \n";

        } catch (\Exception $exception) {

            echo "error \n" . $exception->getMessage();

        }
    }
}
