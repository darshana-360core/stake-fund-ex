<?php

namespace App\Services;

use App\Models\usersModel;
use App\Models\userPlansModel;
use App\Models\withdrawModel;
use App\Models\UserRank;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use function App\Helpers\rtxPrice;

class ClubBonusService
{
    private $clubMatrix;

    private $doraPrice;

    public function __construct()
    {
        $this->clubMatrix = [
            'club1' => ['percent' => 2.5, 'min_rank' => '3', 'max_rank' => '5', 'weak_leg' => 30000],
            'club2' => ['percent' => 1.5, 'min_rank' => '6', 'max_rank' => '8', 'weak_leg' => 100000],
            'club3' => ['percent' => 1.0, 'min_rank' => '9', 'max_rank' => '11', 'weak_leg' => 200000],
        ];
        $this->doraPrice = rtxPrice();
    }

    /**
     * Calculate Club Bonus for a given month
     */
    public function calculateClubBonus($month = null, array $onlyUserIds = null)
    {
        // Step 1: Define period (1st - last day of month)
        $startDate = $month ? Carbon::parse($month)->startOfMonth() : Carbon::now()->startOfMonth();
        $endDate   = $month ? Carbon::parse($month)->endOfMonth()   : Carbon::now()->endOfMonth();

        // Step 2: Calculate company turnover (user_plans in this month)
        $turnoverQ = userPlansModel::whereBetween('created_on', [$startDate, $endDate]);
        if ($onlyUserIds) $turnoverQ->whereIn('user_id', $onlyUserIds);
        $companyTurnover = (float)$turnoverQ->sum('amount');
        // dd($companyTurnover);

        $results = [];

        // Step 4: Loop clubs
        foreach ($this->clubMatrix as $clubName => $rule) {
            // Pool amount
            $clubPool = ($companyTurnover * $rule['percent']) / 100;

            // Step 5: Get eligible users (rank within range during month)
            // $eligibleUsers = usersModel::whereBetween('club_achieved_date', [$startDate, $endDate])
            //                                 ->whereBetween('club', [$rule['min_rank'], $rule['max_rank']])
            //                                 ->pluck('id');

            $eligibleQ = usersModel::whereBetween('rank_id', [$rule['min_rank'], $rule['max_rank']])
                                    ->whereBetween('rank_date', [$startDate->toDateString(), $endDate->toDateString()]);
            if ($onlyUserIds) $eligibleQ->whereIn('id', $onlyUserIds);

            $eligibleUsers = $eligibleQ->pluck('id');

            $qualifiedUsers = [];

            foreach ($eligibleUsers as $userId) {
                // Step 6: Calculate weak leg business for this user in month
                $weakLegBusiness = $this->calculateWeakLegBusiness($userId, $startDate, $endDate);

                if ($weakLegBusiness >= $rule['weak_leg']) {
                    $qualifiedUsers[] = $userId;
                }
            }

            $qualifiedCount = count($qualifiedUsers);

            if ($qualifiedCount > 0) {
                $bonusPerUser = $clubPool / $qualifiedCount;

                foreach ($qualifiedUsers as $userId) {
                    $results[] = [
                        'user_id'      => $userId,
                        'club'         => $clubName,
                        'bonus'        => round($bonusPerUser, 6),
                        'month'        => $startDate->format('Y-m'),
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ];
                }
            }
        }

        // Step 7: Save results
        // if (!empty($results)) {

        //     dd($results);
        //     // DB::table('club_bonus')->insert($results);
        // }

        return $results;
    }

    /**
     * Calculate weak leg business for a user
     */
    private function calculateWeakLegBusiness($userId, $startDate, $endDate, array $onlyUserIds = null)
    {
        // $otherLegs = usersModel::selectRaw("IFNULL((my_business + strong_business),0) as my_business, users.id")
        //     ->leftJoin('user_plans', 'user_plans.user_id', '=', 'users.id')
        //     ->where('users.sponser_id', $userId)
        //     ->groupBy('users.id')
        //     ->get()
        //     ->toArray();
        
        $legsQ = usersModel::selectRaw("IFNULL((my_business + strong_business),0) as my_business, users.id")
        ->leftJoin('user_plans','user_plans.user_id','=','users.id')
        ->where('users.sponser_id', $userId)
        ->groupBy('users.id');

        if ($onlyUserIds) $legsQ->whereIn('users.id', $onlyUserIds);

        $otherLegs = $legsQ->get()->toArray();

        $strongBusiness = 0;
        $weakBusiness = 0;

        if (!empty($otherLegs)) {
            // Sort by business descending
            usort($otherLegs, function($a, $b) {
                return $b['my_business'] <=> $a['my_business'];
            });

            foreach ($otherLegs as $index => &$leg) {
                // Sum of ROI-positive stakes in the period
                $userPlansAmount = userPlansModel::where('user_id', $leg['id'])
                    ->whereBetween('created_on', [$startDate, $endDate])
                    ->whereRaw("roi > 0")
                    ->sum('amount');

                // Sum of unstaked amounts
                $claimedRewards = withdrawModel::where('user_id', $leg['id'])
                    ->where('withdraw_type', 'UNSTAKE')
                    ->sum('amount');

                // Calculate leg business
                $legBusiness = ($leg['my_business'] + $userPlansAmount - $claimedRewards) * $this->doraPrice;

                if ($legBusiness < 0) {
                    $legBusiness = 0;
                }

                // First leg is strong leg, others add to weak
                if ($index === 0) {
                    $strongBusiness = $legBusiness;
                } else {
                    $weakBusiness += $legBusiness;
                }
            }
        }

        return $weakBusiness;
    }
}
