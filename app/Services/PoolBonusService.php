<?php

namespace App\Services;

use App\Models\User;
use App\Models\earningLogsModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function App\Helpers\rtxPrice;

class PoolBonusService
{
    protected $poolConfig = [
        ['lock_period' => 1, 'topCount' => 31, 'percentage' => 0.025], // 30 Days 2.5%
        ['lock_period' => 2, 'topCount' => 21, 'percentage' => 0.015], // 90 Days 1.5%
        ['lock_period' => 3, 'topCount' => 11, 'percentage' => 0.01],  // 180 Days 1%
    ];

    /**
     * Daily Pool Bonus Initiate
     */
    public function dailyPoolRelease()
    {
        $rtxPrice = rtxPrice();

        // Fetch yesterday's pool amount
        $poolAmountData = DB::table('withdraws')
            ->selectRaw("IFNULL(SUM(daily_pool_amount),0) as daily_pool")
            ->whereRaw("DATE_FORMAT(created_on, '%Y-%m-%d') = ?", [date('Y-m-d', strtotime('-1 day'))])
            ->first();

        $poolAmount = $poolAmountData->daily_pool ?? 0;
        if ($poolAmount <= 0) return;

        // Loop through each pool (30/90/180 days)
        foreach ($this->poolConfig as $pool) {
            $this->distributePool(
                $pool['lock_period'],
                $poolAmount * $pool['percentage'],
                $pool['topCount'],
                $rtxPrice
            );
        }
    }

    /**
     * Process Daily Pool Bonus
     */
    public function processPoolBonus($userId, $withdrawAmount)
    {
        DB::beginTransaction();
        try {
            $rtxPrice = rtxPrice();

            // 1. Calculate 5% pool fund
            $poolFund = $withdrawAmount * 0.05;

            // 2. Net withdrawal to user
            $netWithdrawal = $withdrawAmount - $poolFund;
            // DB::table('users')->where('id', $userId)->increment('wallet', $netWithdrawal);

            // 3. Split pool among 30/90/180 day pools
            $poolConfig = [
                ['lock_period' => 1, 'topCount' => 31, 'percentage' => 0.025],
                ['lock_period' => 2, 'topCount' => 21, 'percentage' => 0.015],
                ['lock_period' => 3, 'topCount' => 11, 'percentage' => 0.01],
            ];

            foreach ($poolConfig as $pool) {
                $this->distributePool(
                    $pool['lock_period'],
                    $poolFund * $pool['percentage'],
                    $pool['topCount'],
                    $rtxPrice
                );
            }

            // DB::commit();

            dd([
                'user_id'        => $userId,
                'withdraw'       => $withdrawAmount,
                'pool_fund'      => $poolFund,
                'net_withdrawal' => $netWithdrawal,
            ]);

            return [
                'user_id'        => $userId,
                'withdraw'       => $withdrawAmount,
                'pool_fund'      => $poolFund,
                'net_withdrawal' => $netWithdrawal,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Distribute Pool Share proportionally among Top N users
     */
    protected function distributePool($lockPeriod, $poolShare, $topCount, $rtxPrice)
    {
        // Fetch qualified users
        $qualifiedUserIds = DB::table('user_plans')
                                ->whereRaw('(amount * coin_price) >= 100')
                                ->where('lock_period', $lockPeriod)
                                ->pluck('user_id')
                                ->unique()
                                ->toArray();
        
        $qualifiedUsers = DB::table('user_plans')
                                ->join('users', 'users.id', '=', 'user_plans.user_id')
                                ->select(
                                    'user_plans.user_id',
                                    DB::raw('SUM(user_plans.amount * user_plans.coin_price) as total_investment')
                                )
                                ->whereIn('user_plans.user_id', $qualifiedUserIds)
                                ->where('user_plans.lock_period', $lockPeriod)
                                ->groupBy('user_plans.user_id')
                                ->get();
        
        // echo "<pre>qualifiedUsers1:";
        // print_r($qualifiedUsers);
        // echo "</pre>";

        if ($qualifiedUsers->isEmpty()) return;

        // 2. Apply Top N selection
        if ($qualifiedUsers->count() > $topCount) {
            $qualifiedUsers = $qualifiedUsers->random($topCount);
        }
        
        // 3. Total stake of selected users
        $totalStake = $qualifiedUsers->sum('total_investment');

        // dd("totalStake:",$totalStake);

        if ($totalStake <= 0) return;

        // 4. Distribute proportionally
        foreach ($qualifiedUsers as $user) {
            $bonus = $poolShare * ($user->total_investment / $totalStake);

            // Insert earning log
            // echo "<pre>Bonus:";
            // print_r([
            //         'user_id'     => $user->user_id,
            //         'amount'      => $bonus,
            //         'tag'         => 'DAILY-POOL-BONUS',
            //         'refrence'    => $rtxPrice,
            //         'refrence_id' => '-',
            //         'isCount'     => 1,
            //         'created_on'  => now(),
            //     ]);
            // echo "</pre>";

            // earningLogsModel::insert([
            //     'user_id'     => $user->user_id,
            //     'amount'      => $bonus,
            //     'tag'         => 'DAILY-POOL-BONUS',
            //     'refrence'    => $rtxPrice,
            //     'refrence_id' => '-',
            //     'isCount'     => 1,
            //     'created_on'  => now(),
            // ]);

            // Increment user royalty
            // DB::table('users')
            //     ->where('id', $user->user_id)
            //     ->increment('royalty', $bonus);
        }
    }
}
