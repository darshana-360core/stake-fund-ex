<?php

namespace App\Console\Commands;

use App\Http\Controllers\scriptController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class businessSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'business:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Business Sync';

    /**
     * Execute the console command.
     */
    public function handle(Request $request)
    {
        $appHome = new scriptController;

        $appHome->businessSync($request);
    }
}
