<?php

namespace App\Services;

use App\Models\usersModel;
use App\Models\userPlansModel;
use App\Models\withdrawModel;
use App\Models\earningLogsModel;
use App\Models\rankingModel;
use App\Models\rewardBonusModel;
use Carbon\Carbon;
use DB;
use function App\Helpers\getUserStakedAmount;
use function App\Helpers\getUserStakeAmount;

use function App\Helpers\rtxPrice;

class RankRewardService
{
    private $rankMatrix = [
        1  => ['rank_id'=>1, 'self_stake'=>500, 'rank' => 'D1',  'team_business' => 60000,      'strong' => 30000,      'weak' => 30000,       'directs' => 5,  'onetime' => 1200,     'daily' => 15,     'terms' => 30],
        2  => ['rank_id'=>2, 'self_stake'=>1100, 'rank' => 'D2',  'team_business' => 200000,     'strong' => 100000,     'weak' => 100000,      'directs' => 6,  'onetime' => 4000,     'daily' => 50,     'terms' => 60],
        3  => ['rank_id'=>3, 'self_stake'=>1800, 'rank' => 'D3',  'team_business' => 400000,     'strong' => 200000,     'weak' => 200000,      'directs' => 7,  'onetime' => 8000,     'daily' => 100,    'terms' => 60],
        4  => ['rank_id'=>4, 'self_stake'=>2600, 'rank' => 'D4',  'team_business' => 700000,     'strong' => 350000,     'weak' => 350000,      'directs' => 8,  'onetime' => 14000,    'daily' => 175,    'terms' => 90],
        5  => ['rank_id'=>5, 'self_stake'=>3500, 'rank' => 'D5',  'team_business' => 1400000,    'strong' => 700000,     'weak' => 700000,      'directs' => 9,  'onetime' => 28000,    'daily' => 350,    'terms' => 90],
        6  => ['rank_id'=>6, 'self_stake'=>4500, 'rank' => 'D6',  'team_business' => 3000000,    'strong' => 1500000,    'weak' => 1500000,     'directs' => 10, 'onetime' => 60000,    'daily' => 750,    'terms' => 120],
        7  => ['rank_id'=>7, 'self_stake'=>5600, 'rank' => 'D7',  'team_business' => 6000000,    'strong' => 3000000,    'weak' => 3000000,     'directs' => 11, 'onetime' => 120000,   'daily' => 1500,   'terms' => 120],
        8  => ['rank_id'=>8, 'self_stake'=>6800, 'rank' => 'D8',  'team_business' => 12000000,   'strong' => 6000000,    'weak' => 6000000,     'directs' => 12, 'onetime' => 240000,   'daily' => 3000,   'terms' => 150],
        9  => ['rank_id'=>9, 'self_stake'=>8100, 'rank' => 'D9',  'team_business' => 25000000,   'strong' => 12500000,   'weak' => 12500000,    'directs' => 13, 'onetime' => 500000,   'daily' => 6250,   'terms' => 150],
        10 => ['rank_id'=>10, 'self_stake'=>9500, 'rank' => 'D10', 'team_business' => 50000000,   'strong' => 25000000,   'weak' => 25000000,    'directs' => 14, 'onetime' => 1000000,  'daily' => 12500,  'terms' => 180],
        11 => ['rank_id'=>11, 'self_stake'=>11000, 'rank' => 'D11', 'team_business' => 100000000,  'strong' => 50000000,   'weak' => 50000000,    'directs' => 15, 'onetime' => 2000000,  'daily' => 25000,  'terms' => 180],
    ];

