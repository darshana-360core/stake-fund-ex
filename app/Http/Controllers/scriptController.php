<?php

namespace App\Http\Controllers;

use App\Models\earningLogsModel;
use App\Models\packageTransaction;
use App\Models\levelEarningLogsModel;
use App\Models\levelRoiModel;
use App\Models\myTeamModel;
use App\Models\rankingModel;
use App\Models\rewardBonusModel;
use App\Models\userPlansModel;
use App\Models\usersModel;
use App\Models\withdrawModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx\Rels;

use function App\Helpers\findRankBonusIncome;
use function App\Helpers\findUplineRank;
use function App\Helpers\getRefferer;
use function App\Helpers\getIncome;
use function App\Helpers\getUserMaxReturn;
use function App\Helpers\getRoiMaxReturn;
use function App\Helpers\getTeamRoi;
use function App\Helpers\isUserActive;
use function App\Helpers\rtxPrice;
use function App\Helpers\updateActiveTeam;
use function App\Helpers\updateReverseBusiness;
use function App\Helpers\unstakedAmount;
use function App\Helpers\getUserStakeAmount;
use function App\Helpers\reverseBusiness;
use function App\Helpers\unstakedAmountTest;

use function App\Helpers\unstakedAmountContractStackeid;
use function App\Helpers\claimRoiAmountContractStackeid;

use Carbon\Carbon;

class scriptController extends Controller
{
    public function businessSync(Request $request)
    {
        $rtxPrice = rtxPrice();
        $investment = userPlansModel::where(['isCount' => 0])->orderBy('id', 'asc')->get()->toArray();

        $ids_updated = array();

        foreach ($investment as $key => $value) {
            updateReverseBusiness($value['user_id'], $value['amount']);

            $checkIfFirstPackage = userPlansModel::where('user_id', $value['user_id'])->get()->toArray();

            if (count($checkIfFirstPackage) == 1) {
                updateActiveTeam($value['user_id']);
            }
            userPlansModel::where(['id' => $value['id']])->update(['isCount' => 1]);

            // $stake = getUserStakeAmount($value['user_id']);

            // usersModel::where(['id' => $value['user_id']])->update(['stake' => $stake]);
        }

        $investmentReverse = withdrawModel::where(['isReverse' => 0])->orderBy('id', 'asc')->get()->toArray();

        foreach ($investmentReverse as $key => $value) {
            reverseBusiness($value['user_id'], $value['amount']);

            withdrawModel::where(['id' => $value['id']])->update(['isReverse' => 1]);

            $stake = getUserStakeAmount($value['user_id']);

            usersModel::where(['id' => $value['user_id']])->update(['stake' => $stake]);
        }
    }

    public function setStake(Request $request)
    {
        // avoid huge in-memory query logs during long loops
        DB::connection()->disableQueryLog();

        usersModel::where('status', 1)
            ->orderBy('id')
            ->chunkById(1000, function ($users) {
                $updates = [];

                foreach ($users as $u) {
                    // keeps your existing calculation
                    $stake = getUserStakeAmount($u->id);
                    $updates[] = ['id' => $u->id, 'stake' => $stake];
                }

                if (!empty($updates)) {
                    // single bulk write per chunk (MySQL/PG supported)
                    usersModel::upsert($updates, ['id'], ['stake']);
                }
            });

        return response()->json(['status' => 'ok']);
    }

