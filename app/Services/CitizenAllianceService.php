<?php
namespace App\Services;

use App\Models\usersModel;
use App\Models\myTeamModel;
use App\Models\earningLogsModel;
use App\Models\userPlansModel;
use App\Models\withdrawModel;
use Illuminate\Support\Facades\DB;
use function App\Helpers\getUserStakeAmount;
use function App\Helpers\rtxPrice;
use Illuminate\Support\Str;
use function App\Helpers\getTeamRoi;

class CitizenAllianceService
{
    /**
     * Citizen Alliance Plan Matrix
     */
    private $matrix = [
        1 => ['level' => '1', 'personal_holding' => 100, 'team_requirement' => ['type' => 'volume', 'value' => 5000], 'valid_referrals' => 2, 'profit_ratio' => [5, 10]],
        2 => ['level' => '2', 'personal_holding' => 500, 'team_requirement' => ['type' => 'volume', 'value' => 20000], 'valid_referrals' => 3, 'profit_ratio' => [10, 20]],
        3 => ['level' => '3', 'personal_holding' => 1000, 'team_requirement' => ['type' => 'volume', 'value' => 50000], 'valid_referrals' => 4, 'profit_ratio' => [15, 30]],
        4 => ['level' => '4', 'personal_holding' => 2000, 'team_requirement' => ['type' => 'volume', 'value' => 150000], 'valid_referrals' => 5, 'profit_ratio' => [20, 40]],
        5 => ['level' => '5', 'personal_holding' => 3000, 'team_requirement' => ['type' => 'volume', 'value' => 400000], 'valid_referrals' => 6, 'profit_ratio' => [25, 50]],
        6 => ['level' => '6', 'personal_holding' => 5000, 'team_requirement' => ['type' => 'volume', 'value' => 1000000], 'valid_referrals' => 7, 'profit_ratio' => [30, 60]],
        7 => ['level' => '7', 'personal_holding' => 10000, 'team_requirement' => ['type' => 'volume', 'value' => 2500000], 'valid_referrals' => 8, 'profit_ratio' => [35, 70]],
        8 => ['level' => '8', 'personal_holding' => 12000, 'team_requirement' => ['type' => 'volume', 'value' => 6000000], 'valid_referrals' => 9, 'profit_ratio' => [40, 80]],
        9 => ['level' => '9', 'personal_holding' => 15000, 'team_requirement' => ['type' => 'volume', 'value' => 15000000], 'valid_referrals' => 10, 'profit_ratio' => [45, 90]],
        10 => ['level' => '10', 'personal_holding' => 0, 'team_requirement' => ['type' => 'hold_level', 'level' => 9, 'count' => 2, 'value' => 15000000], 'valid_referrals' => 11, 'profit_ratio' => [50, 100]],
        11 => ['level' => '11', 'personal_holding' => 0, 'team_requirement' => ['type' => 'hold_level', 'level' => 10, 'count' => 2, 'value' => 15000000], 'valid_referrals' => 12, 'profit_ratio' => [55, 110]],
        12 => ['level' => '12', 'personal_holding' => 0, 'team_requirement' => ['type' => 'hold_level', 'level' => 11, 'count' => 2, 'value' => 15000000], 'valid_referrals' => 13, 'profit_ratio' => [60, 120]],
        13 => ['level' => '13', 'personal_holding' => 0, 'team_requirement' => ['type' => 'hold_level', 'level' => 12, 'count' => 2, 'value' => 15000000], 'valid_referrals' => 14, 'profit_ratio' => [65, 130]],
        14 => ['level' => '14', 'personal_holding' => 0, 'team_requirement' => ['type' => 'hold_level', 'level' => 13, 'count' => 2, 'value' => 15000000], 'valid_referrals' => 15, 'profit_ratio' => [70, 140]],
        15 => ['level' => '15', 'personal_holding' => 0, 'team_requirement' => ['type' => 'hold_level', 'level' => 14, 'count' => 2, 'value' => 15000000], 'valid_referrals' => 15, 'profit_ratio' => [75, 150]],
    ];