    /**
     * Check user rank eligibility and assign rewards
     */
    public function checkEligibility($userId)
    {
        $user = usersModel::find($userId);
        if (!$user || $user->status != 1) {
            return null;
        }

        $rtxPrice = rtxPrice();
        // $firstStakeDate = $this->getFirstStakeDate($userId);

        // if (!$firstStakeDate) {
        //     return null;
        // }

        // $dailyRewards = [];
        $achieved = [];

        foreach ($this->rankMatrix as $rank) {

            // Skip if rank already achieved
            if ($user->rank_id >= ($rank['rank_id'] ?? 0)) {
                continue;
            }

            // Check if within terms
            // $daysSinceFirstStake = Carbon::now()->diffInDays($firstStakeDate) + 1;
            // if ($daysSinceFirstStake > $rank['terms']) {
            //     continue; // rank not eligible
            // }

            // 1. Check self stake within term
            $stake = getUserStakeAmount($userId, $rank['terms']);
            echo "Selfstake:".$userId." ".$stake;
            if ($stake * $rtxPrice < ($rank['stake'] ?? 0)) {
                continue;
            }

            // 2. Check active directs
            $activeDirects = $this->getActiveDirects($userId, $rank['terms']);
            echo "Active directs:".$userId." ".$activeDirects;
            if ($activeDirects < $rank['directs']) {
                continue;
            }

            // 3. Check team business
            $teamBusiness = $this->getTeamBusiness($userId, $rank['terms']);
            echo $userId;
            print_r($teamBusiness);
            if ($teamBusiness['strong'] < $rank['strong'] || $teamBusiness['weak'] < $rank['weak']) {
                continue;
            }

            // Rank achieved within terms
            $this->assignRank($user, $rank);

            // Add daily reward
            // $dailyRewards[] = $rank['daily'];

            $achieved[] = $rank['rank'];
            $user->refresh();
        }

        $top = $achieved ? end($achieved) : null;
        
        // return [
        //     'rank' => end($this->rankMatrix) ? $rank['rank'] : null,
        //     'daily_rewards' => $dailyRewards, // array of daily rewards for lifetime
        // ];

        return [
            'achieved'      => $achieved,     // e.g. ["D1","D2","D3"]
            'top_rank'      => $top,          // e.g. "D3"
            // 'daily_rewards' => $dailyRewards, // e.g. [15,50,100]
        ];
    }

    /**
     * Get first stake date for user
     */
    public function getFirstStakeDate($userId)
    {   
        $firstPlan = userPlansModel::where('user_id', $userId)
            ->orderBy('created_on', 'asc')
            ->first();        
        return $firstPlan ? Carbon::parse($firstPlan->created_on) : null;
    }

    /**
     * Count active directs within term
     */

    public function getActiveDirects($userId, $rankId)
    {
        // $deadline = $this->getRankDeadline($userId, $rankId);
        // echo "getActiveDirects:".$deadline;

        // if (!$deadline) return 0;

        $directs = usersModel::select('users.id')
            ->join('user_plans', 'user_plans.user_id', '=', 'users.id')
            ->where('users.sponser_id', $userId)
            // ->where('user_plans.created_on', '<=', $deadline) // ✅ cumulative cutoff
            ->groupBy('users.id')
            ->get();

        $criteriaMatch = 0;
        $rtxPrice = rtxPrice();

        foreach ($directs as $direct) {
            $stakeAmount = getUserStakedAmount($direct->id);
            if ($stakeAmount * $rtxPrice >= 100) {  // threshold here
                $criteriaMatch++;
            }
        }
        
        return $criteriaMatch;
    }

    /**
     * Calculate team business within term
     */
    public function getTeamBusiness($userId, $rankId)
    {
        // $deadline = $this->getRankDeadline($userId, $rankId);
        // echo "getTeamBusiness:".$deadline;

        // if (!$deadline) return ['strong' => 0, 'weak' => 0];

        $rtxPrice = rtxPrice();

        $otherLegs = usersModel::selectRaw("IFNULL((my_business),0) as my_business, users.id")
            ->leftJoin('user_plans', 'user_plans.user_id', '=', 'users.id')
            ->where('users.sponser_id', $userId)
            ->groupBy('users.id')
            ->get()
            ->toArray();

        $strongBusiness = 0;
        $weakBusiness = 0;

        if (!empty($otherLegs)) {
            usort($otherLegs, fn($a, $b) => $b['my_business'] <=> $a['my_business']);

            foreach ($otherLegs as $index => &$leg) {
                $userPlansAmount = userPlansModel::where('user_id', $leg['id'])
                    // ->where('created_on', '<=', $deadline) // ✅ cumulative cutoff
                    ->whereRaw("roi > 0")
                    ->sum('amount');

                $claimedRewards = withdrawModel::where('user_id', $leg['id'])
                    ->where('withdraw_type', 'UNSTAKE')
                    ->sum('amount');

                $legBusiness = ($leg['my_business'] + $userPlansAmount - $claimedRewards) * $rtxPrice;
                if ($legBusiness < 0) $legBusiness = 0;

                if ($index === 0) {
                    $strongBusiness = $legBusiness;
                } else {
                    $weakBusiness += $legBusiness;
                }
            }
        }

        return ['strong' => $strongBusiness, 'weak' => $weakBusiness];
    }