    /*public function checkLevel(Request $request)
    {
        $rtxPrice = rtxPrice();

        // $excludedIds = [
        //     2, 3, 4, 5, 6, 7, 8, 9, 13, 14, 15, 16, 18, 19, 20, 21, 23, 25, 40, 53,
        //     238, 256, 260, 348, 350, 352, 1039, 1828, 2772, 2792, 5163, 5340,
        //     5401, 5402, 5404, 5659, 5660, 5661, 5707, 56, 65, 68, 18785, 259, 37059, 37075, 37081, 37085, 37090, 37099, 37104, 1079
        // ];

        $users = usersModel::where('daily_roi', '>', 0)->where('status', 1)
            // ->whereNotIn('id', $excludedIds)
            ->orderBy('id', 'asc')
            ->get()
            ->toArray();

        foreach ($users as $key => $value) {
            $activeUser = userPlansModel::where(['user_id' => $value['id']])->get()->toArray();
            // $unstakeByUser = withdrawModel::where(['user_id' => $value['id'], 'isReverse' => 0])->get()->toArray();
            if(count($activeUser) > 0)
            {
                $activeDirectCount = DB::select("select SUM(amount + compound_amount) AS cs, user_id from `users` inner join `user_plans` on `user_plans`.`user_id` = `users`.`id` where (`users`.`sponser_id` = ".$value['id'].") GROUP BY user_id HAVING cs >= (".(100 / $rtxPrice).")");

                $userInvestment = 0;

                foreach ($activeUser as $keyValue => $userValue) {
                    $userInvestment += ($userValue['amount'] + $userValue['compound_amount']);
                }

                $unstake1 = unstakedAmount($value['id'], 1);
                $unstake2 = unstakedAmount($value['id'], 2);
                $unstake3 = unstakedAmount($value['id'], 3);

                $userInvestment = ($userInvestment - $unstake1 - $unstake2 - $unstake3);

                $userInvestment = ($rtxPrice * $userInvestment);

                $activeDirectCount = array_map(function ($value) {
                    return (array) $value;
                }, $activeDirectCount);

                $countDirect = count($activeDirectCount);

                $levelsOpen = levelRoiModel::select('id', 'direct', 'business')
                    ->where('direct', '<=', $countDirect)
                    ->whereRaw('CAST(business AS DECIMAL(15,2)) <= CAST(? AS DECIMAL(15,2))', [$userInvestment])
                    ->orderBy('id', 'desc')
                    ->get()
                    ->toArray();
                    
                if (count($levelsOpen) > 0) {
                    // echo $value['id'].' - '.$userInvestment.' - '.count($levelsOpen).' - '.$levelsOpen['0']['id'].' - direct - '.$levelsOpen[0]['direct'].' - business - '.$levelsOpen[0]['business'].' - activeDirectCount - '.$activeDirectCount['0']['count'].' - userInvestment - '.$userInvestment.PHP_EOL;
                    usersModel::where(['id' => $value['id']])->update(['level' => $levelsOpen['0']['id']]);
                }else
                {
                    usersModel::where(['id' => $value['id']])->update(['level' => 0]);
                }
            }else
            {
                usersModel::where(['id' => $value['id']])->update(['level' => 0]);
            }

        }
    }*/

    public function activeTeamCalculate(Request $request)
    {
        usersModel::where(['status' => 1])->update(['active_team' => 0]);

        $userPlans = userPlansModel::select('user_id')->groupBy('user_id')->get()->toArray();

        foreach ($userPlans as $key => $value) {
            updateActiveTeam($value['user_id']);
        }
    }

    public function reverseInvestment(Request $request)
    {
        // $investment = userPlansModel::where(['status' => 2])->orderBy('id', 'asc')->get()->toArray();

        // foreach ($investment as $key => $value) {
        //     reverseBusiness($value['user_id'], $value['amount']);

        //     userPlansModel::where(['id' => $value['id']])->update(['status' => 3]);
        // }
    }

