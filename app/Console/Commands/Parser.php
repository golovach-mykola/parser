<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use function GuzzleHttp\Psr7\try_fopen;

class Parser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:parse {domain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Site parse';

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
        (new \App\Http\Services\Parser($this->argument('domain')))->parse();
        $this->info('Finish');
    }
}
