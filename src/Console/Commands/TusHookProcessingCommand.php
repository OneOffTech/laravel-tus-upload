<?php

namespace Avvertix\TusUpload\Console\Commands;

use Illuminate\Console\Command;
use Avvertix\TusUpload\TusUploadRepository;
use Avvertix\TusUpload\Concerns\ProcessHooks;
use Avvertix\TusUpload\Console\TusHookInput;
use Avvertix\TusUpload\Contracts\AuthenticationResolver;
use Log;
use Exception;


class TusHookProcessingCommand extends Command
{

    use ProcessHooks;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tus:hook {hook : hook type (pre-create, post-finish, post-receive, post-terminate)} {payload : the hook playload, as a json encoded string}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Receives a tus hook from command line and process it. You should not invoke this command directly.';

    /**
     * @var \Avvertix\TusUpload\TusUploadRepository
     */
    private $uploads = null;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(TusUploadRepository $uploads)
    {
        parent::__construct();

        $this->uploads = $uploads;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $hook = $this->argument('hook');

        $payloadString = $this->argument('payload');

        $payload = TusHookInput::create($payloadString);

        Log::info("Processing $hook...", ['payload' => $payload]);


        if(!in_array($hook, ['pre-create', 'post-finish', 'post-terminate', 'post-receive'])){
            throw new Exception("Unrecognized hook {$hook}");
        }

        if(is_null(!$payload)){
            throw new Exception('Payload parsing error');
        }

        if(!$this->isPayloadValid($payload)){
            throw new Exception('Invalid payload');
        }

        $done = $this->{camel_case($hook)}($payload);

        Log::info("$hook processed.", ['payload' => $payload, 'done' => $done]);

        return $done ? 0 : 1;
    }



}
