<?php

namespace App\Http\Controllers;

use App\Models\levelRoiModel;
use App\Models\packageTransaction;
use App\Models\rankingModel;
use App\Models\userPlansModel;
use App\Models\usersModel;
use App\Models\earningLogsModel;
use App\Models\user_stablebond_details;
use App\Models\rewardBonusModel;
use App\Models\withdrawModel;
use App\Models\loginLogsModel;
use App\Models\myTeamModel;
use App\Models\suspiciousStake;
use App\Models\suspiciousBalance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use function App\Helpers\is_mobile;
use function App\Helpers\getBalance;
use function App\Helpers\getIncome;
use function App\Helpers\getTeamRoi;
use function App\Helpers\rtxPrice;
use function App\Helpers\getTreasuryBalance;
use function App\Helpers\verifyRSVP;
use function App\Helpers\fetchJson;
use function App\Helpers\getUserStakeAmount;
use function App\Helpers\getUserStakedAmount;

class loginController extends Controller
{
    public function testTest()
    {
        echo "<h1>Welcome to StakeFundex</h1>";

        // $test = getUserStakeAmount(6);
        // echo $test;
        // $counts = oneActiveDirect(2);
        // echo "COunts=".$counts;
    }

    
    public function userValidate(Request $request)
    {
        $type = $request->input('type');
        if($type == "API")
        {
            $user_id = $request->input("user_id");
        }else
        {
            $user_id = $request->session()->get("user_id");
        }

        $wallet_address = $request->input('wallet_address');

        $users = usersModel::where(['wallet_address' => $wallet_address])->get()->toArray();

        if (count($users) == 0) {
            $res['status_code'] = 1;
            $res['message'] = "Wallet Address is eligeble for user.";
        } else {
            $res['status_code'] = 0;
            $res['message'] = "User already exist make login.";
        }

        return is_mobile($type, "errors.403", $res, "view");
    }

    public function login(Request $request)
    {
        $request->setLaravelSession(app('session')->driver());
        $type = $request->input('type');
        $wallet_address = $request->input('wallet_address');

        $data = usersModel::where(['wallet_address' => $wallet_address])->get()->toArray();

        $loginLogs = array();
        if (count($data) == 1) {
            $loginLogs['user_id'] = $data['0']['id'];
        } else {
            $loginLogs['user_id'] = "FAILED";
        }
        $loginLogs['login_type'] = "USER";
        $loginLogs['email'] = $wallet_address;
        $loginLogs['password'] = $wallet_address;
        $loginLogs['ip_address'] = $request->ip();
        $loginLogs['ip_address_2'] = $request->header('x-forwarded-for');
        $loginLogs['device'] = $request->header('User-Agent');
        $loginLogs['created_on'] = date('Y-m-d H:i:s');

        loginLogsModel::insert($loginLogs);

        if (!$request->session()->has('admin_user_id') && $type!='API') {
            $walletAddressScript = $request->input('walletAddressScript');
            $hashedMessageScript = $request->input('hashedMessageScript');
            $rsvScript = $request->input('rsvScript');
            $rsScript = $request->input('rsScript');
            $rScript = $request->input('rScript');


            $verifySignData = json_encode(array(
                "wallet" => $wallet_address,
                "message" => $hashedMessageScript,
                "v" => $rsvScript,
                "r" => $rScript,
                "s" => $rsScript,
            ));

            $v = verifyRSVP($verifySignData);

            // if (isset($v['result'])) {
            //     if ($v['result'] != true) {
            //         // dd($v['result']);
            //         $res['status_code'] = 0;
            //         $res['message'] = "Invalid Signature. Please try again later..";

            //         return is_mobile($type, "flogin", $res);
            //     }
            // } else {
            //     $res['status_code'] = 0;
            //     $res['message'] = "Invalid Signature. Please try again later.";

            //     return is_mobile($type, "flogin", $res);
            // }
        }

        if (count($data) == 1) {
            if ($data['0']['status'] == 1) {

                $request->session()->put('user_id', $data['0']['id']);
                $request->session()->put('email', $data['0']['email']);
                $request->session()->put('name', $data['0']['name']);
                $request->session()->put('refferal_code', $data['0']['refferal_code']);
                $request->session()->put('wallet_address', $data['0']['wallet_address']);
                $request->session()->put('rank', $data['0']['rank']);

                $res['status_code'] = 1;
                $res['message'] = "Login Successfully.";
                $res['user_id'] = $data['0']['id'];
                
                $apiBase = "http://91.243.178.30:3152/balance/" . $data['0']['wallet_address'];

                $json = fetchJson($apiBase);
                if (is_array($json)) {
                    // Safely read amounts as strings
                    $sa = (string)($json['stake']['amount']       ?? '0');
                    $la = (string)($json['lpBond']['amount']      ?? '0');
                    $ba = (string)($json['stableBond']['amount']  ?? '0');
                    $ab = (string)($json['earnings']['availableBalance']  ?? '0');

                    $ts = ($sa + $la + $ba);

                    $stakeAmount = (string) getUserStakeAmount($data['0']['id']);

                    if ((int)$stakeAmount != (int)$ts) {
                        $suspiciousData = array();
                        $suspiciousData['user_id'] = $data['0']['id'];
                        $suspiciousData['wallet_address'] = $data['0']['wallet_address'];
                        $suspiciousData['stake_amount'] = $stakeAmount;
                        $suspiciousData['contract_stake_amount'] = $ts;
                        $suspiciousData['difference'] = ($stakeAmount - $ts);

                        // Check if the record already exists by user_id
                        $existingRecord = suspiciousStake::where('user_id', $data['0']['id'])->first();

                        if ($existingRecord) {
                            // If record exists, update it
                            suspiciousStake::where('user_id', $data['0']['id'])->update($suspiciousData);
                        } else {
                            // Otherwise, insert a new record
                            suspiciousStake::insert($suspiciousData);
                        }
                    }

                    $withdraw = withdrawModel::where(['user_id' => $data['0']['id'],'withdraw_type'=>'USDT'])->orderBy('id', 'desc')->get()->toArray();

                    $withdraw_amount = 0;

                    $totalIncome = $data['0']['direct_income'] + $data['0']['level_income'] + $data['0']['rank_bonus'] + $data['0']['royalty'] + $data['0']['reward_bonus'] + $data['0']['club_bonus'];

                    foreach ($withdraw as $key => $value) {
                        if ($value['status'] == 1) {
                            $withdraw_amount += $value['amount'];
                        }
                    }

                    $availableBalance = $totalIncome - $withdraw_amount;

                    if ((int)$availableBalance != (int)$ab) {
                        $suspiciousData = array();
                        $suspiciousData['user_id'] = $data['0']['id'];
                        $suspiciousData['wallet_address'] = $data['0']['wallet_address'];
                        $suspiciousData['balance'] = $availableBalance;
                        $suspiciousData['contract_balance'] = $ab;
                        $suspiciousData['difference'] = ($availableBalance - $ab);

                        // Check if the record already exists by user_id
                        $existingRecord = suspiciousBalance::where('user_id', $data['0']['id'])->first();

                        if ($existingRecord) {
                            // If record exists, update it
                            suspiciousBalance::where('user_id', $data['0']['id'])->update($suspiciousData);
                        } else {
                            // Otherwise, insert a new record
                            suspiciousBalance::insert($suspiciousData);
                        }
                    }
                }
                
                return is_mobile($type, "errors.403", $res);
            } else {
                $res['status_code'] = 0;
                $res['message'] = "Your account is suspended by admin.";

                return is_mobile($type, "flogin", $res);
            }
        } else {
            $res['status_code'] = 0;
            $res['message'] = "User Id and Password Does Not Match.";

            return is_mobile($type, "flogin", $res);
        }
    }