    /**
     * Check and update user levels (Citizen Alliance Reward Levels)
     */
    /*public function checkCitizenAllianceReward(array $onlyUserIds = null)
    {
        $users = usersModel::where('status', 1)
            ->when($onlyUserIds, fn($q) => $q->whereIn('id', $onlyUserIds))
            ->get();

        $doraPrice = rtxPrice();

        foreach ($users as $user) {
            $userId = $user->id;
            $personalHolding = getUserStakedAmount($userId);
            $personalHolding = $personalHolding * $doraPrice;
            echo $userId." personalHolding:".$personalHolding.PHP_EOL;

            $validReferrals = $this->getValidReferrals($userId, $doraPrice);
            echo $userId." validReferrals:".$validReferrals.PHP_EOL;

            $totalOrdinaryStaking = $user->my_business ?? 0;
            $totalOrdinaryStaking = $totalOrdinaryStaking * $doraPrice;
            echo $userId." totalTeamBusiness:".$totalOrdinaryStaking.PHP_EOL.PHP_EOL;


            $achievedLevel = 0;

            foreach ($this->matrix as $matrix) {
                $criteriaMatch = true;

                // 1️. Personal Holding check
                if ($personalHolding < $matrix['personal_holding']) {
                    $criteriaMatch = false;
                }

                // 2️. Valid referrals check
                if ($validReferrals < $matrix['valid_referrals']) $criteriaMatch = false;

                // 3️. Team requirement check
                // echo "For level ".$matrix['level']." Matrix team_requirement:".$matrix['team_requirement']['type'].PHP_EOL;

                if ($matrix['team_requirement']['type'] === 'volume') {
                    if ($totalOrdinaryStaking < $matrix['team_requirement']['value'])
                        $criteriaMatch = false;
                } else {
                    // Hold-level requirement
                    $teamCount = myTeamModel::join('user_plans', 'user_plans.user_id', '=', 'my_team.team_id')
                        ->join('users', 'users.id', '=', 'my_team.team_id')
                        ->where('my_team.user_id', $userId)
                        ->where('user_plans.status', 1)
                        ->where('users.level', $matrix['team_requirement']['level'])
                        ->distinct()
                        ->count('my_team.team_id');
                    // echo $userId." teamCount:".$teamCount.PHP_EOL;

                    // $query = myTeamModel::join('user_plans', 'user_plans.user_id', '=', 'my_team.team_id')
                    //     ->join('users', 'users.id', '=', 'my_team.team_id')
                    //     ->where('my_team.user_id', $userId)
                    //     ->where('user_plans.status', 1)
                    //     ->where('users.level', 10)
                    //     ->groupBy('my_team.team_id');

                    // $sql = $query->toSql();            // e.g. "select * from `my_team` inner join ... where `my_team`.`user_id` = ? and ..."
                    // $bindings = $query->getBindings();

                    // $quoted = array_map(function ($b) {
                    //     if (is_null($b)) return 'NULL';
                    //     if (is_numeric($b)) return (string)$b;
                    //     return "'".str_replace("'", "''", (string)$b)."'";
                    // }, $bindings);

                    // $pretty = Str::replaceArray('?', $quoted, $sql);

                    // Dump it
                    // dump($pretty);

                    if ($teamCount < $matrix['team_requirement']['count'])
                        $criteriaMatch = false;
                }

                // 4. If all criteria matched, set achieved level
                // echo $userId." Criteria status:".$criteriaMatch.PHP_EOL;

                if ($criteriaMatch)
                    $achievedLevel = $matrix['level'];
                // else
                //     break;
                // echo PHP_EOL;
            }

            // 5. Update Level
            if ($achievedLevel) 
            {
                // echo "User #$userId eligible for level: $achievedLevel<br>";
                usersModel::where('id', $userId)->update(['level' => $achievedLevel]);
            } 
            else 
            {
                // echo "User #$userId not eligible for any level<br>";
                usersModel::where('id', $userId)->update(['level' => 0]);
            }
        }
    }*/

    
    public function checkCitizenAllianceReward()
    {

        $doraPrice = rtxPrice();

        $investment = userPlansModel::where(['isCount' => 0])->orderBy('id', 'asc')->get()->toArray();

        // UPDATE MY_BUSINESS
        foreach ($investment as $key => $value) 
        {
            updateReverseBusiness($value['user_id'], $value['amount']);
            $checkIfFirstPackage = userPlansModel::where('user_id', $value['user_id'])->get()->toArray();
            if (count($checkIfFirstPackage) == 1) 
            {
                updateActiveTeam($value['user_id']);
            }
            userPlansModel::where(['id' => $value['id']])->update(['isCount' => 1]);
        }

        $users = usersModel::where('status', 1)->get(); //->where('id',64)

        foreach ($users as $user) 
        {
            $userId = $user->id;

            $personalHolding = getUserStakeAmount($userId);

            $personalHolding *= $doraPrice;

            $validReferrals = $this->getValidReferrals($userId, $doraPrice);

            $achievedLevel = 0;
            
            $legs = usersModel::where('sponser_id', $userId)->where('level', 0)->get();

            echo $userId." personalHolding=".$personalHolding." validReferrals=".$validReferrals."<br>".PHP_EOL;
            // echo $userId." hasMultipleLegs=".$this->hasMultipleLegs($legs, 14, 2)."<br>".PHP_EOL;

            // === LEVEL 15 ===
            if ($this->hasMultipleLegs($legs, 14, 2) && $validReferrals >= 15) 
            {
                $legamount = 15000000/2;
                $legbusiness = $this->getTeamBusiness($userId);
                if(($legbusiness['strong'] >= $legamount) && ($legbusiness['weak'] >= $legamount)){
                    $achievedLevel = 15;
                }
            }

            // === LEVEL 14 ===
            if (!$achievedLevel && $this->hasMultipleLegs($legs, 13, 2) && $validReferrals >= 15) 
            {
                $legamount = 15000000/2;
                $legbusiness = $this->getTeamBusiness($userId);
                if(($legbusiness['strong'] >= $legamount) && ($legbusiness['weak'] >= $legamount)){
                    $achievedLevel = 14;
                }
            }

            // === LEVEL 13 ===
            if (!$achievedLevel && $this->hasMultipleLegs($legs, 12, 2) && $validReferrals >= 14) 
            {
                $legamount = 15000000/2;
                $legbusiness = $this->getTeamBusiness($userId);
                if(($legbusiness['strong'] >= $legamount) && ($legbusiness['weak'] >= $legamount)){
                    $achievedLevel = 13;
                }
            }

            // === LEVEL 12 ===
            if (!$achievedLevel && $this->hasMultipleLegs($legs, 11, 2) && $validReferrals >= 13) 
            {
                $legamount = 15000000/2;
                $legbusiness = $this->getTeamBusiness($userId);
                if(($legbusiness['strong'] >= $legamount) && ($legbusiness['weak'] >= $legamount)){
                    $achievedLevel = 12;
                }
            }

            // === LEVEL 11 ===
            if (!$achievedLevel && $this->hasMultipleLegs($legs, 10, 2) && $validReferrals >= 12) 
            {
                $legamount = 15000000/2;
                $legbusiness = $this->getTeamBusiness($userId);
                if(($legbusiness['strong'] >= $legamount) && ($legbusiness['weak'] >= $legamount)){
                    $achievedLevel = 11;
                }
            }
            
            // === LEVEL 10 ===
            if ($this->hasMultipleLegs($legs, 9, 2) && $validReferrals >= 11) {
                $legamount = 15000000/2;
                $legbusiness = $this->getTeamBusiness($userId);
                if(($legbusiness['strong'] >= $legamount) && ($legbusiness['weak'] >= $legamount)){
                    $achievedLevel = 10;
                }
            }

            if ($achievedLevel === 0) 
            {
                // echo PHP_EOL." 2# ".$userId ."=> personalHolding=". $personalHolding .", validReferrals=". $validReferrals ."<br>"; // totalTeamBusiness=". $totalTeamBusiness .", 

                // === LEVEL 9 ===
                if ($personalHolding >= 15000 && $validReferrals >= 10) 
                {
                    $legamount = 15000000/2;
                    $legbusiness = $this->getTeamBusiness($userId);
                    if(($legbusiness['strong'] >= $legamount) && ($legbusiness['weak'] >= $legamount)){
                        $achievedLevel = 9;
                    }
                }

                // === LEVEL 8 ===
                if (!$achievedLevel && $personalHolding >= 12000 && $validReferrals >= 9) 
                {
                    $legamount = 6000000/2;
                    $legbusiness = $this->getTeamBusiness($userId);
                    if(($legbusiness['strong'] >= $legamount) && ($legbusiness['weak'] >= $legamount)){
                        $achievedLevel = 8;
                    }
                }

                // === LEVEL 7 ===
                if (!$achievedLevel && $personalHolding >= 10000 && $validReferrals >= 8) 
                {
                    $legamount = 2500000/2;
                    $legbusiness = $this->getTeamBusiness($userId);
                    if(($legbusiness['strong'] >= $legamount) && ($legbusiness['weak'] >= $legamount)){
                        $achievedLevel = 7;
                    }
                }

                // === LEVEL 6 ===
                if (!$achievedLevel && $personalHolding >= 5000 && $validReferrals >= 7) 
                {
                    $legamount = 1000000/2;
                    $legbusiness = $this->getTeamBusiness($userId);
                    if(($legbusiness['strong'] >= $legamount) && ($legbusiness['weak'] >= $legamount)){
                        $achievedLevel = 6;
                    }
                }

                // === LEVEL 5 ===
                if (!$achievedLevel && $personalHolding >= 3000 && $validReferrals >= 6) 
                {
                    $legamount = 400000/2;
                    $legbusiness = $this->getTeamBusiness($userId);
                    if(($legbusiness['strong'] >= $legamount) && ($legbusiness['weak'] >= $legamount)){
                        $achievedLevel = 5;
                    }
                }

                // === LEVEL 4 ===
                if (!$achievedLevel && $personalHolding >= 2000 && $validReferrals >= 5) 
                {
                    $legamount = 150000/2;
                    $legbusiness = $this->getTeamBusiness($userId);
                    if(($legbusiness['strong'] >= $legamount) && ($legbusiness['weak'] >= $legamount)){
                        $achievedLevel = 4;
                    }
                }

                // === LEVEL 3 ===
                if (!$achievedLevel && $personalHolding >= 1000 && $validReferrals >= 4) 
                {
                    $legamount = 50000/2;
                    $legbusiness = $this->getTeamBusiness($userId);
                    if(($legbusiness['strong'] >= $legamount) && ($legbusiness['weak'] >= $legamount)){
                        $achievedLevel = 3;
                    }
                }

                // === LEVEL 2 ===
                if (!$achievedLevel && $personalHolding >= 500 && $validReferrals >= 3) 
                {
                    $legamount = 20000/2;
                    $legbusiness = $this->getTeamBusiness($userId);
                    if(($legbusiness['strong'] >= $legamount) && ($legbusiness['weak'] >= $legamount)){
                        $achievedLevel = 2;
                    }
                }

                // === LEVEL 1 ===
                if (!$achievedLevel && $personalHolding >= 100 && $validReferrals >= 2) 
                {
                    $legamount = 5000/2;
                    $legbusiness = $this->getTeamBusiness($userId);

                    if(($legbusiness['strong'] >= $legamount) && ($legbusiness['weak'] >= $legamount)){
                        $achievedLevel = 1;
                    }
                }

            }

            echo $userId." achievedLevel=".$achievedLevel.PHP_EOL;
            if ($achievedLevel) 
            {
                usersModel::where('id', $userId)->update(['level' => $achievedLevel]);
            } 
            else 
            {
                usersModel::where('id', $userId)->update(['level' => 0]);
            }
        }
    }


