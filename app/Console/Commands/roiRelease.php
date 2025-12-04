<?php

namespace App\Console\Commands;

use App\Http\Controllers\scriptController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

use App\Services\ReferralBonusService;
use App\Services\CitizenAllianceService;
use Carbon\Carbon;

use App\Models\usersModel;

class roiRelease extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roi:release';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Roi & Level Income Release';

    protected $referralBonusService;
    
    public function __construct(ReferralBonusService $referralBonusService)
    {
        parent::__construct();
        $this->referralBonusService = $referralBonusService;
    }

    /**
     * Execute the console command.
     */
    public function handle(CitizenAllianceService $svc, Request $request)
    {
        $appHome = new scriptController;
        $appHome->roiRelease($request);        
        $appHome->starBonus($request);

        // $res = $svc->citizenAllianceRewardBonus();

        // $appHome = app(\App\Http\Controllers\scriptController::class);
        // $appHome->roiRelease($request);

        // Code to call referral bonus
        // $endDate = Carbon::now();                       // current date-time
        // $startDate = Carbon::now()->subDay();           // 24 hours ago
        // $startDateStr = $startDate->toDateTimeString(); // '2025-07-17 00:00:00';
        // $endDateStr   = $endDate->toDateTimeString();   // '2025-07-17 23:59:59';
        // $getUsers = usersModel::select('id')->where('my_direct','>=',2)->get();
        // foreach($getUsers as $uvalue)
        // {
        //     $this->referralBonusService->calculateReferralBonus($uvalue['id'], $startDateStr, $endDateStr, true);
        // }
    }
}
