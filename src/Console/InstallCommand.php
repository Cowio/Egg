<?php

namespace G4\Egg\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'egg:install';
    protected $description = 'Install the Egg exception tracking package';

    public function handle()
    {
        $this->info('🔧 Installing Egg...');

        $this->info('📄 Publishing config file...');
        $this->callSilent('vendor:publish', [
            '--tag' => 'egg-config',
            '--force' => true,
        ]);

        $this->info('🧱 Publishing migrations...');
        $this->callSilent('vendor:publish', [
            '--tag' => 'egg-migrations',
            '--force' => true,
        ]);

        // Publish Prism config
        $this->info('🪄 Publishing Prism config...');
        $this->callSilent('vendor:publish', [
            '--tag' => 'prism-config',
            '--force' => false, // don't overwrite if exists
        ]);

        $this->info('🔄 Running migrations...');
        $this->call('migrate');

        $this->newLine();
        $this->info('🎉 Egg installation complete!');
        $this->info('You can now start tracking exceptions.');
        $this->newLine();

        return static::SUCCESS;
    }
}
