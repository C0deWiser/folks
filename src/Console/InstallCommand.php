<?php

namespace Codewiser\Folks\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'folks:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install all of the Folks resources';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->comment('Publishing Folks Service Provider...');
        $this->callSilent('vendor:publish', ['--tag' => 'folks-provider']);

        $this->comment('Publishing Folks Assets...');
        $this->callSilent('vendor:publish', ['--tag' => 'folks-assets']);

        $this->comment('Publishing Folks Actions...');
        $this->callSilent('vendor:publish', ['--tag' => 'folks-support']);

        $this->comment('Publishing Folks Configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'folks-config']);

        $this->registerFolksServiceProvider();

        $this->info('Folks scaffolding installed successfully.');
    }

    /**
     * Register the Folks service provider in the application configuration file.
     *
     * @return void
     */
    protected function registerFolksServiceProvider()
    {
        $namespace = Str::replaceLast('\\', '', $this->laravel->getNamespace());

        $appConfig = file_get_contents(config_path('app.php'));

        if (Str::contains($appConfig, $namespace.'\\Providers\\FolksServiceProvider::class')) {
            return;
        }

        file_put_contents(config_path('app.php'), str_replace(
            "{$namespace}\\Providers\EventServiceProvider::class,".PHP_EOL,
            "{$namespace}\\Providers\EventServiceProvider::class,".PHP_EOL."        {$namespace}\Providers\FolksServiceProvider::class,".PHP_EOL,
            $appConfig
        ));

        file_put_contents(app_path('Providers/FolksServiceProvider.php'), str_replace(
            "namespace App\Providers;",
            "namespace {$namespace}\Providers;",
            file_get_contents(app_path('Providers/FolksServiceProvider.php'))
        ));
    }
}