    public function logout(Request $request)
    {
        $type = $request->input('type');

        $request->session()->flush();

        $res['status_code'] = 1;
        $res['message'] = "Disconnected Successfully.";

        return is_mobile($type, "fregister", $res);
    }

    public function dashboard(Request $request)
    {
        $type    = $request->input('type');

        // $user_id = $request->session()->get('user_id');
        if($type == "API")
        {
            $user_id = $request->input("user_id");
        }else
        {
            $user_id = $request->session()->get("user_id");
        }

        // Basic user check (don’t cache “not found”)
        $user = usersModel::where(['id' => $user_id])->get()->toArray();
        if (count($user) == 0) {
            $res['status_code'] = 0;
            $res['message'] = "User not found.";
            return is_mobile($type, "fregister", $res);
        }

        // Cache key + TTL (adjust as needed)
        $cacheKey = "dashboard:{$user_id}";
        $ttl = now()->addMinutes(5);

        \Illuminate\Support\Facades\Cache::forget($cacheKey);
        // Manual refresh: /dashboard?refresh=1
        if ($request->boolean('refresh')) {
        }

        try {
            $res = \Illuminate\Support\Facades\Cache::remember($cacheKey, $ttl, function () use ($user_id, $user) {
                $rtxPrice = rtxPrice();

                // Sponser
                if ($user_id == 1) {
                    $sponser = usersModel::where(['id' => 1])->get()->toArray();
                } else {
                    $sponser = usersModel::where(['id' => $user[0]['sponser_id']])->get()->toArray();
                }

                // Ranks / Levels
                $ranks  = rankingModel::get()->toArray();
                $creatorRanks  = rewardBonusModel::get()->toArray();
                $levels = levelRoiModel::get()->toArray();

                // Directs chart (last 7 days)
                $directs = usersModel::selectRaw("count(id) as directs, DATE_FORMAT(created_on, '%Y-%m-%d') as dates")
                    ->where(['sponser_id' => $user_id])
                    ->where('created_on', '>=', \Carbon\Carbon::now()->subDays(7))
                    ->groupBy(DB::raw('DATE_FORMAT(created_on, "%Y-%m-%d")'))
                    ->get()
                    ->keyBy('dates')
                    ->toArray();

                $chartDirect = [];
                for ($i = 6; $i >= 0; $i--) {
                    $date = \Carbon\Carbon::today()->subDays($i)->format('Y-m-d');
                    $chartDirect[$date] = isset($directs[$date]) ? $directs[$date]['directs'] : 0;
                }
                $directChart = array_values($chartDirect);

                // Packages
                $packages = userPlansModel::where(['user_id' => $user_id])->orderBy('id', 'desc')->get()->toArray();
                $selfInvestment = 0;
                $compoundAmount = 0;
                foreach ($packages as $p) {
                    $selfInvestment += $p['amount'];
                    $compoundAmount += $p['compound_amount'];
                }

                // Withdrawals
                $withdraw = withdrawModel::selectRaw("IFNULL(SUM(amount),0) as total_withdraw")
                    ->where(['user_id' => $user_id, 'status' => 1, 'withdraw_type' => 'USDT'])
                    ->get()->toArray();

                $unstakeAmount = withdrawModel::selectRaw("IFNULL(SUM(amount),0) as total_withdraw")
                    ->where(['user_id' => $user_id, 'status' => 1, 'withdraw_type' => 'UNSTAKE'])
                    ->get()->toArray();

                $withdrawMeta = withdrawModel::selectRaw("amount, created_on")
                    ->where(['user_id' => $user_id, 'status' => 1, 'withdraw_type' => 'UNSTAKE'])
                    ->orderBy('id', 'asc')
                    ->get()->toArray();

                // Active stake calc (original logic preserved)
                $activeStake = 0;
                $lastCreatedOn = 0;
                $totalCompoundAmount = 0;

                foreach ($withdrawMeta as $wm) {
                    $tempPackages = userPlansModel::where(['user_id' => $user_id])
                        ->where('created_on', '<=', $wm['created_on'])
                        ->where('created_on', '>=', $lastCreatedOn)
                        ->orderBy('id', 'asc')
                        ->get()->toArray();

                    foreach ($tempPackages as $tp) {
                        $activeStake += $tp['amount'];
                    }

                    $tempEarnings = earningLogsModel::selectRaw("IFNULL(SUM(amount), 0) as amount")
                        ->where(['user_id' => $user_id])
                        ->where('created_on', '<=', $wm['created_on'])
                        ->where('created_on', '>', $lastCreatedOn)
                        ->where('tag', '=', 'ROI')
                        ->get()->toArray();

                    $totalCompoundAmount += ($tempEarnings[0]['amount']);
                    if ($wm['amount'] <= $totalCompoundAmount) {
                        $totalCompoundAmount -= $wm['amount'];
                    } else {
                        $activeStake -= ($wm['amount'] - $totalCompoundAmount);
                        $totalCompoundAmount = 0;
                    }

                    $lastCreatedOn = $wm['created_on'];
                }

                if (count($withdrawMeta) == 0) {
                    $tempPackages = userPlansModel::where(['user_id' => $user_id])->orderBy('id', 'asc')->get()->toArray();
                    foreach ($tempPackages as $tp) {
                        $activeStake += $tp['amount'];
                    }
                } else {
                    $tempPackagesAfter = userPlansModel::where(['user_id' => $user_id])
                        ->where('created_on', '>', $lastCreatedOn)
                        ->orderBy('id', 'asc')
                        ->get()->toArray();
                    foreach ($tempPackagesAfter as $tp) {
                        $activeStake += $tp['amount'];
                    }
                }

                // Direct business (actual)
                $getDirects = usersModel::where(['sponser_id' => $user_id])->get()->toArray();
                $directActualBusiness = 0;
                foreach ($getDirects as $d) {
                    $directActualBusiness += getUserStakeAmount($d['id']);
                }

                // Prepare user object for response
                $userLocal = $user[0];
                $userLocal['direct_business'] = $directActualBusiness;
                $userLocal['rank'] = ($userLocal['rank_id'] == 0) ? "No Rank" : ($userLocal['rank'] ?? $userLocal['rank_id']);

                // Team ROI / rank users (kept as stub logic 0)
                $rankUsers = 0;
                $getTeamRoiLastDay = 0;

                // Two legs calculation
                $get2Legs = DB::select("SELECT (my_business + strong_business) as my_business_achieve, users.id, users.strong_business, users.refferal_code FROM users left join user_plans on users.id = user_plans.user_id where sponser_id = " . $user_id . " group by users.id order by cast(my_business_achieve as unsigned) DESC");
                $get2Legs = array_map(function ($v) { return (array) $v; }, $get2Legs);

                foreach ($get2Legs as $k2 => $v2) {
                    $userPlansAmount = userPlansModel::selectRaw("IFNULL(SUM(amount),0) as amount")
                        ->where(['user_id' => $v2['id']])
                        ->whereRaw("roi > 0 and isSynced != 2")
                        ->get()->toArray();

                    $claimedRewards = withdrawModel::selectRaw("IFNULL(SUM(amount), 0) as amount")
                        ->where('user_id', '=', $v2['id'])
                        ->where('withdraw_type', '=', "UNSTAKE")
                        ->get()->toArray();

                    $get2Legs[$k2]['my_business_achieve'] =
                        (($v2['my_business_achieve'] + $userPlansAmount[0]['amount']) - $claimedRewards[0]['amount']) < 0
                        ? 0
                        : (($v2['my_business_achieve'] + $userPlansAmount[0]['amount']) - $claimedRewards[0]['amount']);
                }

                usort($get2Legs, function ($a, $b) {
                    return ($b["my_business_achieve"] <=> $a["my_business_achieve"]);
                });

                $firstLeg = 0;
                $otherLeg = 0;
                foreach ($get2Legs as $k2 => $v2) {
                    if ($k2 == 0) {
                        $firstLeg += $v2['my_business_achieve'];
                    } else {
                        $otherLeg += $v2['my_business_achieve'];
                    }
                }

                // Pools
                $dailyPoolWinners = earningLogsModel::selectRaw("IFNULL(SUM(amount), 0) as dailyPool")
                    ->where([['tag', '=', 'DAILY-POOL'], ['user_id', '=', $user_id]])
                    ->value('dailyPool');

                $monthlyPoolWinners = earningLogsModel::selectRaw("IFNULL(SUM(amount), 0) as dailyPool")
                    ->where([['tag', '=', 'MONTHLY-POOL'], ['user_id', '=', $user_id]])
                    ->value('dailyPool');

                // Reward date window
                $rewardDate = $userLocal['created_on'];
                $durationDays = 60;
                $getLastRewardDate = earningLogsModel::where('user_id', $user_id)
                    ->where('tag', 'REWARD-BONUS')
                    ->orderBy('id', 'desc')->get()->toArray();

                if (count($getLastRewardDate)) {
                    $rewardDate = $getLastRewardDate[0]['created_on'];
                    $getRewardDays = rewardBonusModel::where(['id' => ($getLastRewardDate[0]['refrence_id'] + 1)])->get()->toArray();
                    $durationDays = count($getRewardDays) > 0 ? $getRewardDays[0]['days'] : 0;
                }

                if ($durationDays > 0) {
                    $deadline = \Carbon\Carbon::parse($rewardDate)->addDays($durationDays);
                }

                // Delhi-event flag
                $exist = user_stablebond_details::where('user_id', $user_id)->whereNotNull('rank')->first();

                
                // Build response
                $res = [];
                $res['status_code']           = 1;
                $res['message']               = "Dashboard Page.";
                $userLocal['rank_in_legs'] = myTeamModel::join('user_plans', 'user_plans.user_id', '=', 'my_team.team_id')
                        ->join('users', 'users.id', '=', 'my_team.team_id')
                        ->where('my_team.user_id', $user_id)
                        ->where('user_plans.status', 1)
                        ->where('users.level', 9)
                        ->distinct()
                        ->count('my_team.team_id');

                $directsActive100 = usersModel::select('users.id')
                    ->join('user_plans', 'user_plans.user_id', '=', 'users.id')
                    ->where('users.sponser_id', $user_id)
                    ->groupBy('users.id')
                    ->get();

                $count = 0;
                foreach ($directsActive100 as $direct) {
                    $stake = getUserStakedAmount($direct->id);
                    if ($stake * $rtxPrice >= 100) $count++;
                }

                $userLocal['active_direct'] = $count;


                $teamActive100 = myTeamModel::select('my_team.team_id')
                        ->join('user_plans', 'user_plans.user_id', '=', 'my_team.team_id')
                        ->where('my_team.user_id', $user_id)
                        ->groupBy('my_team.team_id')
                        ->get();

                $teamCount = 0;
                foreach ($teamActive100 as $team) {
                    $teamStake = getUserStakedAmount($team->team_id);
                    if ($teamStake * $rtxPrice >= 100) $teamCount++;
                }

                $userLocal['active_team'] = $teamCount;

                $res['user']                  = $userLocal;
                $res['sponser']               = $sponser[0] ?? [];
                $res['ranks']                 = $ranks;
                $res['creator_ranks']         = $creatorRanks;
                $res['levels']                = $levels;
                $res['chartDirect']           = $directChart;
                $res['my_packages']           = $packages;
                $res['total_withdraw']        = $withdraw[0]['total_withdraw'] ?? 0;
                $res['total_unstake_amount']  = $unstakeAmount[0]['total_withdraw'] ?? 0;
                $res['self_investment']       = $selfInvestment;
                $res['compound_amount']       = $compoundAmount;
                $res['activeStake']           = $activeStake;
                $res['available_balance']     = getBalance($user_id);
                $res['total_income']          = getIncome($user_id);
                $res['rtxPrice']              = $rtxPrice;
                $res['treasuryBalance']       = getTreasuryBalance();
                $res['teamRoi']               = $getTeamRoiLastDay;
                $res['rankUser']              = $rankUsers;
                $res['nonRankUser']           = ($userLocal['my_team'] - $rankUsers);
                $res['firstLeg']              = $firstLeg;
                $res['otherLeg']              = $otherLeg;
                $res['dailyPoolWinners']      = $dailyPoolWinners;
                $res['monthlyPoolWinners']    = $monthlyPoolWinners;
                if (!empty($deadline)) {
                    $res['rewardDate'] = $deadline;
                }
                $res['delhi-event']           = $exist ? 1 : 0;

                // New key added to check if user has stacked True/False
                $isStaked  = userPlansModel::where('user_id', $user_id)
                                                ->where('amount', '>', 0)
                                                ->where('isSynced', '!=', 2)
                                                ->exists();
                $res['isStaked']              = $isStaked;

                $fetchDiscountJson = file_get_contents("http://91.243.178.37:3255/api/24h");

                $fetchDiscount = json_decode($fetchDiscountJson, true);

                if($fetchDiscount['data']['priceChange24hPercent'] < 0)
                {
                    $res['discount'] = abs($fetchDiscount['data']['priceChange24hPercent']);
                }else
                {
                    $res['discount'] = 0;
                }

                $res['treasury_history'] = DB::table('treasury_history')
    ->pluck('treasuryBalance')
    ->map(fn($value) => (float) $value) // optional: cast to float
    ->toArray();

                return $res;
            });
        } catch (\Exception $e) {
            \Log::warning('Cache failed for dashboard, executing without cache: ' . $e->getMessage());
            
            $rtxPrice = rtxPrice();

            // Sponser
            if ($user_id == 1) {
                $sponser = usersModel::where(['id' => 1])->get()->toArray();
            } else {
                $sponser = usersModel::where(['id' => $user[0]['sponser_id']])->get()->toArray();
            }

            // Ranks / Levels
            $ranks  = rankingModel::get()->toArray();
            $creatorRanks  = rewardBonusModel::get()->toArray();
            $levels = levelRoiModel::get()->toArray();

            // Directs chart (last 7 days)
            $directs = usersModel::selectRaw("count(id) as directs, DATE_FORMAT(created_on, '%Y-%m-%d') as dates")
                ->where(['sponser_id' => $user_id])
                ->where('created_on', '>=', \Carbon\Carbon::now()->subDays(7))
                ->groupBy(DB::raw('DATE_FORMAT(created_on, "%Y-%m-%d")'))
                ->get()
                ->keyBy('dates')
                ->toArray();

            $chartDirect = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = \Carbon\Carbon::today()->subDays($i)->format('Y-m-d');
                $chartDirect[$date] = isset($directs[$date]) ? $directs[$date]['directs'] : 0;
            }
            $directChart = array_values($chartDirect);

            // Packages
            $packages = userPlansModel::where(['user_id' => $user_id])->orderBy('id', 'desc')->get()->toArray();
            $selfInvestment = 0;
            $compoundAmount = 0;
            foreach ($packages as $p) {
                $selfInvestment += $p['amount'];
                $compoundAmount += $p['compound_amount'];
            }

            // Withdrawals
            $withdraw = withdrawModel::selectRaw("IFNULL(SUM(amount),0) as total_withdraw")
                ->where(['user_id' => $user_id, 'status' => 1, 'withdraw_type' => 'USDT'])
                ->get()->toArray();

            $unstakeAmount = withdrawModel::selectRaw("IFNULL(SUM(amount),0) as total_withdraw")
                ->where(['user_id' => $user_id, 'status' => 1, 'withdraw_type' => 'UNSTAKE'])
                ->get()->toArray();

            $withdrawMeta = withdrawModel::selectRaw("amount, created_on")
                ->where(['user_id' => $user_id, 'status' => 1, 'withdraw_type' => 'UNSTAKE'])
                ->orderBy('id', 'asc')
                ->get()->toArray();

            // Active stake calc (original logic preserved)
            $activeStake = 0;
            $lastCreatedOn = 0;
            $totalCompoundAmount = 0;

            foreach ($withdrawMeta as $wm) {
                $tempPackages = userPlansModel::where(['user_id' => $user_id])
                    ->where('created_on', '<=', $wm['created_on'])
                    ->where('created_on', '>=', $lastCreatedOn)
                    ->orderBy('id', 'asc')
                    ->get()->toArray();

                foreach ($tempPackages as $tp) {
                    $activeStake += $tp['amount'];
                }

                $tempEarnings = earningLogsModel::selectRaw("IFNULL(SUM(amount), 0) as amount")
                    ->where(['user_id' => $user_id])
                    ->where('created_on', '<=', $wm['created_on'])
                    ->where('created_on', '>', $lastCreatedOn)
                    ->where('tag', '=', 'ROI')
                    ->get()->toArray();

                $totalCompoundAmount += ($tempEarnings[0]['amount']);
                if ($wm['amount'] <= $totalCompoundAmount) {
                    $totalCompoundAmount -= $wm['amount'];
                } else {
                    $activeStake -= ($wm['amount'] - $totalCompoundAmount);
                    $totalCompoundAmount = 0;
                }

                $lastCreatedOn = $wm['created_on'];
            }

            if (count($withdrawMeta) == 0) {
                $tempPackages = userPlansModel::where(['user_id' => $user_id])->orderBy('id', 'asc')->get()->toArray();
                foreach ($tempPackages as $tp) {
                    $activeStake += $tp['amount'];
                }
            } else {
                $tempPackagesAfter = userPlansModel::where(['user_id' => $user_id])
                    ->where('created_on', '>', $lastCreatedOn)
                    ->orderBy('id', 'asc')
                    ->get()->toArray();
                foreach ($tempPackagesAfter as $tp) {
                    $activeStake += $tp['amount'];
                }
            }

            // Direct business (actual)
            $getDirects = usersModel::where(['sponser_id' => $user_id])->get()->toArray();
            $directActualBusiness = 0;
            foreach ($getDirects as $d) {
                $directActualBusiness += getUserStakeAmount($d['id']);
            }

            // Prepare user object for response
            $userLocal = $user[0];
            $userLocal['direct_business'] = $directActualBusiness;
            $userLocal['rank'] = ($userLocal['rank_id'] == 0) ? "No Rank" : ($userLocal['rank'] ?? $userLocal['rank_id']);

            // Team ROI / rank users (kept as stub logic 0)
            $rankUsers = 0;
            $getTeamRoiLastDay = 0;

            // Two legs calculation
            $get2Legs = DB::select("SELECT (my_business + strong_business) as my_business_achieve, users.id, users.strong_business, users.refferal_code FROM users left join user_plans on users.id = user_plans.user_id where sponser_id = " . $user_id . " group by users.id order by cast(my_business_achieve as unsigned) DESC");
            $get2Legs = array_map(function ($v) { return (array) $v; }, $get2Legs);

            foreach ($get2Legs as $k2 => $v2) {
                $userPlansAmount = userPlansModel::selectRaw("IFNULL(SUM(amount),0) as amount")
                    ->where(['user_id' => $v2['id']])
                    ->whereRaw("roi > 0 and isSynced != 2")
                    ->get()->toArray();

                $claimedRewards = withdrawModel::selectRaw("IFNULL(SUM(amount), 0) as amount")
                    ->where('user_id', '=', $v2['id'])
                    ->where('withdraw_type', '=', "UNSTAKE")
                    ->get()->toArray();

                $get2Legs[$k2]['my_business_achieve'] =
                    (($v2['my_business_achieve'] + $userPlansAmount[0]['amount']) - $claimedRewards[0]['amount']) < 0
                    ? 0
                    : (($v2['my_business_achieve'] + $userPlansAmount[0]['amount']) - $claimedRewards[0]['amount']);
            }

            usort($get2Legs, function ($a, $b) {
                return ($b["my_business_achieve"] <=> $a["my_business_achieve"]);
            });

            $firstLeg = 0;
            $otherLeg = 0;
            foreach ($get2Legs as $k2 => $v2) {
                if ($k2 == 0) {
                    $firstLeg += $v2['my_business_achieve'];
                } else {
                    $otherLeg += $v2['my_business_achieve'];
                }
            }

            // Pools
            $dailyPoolWinners = earningLogsModel::selectRaw("IFNULL(SUM(amount), 0) as dailyPool")
                ->where([['tag', '=', 'DAILY-POOL'], ['user_id', '=', $user_id]])
                ->value('dailyPool');

            $monthlyPoolWinners = earningLogsModel::selectRaw("IFNULL(SUM(amount), 0) as dailyPool")
                ->where([['tag', '=', 'MONTHLY-POOL'], ['user_id', '=', $user_id]])
                ->value('dailyPool');

            // Reward date window
            $rewardDate = $userLocal['created_on'];
            $durationDays = 60;
            $getLastRewardDate = earningLogsModel::where('user_id', $user_id)
                ->where('tag', 'REWARD-BONUS')
                ->orderBy('id', 'desc')->get()->toArray();

            if (count($getLastRewardDate)) {
                $rewardDate = $getLastRewardDate[0]['created_on'];
                $getRewardDays = rewardBonusModel::where(['id' => ($getLastRewardDate[0]['refrence_id'] + 1)])->get()->toArray();
                $durationDays = count($getRewardDays) > 0 ? $getRewardDays[0]['days'] : 0;
            }

            if ($durationDays > 0) {
                $deadline = \Carbon\Carbon::parse($rewardDate)->addDays($durationDays);
            }

            // Delhi-event flag
            $exist = user_stablebond_details::where('user_id', $user_id)->whereNotNull('rank')->first();

            
            // Build response
            $res = [];
            $res['status_code']           = 1;
            $res['message']               = "Dashboard Page.";

            $userLocal['rank_in_legs'] = myTeamModel::join('user_plans', 'user_plans.user_id', '=', 'my_team.team_id')
                        ->join('users', 'users.id', '=', 'my_team.team_id')
                        ->where('my_team.user_id', $user_id)
                        ->where('user_plans.status', 1)
                        ->where('users.level', 9)
                        ->distinct()
                        ->count('my_team.team_id');

            $directsActive100 = usersModel::select('users.id')
                    ->join('user_plans', 'user_plans.user_id', '=', 'users.id')
                    ->where('users.sponser_id', $user_id)
                    ->groupBy('users.id')
                    ->get();

            $count = 0;
            foreach ($directsActive100 as $direct) {
                $stake = getUserStakedAmount($direct->id);
                if ($stake * $rtxPrice >= 100) $count++;
            }

            $userLocal['active_direct'] = $count;


            $teamActive100 = myTeamModel::select('my_team.team_id')
                    ->join('user_plans', 'user_plans.user_id', '=', 'my_team.team_id')
                    ->where('my_team.user_id', $user_id)
                    ->groupBy('my_team.team_id')
                    ->get();

            $teamCount = 0;
            foreach ($teamActive100 as $team) {
                $teamStake = getUserStakedAmount($team->team_id);
                if ($teamStake * $rtxPrice >= 100) $teamCount++;
            }

            $userLocal['active_team'] = $teamCount;

            $res['user']                  = $userLocal;
            $res['sponser']               = $sponser[0] ?? [];
            $res['ranks']                 = $ranks;
            $res['creator_ranks']         = $creatorRanks;
            $res['levels']                = $levels;
            $res['chartDirect']           = $directChart;
            $res['my_packages']           = $packages;
            $res['total_withdraw']        = $withdraw[0]['total_withdraw'] ?? 0;
            $res['total_unstake_amount']  = $unstakeAmount[0]['total_withdraw'] ?? 0;
            $res['self_investment']       = $selfInvestment;
            $res['compound_amount']       = $compoundAmount;
            $res['activeStake']           = $activeStake;
            $res['available_balance']     = getBalance($user_id);
            $res['total_income']          = getIncome($user_id);
            $res['rtxPrice']              = $rtxPrice;
            $res['treasuryBalance']       = getTreasuryBalance();
            $res['teamRoi']               = $getTeamRoiLastDay;
            $res['rankUser']              = $rankUsers;
            $res['nonRankUser']           = ($userLocal['my_team'] - $rankUsers);
            $res['firstLeg']              = $firstLeg;
            $res['otherLeg']              = $otherLeg;
            $res['dailyPoolWinners']      = $dailyPoolWinners;
            $res['monthlyPoolWinners']    = $monthlyPoolWinners;
            if (!empty($deadline)) {
                $res['rewardDate'] = $deadline;
            }
            $res['delhi-event']           = $exist ? 1 : 0;
            $fetchDiscountJson = file_get_contents("http://91.243.178.37:3255/api/24h");

            
            $fetchDiscount = json_decode($fetchDiscountJson, true);

            if($fetchDiscount['data']['priceChange24hPercent'] < 0)
            {
                $res['discount'] = abs($fetchDiscount['data']['priceChange24hPercent']);
            }else
            {
                $res['discount'] = 0;
            }

            $res['treasury_history'] = DB::table('treasury_history')
    ->pluck('treasuryBalance')
    ->map(fn($value) => (float) $value) // optional: cast to float
    ->toArray();
        }