    public function checkUserRank(Request $request)
    {
        $type = $request->input('type');

        $user = usersModel::where('daily_roi', '>', 0)->where('status', 1)->get()->toArray(); //->where('id', 47)

        foreach ($user as $key => $value) {

            $business_amount = 0;
            $investment_amount = 0;

            usersModel::where('id', $value['id'])->update([
                'rank' => null,
                'rank_id' => 0
            ]);

            $getSelfInvestment = userPlansModel::where(['user_id' => $value['id']])->get()->toArray();

            $unstake0 = 0;
            $unstake1 = 0;
            $unstake2 = 0;
            $unstake3 = 0;

            foreach ($getSelfInvestment as $gsik => $gsiv) {
                $investment_amount += $gsiv['amount'] + $gsiv['compound_amount'];

                $unstake0 += unstakedAmountTest($value['id'], 0, $gsiv['contract_stakeid']);
                $unstake1 += unstakedAmountTest($value['id'], 1, $gsiv['contract_stakeid']);
                $unstake2 += unstakedAmountTest($value['id'], 2, $gsiv['contract_stakeid']);
                $unstake3 += unstakedAmountTest($value['id'], 3, $gsiv['contract_stakeid']);
            }

            /*$unstake1 = unstakedAmount($value['id'], 1);
            $unstake2 = unstakedAmount($value['id'], 2);
            $unstake3 = unstakedAmount($value['id'], 3);*/

            $investment_amount = $investment_amount - ($unstake1 - $unstake2 - $unstake3 - $unstake0);

            $rewardDate = $value['created_on'];

            $getLastRewardDate = earningLogsModel::where('user_id', $value['id'])->where('tag', 'REWARD-BONUS')->orderBy('id', 'desc')->get()->toArray();

            if(count($getLastRewardDate))
            {
                $rewardDate = $getLastRewardDate['0']['created_on'];
            }

            $userJoiningDate = \Carbon\Carbon::parse($rewardDate);

            $rtxPrice = rtxPrice();

            $investment_amount = ($rtxPrice * $investment_amount);

            $otherLegs = usersModel::selectRaw("(my_business + strong_business) + IFNULL(SUM(user_plans.amount), 0) as legbusiness, users.id")
                                            ->leftjoin('user_plans', 'user_plans.user_id', '=', 'users.id')
                                            ->where(['sponser_id' => $value['id']])
                                            ->groupBy("users.id")
                                            ->get()->toArray();

            foreach ($otherLegs as $olk => $olv) {
                $business_amount += $olv['legbusiness'];
            }

            $business_amount = ($rtxPrice * $business_amount);

            $checkLevel = rewardBonusModel::whereRaw("eligible <= (".($business_amount).")")
                                                ->orderByRaw('CAST(eligible as unsigned) desc')
                                                ->get()->toArray();


            if (count($checkLevel) > 0) {
                foreach ($checkLevel as $clk => $clv) {

                    $getRewardRanking = rewardBonusModel::where(['id' => $clv['id']])->get()->toArray();

                    $isEligible = 0;
                    $countBusiness = 0;
                    $remaingBusines = 0;
                    $eligible = $clv['eligible'];
                    $eligiblePerLeg = $eligible / 2;
                    $rewardAmount = $getRewardRanking['0']['income'];
                    $durationDays = $getRewardRanking['0']['days'];


                    $deadline = $userJoiningDate->copy()->addDays($durationDays);
                    $now = \Carbon\Carbon::now();
                    $finalReward = $now->lte($deadline) ? $rewardAmount : 0;

                    $otherLegs = usersModel::selectRaw("IFNULL((my_business + strong_business),0) as my_business, users.id")
                                                    ->leftjoin('user_plans', 'user_plans.user_id', '=', 'users.id')
                                                    ->where(['sponser_id' => $value['id']])
                                                    ->groupBy('users.id')
                                                    ->get()
                                                    ->toArray();

                    // dd($otherLegs);

                    foreach ($otherLegs as $kl => $vl) {
                        $userPlansAmount = userPlansModel::selectRaw("IFNULL(SUM(amount),0) as amount")->where(['user_id' => $vl['id']])->whereRaw("roi > 0")->get()->toArray();

                        $claimedRewards = withdrawModel::selectRaw("IFNULL(SUM(amount), 0) as amount")->where('withdraw_type', '=', 'UNSTAKE')->where('user_id', '=', $vl['id'])->get()->toArray();

                        $vl['my_business'] = (($vl['my_business'] + $userPlansAmount['0']['amount']) - $claimedRewards['0']['amount']) * $rtxPrice;

                        if($vl['my_business'] < 0)
                        {
                            $vl['my_business'] = 0;
                        }

                        // echo  $vl['id'] . " -> Business " . $vl['my_business'] . "/" . $eligiblePerLeg . " - Remaining Business" . $remaingBusines .PHP_EOL;

                        if ($vl['my_business'] >= $eligiblePerLeg) {
                            $countBusiness += $eligiblePerLeg;
                            $remaingBusines += ($vl['my_business'] - $eligiblePerLeg);
                            $isEligible = 1;
                        } else {
                            $countBusiness += $vl['my_business'];
                        }

                        // if($value['strong_business'] > 0)
                        // {
                        //         $countBusiness += $vl['my_business'];
                        // }else
                        // {
                            
                        // }
                    }

                    if ($countBusiness >= $eligible && $isEligible == 1) {

                        usersModel::where('id', $value['id'])->update([
                            'rank' => $clv['name'],
                            'rank_id' => $clv['id']
                        ]);

                        $userRank = [
                            'user_id' => $value['id'],
                            'rank'    => $clv['id'],
                            'amount'  => $clv['income'],
                            'week'    => $clv['days'],
                            'date'    => date('Y-m-d'),
                        ];

                        // Check if already exists
                        $exists = DB::table('user_ranks')
                            ->where('user_id', $userRank['user_id'])
                            ->where('rank', $userRank['rank'])
                            ->exists();

                        if (!$exists) {

                            usersModel::where('id', $value['id'])->update([
                                'rank' => $clv['name'],
                                'rank_id' => $clv['id'],
                                'rank_date' => date('Y-m-d')
                            ]);

                            // DB::table('user_ranks')->insert($userRank);
                        }

                        $existing = earningLogsModel::where('user_id', $value['id'])
                            ->where('refrence_id', $clv['id'])
                            ->where('tag', 'REWARD-BONUS')
                            ->first();

                        if (!$existing) {
                            if($finalReward > 0)
                            {
                                $roi = array();
                                $roi['user_id'] = $value['id'];
                                $roi['amount'] = ($finalReward / $rtxPrice);
                                $roi['tag'] = "REWARD-BONUS";
                                $roi['isCount'] = 1;
                                $roi['refrence'] = $rtxPrice;
                                $roi['refrence_id'] = $clv['id'];
                                $roi['created_on'] = date('Y-m-d H:i:s');

                                earningLogsModel::insert($roi);

                                DB::statement("UPDATE users set reward_bonus = (IFNULL(reward_bonus,0) + (".$roi['amount'].")) where id = '" . $value['id'] . "'");
                            }
                        }

                        break;
                    }
                }
            }
        }
    }