    /**
     * Assign rank and log rewards
     */
    public function assignRank($user, $rank)
    {
        $rankId = $rank['rank_id'] ?? null;
        // $deadline = $this->getRankDeadline($user->id, $rankId);
        // echo "assignRank:".$deadline;

        // if (!$deadline) {
        //     return ['status' => 'failed', 'message' => 'No stake date found'];
        // }

        // Check if user missed the deadline
        // if (Carbon::now()->gt($deadline)) {
        //     return ['status' => 'skipped', 'message' => "Missed deadline for {$rank['rank']}"];
        // }

        // echo "Update user Rank to ". $rank['rank']."(".$rankId.")<br>";

        usersModel::where('id', $user->id)->update([
            'rank'      => $rank['rank'],
            'rank_id'   => $rankId,
            'rank_date' => Carbon::now()->toDateString(),
        ]);
        
        // 🔹 One-time reward
        $exists = earningLogsModel::where('user_id', $user->id)
            ->where('refrence_id', $rankId)
            ->where('tag', 'RANKREWARD-BONUS')
            ->exists();

        if (!$exists && $rank['onetime'] > 0) {
            // echo "insert into earning_logs user_id =>".$user->id.", amount =>".$rank['onetime'].", tag =>RANKREWARD-BONUS, refrence_id =>".$rankId.", created_on =>". Carbon::now()."<br>";

            earningLogsModel::create([
                'user_id'     => $user->id,
                'amount'      => $rank['onetime'],
                'tag'         => 'RANKREWARD-BONUS',
                'reference'   => rtxPrice(),
                'refrence_id' => $rankId,
                'created_on'  => Carbon::now(),
            ]);

            // echo "UPDATE users SET reward_income = IFNULL(reward_income,0) + {$rank['onetime']} WHERE id = ".$user->id."<br>";

            DB::statement("UPDATE users 
                           SET reward_bonus = IFNULL(reward_bonus,0) + {$rank['onetime']} 
                           WHERE id = ?", [$user->id]);
        }

        // Daily reward stopped
        // $dailyExists = earningLogsModel::where('user_id', $user->id)
        //     ->where('tag', 'DAILY-RANK-REWARD')
        //     ->where('refrence_id', $rankId)
        //     ->exists();

        // if (!$dailyExists && $rank['daily'] > 0) {
        //     // echo "insert into earning_logs user_id =>".$user->id.", amount =>".$rank['daily'].", tag =>DAILY-RANK-REWARD, refrence_id =>".$rankId.", created_on =>". Carbon::now()."<br>";

        //     earningLogsModel::create([
        //         'user_id'     => $user->id,
        //         'amount'      => $rank['daily'],
        //         'tag'         => 'DAILY-RANK-REWARD',
        //         'refrence_id' => $rankId,
        //         'created_on'  => Carbon::now(),
        //     ]);
        // }

        return ['status' => 'success', 'message' => 'Ranked successfully'];
    }

    public function getRankDeadline($userId, $rankId)
    {
        $firstStake = $this->getFirstStakeDate($userId);
        // echo "Firststake:".$firstStake;

        if (!$firstStake) {
            return null;
        }
        // echo "rankId:".$rankId;
        $totalTerms = 0;
        for ($i = 1; $i <= $rankId; $i++) {
            if (isset($this->rankMatrix[$i])) {
                $totalTerms += $this->rankMatrix[$i]['terms'];
            }
        }
        
        return $firstStake->copy()->addDays($totalTerms);
    }
}