    public function hasMultipleLegs($legs, $requiredLevel, $countDifferentLegs)
    {
        $legCount = 0;
        $legFoundArray = array();

        // Loop through each leg to check if it meets the required rank
        foreach ($legs as $leg) {
            if ($leg->level >= $requiredLevel) {
                $legCount++;
                array_push($legFoundArray, $leg->id); // Store the leg's id that qualifies
            }
        }

        // If there aren't enough legs with the required rank, check within teams under each leg
        if ($legCount < $countDifferentLegs) {
            // Loop through the remaining legs to find the teams
            foreach ($legs as $leg) {
                // If leg is already counted, skip it
                if (in_array($leg->id, $legFoundArray)) {
                    continue;
                }

                // Find the users in the leg's team (downline) using their sponsor_id
                $legTeam = myTeamModel::join('users', 'users.id', '=', 'my_team.team_id')
                                            ->where(['my_team.user_id' => $leg->id])
                                            ->get();

                foreach ($legTeam as $legMember) {
                    // Check if the team member qualifies based on the rank
                    if ($legMember->level >= $requiredLevel) {
                        $legCount++;
                        array_push($legFoundArray, $leg->id); // Add the leg id if the team member qualifies
                        break; // Stop as soon as we find a qualifying team member
                    }
                }
            }
        }

        // Return whether the number of qualifying legs meets the required count
        return $legCount >= $countDifferentLegs;
    }