    /*public function checkRankForOneUser($user_id)
    {
        $user = usersModel::where(['status' => 1, 'id' => $user_id])->get()->toArray();

        foreach ($user as $key => $value) {
            $business_amount = 0;
            $investment_amount = 0;

            usersModel::where('id', $value['id'])->update([
                'rank' => null,
                'rank_id' => 0
            ]);

            $getSelfInvestment = userPlansModel::where(['user_id' => $value['id']])->get()->toArray();

            foreach ($getSelfInvestment as $gsik => $gsiv) {
                $investment_amount += $gsiv['amount'] + $gsiv['compound_amount'];
            }

            $unstake1 = unstakedAmount($value['id'], 1);
            $unstake2 = unstakedAmount($value['id'], 2);
            $unstake3 = unstakedAmount($value['id'], 3);

            $investment_amount = $investment_amount - ($unstake1 - $unstake2 - $unstake3);

            $rewardDate = $value['created_on'];

            $getLastRewardDate = earningLogsModel::where('user_id', $value['id'])->where('tag', 'REWARD-BONUS')->orderBy('id', 'desc')->get()->toArray();

            if(count($getLastRewardDate))
            {
                $rewardDate = $getLastRewardDate['0']['created_on'];
            }

            $userJoiningDate = \Carbon\Carbon::parse($rewardDate);

            $rtxPrice = rtxPrice();

            $investment_amount = ($rtxPrice * $investment_amount);

            $otherLegs = usersModel::selectRaw("(my_business + strong_business) + IFNULL(SUM(user_plans.amount), 0) as legbusiness, users.id")->leftjoin('user_plans', 'user_plans.user_id', '=', 'users.id')->where(['sponser_id' => $value['id']])->groupBy("users.id")->get()->toArray();

            // dd($otherLegs);

            foreach ($otherLegs as $olk => $olv) {
                $business_amount += $olv['legbusiness'];
            }

            $business_amount = ($rtxPrice * $business_amount);

            $checkLevel = rankingModel::whereRaw("eligible <= (".($business_amount).") and account_balance <= $investment_amount")->orderByRaw('CAST(eligible as unsigned) desc')->get()->toArray();
            // dd($checkLevel);
            if (count($checkLevel) > 0) {
                foreach ($checkLevel as $clk => $clv) {

                    $getRewardRanking = rewardBonusModel::where(['id' => $clv['id']])->get()->toArray();

                    $isEligible = 0;
                    $countBusiness = 0;
                    $remaingBusines = 0;
                    $eligible = $clv['eligible'];
                    $eligiblePerLeg = $eligible / 2;
                    $rewardAmount = $getRewardRanking['0']['income'];
                    $durationDays = $getRewardRanking['0']['days'];


                    $deadline = $userJoiningDate->copy()->addDays($durationDays);
                    $now = \Carbon\Carbon::now();
                    $finalReward = $now->lte($deadline) ? $rewardAmount : $rewardAmount / 2;

                    $otherLegs = usersModel::selectRaw("IFNULL((my_business + strong_business),0) as my_business, users.id")->leftjoin('user_plans', 'user_plans.user_id', '=', 'users.id')->where(['sponser_id' => $value['id']])->groupBy('users.id')->get()->toArray();

                    foreach ($otherLegs as $kl => $vl) {
                        $userPlansAmount = userPlansModel::selectRaw("IFNULL(SUM(amount),0) as amount")->where(['user_id' => $vl['id']])->whereRaw("roi > 0")->get()->toArray();

                        $claimedRewards = withdrawModel::selectRaw("IFNULL(SUM(amount), 0) as amount")->where('withdraw_type', '=', 'UNSTAKE')->where('user_id', '=', $vl['id'])->get()->toArray();

                        $vl['my_business'] = (($vl['my_business'] + $userPlansAmount['0']['amount']) - $claimedRewards['0']['amount']) * $rtxPrice;

                        if($vl['my_business'] < 0)
                        {
                            $vl['my_business'] = 0;
                        }

                        if ($vl['my_business'] >= $eligiblePerLeg) {
                            $countBusiness += $eligiblePerLeg;
                            $remaingBusines += ($vl['my_business'] - $eligiblePerLeg);
                            $isEligible = 1;
                        } else {
                            $countBusiness += $vl['my_business'];
                        }
                    }

                    if ($countBusiness >= $eligible && $isEligible == 1) {

                        usersModel::where('id', $value['id'])->update([
                            'rank' => $clv['name'],
                            'rank_id' => $clv['id']
                        ]);

                        $userRank = [
                            'user_id' => $value['id'],
                            'rank'    => $clv['id'],
                            'amount'  => $clv['income'],
                            'week'    => $clv['week'],
                            'date'    => date('Y-m-d'),
                        ];

                        // Check if already exists
                        $exists = DB::table('user_ranks')
                            ->where('user_id', $userRank['user_id'])
                            ->where('rank', $userRank['rank'])
                            ->exists();

                        if (!$exists) {
                            usersModel::where('id', $value['id'])->update([
                                'rank' => $clv['name'],
                                'rank_id' => $clv['id'],
                                'rank_date' => date('Y-m-d')
                            ]);

                            DB::table('user_ranks')->insert($userRank);
                        }
                        break;
                    }
                }
            }
        }
    }*/

