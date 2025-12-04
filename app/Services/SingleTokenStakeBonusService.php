<!-- <?php

// namespace App\Services;

// use App\Models\userPlansModel;
// use App\Models\UsersusersModel;
// use Carbon\Carbon;
// use Illuminate\Support\Facades\DB;
// use App\Models\SingleTokenStakeModel;
// use App\Models\withdrawModel;

// class SingleTokenStakeBonusService
// {
    // protected $dailyRate; // = 0.007; // 0.7% daily

    // public function __construct()
    // {
    //     $sts_plan = SingleTokenStakeModel::first();
    //     $this->dailyRate = $sts_plan ? $sts_plan->daily_bonus_rate : 0; // $this->dailyRate = 0.007; // 0.7% per day
    // }
 
    // /**
    //  * Process reward for a single user
    //  */
    // public function processUserReward($userId)
    // {
    //     // Calculate net stake
    //     $stake = userPlansModel::where('user_id', $userId)
    //         ->selectRaw('COALESCE(SUM(amount + compound_amount),0) as total')
    //         ->value('total');

    //     $unstake = withdrawModel::where('withdraw_type','UNSTAKE')
    //         ->where('user_id', $userId)
    //         ->sum('amount');

    //     $netStake = $stake - $unstake;     

    //     if ($netStake <= 0) {
    //         return 0;
    //     }

    //     echo "netStake:".$netStake."<br>";

    //     echo "1 DORA: $10<br>";
        
    //     // Convert to DORA (assuming 1 DORA = $10)
    //     $doraCount = $netStake / 10;

    //     echo "doraCount:".$doraCount."<br>";

    //     // Daily reward
    //     $rate = $this->dailyRate;

    //     echo "rate:".$rate."%"."<br>";

    //     if ($this->dailyRate > 0) {
    //         $rate = $this->dailyRate / 100.0;
    //     }

    //     echo "rate/100:".$rate."<br>";

    //     $reward = $doraCount * $rate;

    //     echo "reward:".$reward."<br>";

    //     $doraCount = $doraCount + $reward;

    //     echo "doraCount:".$doraCount."<br>";

    //     $doraCount_rev = $doraCount * 10;

    //     echo "doraCount_rev:".$doraCount_rev."<br>";

    //     dd($netStake, $doraCount, $doraCount_rev);

    //     // dd($stake, $unstake, $netStake, $rate, $reward);

    //     // // Save transactionally
    //     // DB::transaction(function () use ($userId, $reward) {
    //     //     UserRewards::create([
    //     //         'user_id'    => $userId,
    //     //         'amount'     => $reward,
    //     //         'reward_date'=> now(),
    //     //     ]);

    //     //     // optionally update plan balance here
    //     // });


    //     return [
    //             'user_id'    => $userId,
    //             'amount'     => $reward,
    //             'doraCount_rev' => $doraCount_rev,
    //             'reward_date'=> now(),
    //     ];

    //     return $reward;
    // }

    // public function getDailyRate()
    // {
    //     return $this->dailyRate;
    // }
// }