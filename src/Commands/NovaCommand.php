<?php

namespace UnknowSk\Nova\Commands;

use Illuminate\Console\Command;

class NovaCommand extends Command
{
    public $signature = 'laravel-nova';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