    public function starBonus(Request $request)
    {
        $rankPercentage = [
            0 => 0,
            1 => 5,
            2 => 10,
            3 => 15,
            4 => 20,
            5 => 25,
            6 => 30,
            7 => 35,
            8 => 40,
            9 => 45,
            10 => 50,
            11 => 55,
            12 => 60,
            13 => 65,
            14 => 70,
            15 => 75,
        ];

        $users = usersModel::where('level', '>', 0)->get();

        foreach ($users as $user) {


            $userRank = $user->level;
            $userPercent = $rankPercentage[$userRank];

            $teamRoi = getTeamRoi($user->id);


            $distributeAmount = 0;

            $directs = usersModel::where('sponser_id', $user->id)->get();

            foreach ($directs as $d) {
            }

            foreach ($directs as $direct) {


                $legRoi = getTeamRoi($direct->id);
                $remainingLegRoi = $legRoi;


                $deductedTeamIds = [];
                $directIncluded = false;

                // Check direct himself
                if ($direct->level >= $userRank) {

                    $directRoi = getTeamRoi($direct->id);


                    $teamRoi -= $directRoi;
                    $remainingLegRoi -= $directRoi;
                    $directIncluded = true;

                } elseif ($direct->level > 0) {


                    $directRoi = getTeamRoi($direct->id);
                    $effectiveRoi = min($directRoi, $remainingLegRoi);
                    $diff = $userPercent - $rankPercentage[$direct->level];


                    if ($diff > 0) {
                        $give = ($effectiveRoi * $diff / 100);
                        $distributeAmount += $give;
                    }

                    $remainingLegRoi -= $effectiveRoi;
                    $deductedTeamIds[] = $direct->id;
                }


                // Downline ranked members
                $rankedMembers = usersModel::join('my_team', 'my_team.team_id', '=', 'users.id')
                    ->where('my_team.user_id', $direct->id)
                    ->where('users.level', '>', 0)
                    ->orderBy('users.level', 'desc')
                    ->get();

                foreach ($rankedMembers as $rankedUser) {


                    if (in_array($rankedUser->sponser_id, $deductedTeamIds)) {
                        continue;
                    }

                    $rankedUserRoi = getTeamRoi($rankedUser->id);


                    $effectiveRoi = min($rankedUserRoi, $remainingLegRoi);

                    if ($rankedUser->level >= $userRank) {

                        $teamRoi -= $effectiveRoi;
                        $remainingLegRoi -= $effectiveRoi;

                    } else {

                        $diff = $userPercent - $rankPercentage[$rankedUser->level];


                        if ($diff > 0) {
                            $give = ($effectiveRoi * $diff / 100);
                            $distributeAmount += $give;
                        }

                        $remainingLegRoi -= $effectiveRoi;
                    }


                    $deductedTeamIds[] = $rankedUser->id;
                }

                if ($remainingLegRoi > 0) {
                    $give = ($remainingLegRoi * $userPercent / 100);
                    $distributeAmount += $give;
                }


                $teamRoi -= $legRoi;

            }

            if ($teamRoi > 0) {
                $give = ($teamRoi * $userPercent / 100);
                $distributeAmount += $give;
            }


            $roi = [
                'user_id' => $user->id,
                'amount' => round($distributeAmount, 6),
                'tag' => "DIFF-TEAM-BONUS",
                'refrence' => $user->rank_id,
                'refrence_id' => $teamRoi,
                'created_on' => now(),
            ];


            earningLogsModel::insert($roi);

            DB::statement("UPDATE users 
                           SET rank_bonus = IFNULL(rank_bonus, 0) + {$roi['amount']} 
                           WHERE id = {$user->id}");

        }
    }

    public function uplineBonus(Request $request)
    {
        $rtxPrice = rtxPrice();

        $uplineBonusUsers = array();

        $users = usersModel::whereRaw(" active_direct >= 8 and direct_business >= ".(8000 / $rtxPrice))->get()->toArray();
        
        foreach ($users as $key => $value) {
            $getActiveDirects = usersModel::selectRaw("IFNULL(SUM(user_plans.amount) ,0) as db, users.id")->join('user_plans', 'user_plans.user_id', '=', 'users.id')->where(['sponser_id' => $value['id']])->groupBy("users.id")->get()->toArray();

            $criteriaMatch = 0;

            foreach ($getActiveDirects as $gadk => $gadv) {
                // $unstake1 = unstakedAmount($gadv['id'], 1);
                // $unstake2 = unstakedAmount($gadv['id'], 2);
                // $unstake3 = unstakedAmount($gadv['id'], 3);
                // $stakeAmount = ($gadv['db'] - $unstake1 - $unstake2 - $unstake3);
                $stakeAmount = getUserStakeAmount($gadv['id']);
                if (($stakeAmount * $rtxPrice) >= 1000) {
                    $criteriaMatch++;
                }
            }

            if ($criteriaMatch >= 8) {
                $checkInvestment = userPlansModel::selectRaw("SUM(amount) as investment")->where(['user_id' => $value['id']])->get()->toArray();
                // $unstake1 = unstakedAmount($value['id'], 1);
                // $unstake2 = unstakedAmount($value['id'], 2);
                // $unstake3 = unstakedAmount($value['id'], 3);
                // $stakeAmount = ($checkInvestment['0']['investment'] - $unstake1 - $unstake2 - $unstake3);
                $stakeAmount = getUserStakeAmount($value['id']);
                if(($stakeAmount * $rtxPrice) >= 3000)
                {
                    $uplineBonusUsers[$value['sponser_id']][] = $value['id'];
                }
            }
        }

        foreach($uplineBonusUsers as $sponser => $users) {

            $getEarnings = earningLogsModel::selectRaw("IFNULL(SUM(amount), 0) as earnings")
                ->where('user_id', $sponser)
                ->where('isCount', 0)
                ->where('tag', '!=', 'UPLINE-BONUS')
                ->first();

            $getLevelEarnings = levelEarningLogsModel::selectRaw("IFNULL(SUM(amount), 0) as earnings")
                ->where('user_id', $sponser)
                ->where('isCount', 0)
                ->where('tag', '!=', 'UPLINE-BONUS')
                ->first();

            $totalEarnings = $getEarnings->earnings + $getLevelEarnings->earnings;

            if ($totalEarnings > 0) {
                $bonusPool = $totalEarnings * 0.05;
                $userCount = count($users);
                $bonusPerUser = $userCount > 0 ? ($bonusPool / $userCount) : 0;

                foreach ($users as $userId) {
                    if($bonusPerUser > 0)
                    {
                        $roi = [
                            'user_id' => $userId,
                            'amount' => $bonusPerUser,
                            'tag' => 'UPLINE-BONUS',
                            'refrence' => $totalEarnings,
                            'refrence_id' => $sponser,
                            'isCount' => 1,
                            'created_on' => now()
                        ];

                        earningLogsModel::insert($roi);

                        DB::statement("UPDATE users SET direct_income = IFNULL(direct_income, 0) + {$bonusPerUser} WHERE id = '{$userId}'");
                    }
                }
            }
        }

    }

    public function dailyPoolRelease(Request $request)
    {
        $rtxPrice = rtxPrice();

        $qualifiedUsers = DB::table('user_plans')
            ->whereRaw('(amount * coin_price) >= 100')
            ->whereRaw('user_id in (52738,52741,52747,52750,52753,52758,52776,52785,52805,52726,52735)')
            ->pluck('user_id')
            ->unique()
            ->toArray();

        $getPoolAmount = withdrawModel::selectRaw("IFNULL(SUM(daily_pool_amount), 0) as daily_pool")
            ->whereRaw("DATE_FORMAT(created_on, '%Y-%m-%d') = ?", [date('Y-m-d', strtotime('-1 day'))])
            ->get()
            ->toArray();

        $poolAmount = $getPoolAmount['0']['daily_pool']; // Example daily pool amount

        if ($poolAmount > 0) {
            if (count($qualifiedUsers) > 11) {
                $winners = collect($qualifiedUsers)->random(11);
                $amountPerWinner = $poolAmount / 11;
            } else {
                $winners = $qualifiedUsers;
                $amountPerWinner = $poolAmount / count($qualifiedUsers);
            }

            foreach ($winners as $winnerId) {
                $roi = array();
                $roi['user_id'] = $winnerId;
                $roi['amount'] = $amountPerWinner;
                $roi['tag'] = "DAILY-POOL";
                $roi['refrence'] = $rtxPrice;
                $roi['refrence_id'] = "-";
                $roi['isCount'] = "1";
                // $roi['isSynced'] = "1";
                $roi['created_on'] = '2025-09-06 23:01:01';

                // echo "--------------------------------".PHP_EOL;
                // echo $roi['amount']."-".$winnerId.PHP_EOL;
                // echo "--------------------------------".PHP_EOL;

                earningLogsModel::insert($roi);

                DB::statement("UPDATE users set royalty = (IFNULL(royalty,0) + (" . $roi['amount'] . ")) where id = '" . $winnerId . "'");
            }
        }
    }

    public function monthlyPoolRelease(Request $request)
    {
        $rtxPrice = rtxPrice();
        $month = date('Y-m', strtotime('-1 month'));

        $poolAmount = withdrawModel::whereRaw("DATE_FORMAT(created_on, '%Y-%m') = ?", [$month])
            ->sum('monthly_pool_amount');

        if ($poolAmount > 0) {

            // Inner query: Top 50 investments for the month
            $innerQuery = userPlansModel::select([
                    'user_id', 'amount',
                    DB::raw("(amount * coin_price) as investment"),
                    DB::raw("(SELECT wallet_address FROM users WHERE users.id = user_plans.user_id) as wallet_address"),
                ])
                ->whereBetween('created_on', ['2025-09-01 16:30:01', '2025-10-01 16:29:59'])
                ->orderByRaw("CAST((amount * coin_price) AS UNSIGNED) DESC")
                ->limit(50);

            // Outer query: Group by wallet_address, order by investment, limit 31
            $data = DB::table(DB::raw("({$innerQuery->toSql()}) as monthly_pool"))
                ->mergeBindings($innerQuery->getQuery())
                ->select('*')
                ->groupBy('wallet_address')
                ->orderByDesc('investment')
                ->limit(31)
                ->get();

            // If no valid users found, return
            if ($data->isEmpty()) {
                return response()->json(['message' => 'No eligible users found.'], 200);
            }

            $seventyPercent = $poolAmount * 0.7;
            $thirtyPercent = $poolAmount * 0.3;
            $thirtyUserShare = $seventyPercent / 30;

            // Distribute 70% to the top user
            $topUser = $data->first();

            earningLogsModel::insert([
                'user_id' => $topUser->user_id,
                'amount' => $thirtyPercent,
                'tag' => 'MONTHLY-POOL',
                'refrence' => $topUser->amount,
                'refrence_id' => '-',
                'isCount' => 1,
                'isSynced' => 1,
                'created_on' => now(),
            ]);

            DB::statement("UPDATE users SET royalty = IFNULL(royalty, 0) + ? WHERE id = ?", [
                $thirtyPercent, $topUser->user_id
            ]);

            // Distribute 30% equally among next 30 users
            $otherUsers = $data->slice(1); // Exclude the top user

            $totalInvestments = 0;

            foreach ($otherUsers as $user) {
                $totalInvestments += $user->amount;
            }

            $distributePerRtx = ($seventyPercent / $totalInvestments);

            foreach ($otherUsers as $user) {
                $calculateAmount = ($distributePerRtx * $user->amount);
                earningLogsModel::insert([
                    'user_id' => $user->user_id,
                    'amount' => $calculateAmount,
                    'tag' => 'MONTHLY-POOL',
                    'refrence' => $user->amount,
                    'refrence_id' => '-',
                    'isCount' => 1,
                    'isSynced' => 1,
                    'created_on' => now(),
                ]);

                DB::statement("UPDATE users SET royalty = IFNULL(royalty, 0) + ? WHERE id = ?", [
                    $calculateAmount, $user->user_id
                ]);
            }

            return response()->json(['message' => 'Monthly pool released successfully.'], 200);
        }

        return response()->json(['message' => 'No pool amount to distribute.'], 200);
    }


    public function roiRelease(Request $request)
    {
        /*
         * R1 :: This is to release ROI manually for DATE TIME AND SKIP RECENT STAKED PLANS
         */
        // Log::channel('roi_release')->info("In roiRelease... for 2025-11-19 20:01:01");
        // Log::channel('roi_release')->info("In roiRelease... for 2025-11-20 08:01:01");
        // $cutoff = Carbon::parse('2025-11-20 08:01:01')->subHours(12);

        $entryDate = date('Y-m-d H:i:s'); // :: R1

        $packages = userPlansModel::where('status', 1)
                                        ->whereRaw('roi > 0')
                                        ->where('created_on', '<', Carbon::now()->subHours(12))
                                        // ->where('created_on', '<', $cutoff) // :: R1
                                        ->select(
                                            'user_id',
                                            'package_id',
                                            'amount as amount',
                                            'compound_amount as compound_amount',
                                            'id as id',
                                            'status as status',
                                            'roi as roi',
                                            'lock_period as lock_period',
                                            'contract_stakeid as contract_stakeid'
                                        )
                                        ->orderByDesc('id')
                                        ->get()
                                        ->toArray();

        earningLogsModel::where(['isCount' => 0])->update(['isCount' => 1]);

        levelEarningLogsModel::where(['isCount' => 0])->update(['isCount' => 1]);

        DB::statement("UPDATE users set daily_roi = 0");

        $levelRoi = levelRoiModel::where(['status' => 1])->get()->toArray();

        $roiLevel = array();
        
        foreach ($levelRoi as $key => $value) {
            $roiLevel[$value['level']] = $value['percentage'];
        }

        foreach ($packages as $key => $value) {

            // 1.0 If the latest stake on this package happened ON/AFTER lastCutoff,
            // Skip if last stake is on/after the 12h cutoff for this release
            // $lastStakeOn = isset($value['last_stake_on']) ? Carbon::parse($value['last_stake_on'], 'Asia/Kolkata') : null;
            // if ($lastStakeOn && $lastStakeOn->greaterThanOrEqualTo($lastCutoff)) {
            //     continue;
            // }

            $ogRoi = $value['roi'];
            $packageId = $value['package_id'];
            $lock_period = $value['lock_period'];
            $contract_stakeid = $value['contract_stakeid'];
            
            $unstakeAmount = unstakedAmountContractStackeid($value['user_id'], $packageId, $contract_stakeid);
            $claimRoiAmount = claimRoiAmountContractStackeid($value['user_id'], $packageId, $contract_stakeid);
            
            // echo "unstakeAmount:=".$unstakeAmount.PHP_EOL;
            // echo "claimRoiAmount:=".$claimRoiAmount.PHP_EOL;

            $value['compound_amount'] = 
                ($value['compound_amount'] - $claimRoiAmount < 0)
                    ? 0
                    : ($value['compound_amount'] - $claimRoiAmount);
            
            // echo "value[compound_amount]:=".$value['compound_amount'].PHP_EOL;

            $amount = ($value['amount'] + $value['compound_amount']);

            // echo "Amount+compound_amount:=".$amount.PHP_EOL;

            $amount = ($amount - $unstakeAmount);

            // echo "Amount-unstakeAmount:=".$amount.PHP_EOL;

            $user_id = $value['user_id'];
            $investment_id = $value['id'];

            // $roiUser = usersModel::select('refferal_code')->where(['id' => $user_id])->get()->toArray();
            $roiUser = usersModel::select('refferal_code')->where(['id' => $user_id])->first(); //,'sponser_id','sponser_code'
            $refCode = $roiUser ? $roiUser->refferal_code : null;
            
            $today = date('Y-m-d');

            if($amount < 1){
                continue;
            }

            // echo $amount ."*". $ogRoi.PHP_EOL;
            $roi_amount = round($final_amount = ($amount * $ogRoi) / 100, 6);

            if ($roi_amount <= 0) {
                continue;
            }

            $roi = array();

            $roi['user_id'] = $user_id;
            $roi['amount'] = $roi_amount;
            $roi['tag'] = "ROI";
            $roi['refrence'] = $amount;
            $roi['refrence_id'] = $packageId;
            $roi['contract_stakeid'] = $contract_stakeid;
            $roi['lock_period'] = $lock_period;
            $roi['created_on'] = $entryDate;

            earningLogsModel::insert($roi);

            DB::statement("UPDATE users set roi_income = (IFNULL(roi_income,0) + ($roi_amount)), daily_roi = (IFNULL(daily_roi,0) + ($roi_amount)) where id = '" . $user_id . "'");

            userPlansModel::where(['id' => $investment_id])->update(['return' => DB::raw('`return` + ' . $roi_amount), 'compound_amount' => DB::raw('`compound_amount` + ' . $roi_amount)]);

            // roi calculation end

            //START LEADERSHIP REFERRAL INCOME (ROI-ON-ROI)
            $level1 = getRefferer($user_id);
            // echo count($level1);
            if (isset($level1['sponser_id']) && $level1['sponser_id'] > 0) {
                $this->distributeLeadershipReferralRoi($level1, 1, $roi_amount, $roiLevel, $refCode, $investment_id, $entryDate);
            }
            //END LEADERSHIP REFERRAL INCOME (ROI-ON-ROI)
        }
    }

    private function calculateROIAmount($userid, $contract_stakeid, $packageid)
    {

    }

    private function distributeLeadershipReferralRoi(array $levelNode, int $level, float $roi_amount, array $roiLevel, ?string $refCode, int $investment_id, string $entryDate): void
    {
        if (!isset($levelNode['sponser_id']) || $levelNode['sponser_id'] <= 0) return;
        $receiverId = (int)$levelNode['sponser_id'];
        if (!isUserActive($receiverId)) return;
        // if ($levelNode['level'] < $level) return;

        $pct = $roiLevel[$level] ?? 0;
        if ($pct <= 0) return;

        $amt = round(($roi_amount * $pct) / 100, 6);
        if ($amt <= 0) return;

        $log = [
            'user_id'     => $receiverId,
            'amount'      => $amt,
            'tag'         => "LEVEL{$level}-ROI",
            'refrence'    => $refCode . " - " . $roi_amount,
            'refrence_id' => $investment_id,
            'created_on'  => $entryDate, // align with release time
        ];
        levelEarningLogsModel::insert($log);

        DB::statement("
            UPDATE users
            SET level_income = IFNULL(level_income,0) + (?)
            WHERE id = ?
        ", [$amt, $receiverId]);
    }

}
