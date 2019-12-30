<?php

namespace OneOffTech\TusUpload\Console;

use RuntimeException;
use Symfony\Component\Process\Process;
use OneOffTech\TusUpload\Events\TusUploaderStarted;
use OneOffTech\TusUpload\Events\TusUploaderStopped;

trait SupportsTusd
{
    /**
     * The path to the custom Tusd binary.
     *
     * @var string|null
     */
    protected static $tusDriver;

    /**
     * The Chromedriver process instance.
     *
     * @var \Symfony\Component\Process\Process
     */
    protected static $tusProcess;

    /**
     * Start the Tusd process.
     *
     * @throws \RuntimeException if the driver file path doesn't exist.
     *
     * @return \Symfony\Component\Process\Process the tusd process
     */
    public static function startTusd(callable $callback = null)
    {
        static::$tusProcess = static::buildTusProcess();

        static::$tusProcess->setTimeout(null);
        static::$tusProcess->setIdleTimeout(null);

        static::$tusProcess->start($callback);

        return static::$tusProcess;
    }
    
    public static function tusdIsRunning()
    {
        if (static::$tusProcess) {
            return static::$tusProcess->isRunning();
        }

        return false;
    }

    /**
     * Stop the Tusd process.
     *
     * @return void
     */
    public static function stopTusd()
    {
        if (static::$tusProcess) {
            static::$tusProcess->stop(0);
        }
    }

    /**
     * Build the process to run the Tus server.
     *
     * @throws \RuntimeException if the driver file path doesn't exist.
     *
     * @return \Symfony\Component\Process\Process
     */
    protected static function buildTusProcess()
    {
        $path = config('tusupload.executable').static::driverSuffix();
        $driver = static::$tusDriver ?: realpath($path);

        if (is_bool($driver) || realpath($driver) === false) {
            throw new RuntimeException("Invalid path to tusd [{$driver}, {$path}].");
        }
                
        $tus_arguments = static::tusdArguments();

        $arguments = array_merge([realpath($driver)], $tus_arguments);

        return new Process($arguments);
    }

    /**
     * Set the path to the custom Tusd binary.
     *
     * @param  string  $path
     * @return void
     */
    public static function useTusdBinary($path)
    {
        static::$tusDriver = $path;
    }

    /**
     * Get the Tusd arguments.
     *
     * @return array
     */
    protected static function tusdArguments()
    {
        $arguments = [
            '-host=' . config('tusupload.host'),
            '-port=' . config('tusupload.port'),
            '-base-path=' . config('tusupload.base_path'),
            '-upload-dir=' .  config('tusupload.storage'),            
        ];

        $hooksPath = static::hooksPath();

        if(!empty($hooksPath)){
            $arguments[] = '-hooks-dir=' . $hooksPath;
        }

        if(config('tusupload.behind_proxy')){
            $arguments[] = '-behind-proxy';
        }
        
        if(config('tusupload.storage_size')){
            $arguments[] = '-store-size=' . config('tusupload.storage_size');
        }
        
        if(config('tusupload.expose_metrics')){
            $arguments[] = '-expose-metrics';
        }

        return $arguments;
    }

    /**
     * Get the suffix for the tusd binary.
     *
     * @return string
     */
    protected static function driverSuffix()
    {
        switch (PHP_OS) {
            case 'Darwin':
                return 'mac';
            case 'WINNT':
                return 'win.exe';
            default:
                return 'linux';
        }
    }

    /**
     * Get the suffix for the tusd binary.
     *
     * @return string
     */
    protected static function hooksPath()
    {

        $base = config('tusupload.hooks');

        if(empty($base)){
            return null;
        }

        switch (PHP_OS) {
            case 'WINNT':
                return $base . '/win';
            default:
                return $base . '/linux';
        }
    }
}