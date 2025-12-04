<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\CitizenAllianceService;

use App\Models\usersModel;

class checkLevel extends Command
{
    protected $signature = 'check:level';
    protected $description = 'Run TeamBonusService::checkLevel';

    public function handle(CitizenAllianceService $svc)
    {
        $res = $svc->checkCitizenAllianceReward();

        // Check if connected
        // if web3.is_connected():
        //     print("Successfully connected to Sepolia testnet via Infura!")
        // else:
        //     print("Failed to connect to Sepolia testnet.")

        $this->info('Check level excuted.');
        $this->info($res);
    }
}
