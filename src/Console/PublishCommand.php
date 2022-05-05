<?php

namespace Codewiser\Folks\Console;

use Illuminate\Console\Command;

class PublishCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'folks:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish all of the Folks resources';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->call('vendor:publish', [
            '--tag' => 'folks-assets',
            '--force' => true,
        ]);
    }
}
