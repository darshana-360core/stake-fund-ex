<?php

namespace App\Services;

use App\Models\usersModel;

use function App\Helpers\getUserStakedAmount;

class UplineBonusService
{
    protected $planMatrix = [
        1 => ['stake' => 2100, 'directs' => 6,  'bonus' => 0.10], // 10% from 1st upline
        2 => ['stake' => 2800, 'directs' => 7,  'bonus' => 0.08], // 8% from 2nd upline
        3 => ['stake' => 3600, 'directs' => 8,  'bonus' => 0.06], // 6% from 3rd upline
        4 => ['stake' => 4500, 'directs' => 9,  'bonus' => 0.04], // 4% from 4th upline
        5 => ['stake' => 5500, 'directs' => 10, 'bonus' => 0.02], // 2% from 5th upline
        6 => ['stake' => 6600, 'directs' => 11, 'bonus' => 0.01], // 1% from 6th upline
    ];

    /**
     * Main method: check eligibility of a user.
     */
    public function check(usersModel $user): array
    {
        // Step 1: collect self stake
        $selfStake = getUserStakedAmount($user->id);
        // echo "Selfstake: ".$selfStake;

        // Step 2: collect directs (filter ≥ 1000 stake)
        $directs = $this->getQualifiedDirects($user);
        // echo "<pre>"; print_r($directs); echo "</pre>";

        // Step 3: collect uplines + incomes
        $uplines = $this->getTargetUpline($user);
        // echo "<pre>"; print_r($uplines); echo "</pre>";
        
        // Step 4: check eligibility
        $eligibility = $this->evaluateEligibility($selfStake, $directs->count());
        // echo "<pre>"; print_r($eligibility); echo "</pre>";

        // Step 5: prepare response
        return [
            'eligible'   => $eligibility['eligible'],
            'level'      => $eligibility['level'],
            'target_bonus' => $eligibility['bonus'],
            'self_stake' => $selfStake,
            'directs'    => $directs->count(),
            'direct_list'=> $directs->values(),
            'uplines'    => $uplines,
        ];
    }

    /**
     * Step 4: Evaluate eligibility using plan matrix.
     */
    protected function evaluateEligibility(float $selfStake, int $qualifiedDirects): array
    {
        foreach ($this->planMatrix as $level => $rule) {
            if ($selfStake >= $rule['stake'] && $qualifiedDirects >= $rule['directs']) {
                return [
                    'eligible' => true,
                    'level'    => $level,
                    'bonus'    => $rule['bonus'],
                ];
            }
        }

        return ['eligible' => false, 'level' => null, 'bonus' => 0];
    }

    /**
     * Step 2: Get qualified directs.
     */
    public function getQualifiedDirects(usersModel $user)
    {
        $directs = usersModel::where('sponser_id', $user->id)->get();

        return $directs->filter(function ($direct) {
            return getUserStakedAmount($direct->id) >= 1000;
        })->map(function ($direct) {
            return [
                'id'            => $direct->id,
                'refferal_code' => $direct->refferal_code,
                'stake'         => getUserStakedAmount($direct->id),
            ];
        });
    }

    /**
     * Find target upline based on eligibility
     */
    public function getTargetUpline(usersModel $user)
    {
        // $eligibility = $this->getEligibility($user);

        // if (!$eligibility['eligible']) {
        //     return null;
        // }

        // Get uplines list
        $uplines = [];
        $current = $user;
        $level   = 1;

        while ($current && $current->sponser_id) {
            $sponsor = usersModel::find($current->sponser_id);
            if ($sponsor) {
                $sponsor['income'] = $sponsor->roi_income + $sponsor->direct_income + $sponsor->level_income;
                $uplines[$level] = $sponsor;
                $current = $sponsor;
                $level++;
            } else {
                break;
            }
        }

        return $uplines ?? null;
    }

}
