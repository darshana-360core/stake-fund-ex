<?php
namespace App\Services;

use App\Models\usersModel;
use App\Models\earningLogsModel;
use Carbon\Carbon;
use DB;

class ReferralBonusService
{
    protected $referralBonusSlabs = [
        ['min' => 500,   'max' => 999,    'nft' => 500,   'bonus' => 3, 'direct' => 2],
        ['min' => 1000,  'max' => 4999,   'nft' => 1000,  'bonus' => 5, 'direct' => 2],
        ['min' => 5000,  'max' => 19999,  'nft' => 5000,  'bonus' => 6, 'direct' => 2],
        ['min' => 20000, 'max' => INF,    'nft' => 20000, 'bonus' => 10, 'direct' => 2],
    ];

    public function calculateReferralBonus($sponserId, $startDate, $endDate, $withDbUpdate = false)
    {
        $start = Carbon::parse($startDate);
        $end   = Carbon::parse($endDate);

        $userPlans = DB::table('user_plans')
                            ->join('users', 'user_plans.user_id', '=', 'users.id')
                            ->where('users.sponser_id', $sponserId)
                            ->where('user_plans.roi', '>', 0)
                            ->where('user_plans.isSynced', '!=', 2)
                            ->whereBetween('user_plans.created_on', [$start, $end])
                            ->select('user_plans.user_id', 'user_plans.amount', 'user_plans.created_on')
                            ->get();
        
        $usersIds = $userPlans->pluck('user_id')->unique()->toArray();

        $previousPlans =  DB::table('user_plans')
                                ->whereIn('user_id', $usersIds)
                                ->where('created_on', '<', $start)
                                ->pluck('user_id')
                                ->toArray(); 
                                
        $usersData = [];

        foreach ($userPlans as $plan) {
            if (in_array($plan->user_id, $previousPlans)) {
                continue;
            }
            
            if (!isset($usersData[$plan->user_id])) {
                $usersData[$plan->user_id] = ['total_amount' => 0];
            }

            $usersData[$plan->user_id]['total_amount'] += $plan->amount;
        }

        // echo "<pre>"; print_r($usersData); echo "</pre>";
        
        $qualifiedDirectsCount = 0;
        $totalDirectBusiness = 0;

        foreach ($usersData as $userId => $data) {
            if ($data['total_amount'] >= 100) {
                $qualifiedDirectsCount++;
                $totalDirectBusiness += $data['total_amount'];
            }
        }

        // echo "qualifiedDirectsCount:".$qualifiedDirectsCount."<br>";
        // echo "totalDirectBusiness:".$totalDirectBusiness."<br>";

        $bonus = 0;
        $nftValue = 0;
        $directRequirement = 0;

        foreach ($this->referralBonusSlabs as $slab) {

            //echo $qualifiedDirectsCount." ".$totalDirectBusiness." ".$totalDirectBusiness."<br>";
            if ($qualifiedDirectsCount >= $slab['direct'] && $totalDirectBusiness >= $slab['min'] && $totalDirectBusiness <= $slab['max']) {
                $nftValue = $slab['nft'];
                $bonus = $slab['bonus'];
                $directRequirement = $slab['direct'];
                break;
            }
        }

        $bonusAmount = ($bonus > 0) ? ($nftValue * $bonus / 100) : 0;

        $result = [
            'sponser_id'              => $sponserId,
            'qualified_directs_count' => $qualifiedDirectsCount,
            'direct_requirement'      => $directRequirement,
            'total_direct_business'   => $totalDirectBusiness,
            'nft_value'               => $nftValue,
            'bonus_percent'           => $bonus,
            'bonus_amount'            => $bonusAmount,
        ];

        if ($withDbUpdate && $bonusAmount > 0) {
            DB::transaction(function () use ($sponserId, $bonusAmount, $result) {
                // usersModel::where('id', $sponserId)->update([
                //     'referral_bonus' => DB::raw("referral_bonus + {$bonusAmount}")
                // ]);

                earningLogsModel::create([
                    'user_id'     => $sponserId,
                    'amount'      => $bonusAmount,
                    'flush_amount'=> 0,
                    'tag'         => 'REFERRAL-BONUS',
                    'refrence'    => json_encode($result),
                    'refrence_id' => 0,
                    'status'      => 1,
                ]);
            });
        }

        return $result;
    }
}