    public function getTeamBusiness($userId)
    {
        $doraPrice = rtxPrice();

       $result = usersModel::selectRaw('
            users.id,
            IFNULL(SUM(user_plans.amount), 0) as personal_stake,
            IFNULL(SUM(users.my_business), 0) as my_business,
            IFNULL(SUM(user_plans.amount), 0) + IFNULL(SUM(users.my_business), 0) as total
        ')
        ->leftJoin('user_plans', 'user_plans.user_id', '=', 'users.id')
        ->whereRaw("user_plans.roi > 0")
        ->where('users.sponser_id', $userId)
        ->groupBy('users.id')
        ->get();


        $otherLegs = $result->toArray();

        $strongBusiness = 0;
        $weakBusiness = 0;
        if (!empty($otherLegs)) {
            foreach ($otherLegs as $index => $leg) {

                $claimedRewards = withdrawModel::where('user_id', $leg['id'])
                    ->where('withdraw_type', 'UNSTAKE')
                    ->sum('amount');

                $otherLegs[$index]['total'] =
                    ($leg['total'] - $claimedRewards) * $doraPrice;
            }
            usort($otherLegs, fn($a, $b) => $b['total'] <=> $a['total']);
            $strongBusiness = $otherLegs[0]['total'];
            for ($i = 1; $i < count($otherLegs); $i++) {
                $weakBusiness += $otherLegs[$i]['total'];
            }
        }

        echo $userId." Strong=".$strongBusiness." Weak=".$weakBusiness.PHP_EOL;
        return ['strong' => $strongBusiness, 'weak' => $weakBusiness];
    }
    

    /**
     * Count valid direct referrals (>= $100)
     */
    public function getValidReferrals($userId, $price)
    {
        $directs = usersModel::select('users.id')
            ->join('user_plans', 'user_plans.user_id', '=', 'users.id')
            ->where('users.sponser_id', $userId)
            ->groupBy('users.id')
            ->get();

        $count = 0;
        foreach ($directs as $direct) {
            $stake = getUserStakeAmount($direct->id);
            if ($stake * $price >= 100) $count++;
        }
        // echo "getValidReferrals:".$userId." > ".$count.PHP_EOL;
        return $count;
    }

    

    /*public function citizenAllianceRewardBonus(array $onlyUserIds = null, bool $debug = false)
    {
        // Differential bonus
        $rankPercentage = [];
        foreach ($this->matrix as $level => $m) {
            $rankPercentage[$level] = $m['profit_ratio'];
        }
        
        $users = usersModel::where('level', '>', 0)->get();

        foreach ($users as $user) {
            $userLevel = $user->level;
            
            $userPercent = $rankPercentage[$userLevel];
            $userPercent = $userPercent[0];
            $teamRoi = ($user->my_business * 0.003); //getTeamRoi($user->id);
            $distributeAmount = 0;

            $directs = usersModel::where('sponser_id', $user->id)->get();

            // echo $user->id." SponserLevel:".$userLevel." Sponser TeamROI:".$teamRoi.PHP_EOL;
            // echo "Directs:".count($directs);

            foreach ($directs as $direct) {
                $legRoi = ($direct->my_business * 0.003); //getTeamRoi($direct->id);
                
                $remainingLegRoi = $legRoi;

                // echo $direct->id." DirectLevel:".$direct->level." Direct TeamROI:".$legRoi.PHP_EOL;

                $deductedTeamIds = [];
                $directIncluded = false;
                
                // echo "Check direct user's level with sponser's level".PHP_EOL;

                // First, check the direct himself
                if ($direct->level >= $userLevel) {
                    // Direct is higher/equal ranked → subtract full ROI
                    $directRoi = ($direct->my_business * 0.003); //getTeamRoi($direct->id);
                    $teamRoi -= $directRoi;
                    $remainingLegRoi -= $directRoi;
                    $directIncluded = true;
                } else if ($direct->level > 0) {
                    // Direct is lower ranked → give differential bonus
                    $directRoi = ($direct->my_business * 0.003); //getTeamRoi($direct->id);
                    $effectiveRoi = min($directRoi, $remainingLegRoi);

                    // echo "directRoi:".$directRoi." remainingLegRoi:".$remainingLegRoi." effectiveRoi:".$effectiveRoi.PHP_EOL;

                    $diff = $userPercent - $rankPercentage[$direct->level];

                    // echo "User Percent:".$userPercent." rank percent:".$rankPercentage[$direct->level]." Diff:".$diff.PHP_EOL;

                    if ($diff > 0) {
                        $distributeAmount += ($effectiveRoi * $diff / 100);
                    }

                    $remainingLegRoi -= $effectiveRoi;
                    $deductedTeamIds[] = $direct->id;
                }
                // else{
                //     echo "No direct->level >= userLevel(".$direct->level.",".$userLevel.") AND No direct->level > 0 (".$direct->level.",0)".PHP_EOL;
                // }

                // echo "Now go through downline leveled members:";
                // Now go through downline ranked members
                $leveledMembers = usersModel::join('my_team', 'my_team.team_id', '=', 'users.id')
                                            ->where('my_team.user_id', $direct->id)
                                            ->where('users.level', '>', 0)
                                            ->orderBy('users.level', 'desc')
                                            ->get();
                // echo count($leveledMembers).PHP_EOL;

                foreach ($leveledMembers as $leveledUser) {
                    if (in_array($leveledUser->sponser_id, $deductedTeamIds)) {
                        continue;
                    }

                    $leveledUserRoi = ($leveledUser->my_business * 0.003); //getTeamRoi($leveledUser->id);
                    $effectiveRoi = min($leveledUserRoi, $remainingLegRoi);

                    if ($leveledUser->level >= $userLevel) {
                        $teamRoi -= $effectiveRoi;
                        $remainingLegRoi -= $effectiveRoi;
                    } else {
                        $diff = $userPercent - $rankPercentage[$leveledUser->level];
                        if ($diff > 0) {
                            $distributeAmount += ($effectiveRoi * $diff / 100);
                        }
                        $remainingLegRoi -= $effectiveRoi;
                    }

                    $deductedTeamIds[] = $leveledUser->id;
                }

                // Remaining ROI in leg → full % bonus
                if ($remainingLegRoi > 0) {
                    $distributeAmount += ($remainingLegRoi * $userPercent / 100);
                }
                // echo "TeamROI Before:".$teamRoi.PHP_EOL;
                $teamRoi -= $legRoi;
                // echo "TeamROI After:".$teamRoi.PHP_EOL.PHP_EOL;
            }

            // Final remaining ROI (other than directs) → full % bonus
            if ($teamRoi > 0) {
                $distributeAmount += ((float)$teamRoi * (float)$userPercent / 100);
            }

            $roi = [
                'user_id' => $user->id,
                'amount' => round($distributeAmount, 6),
                'tag' => "DIFFERANTIAL-TEAM-BONUS",
                'refrence' => $user->level,
                'refrence_id' => $teamRoi,
                // 'created_on' => now(),
            ];

            // print_r($roi);

            earningLogsModel::insert($roi);

            DB::statement("UPDATE users SET rank_bonus = IFNULL(rank_bonus, 0) + {$roi['amount']} WHERE id = {$user->id}");
        }

    }*/

    public function citizenAllianceRewardBonus(array $onlyUserIds = null, bool $debug = true)
    {
        // 1) Flatten matrix -> single % per level (choose the LOWER bound: [0])
        $rankPct = [];
        foreach ($this->matrix as $lvl => $cfg) {
            // e.g. [5, 10] => use 5 as the effective differential percentage for that level
            $rankPct[(int)$lvl] = (float)($cfg['profit_ratio'][0] ?? 0);
        }

        // 2) Scope to eligible users (level > 0) AND optional filter
        $users = usersModel::where('level', '>', 0)
            ->when($onlyUserIds, fn($q) => $q->whereIn('id', $onlyUserIds))
            ->get();

        foreach ($users as $user) {
            DB::beginTransaction();
            try {
                $userLevel = (int)$user->level;
                $userPercent = (float)($rankPct[$userLevel] ?? 0); // sponsor % (e.g., 25)
                if ($userPercent <= 0) { DB::rollBack(); continue; }

                // Proxy for total team ROI (non-direct pool + directs): you’re using my_business * 0.003
                $teamRoiTotal = max(0.0, (float)$user->my_business * 0.003);
                $distribute   = 0.0;

                // 3) Iterate DIRECT legs
                $directs = usersModel::where('sponser_id', $user->id)->get();
                foreach ($directs as $direct) {
                    $legRoi = max(0.0, (float)$direct->my_business * 0.003);
                    if ($legRoi <= 0) { $teamRoiTotal -= 0; continue; }

                    $remaining = $legRoi;
                    $directLevel = (int)($direct->level ?? 0);

                    // 3.a) Direct is equal/higher → block entire leg ROI slice at direct
                    if ($directLevel >= $userLevel) {
                        $remaining -= min($remaining, $legRoi); // block
                    }
                    // 3.b) Direct is lower leveled → pay differential at direct node
                    elseif ($directLevel > 0) {
                        $directPct = (float)($rankPct[$directLevel] ?? 0);
                        $diff = $userPercent - $directPct;
                        if ($diff > 0) {
                            $pay = $remaining * ($diff / 100.0);
                            $distribute += $pay;
                        }
                        // consume the whole direct node amount
                        $remaining = 0.0;
                    }
                    // else (direct level = 0): nothing at this node, flows to remainder

                    // 3.c) Downline leveled members under this direct (unique, highest level first)
                    if ($remaining > 0) {
                        $members = usersModel::join('my_team', 'my_team.team_id', '=', 'users.id')
                            ->where('my_team.user_id', $direct->id)
                            ->where('users.level', '>', 0)
                            ->groupBy('users.id')
                            ->orderBy('users.level', 'desc')
                            ->get(['users.id', 'users.level', 'users.my_business']);

                        $seen = [];
                        foreach ($members as $m) {
                            if ($remaining <= 0) break;

                            $mId = (int)$m->id;
                            if (isset($seen[$mId])) continue;
                            $seen[$mId] = true;

                            $mLevel = (int)$m->level;
                            $mRoi   = max(0.0, (float)$m->my_business * 0.003);
                            if ($mRoi <= 0) continue;

                            $effective = min($mRoi, $remaining);

                            if ($mLevel >= $userLevel) {
                                // block this slice
                                $remaining -= $effective;
                            } else {
                                $mPct = (float)($rankPct[$mLevel] ?? 0);
                                $diff = $userPercent - $mPct;
                                if ($diff > 0) {
                                    $distribute += $effective * ($diff / 100.0);
                                }
                                $remaining -= $effective;
                            }
                        }
                    }

                    // 3.d) Leg remainder → sponsor gets full %
                    if ($remaining > 0) {
                        $distribute += $remaining * ($userPercent / 100.0);
                    }

                    // Subtract this leg once from global remainder pool
                    $teamRoiTotal -= $legRoi;
                }

                // 4) Non-direct remainder → full sponsor %
                if ($teamRoiTotal > 0) {
                    $distribute += $teamRoiTotal * ($userPercent / 100.0);
                }

                // 5) Persist payout
                $amount = round(max(0.0, $distribute), 6);
                if($amount > 0)
                {
                    earningLogsModel::insert([
                        'user_id'     => $user->id,
                        'amount'      => $amount,
                        'tag'         => 'DIFF-TEAM-BONUS',
                        'refrence'    => (string)$userLevel,            // level at calc time
                        'refrence_id' => (string)max(0.0, $teamRoiTotal), // remainder pool snapshot
                        'created_on'  => now(),
                        'status'      => 1,
                        'isCount'     => 0,
                        'isSynced'    => 0,
                    ]);

                    DB::statement(
                        "UPDATE users SET rank_bonus = IFNULL(rank_bonus,0) + ? WHERE id = ?",
                        [$amount, $user->id]
                    );
                }

                if ($debug) {
                    $pctFmt = rtrim(rtrim(number_format($userPercent, 2, '.', ''), '0'), '.');
                    echo "User {$user->id} (L{$userLevel} @ {$pctFmt}%): payout=" . number_format($amount, 6, '.', '') . PHP_EOL;
                }

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                if ($debug) {
                    echo "User {$user->id} failed: {$e->getMessage()}" . PHP_EOL;
                }
            }
        }
    }


}
