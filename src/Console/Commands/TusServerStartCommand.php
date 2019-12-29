<?php

namespace OneOffTech\TusUpload\Console\Commands;

use Illuminate\Console\Command;
use OneOffTech\TusUpload\Console\SupportsTusd;
use OneOffTech\TusUpload\Events\TusUploaderStarted;
use OneOffTech\TusUpload\Events\TusUploaderStopped;
use Symfony\Component\Process\Process;

class TusServerStartCommand extends Command
{

    use SupportsTusd;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tus:start {--no-hooks : Disable tus hooks, upload progress will not be tracked}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Starts the tus server with the options configured in the configuration file';

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
     * @return mixed
     */
    public function handle()
    {
        register_shutdown_function([$this, 'shutdownCallback']);

        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$this, 'sigintShutdown']);
            pcntl_signal(SIGINT, [$this, 'sigintShutdown']);
        }

        try {
            $this->line("Starting Tus uploader...");

            $noHooks = $this->option('no-hooks');

            if($noHooks){
                config(['tusupload.hooks' => '']);
            }

            static::startTusd(function ($type, $buffer) {
            
                if (Process::ERR === $type) {
                    $this->error('ERROR output -----------------');
                    $this->error($buffer);
                } else {
                    $this->line($buffer);
                }

                event(new TusUploaderStarted());

            })->wait();

            while(static::tusdIsRunning()){
                // keep running until the executable runs
                static::$tusProcess->wait();
            }
            
            $this->line('Going to shutdown...');
            
        } catch (\Exception $ex) {

            $this->error($ex->getMessage());
           return 1;
        }

        return 0;
    }
 
    public function sigintShutdown($signal)
    {
        if ($signal === SIGINT || $signal === SIGTERM || $signal === SIGKILL) {
            $this->shutdownCallback();
        }
    }

    /**
     * Handle the shutdown of the PHP cli command
     */
    public function shutdownCallback()
    {
        $lastError = error_get_last();

        $reason = !is_null($lastError) ? 'error' : 'user-action';

        static::stopTusd();

        event(new TusUploaderStopped($reason, $lastError));

        $this->comment(sprintf('Shutdown Tus uploader [%1$s].', $reason) );
    }
}
