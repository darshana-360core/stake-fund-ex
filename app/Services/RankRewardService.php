<?php

namespace App\Services;

use App\Models\usersModel;
use App\Models\userPlansModel;
use App\Models\withdrawModel;
use App\Models\earningLogsModel;
use Carbon\Carbon;
use DB;
use function App\Helpers\getUserStakedAmount;
use function App\Helpers\getUserStakeAmount;
use function App\Helpers\rtxPrice;

class RankRewardService
{
    private $currentRank;
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

        $this->currentRank = intval($user->rank_id ?? 0);
        $eligibleRanks = [];

        $rtxPrice = rtxPrice();

        $achieved = [];

        foreach ($this->rankMatrix as $rank) {

            $rankId = $rank['rank_id'];
            // Skip if user already has this rank or higher
            $currentRankId = intval($user->rank_id ?? 0);
            if ($currentRankId >= ($rank['rank_id'] ?? 0)) {
                // continue;
            }

            // 1. Check self stake within term (uses correct key 'self_stake')
            // getUserStakeAmount returns token amount (not fiat), so multiply by price
            $stake = getUserStakeAmount($userId, $rank['terms']);
            echo "Stake amount rtx:".$stake*$rtxPrice.PHP_EOL;
            echo "Stake amount:".$rank['self_stake'].PHP_EOL;
            if (($stake * $rtxPrice) < ($rank['self_stake'] ?? 0)) {
                continue;
            }

            // 2. Check active directs (>= threshold)
            $activeDirects = $this->getActiveDirects($userId, $rank['terms']);
            echo "activeDirects:".$activeDirects.PHP_EOL;
            if ($activeDirects < $rank['directs']) {
                continue;
            }

            // 3. Check cumulative team business (strong & weak) for ranks 1..rank_id
            $teamBusiness = $this->getTeamBusiness($userId, $rank['terms']); // returns ['strong'=>..., 'weak'=>...]
            $cumulativeReq = $this->getCumulativeTeamRequirements($rank['rank_id']);
            echo "Team business".PHP_EOL;
            print_r($teamBusiness);
            echo "Required team business".PHP_EOL;
            print_r($cumulativeReq);
            echo " ".PHP_EOL;
            if ($teamBusiness['strong'] < $cumulativeReq['strong'] || $teamBusiness['weak'] < $cumulativeReq['weak']) {
                continue;
            }

            // Passed all checks → assign rank (note: assignRank handles one-time award logic)
            // $this->assignRank($user, $rank);

            $eligibleRanks[] = $rankId;

            $achieved[] = $rank['rank'];
            // $user->refresh();
        }

        // $top = $achieved ? end($achieved) : null;

        // Determine all eligible ranks
        if (!empty($eligibleRanks)) {
            $maxRank = max($eligibleRanks);
            $currentRank = intval($this->currentRank ?? 0);

            // CASE 1: Upgrade (eligible rank higher than current)
            if ($maxRank > $currentRank) {
                // Loop through only higher ranks
                for ($r = $currentRank + 1; $r <= $maxRank; $r++) {
                    if (in_array($r, $eligibleRanks)) {
                        $this->assignRank($user, $this->rankMatrix[$r]);
                    }
                }
            }
            // CASE 2: Downgrade (eligible rank lower than current)
            elseif ($maxRank < $currentRank) {
                usersModel::where('id', $user->id)->update([
                    'rank'      => $this->rankMatrix[$maxRank]['rank'],
                    'rank_id'   => $maxRank,
                    'rank_date' => now()->toDateString(),
                ]);
            }

        }

        $top = !empty($achieved) ? end($achieved) : null;
        
        return [
            'achieved' => $achieved,
            'top_rank' => $top,
        ];
    }

    /**
     * Count active directs within term
     */
    public function getActiveDirects($userId, $rankTerms)
    {
        $directs = usersModel::select('users.id')
            ->join('user_plans', 'user_plans.user_id', '=', 'users.id')
            ->where('users.sponser_id', $userId)
            ->groupBy('users.id')
            ->get();

        $count = 0;
        $rtxPrice = rtxPrice();

        foreach ($directs as $direct) {
            $stakeAmount = getUserStakedAmount($direct->id);
            echo "Active dir stake amount:".$stakeAmount.PHP_EOL;
            if ($stakeAmount * $rtxPrice >= 100) {  // threshold here
                $count++;
            }
        }

        return $count;
    }

    /**
     * Calculate team business within term (strong & weak)
     * returns amounts in fiat (multiplied by rtxPrice)
     */
    public function getTeamBusiness($userId, $rankTerms)
    {
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
            // sort descending by leg business
            usort($otherLegs, fn($a, $b) => $b['my_business'] <=> $a['my_business']);

            foreach ($otherLegs as $index => $leg) {
                $userPlansAmount = userPlansModel::where('user_id', $leg['id'])
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
     * Compute cumulative team requirements (sum of strong/weak for ranks 1..$rankId)
     * returns ['strong' => <amount>, 'weak' => <amount>]
     */
    private function getCumulativeTeamRequirements($rankId)
    {
        $strong = 0;
        $weak = 0;

        for ($i = 1; $i <= $rankId; $i++) {
            if (isset($this->rankMatrix[$i])) {
                $strong += ($this->rankMatrix[$i]['strong'] ?? 0);
                $weak   += ($this->rankMatrix[$i]['weak'] ?? 0);
            }
        }

        return ['strong' => $strong, 'weak' => $weak];
    }

    /**
     * Assign rank and log rewards
     *
     * NOTE: One-time onetime rewards are awarded ONLY if new rank_id > existing rank_id.
     */
    public function assignRank($user, $rank)
    {
        $rankId = $rank['rank_id'] ?? null;
        if (!$rankId) return;

        $userFresh = usersModel::find($user->id);
        $prevRankId = intval($userFresh->rank_id ?? 0);

        // Always update user's latest achieved rank (even if already rewarded)
        usersModel::where('id', $user->id)->update([
            'rank'      => $rank['rank'],
            'rank_id'   => $rankId,
            'rank_date' => now()->toDateString(),
        ]);

        // Check if user already rewarded for this rank
        $alreadyRewarded = earningLogsModel::where([
            'user_id' => $user->id,
            'tag'     => 'RANKREWARD-BONUS',
            'refrence_id' => $rankId,
        ])->exists();

        // Award only if:
        // - this rank is higher than previous AND not rewarded before
        if ($rank['onetime'] > 0 && $rankId > $prevRankId && !$alreadyRewarded) {
            earningLogsModel::create([
                'user_id'     => $user->id,
                'amount'      => $rank['onetime'],
                'tag'         => 'RANKREWARD-BONUS',
                'reference'   => rtxPrice(),
                'refrence_id' => $rankId,
                'created_on'  => now(),
            ]);

            DB::statement("UPDATE users SET reward_bonus = IFNULL(reward_bonus,0) + ? WHERE id = ?", [$rank['onetime'], $user->id]);
        }
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
     * (Optional) Calculate cumulative days cutoff — kept as before if needed
     */
    public function getRankDeadline($userId, $rankId)
    {
        $firstStake = $this->getFirstStakeDate($userId);
        if (!$firstStake) return null;

        $totalTerms = 0;
        for ($i = 1; $i <= $rankId; $i++) {
            if (isset($this->rankMatrix[$i])) {
                $totalTerms += $this->rankMatrix[$i]['terms'];
            }
        }

        return $firstStake->copy()->addDays($totalTerms);
    }
}