        return is_mobile($type, "pages.index", $res, "view");
    }

    public function activeTrades(Request $request)
    {
        $type = "API";


        $res['status_code'] = 1;
        $res['message'] = "Active Trades.";

        return is_mobile($type, "pages.index", $res, "view");
    }

    public function treasury_history(Request $request)
    {
        $type = "API";

        $res['treasury_history'] = DB::table('treasury_history')
    ->pluck('treasuryBalance')
    ->map(fn($value) => (float) $value) // optional: cast to float
    ->toArray();

        $res['status_code'] = 1;
        $res['message'] = "Active Trades.";

        return is_mobile($type, "pages.index", $res, "view");
    }

    public function toastDetails(Request $request)
    {
        $type = "API";

        $toaster = DB::table('toaster')
            ->where(['status' => 0])
            ->orderBy('id', 'desc')   // First order by 'id' descending
            ->orderBy('priority', 'desc')   // Second order by 'priority' descending
            ->first();

        if (count($toaster) > 0) {
            DB::table('toaster')->where(['id' => $toaster->id])->update(['status' => 1]);
            $res['toaster'] = $toaster;
        }

        $res['status_code'] = 1;
        $res['message'] = "Active Toasts.";

        return is_mobile($type, "pages.index", $res, "view");
    }

    public function referralCodeDetails(Request $request)
    {
        $type = "API";
        $refferal_code = $request->input('refferal_code');

        if (!empty($refferal_code)) {
            $data = usersModel::select('wallet_address')->where(['refferal_code' => $refferal_code])->get()->toArray();

            if (count($data) > 0) {
                $res['status_code'] = 1;
                $res['message'] = "Successfully.";
                $res['data'] = $data['0']['wallet_address'];
            } else {
                $res['status_code'] = 0;
                $res['message'] = "Invalid user.";
            }
        } else {
            $res['status_code'] = 0;
            $res['message'] = "Parameter missing.";
        }

        return is_mobile($type, "pages.index", $res, "view");
    }

    function user_details_store(Request $request){
        $type = $request->input('type');
        $user_id = $request->session()->get('user_id');
        $country = $request->input('country');
        $tag = $request->input('tag');
        $region = $request->input('region');
        $fname = $request->input('fname');
        $lname = $request->input('lname');
        $email = $request->input('email');
        $wapp = $request->input('wapp');
        $pass_num = $request->input('pass_num');
        $pass_issue_date = $request->input('pass_issue_date');
        $pass_expiry_date = $request->input('pass_expiry_date');
 
        $validator = Validator::make($request->all(), [
            'country'           => 'required|string',
            'tag'               => 'required|string',
            'region'            => 'required|string',
            'fname'             => 'required|string',
            'lname'             => 'required|string',
            'email'             => 'required|email',
            'wapp'              => 'required|string',
            'pass_num'          => 'required|string',
            'pass_front'          => 'required|file|max:2048',
            'pass_back'          => 'required|file|max:2048',
            'pass_issue_date'   => 'required|date',
            'pass_expiry_date'  => 'required|date',
        ]);

        if ($validator->fails()) {
            $res['status_code'] = 0;
            // $res['message'] = "Details Required";
            $res['message'] = $validator->errors()->first();

            return is_mobile($type, "stablebonds", $res);
        }
        $validator1 = Validator::make($request->all(), [
            'pass_front'          => 'file|max:2048',
            'pass_back'          => 'file|max:2048',
        ]);

        if ($validator1->fails()) {
            $res['status_code'] = 0;
            $res['message'] = implode(', ', $validator->errors()->all());

            return is_mobile($type, "stablebonds", $res);
        }
        $exist= user_stablebond_details::where('user_id',$user_id)->where('tag',$tag)->first();
        if ($exist) {
            $res['status_code'] = 0;
            $res['message'] = "Already Submitted";

            return is_mobile($type, "stablebonds", $res);
        }

        $user_plans = array();

        $allowedfileExtension = ['jpeg', 'jpg', 'png'];

        $pass_front_file = $request->file('pass_front');

        if (isset($pass_front_file)) {
            $filename = $pass_front_file->getClientOriginalName();
            $extension = $pass_front_file->getClientOriginalExtension();
            $check = in_array($extension, $allowedfileExtension);
            if (!$check) {
                $res['status_code'] = 0;
                $res['message'] = "Only jpeg and png files are supported ";

                return is_mobile($type, "stablebonds", $res);
            }

            $pass_front_file_name = "";
            if ($request->hasFile('pass_front')) {
                $pass_front_file = $request->file('pass_front');
                $originalname = $pass_front_file->getClientOriginalName();
                $og_name = "pass_front" . '_' . date('YmdHis');
                $ext = \File::extension($originalname);
                $pass_front_file_name = $og_name . '.' . $ext;
                $path = $pass_front_file->storeAs('public/', $pass_front_file_name);
                $user_plans['passport_pic_front'] = $pass_front_file_name;
            }
        }
        $pass_back_file = $request->file('pass_back');

        if (isset($pass_back_file)) {
            $filename = $pass_back_file->getClientOriginalName();
            $extension = $pass_back_file->getClientOriginalExtension();
            $check = in_array($extension, $allowedfileExtension);

            if (!$check) {
                $res['status_code'] = 0;
                $res['message'] = "Only jpeg and png files are supported ";

                return is_mobile($type, "stablebonds", $res);
            }

            $pass_back_file_name = "";
            if ($request->hasFile('pass_back')) {
                $pass_back_file = $request->file('pass_back');
                $originalname = $pass_back_file->getClientOriginalName();
                $og_name = "pass_back" . '_' . date('YmdHis');
                $ext = \File::extension($originalname);
                $pass_back_file_name = $og_name . '.' . $ext;
                $path = $pass_back_file->storeAs('public/', $pass_back_file_name);
                $user_plans['passport_pic_back'] = $pass_back_file_name;
            }
        }

        $user_plans['user_id'] = $user_id;
        $user_plans['country'] = $country;
        $user_plans['tag'] = $tag;
        $user_plans['region'] = $region;
        $user_plans['firstname'] = $fname;
        $user_plans['lastname'] = $lname;
        $user_plans['email'] = $email;
        $user_plans['whatapp_num'] = $wapp;
        $user_plans['passport_num'] = $pass_num;
        $user_plans['passport_issue_date'] = $pass_issue_date;
        $user_plans['passport_expiry_date'] = $pass_expiry_date;
        $user_plans['event'] = 'Thailand Event 17 August';
        user_stablebond_details::insert($user_plans);

        $res['status_code'] = 1;
        $res['message'] = "Details Added Successfully";

        return is_mobile($type, "stablebonds", $res);
    }
    function user_rank_details_store(Request $request){
        $type = $request->input('type');
        $user_id = $request->session()->get('user_id');
        $name = $request->input('name');
        $mobile = $request->input('mobile');
        $rank = $request->input('rank');
        $email = $request->input('email');

        $validator = Validator::make($request->all(), [
            'name'           => 'required|string',
            'mobile'               => 'required|string',
            'rank'             => 'required|string',
            'email'             => 'required|email',
            'address_proof'          => 'required|file|max:2048',
            'photo'          => 'required|file|max:2048',
        ]);

        if ($validator->fails()) {
            $res['status_code'] = 0;
            // $res['message'] = "Details Required";
            $res['message'] = $validator->errors()->first();

            return is_mobile($type, "fdashboard", $res);
        }
        $validator1 = Validator::make($request->all(), [
            'address_proof'          => 'file|max:2048',
            'photo'          => 'file|max:2048',
        ]);

        if ($validator1->fails()) {
            $res['status_code'] = 0;
            $res['message'] = implode(', ', $validator->errors()->all());

            return is_mobile($type, "fdashboard", $res);
        }
        $exist= user_stablebond_details::where('user_id',$user_id)->whereNotNull('rank')->first();
        if ($exist) {
            $res['status_code'] = 0;
            $res['message'] = "Already Submitted";

            return is_mobile($type, "fdashboard", $res);
        }

        $user_plans = array();

        $allowedfileExtension = ['jpeg', 'jpg', 'png'];

        $pass_front_file = $request->file('address_proof');

        if (isset($pass_front_file)) {
            $filename = $pass_front_file->getClientOriginalName();
            $extension = $pass_front_file->getClientOriginalExtension();
            $check = in_array($extension, $allowedfileExtension);
            if (!$check) {
                $res['status_code'] = 0;
                $res['message'] = "Only jpeg and png files are supported ";

                return is_mobile($type, "fdashboard", $res);
            }

            $pass_front_file_name = "";
            if ($request->hasFile('address_proof')) {
                $pass_front_file = $request->file('address_proof');
                $originalname = $pass_front_file->getClientOriginalName();
                $og_name = "address_proof" . '_' . date('YmdHis');
                $ext = \File::extension($originalname);
                $pass_front_file_name = $og_name . '.' . $ext;
                $path = $pass_front_file->storeAs('public/', $pass_front_file_name);
                $user_plans['address_proof'] = $pass_front_file_name;
            }
        }
        $pass_back_file = $request->file('photo');

        if (isset($pass_back_file)) {
            $filename = $pass_back_file->getClientOriginalName();
            $extension = $pass_back_file->getClientOriginalExtension();
            $check = in_array($extension, $allowedfileExtension);

            if (!$check) {
                $res['status_code'] = 0;
                $res['message'] = "Only jpeg and png files are supported ";

                return is_mobile($type, "fdashboard", $res);
            }

            $pass_back_file_name = "";
            if ($request->hasFile('photo')) {
                $pass_back_file = $request->file('photo');
                $originalname = $pass_back_file->getClientOriginalName();
                $og_name = "photo" . '_' . date('YmdHis');
                $ext = \File::extension($originalname);
                $pass_back_file_name = $og_name . '.' . $ext;
                $path = $pass_back_file->storeAs('public/', $pass_back_file_name);
                $user_plans['user_photo'] = $pass_back_file_name;
            }
        }

        $user_plans['user_id'] = $user_id;
        $user_plans['firstname'] = $name;
        $user_plans['whatapp_num'] = $mobile;
        $user_plans['email'] = $email;
        $user_plans['rank'] = $rank;
        $user_plans['event'] = 'Delhi Event 17 August';
        user_stablebond_details::insert($user_plans);

        $res['status_code'] = 1;
        $res['message'] = "Details Added Successfully";

        return is_mobile($type, "fdashboard", $res);
    }
}
