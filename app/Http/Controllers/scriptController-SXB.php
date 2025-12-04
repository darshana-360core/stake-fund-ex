<?php

namespace App\Http\Controllers;

use App\Models\earningLogsModel;
use App\Models\levelEarningLogsModel;
use App\Models\levelRoiModel;
use App\Models\myTeamModel;
use App\Models\packageModel;
use App\Models\userPlansModel;
use App\Models\usersModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use function App\Helpers\getRefferer;
use function App\Helpers\rankwiseRoiIncome;
use function App\Helpers\getUserMaxReturn;
use function App\Helpers\getRoiMaxReturn;
use function App\Helpers\getRoiIncome;
use function App\Helpers\getIncome;
use function App\Helpers\updateActiveTeam;
use function App\Helpers\updateReverseBusiness;

class scriptController extends Controller
{
    
    public function checkLevel(Request $request)
    {
        $investment = userPlansModel::where(['isCount' => 0])->orderBy('id', 'asc')->get()->toArray();

        foreach ($investment as $key => $value) {
            updateReverseBusiness($value['user_id'], $value['amount']);

            $checkIfFirstPackage = userPlansModel::where('user_id', $value['user_id'])->get()->toArray();

            if (count($checkIfFirstPackage) == 1) {
                updateActiveTeam($value['user_id']);
            }

            userPlansModel::where(['id' => $value['id']])->update(['isCount' => 1]);
        }

        $users = usersModel::where(['status' => 1])->orderBy('id', 'asc')->get()->toArray();

        foreach ($users as $key => $value) {
            $activeDirectCount = usersModel::join('user_plans', 'user_plans.user_id', '=', 'users.id')
                ->where(['users.sponser_id' => $value['id']])
                ->selectRaw('COUNT(DISTINCT users.id) as count')
                ->first()
                ->count;

            $levelsOpen = levelRoiModel::select('id')->where(['direct' => $activeDirectCount])->orderBy('id', 'desc')->get()->toArray();

            if (count($levelsOpen) > 0) {
                usersModel::where(['id' => $value['id']])->update(['level' => $levelsOpen['0']['id']]);
            }
        }
    }

    public function activeTeamCalculate(Request $request)
    {
        usersModel::where(['status' => 1])->update(['active_team' => 0]);

        $userPlans = userPlansModel::select('user_id')->groupBy('user_id')->get()->toArray();

        foreach ($userPlans as $key => $value) {
            updateActiveTeam($value['user_id']);
        }
    }

    public function roiRelease(Request $request)
    {
        $entryDate = date('Y-m-d H:i:s');

        $packages = userPlansModel::where(['status' => 1])->whereRaw("roi > 0")->orderBy('id', 'desc')->get()->toArray();

        foreach ($packages as $key => $value) {
            $getUserRank = usersModel::select('topup_balance', 'sponser_id', 'rank_id', 'refferal_code', 'ad_viewed', 'active_direct')->where('id', $value['user_id'])->get()->toArray();

            //roi calculation start


            $amount = $value['amount'];
            $user_id = $value['user_id'];
            $investment_id = $value['id'];

            if ($getUserRank['0']['ad_viewed'] == 0) {
                continue;
            }

            if ($getUserRank['0']['ad_viewed'] == 1) {
                $ogRoi = 0.25;
            } else {
                $ogRoi = 0.5;
            }

            $levelRoi = levelRoiModel::where(['status' => 1])->get()->toArray();

            $roiUser = usersModel::where(['id' => $user_id])->get()->toArray();

            $roiLevel = array();

            foreach ($levelRoi as $key => $value) {
                $roiLevel[$value['level']] = $value['percentage'];
            }

            $roi_amount = $final_amount = ($amount * $ogRoi) / 100;

            $totalMaxReturn = getRoiMaxReturn($user_id);
            $totalIncome = getRoiIncome($user_id);
            $flush_amount = 0;

            if (($totalIncome + $roi_amount) >= $totalMaxReturn) {
                $roi_amount = ($totalMaxReturn - $totalIncome);

                $flush_amount = ($final_amount - $roi_amount);

                userPlansModel::where(['user_id' => $user_id, 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
            }

            if ($roi_amount > 0) {
                $roi = array();
                $roi['user_id'] = $user_id;
                $roi['amount'] = $roi_amount;
                $roi['flush_amount'] = $flush_amount;
                $roi['tag'] = "ROI";
                $roi['refrence'] = $ogRoi;
                $roi['refrence_id'] = $investment_id;
                $roi['created_on'] = $entryDate;

                earningLogsModel::insert($roi);

                DB::statement("UPDATE users set roi_income = (IFNULL(roi_income,0) + ($roi_amount)) where id = '" . $user_id . "'");

                userPlansModel::where(['id' => $investment_id])->update(['return' => DB::raw('`return` + ' . $roi_amount)]);
                //roi calculation end

                if ($getUserRank['0']['ad_viewed'] > 2) {
                    if (($getUserRank['0']['ad_viewed'] - 2) > $getUserRank['0']['active_direct']) {
                        $adRoi = $getUserRank['0']['active_direct'];
                    } else {
                        $adRoi = ($getUserRank['0']['ad_viewed'] - 2);
                    }

                    if ($adRoi > 0) {
                        $checkReferral = earningLogsModel::whereDate('created_on', '=', date('Y-m-d', strtotime($entryDate)))->where('user_id', $user_id)->where('tag', 'REFERRAL')->get()->toArray();

                        if (count($checkReferral) == 0) {
                            $selftPackagesAmount = userPlansModel::where(['user_id' => $user_id, 'status' => 1])->sum('amount');

                            $adRoi = $adRoi * 0.25;
                            //referral income
                            $referral_amount = $final_amount = ($selftPackagesAmount * $adRoi) / 100;

                            $totalMaxReturn = getUserMaxReturn($user_id);
                            $totalIncome = getIncome($user_id);
                            $flush_amount = 0;

                            if (($totalIncome + $referral_amount) >= $totalMaxReturn) {
                                $referral_amount = ($totalMaxReturn - $totalIncome);

                                $flush_amount = ($final_amount - $referral_amount);

                                userPlansModel::where(['user_id' => $user_id, 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                            }

                            if ($referral_amount > 0) {
                                $roi = array();
                                $roi['user_id'] = $user_id;
                                $roi['amount'] = $referral_amount;
                                $roi['flush_amount'] = $flush_amount;
                                $roi['tag'] = "REFERRAL";
                                $roi['refrence'] = $adRoi;
                                $roi['refrence_id'] = $investment_id;
                                $roi['created_on'] = $entryDate;

                                earningLogsModel::insert($roi);

                                DB::statement("UPDATE users set direct_income = (IFNULL(direct_income,0) + ($referral_amount)) where id = '" . $user_id . "'");

                                userPlansModel::where(['id' => $investment_id])->update(['return' => DB::raw('`return` + ' . $referral_amount)]);

                                //level roi distribution
                                $level1 = getRefferer($user_id);
                                if (isset($level1['sponser_id']) && $level1['sponser_id'] > 0) {
                                    if ($level1['active_direct'] >= 1 && $level1['ad_viewed'] >= 1) {
                                        $level1_amount = $final_amount = ($referral_amount * $roiLevel['1']) / 100;

                                        $totalMaxReturn = getUserMaxReturn($level1['sponser_id']);
                                        $totalIncome = getIncome($level1['sponser_id']);
                                        $flush_amount = 0;

                                        if (($totalIncome + $level1_amount) >= $totalMaxReturn) {
                                            $level1_amount = ($totalMaxReturn - $totalIncome);

                                            $flush_amount = ($final_amount - $level1_amount);

                                            userPlansModel::where(['user_id' => $level1['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                        }

                                        if ($level1_amount > 0) {
                                            $level1_roi = array();
                                            $level1_roi['user_id'] = $level1['sponser_id'];
                                            $level1_roi['amount'] = $level1_amount;
                                            $level1_roi['flush_amount'] = $flush_amount;
                                            $level1_roi['tag'] = "LEVEL1-REFERRAL";
                                            $level1_roi['refrence'] = $roiUser['0']['refferal_code'];
                                            $level1_roi['refrence_id'] = $investment_id;
                                            $level1_roi['created_on'] = $entryDate;

                                            levelEarningLogsModel::insert($level1_roi);
                                            // 
                                            DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level1_amount)) where id = '" . $level1['sponser_id'] . "'");
                                        }
                                    }

                                    $level2 = getRefferer($level1['sponser_id']);
                                    if (isset($level2['sponser_id']) && $level2['sponser_id'] > 0) {
                                        if ($level2['active_direct'] >= 2 && $level2['ad_viewed'] >= 1) {
                                            $level2_amount = $final_amount = ($referral_amount * $roiLevel['2']) / 100;

                                            $totalMaxReturn = getUserMaxReturn($level2['sponser_id']);
                                            $totalIncome = getIncome($level2['sponser_id']);
                                            $flush_amount = 0;

                                            if (($totalIncome + $level2_amount) >= $totalMaxReturn) {
                                                $level2_amount = ($totalMaxReturn - $totalIncome);

                                                $flush_amount = ($final_amount - $level2_amount);

                                                userPlansModel::where(['user_id' => $level2['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                            }

                                            if ($level2_amount > 0) {
                                                $level2_roi = array();
                                                $level2_roi['user_id'] = $level2['sponser_id'];
                                                $level2_roi['amount'] = $level2_amount;
                                                $level2_roi['flush_amount'] = $flush_amount;
                                                $level2_roi['tag'] = "LEVEL2-REFERRAL";
                                                $level2_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                $level2_roi['refrence_id'] = $investment_id;
                                                $level2_roi['created_on'] = $entryDate;

                                                levelEarningLogsModel::insert($level2_roi);
                                                // 
                                                DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level2_amount)) where id = '" . $level2['sponser_id'] . "'");
                                            }
                                        }

                                        $level3 = getRefferer($level2['sponser_id']);
                                        if (isset($level3['sponser_id']) && $level3['sponser_id'] > 0) {
                                            if ($level3['active_direct'] >= 3 && $level3['ad_viewed'] >= 1) {
                                                $level3_amount = $final_amount = ($referral_amount * $roiLevel['3']) / 100;

                                                $totalMaxReturn = getUserMaxReturn($level3['sponser_id']);
                                                $totalIncome = getIncome($level3['sponser_id']);
                                                $flush_amount = 0;

                                                if (($totalIncome + $level3_amount) >= $totalMaxReturn) {
                                                    $level3_amount = ($totalMaxReturn - $totalIncome);

                                                    $flush_amount = ($final_amount - $level3_amount);

                                                    userPlansModel::where(['user_id' => $level3['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                                }

                                                if ($level3_amount > 0) {
                                                    $level3_roi = array();
                                                    $level3_roi['user_id'] = $level3['sponser_id'];
                                                    $level3_roi['amount'] = $level3_amount;
                                                    $level3_roi['flush_amount'] = $flush_amount;
                                                    $level3_roi['tag'] = "LEVEL3-REFERRAL";
                                                    $level3_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                    $level3_roi['refrence_id'] = $investment_id;
                                                    $level3_roi['created_on'] = $entryDate;

                                                    levelEarningLogsModel::insert($level3_roi);
                                                    // 
                                                    DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level3_amount)) where id = '" . $level3['sponser_id'] . "'");
                                                }
                                            }

                                            $level4 = getRefferer($level3['sponser_id']);
                                            if (isset($level4['sponser_id']) && $level4['sponser_id'] > 0) {
                                                if ($level4['active_direct'] >= 4 && $level4['ad_viewed'] >= 1) {
                                                    $level4_amount = $final_amount = ($referral_amount * $roiLevel['4']) / 100;

                                                    $totalMaxReturn = getUserMaxReturn($level4['sponser_id']);
                                                    $totalIncome = getIncome($level4['sponser_id']);
                                                    $flush_amount = 0;

                                                    if (($totalIncome + $level4_amount) >= $totalMaxReturn) {
                                                        $level4_amount = ($totalMaxReturn - $totalIncome);

                                                        $flush_amount = ($final_amount - $level4_amount);

                                                        userPlansModel::where(['user_id' => $level4['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                                    }

                                                    if ($level4_amount > 0) {
                                                        $level4_roi = array();
                                                        $level4_roi['user_id'] = $level4['sponser_id'];
                                                        $level4_roi['amount'] = $level4_amount;
                                                        $level4_roi['flush_amount'] = $flush_amount;
                                                        $level4_roi['tag'] = "LEVEL4-REFERRAL";
                                                        $level4_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                        $level4_roi['refrence_id'] = $investment_id;
                                                        $level4_roi['created_on'] = $entryDate;

                                                        levelEarningLogsModel::insert($level4_roi);
                                                        // 
                                                        DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level4_amount)) where id = '" . $level4['sponser_id'] . "'");
                                                    }
                                                }

                                                $level5 = getRefferer($level4['sponser_id']);
                                                if (isset($level5['sponser_id']) && $level5['sponser_id'] > 0) {
                                                    if ($level5['active_direct'] >= 5 && $level5['ad_viewed'] >= 1) {
                                                        $level5_amount = $final_amount = ($referral_amount * $roiLevel['5']) / 100;

                                                        $totalMaxReturn = getUserMaxReturn($level5['sponser_id']);
                                                        $totalIncome = getIncome($level5['sponser_id']);
                                                        $flush_amount = 0;

                                                        if (($totalIncome + $level5_amount) >= $totalMaxReturn) {
                                                            $level5_amount = ($totalMaxReturn - $totalIncome);

                                                            $flush_amount = ($final_amount - $level5_amount);

                                                            userPlansModel::where(['user_id' => $level5['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                                        }

                                                        if ($level5_amount > 0) {
                                                            $level5_roi = array();
                                                            $level5_roi['user_id'] = $level5['sponser_id'];
                                                            $level5_roi['amount'] = $level5_amount;
                                                            $level5_roi['flush_amount'] = $flush_amount;
                                                            $level5_roi['tag'] = "LEVEL5-REFERRAL";
                                                            $level5_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                            $level5_roi['refrence_id'] = $investment_id;
                                                            $level5_roi['created_on'] = $entryDate;

                                                            levelEarningLogsModel::insert($level5_roi);
                                                            // 
                                                            DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level5_amount)) where id = '" . $level5['sponser_id'] . "'");
                                                        }
                                                    }

                                                    $level6 = getRefferer($level5['sponser_id']);
                                                    if (isset($level6['sponser_id']) && $level6['sponser_id'] > 0) {
                                                        if ($level6['active_direct'] >= 6 && $level6['ad_viewed'] >= 1) {
                                                            $level6_amount = $final_amount = ($referral_amount * $roiLevel['6']) / 100;

                                                            $totalMaxReturn = getUserMaxReturn($level6['sponser_id']);
                                                            $totalIncome = getIncome($level6['sponser_id']);
                                                            $flush_amount = 0;

                                                            if (($totalIncome + $level6_amount) >= $totalMaxReturn) {
                                                                $level6_amount = ($totalMaxReturn - $totalIncome);

                                                                $flush_amount = ($final_amount - $level6_amount);

                                                                userPlansModel::where(['user_id' => $level6['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                                            }

                                                            if ($level6_amount > 0) {
                                                                $level6_roi = array();
                                                                $level6_roi['user_id'] = $level6['sponser_id'];
                                                                $level6_roi['amount'] = $level6_amount;
                                                                $level6_roi['flush_amount'] = $flush_amount;
                                                                $level6_roi['tag'] = "LEVEL6-REFERRAL";
                                                                $level6_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                                $level6_roi['refrence_id'] = $investment_id;
                                                                $level6_roi['created_on'] = $entryDate;

                                                                levelEarningLogsModel::insert($level6_roi);
                                                                // 
                                                                DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level6_amount)) where id = '" . $level6['sponser_id'] . "'");
                                                            }
                                                        }

                                                        $level7 = getRefferer($level6['sponser_id']);
                                                        if (isset($level7['sponser_id']) && $level7['sponser_id'] > 0) {
                                                            if ($level7['active_direct'] >= 6 && $level7['ad_viewed'] >= 1) {
                                                                $level7_amount = $final_amount = ($referral_amount * $roiLevel['7']) / 100;

                                                                $totalMaxReturn = getUserMaxReturn($level7['sponser_id']);
                                                                $totalIncome = getIncome($level7['sponser_id']);
                                                                $flush_amount = 0;

                                                                if (($totalIncome + $level7_amount) >= $totalMaxReturn) {
                                                                    $level7_amount = ($totalMaxReturn - $totalIncome);

                                                                    $flush_amount = ($final_amount - $level7_amount);

                                                                    userPlansModel::where(['user_id' => $level7['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                                                }

                                                                if ($level7_amount > 0) {
                                                                    $level7_roi = array();
                                                                    $level7_roi['user_id'] = $level7['sponser_id'];
                                                                    $level7_roi['amount'] = $level7_amount;
                                                                    $level7_roi['flush_amount'] = $flush_amount;
                                                                    $level7_roi['tag'] = "LEVEL7-REFERRAL";
                                                                    $level7_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                                    $level7_roi['refrence_id'] = $investment_id;
                                                                    $level7_roi['created_on'] = $entryDate;

                                                                    levelEarningLogsModel::insert($level7_roi);
                                                                    // 
                                                                    DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level7_amount)) where id = '" . $level7['sponser_id'] . "'");
                                                                }
                                                            }

                                                            $level8 = getRefferer($level7['sponser_id']);
                                                            if (isset($level8['sponser_id']) && $level8['sponser_id'] > 0) {
                                                                if ($level8['active_direct'] >= 6 && $level8['ad_viewed'] >= 1) {
                                                                    $level8_amount = $final_amount = ($referral_amount * $roiLevel['8']) / 100;

                                                                    $totalMaxReturn = getUserMaxReturn($level8['sponser_id']);
                                                                    $totalIncome = getIncome($level8['sponser_id']);
                                                                    $flush_amount = 0;

                                                                    if (($totalIncome + $level8_amount) >= $totalMaxReturn) {
                                                                        $level8_amount = ($totalMaxReturn - $totalIncome);

                                                                        $flush_amount = ($final_amount - $level8_amount);

                                                                        userPlansModel::where(['user_id' => $level8['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                                                    }

                                                                    if ($level8_amount > 0) {
                                                                        $level8_roi = array();
                                                                        $level8_roi['user_id'] = $level8['sponser_id'];
                                                                        $level8_roi['amount'] = $level8_amount;
                                                                        $level8_roi['flush_amount'] = $flush_amount;
                                                                        $level8_roi['tag'] = "LEVEL8-REFERRAL";
                                                                        $level8_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                                        $level8_roi['refrence_id'] = $investment_id;
                                                                        $level8_roi['created_on'] = $entryDate;

                                                                        levelEarningLogsModel::insert($level8_roi);
                                                                        // 
                                                                        DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level8_amount)) where id = '" . $level8['sponser_id'] . "'");
                                                                    }
                                                                }

                                                                $level9 = getRefferer($level8['sponser_id']);
                                                                if (isset($level9['sponser_id']) && $level9['sponser_id'] > 0) {
                                                                    if ($level9['active_direct'] >= 6 && $level9['ad_viewed'] >= 1) {
                                                                        $level9_amount = $final_amount = ($referral_amount * $roiLevel['9']) / 100;

                                                                        $totalMaxReturn = getUserMaxReturn($level9['sponser_id']);
                                                                        $totalIncome = getIncome($level9['sponser_id']);
                                                                        $flush_amount = 0;

                                                                        if (($totalIncome + $level9_amount) >= $totalMaxReturn) {
                                                                            $level9_amount = ($totalMaxReturn - $totalIncome);

                                                                            $flush_amount = ($final_amount - $level9_amount);

                                                                            userPlansModel::where(['user_id' => $level9['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                                                        }

                                                                        if ($level9_amount > 0) {
                                                                            $level9_roi = array();
                                                                            $level9_roi['user_id'] = $level9['sponser_id'];
                                                                            $level9_roi['amount'] = $level9_amount;
                                                                            $level9_roi['flush_amount'] = $flush_amount;
                                                                            $level9_roi['tag'] = "LEVEL9-REFERRAL";
                                                                            $level9_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                                            $level9_roi['refrence_id'] = $investment_id;
                                                                            $level9_roi['created_on'] = $entryDate;

                                                                            levelEarningLogsModel::insert($level9_roi);
                                                                            // 
                                                                            DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level9_amount)) where id = '" . $level9['sponser_id'] . "'");
                                                                        }
                                                                    }

                                                                    $level10 = getRefferer($level9['sponser_id']);
                                                                    if (isset($level10['sponser_id']) && $level10['sponser_id'] > 0) {
                                                                        if ($level10['active_direct'] >= 6 && $level10['ad_viewed'] >= 1) {
                                                                            $level10_amount = $final_amount = ($referral_amount * $roiLevel['10']) / 100;

                                                                            $totalMaxReturn = getUserMaxReturn($level10['sponser_id']);
                                                                            $totalIncome = getIncome($level10['sponser_id']);
                                                                            $flush_amount = 0;

                                                                            if (($totalIncome + $level10_amount) >= $totalMaxReturn) {
                                                                                $level10_amount = ($totalMaxReturn - $totalIncome);

                                                                                $flush_amount = ($final_amount - $level10_amount);

                                                                                userPlansModel::where(['user_id' => $level10['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                                                            }

                                                                            if ($level10_amount > 0) {
                                                                                $level10_roi = array();
                                                                                $level10_roi['user_id'] = $level10['sponser_id'];
                                                                                $level10_roi['amount'] = $level10_amount;
                                                                                $level10_roi['flush_amount'] = $flush_amount;
                                                                                $level10_roi['tag'] = "LEVEL10-REFERRAL";
                                                                                $level10_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                                                $level10_roi['refrence_id'] = $investment_id;
                                                                                $level10_roi['created_on'] = $entryDate;

                                                                                levelEarningLogsModel::insert($level10_roi);
                                                                                // 
                                                                                DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level10_amount)) where id = '" . $level10['sponser_id'] . "'");
                                                                            }
                                                                        }

                                                                        $level11 = getRefferer($level10['sponser_id']);
                                                                        if (isset($level11['sponser_id']) && $level11['sponser_id'] > 0) {
                                                                            if ($level11['active_direct'] >= 7 && $level11['ad_viewed'] >= 1) {
                                                                                $level11_amount = $final_amount = ($referral_amount * $roiLevel['11']) / 100;

                                                                                $totalMaxReturn = getUserMaxReturn($level11['sponser_id']);
                                                                                $totalIncome = getIncome($level11['sponser_id']);
                                                                                $flush_amount = 0;

                                                                                if (($totalIncome + $level11_amount) >= $totalMaxReturn) {
                                                                                    $level11_amount = ($totalMaxReturn - $totalIncome);

                                                                                    $flush_amount = ($final_amount - $level11_amount);

                                                                                    userPlansModel::where(['user_id' => $level11['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                                                                }

                                                                                if ($level11_amount > 0) {
                                                                                    $level11_roi = array();
                                                                                    $level11_roi['user_id'] = $level11['sponser_id'];
                                                                                    $level11_roi['amount'] = $level11_amount;
                                                                                    $level11_roi['flush_amount'] = $flush_amount;
                                                                                    $level11_roi['tag'] = "LEVEL11-REFERRAL";
                                                                                    $level11_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                                                    $level11_roi['refrence_id'] = $investment_id;
                                                                                    $level11_roi['created_on'] = $entryDate;

                                                                                    levelEarningLogsModel::insert($level11_roi);
                                                                                    // 
                                                                                    DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level11_amount)) where id = '" . $level11['sponser_id'] . "'");
                                                                                }
                                                                            }

                                                                            $level12 = getRefferer($level11['sponser_id']);
                                                                            if (isset($level12['sponser_id']) && $level12['sponser_id'] > 0) {
                                                                                if ($level12['active_direct'] >= 7 && $level12['ad_viewed'] >= 1) {
                                                                                    $level12_amount = $final_amount = ($referral_amount * $roiLevel['12']) / 100;

                                                                                    $totalMaxReturn = getUserMaxReturn($level12['sponser_id']);
                                                                                    $totalIncome = getIncome($level12['sponser_id']);
                                                                                    $flush_amount = 0;

                                                                                    if (($totalIncome + $level12_amount) >= $totalMaxReturn) {
                                                                                        $level12_amount = ($totalMaxReturn - $totalIncome);

                                                                                        $flush_amount = ($final_amount - $level12_amount);

                                                                                        userPlansModel::where(['user_id' => $level12['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                                                                    }

                                                                                    if ($level12_amount > 0) {
                                                                                        $level12_roi = array();
                                                                                        $level12_roi['user_id'] = $level12['sponser_id'];
                                                                                        $level12_roi['amount'] = $level12_amount;
                                                                                        $level12_roi['flush_amount'] = $flush_amount;
                                                                                        $level12_roi['tag'] = "LEVEL12-REFERRAL";
                                                                                        $level12_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                                                        $level12_roi['refrence_id'] = $investment_id;
                                                                                        $level12_roi['created_on'] = $entryDate;

                                                                                        levelEarningLogsModel::insert($level12_roi);
                                                                                        // 
                                                                                        DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level12_amount)) where id = '" . $level12['sponser_id'] . "'");
                                                                                    }
                                                                                }

                                                                                $level13 = getRefferer($level12['sponser_id']);
                                                                                if (isset($level13['sponser_id']) && $level13['sponser_id'] > 0) {
                                                                                    if ($level13['active_direct'] >= 7 && $level13['ad_viewed'] >= 1) {
                                                                                        $level13_amount = $final_amount = ($referral_amount * $roiLevel['13']) / 100;

                                                                                        $totalMaxReturn = getUserMaxReturn($level13['sponser_id']);
                                                                                        $totalIncome = getIncome($level13['sponser_id']);
                                                                                        $flush_amount = 0;

                                                                                        if (($totalIncome + $level13_amount) >= $totalMaxReturn) {
                                                                                            $level13_amount = ($totalMaxReturn - $totalIncome);

                                                                                            $flush_amount = ($final_amount - $level13_amount);

                                                                                            userPlansModel::where(['user_id' => $level13['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                                                                        }

                                                                                        if ($level13_amount > 0) {
                                                                                            $level13_roi = array();
                                                                                            $level13_roi['user_id'] = $level13['sponser_id'];
                                                                                            $level13_roi['amount'] = $level13_amount;
                                                                                            $level13_roi['flush_amount'] = $flush_amount;
                                                                                            $level13_roi['tag'] = "LEVEL13-REFERRAL";
                                                                                            $level13_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                                                            $level13_roi['refrence_id'] = $investment_id;
                                                                                            $level13_roi['created_on'] = $entryDate;

                                                                                            levelEarningLogsModel::insert($level13_roi);
                                                                                            // 
                                                                                            DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level13_amount)) where id = '" . $level13['sponser_id'] . "'");
                                                                                        }
                                                                                    }

                                                                                    $level14 = getRefferer($level13['sponser_id']);
                                                                                    if (isset($level14['sponser_id']) && $level14['sponser_id'] > 0) {
                                                                                        if ($level14['active_direct'] >= 7 && $level14['ad_viewed'] >= 1) {
                                                                                            $level14_amount = $final_amount = ($referral_amount * $roiLevel['14']) / 100;

                                                                                            $totalMaxReturn = getUserMaxReturn($level14['sponser_id']);
                                                                                            $totalIncome = getIncome($level14['sponser_id']);
                                                                                            $flush_amount = 0;

                                                                                            if (($totalIncome + $level14_amount) >= $totalMaxReturn) {
                                                                                                $level14_amount = ($totalMaxReturn - $totalIncome);

                                                                                                $flush_amount = ($final_amount - $level14_amount);

                                                                                                userPlansModel::where(['user_id' => $level14['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                                                                            }

                                                                                            if ($level14_amount > 0) {
                                                                                                $level14_roi = array();
                                                                                                $level14_roi['user_id'] = $level14['sponser_id'];
                                                                                                $level14_roi['amount'] = $level14_amount;
                                                                                                $level14_roi['flush_amount'] = $flush_amount;
                                                                                                $level14_roi['tag'] = "LEVEL14-REFERRAL";
                                                                                                $level14_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                                                                $level14_roi['refrence_id'] = $investment_id;
                                                                                                $level14_roi['created_on'] = $entryDate;

                                                                                                levelEarningLogsModel::insert($level14_roi);
                                                                                                // 
                                                                                                DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level14_amount)) where id = '" . $level14['sponser_id'] . "'");
                                                                                            }
                                                                                        }

                                                                                        $level15 = getRefferer($level14['sponser_id']);
                                                                                        if (isset($level15['sponser_id']) && $level15['sponser_id'] > 0) {
                                                                                            if ($level15['active_direct'] >= 7 && $level15['ad_viewed'] >= 1) {
                                                                                                $level15_amount = $final_amount = ($referral_amount * $roiLevel['15']) / 100;

                                                                                                $totalMaxReturn = getUserMaxReturn($level15['sponser_id']);
                                                                                                $totalIncome = getIncome($level15['sponser_id']);
                                                                                                $flush_amount = 0;

                                                                                                if (($totalIncome + $level15_amount) >= $totalMaxReturn) {
                                                                                                    $level15_amount = ($totalMaxReturn - $totalIncome);

                                                                                                    $flush_amount = ($final_amount - $level15_amount);

                                                                                                    userPlansModel::where(['user_id' => $level15['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                                                                                }

                                                                                                if ($level15_amount > 0) {
                                                                                                    $level15_roi = array();
                                                                                                    $level15_roi['user_id'] = $level15['sponser_id'];
                                                                                                    $level15_roi['amount'] = $level15_amount;
                                                                                                    $level15_roi['flush_amount'] = $flush_amount;
                                                                                                    $level15_roi['tag'] = "LEVEL15-REFERRAL";
                                                                                                    $level15_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                                                                    $level15_roi['refrence_id'] = $investment_id;
                                                                                                    $level15_roi['created_on'] = $entryDate;

                                                                                                    levelEarningLogsModel::insert($level15_roi);
                                                                                                    // 
                                                                                                    DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level15_amount)) where id = '" . $level15['sponser_id'] . "'");
                                                                                                }
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                //level roi distribution
                $level1 = getRefferer($user_id);
                if (isset($level1['sponser_id']) && $level1['sponser_id'] > 0) {
                    if ($level1['active_direct'] >= 1 && $level1['ad_viewed'] >= 1) {
                        $level1_amount = $final_amount = ($roi_amount * $roiLevel['1']) / 100;

                        $totalMaxReturn = getUserMaxReturn($level1['sponser_id']);
                        $totalIncome = getIncome($level1['sponser_id']);
                        $flush_amount = 0;

                        if (($totalIncome + $level1_amount) >= $totalMaxReturn) {
                            $level1_amount = ($totalMaxReturn - $totalIncome);

                            $flush_amount = ($final_amount - $level1_amount);

                            userPlansModel::where(['user_id' => $level1['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                        }

                        if ($level1_amount > 0) {
                            $level1_roi = array();
                            $level1_roi['user_id'] = $level1['sponser_id'];
                            $level1_roi['amount'] = $level1_amount;
                            $level1_roi['flush_amount'] = $flush_amount;
                            $level1_roi['tag'] = "LEVEL1-ROI";
                            $level1_roi['refrence'] = $roiUser['0']['refferal_code'];
                            $level1_roi['refrence_id'] = $investment_id;
                            $level1_roi['created_on'] = $entryDate;

                            levelEarningLogsModel::insert($level1_roi);
                            // 
                            DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level1_amount)) where id = '" . $level1['sponser_id'] . "'");
                        }
                    }

                    $level2 = getRefferer($level1['sponser_id']);
                    if (isset($level2['sponser_id']) && $level2['sponser_id'] > 0) {
                        if ($level2['active_direct'] >= 2 && $level2['ad_viewed'] >= 1) {
                            $level2_amount = $final_amount = ($roi_amount * $roiLevel['2']) / 100;

                            $totalMaxReturn = getUserMaxReturn($level2['sponser_id']);
                            $totalIncome = getIncome($level2['sponser_id']);
                            $flush_amount = 0;

                            if (($totalIncome + $level2_amount) >= $totalMaxReturn) {
                                $level2_amount = ($totalMaxReturn - $totalIncome);

                                $flush_amount = ($final_amount - $level2_amount);

                                userPlansModel::where(['user_id' => $level2['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                            }

                            if ($level2_amount > 0) {
                                $level2_roi = array();
                                $level2_roi['user_id'] = $level2['sponser_id'];
                                $level2_roi['amount'] = $level2_amount;
                                $level2_roi['flush_amount'] = $flush_amount;
                                $level2_roi['tag'] = "LEVEL2-ROI";
                                $level2_roi['refrence'] = $roiUser['0']['refferal_code'];
                                $level2_roi['refrence_id'] = $investment_id;
                                $level2_roi['created_on'] = $entryDate;

                                levelEarningLogsModel::insert($level2_roi);
                                // 
                                DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level2_amount)) where id = '" . $level2['sponser_id'] . "'");
                            }
                        }

                        $level3 = getRefferer($level2['sponser_id']);
                        if (isset($level3['sponser_id']) && $level3['sponser_id'] > 0) {
                            if ($level3['active_direct'] >= 3 && $level3['ad_viewed'] >= 1) {
                                $level3_amount = $final_amount = ($roi_amount * $roiLevel['3']) / 100;

                                $totalMaxReturn = getUserMaxReturn($level3['sponser_id']);
                                $totalIncome = getIncome($level3['sponser_id']);
                                $flush_amount = 0;

                                if (($totalIncome + $level3_amount) >= $totalMaxReturn) {
                                    $level3_amount = ($totalMaxReturn - $totalIncome);

                                    $flush_amount = ($final_amount - $level3_amount);

                                    userPlansModel::where(['user_id' => $level3['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                }

                                if ($level3_amount > 0) {
                                    $level3_roi = array();
                                    $level3_roi['user_id'] = $level3['sponser_id'];
                                    $level3_roi['amount'] = $level3_amount;
                                    $level3_roi['flush_amount'] = $flush_amount;
                                    $level3_roi['tag'] = "LEVEL3-ROI";
                                    $level3_roi['refrence'] = $roiUser['0']['refferal_code'];
                                    $level3_roi['refrence_id'] = $investment_id;
                                    $level3_roi['created_on'] = $entryDate;

                                    levelEarningLogsModel::insert($level3_roi);
                                    // 
                                    DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level3_amount)) where id = '" . $level3['sponser_id'] . "'");
                                }
                            }

                            $level4 = getRefferer($level3['sponser_id']);
                            if (isset($level4['sponser_id']) && $level4['sponser_id'] > 0) {
                                if ($level4['active_direct'] >= 4 && $level4['ad_viewed'] >= 1) {
                                    $level4_amount = $final_amount = ($roi_amount * $roiLevel['4']) / 100;

                                    $totalMaxReturn = getUserMaxReturn($level4['sponser_id']);
                                    $totalIncome = getIncome($level4['sponser_id']);
                                    $flush_amount = 0;

                                    if (($totalIncome + $level4_amount) >= $totalMaxReturn) {
                                        $level4_amount = ($totalMaxReturn - $totalIncome);

                                        $flush_amount = ($final_amount - $level4_amount);

                                        userPlansModel::where(['user_id' => $level4['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                    }

                                    if ($level4_amount > 0) {
                                        $level4_roi = array();
                                        $level4_roi['user_id'] = $level4['sponser_id'];
                                        $level4_roi['amount'] = $level4_amount;
                                        $level4_roi['flush_amount'] = $flush_amount;
                                        $level4_roi['tag'] = "LEVEL4-ROI";
                                        $level4_roi['refrence'] = $roiUser['0']['refferal_code'];
                                        $level4_roi['refrence_id'] = $investment_id;
                                        $level4_roi['created_on'] = $entryDate;

                                        levelEarningLogsModel::insert($level4_roi);
                                        // 
                                        DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level4_amount)) where id = '" . $level4['sponser_id'] . "'");
                                    }
                                }

                                $level5 = getRefferer($level4['sponser_id']);
                                if (isset($level5['sponser_id']) && $level5['sponser_id'] > 0) {
                                    if ($level5['active_direct'] >= 5 && $level5['ad_viewed'] >= 1) {
                                        $level5_amount = $final_amount = ($roi_amount * $roiLevel['5']) / 100;

                                        $totalMaxReturn = getUserMaxReturn($level5['sponser_id']);
                                        $totalIncome = getIncome($level5['sponser_id']);
                                        $flush_amount = 0;

                                        if (($totalIncome + $level5_amount) >= $totalMaxReturn) {
                                            $level5_amount = ($totalMaxReturn - $totalIncome);

                                            $flush_amount = ($final_amount - $level5_amount);

                                            userPlansModel::where(['user_id' => $level5['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                        }

                                        if ($level5_amount > 0) {
                                            $level5_roi = array();
                                            $level5_roi['user_id'] = $level5['sponser_id'];
                                            $level5_roi['amount'] = $level5_amount;
                                            $level5_roi['flush_amount'] = $flush_amount;
                                            $level5_roi['tag'] = "LEVEL5-ROI";
                                            $level5_roi['refrence'] = $roiUser['0']['refferal_code'];
                                            $level5_roi['refrence_id'] = $investment_id;
                                            $level5_roi['created_on'] = $entryDate;

                                            levelEarningLogsModel::insert($level5_roi);
                                            // 
                                            DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level5_amount)) where id = '" . $level5['sponser_id'] . "'");
                                        }
                                    }

                                    $level6 = getRefferer($level5['sponser_id']);
                                    if (isset($level6['sponser_id']) && $level6['sponser_id'] > 0) {
                                        if ($level6['active_direct'] >= 6 && $level6['ad_viewed'] >= 1) {
                                            $level6_amount = $final_amount = ($roi_amount * $roiLevel['6']) / 100;

                                            $totalMaxReturn = getUserMaxReturn($level6['sponser_id']);
                                            $totalIncome = getIncome($level6['sponser_id']);
                                            $flush_amount = 0;

                                            if (($totalIncome + $level6_amount) >= $totalMaxReturn) {
                                                $level6_amount = ($totalMaxReturn - $totalIncome);

                                                $flush_amount = ($final_amount - $level6_amount);

                                                userPlansModel::where(['user_id' => $level6['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                            }

                                            if ($level6_amount > 0) {
                                                $level6_roi = array();
                                                $level6_roi['user_id'] = $level6['sponser_id'];
                                                $level6_roi['amount'] = $level6_amount;
                                                $level6_roi['flush_amount'] = $flush_amount;
                                                $level6_roi['tag'] = "LEVEL6-ROI";
                                                $level6_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                $level6_roi['refrence_id'] = $investment_id;
                                                $level6_roi['created_on'] = $entryDate;

                                                levelEarningLogsModel::insert($level6_roi);
                                                // 
                                                DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level6_amount)) where id = '" . $level6['sponser_id'] . "'");
                                            }
                                        }

                                        $level7 = getRefferer($level6['sponser_id']);
                                        if (isset($level7['sponser_id']) && $level7['sponser_id'] > 0) {
                                            if ($level7['active_direct'] >= 6 && $level7['ad_viewed'] >= 1) {
                                                $level7_amount = $final_amount = ($roi_amount * $roiLevel['7']) / 100;

                                                $totalMaxReturn = getUserMaxReturn($level7['sponser_id']);
                                                $totalIncome = getIncome($level7['sponser_id']);
                                                $flush_amount = 0;

                                                if (($totalIncome + $level7_amount) >= $totalMaxReturn) {
                                                    $level7_amount = ($totalMaxReturn - $totalIncome);

                                                    $flush_amount = ($final_amount - $level7_amount);

                                                    userPlansModel::where(['user_id' => $level7['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                                }

                                                if ($level7_amount > 0) {
                                                    $level7_roi = array();
                                                    $level7_roi['user_id'] = $level7['sponser_id'];
                                                    $level7_roi['amount'] = $level7_amount;
                                                    $level7_roi['flush_amount'] = $flush_amount;
                                                    $level7_roi['tag'] = "LEVEL7-ROI";
                                                    $level7_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                    $level7_roi['refrence_id'] = $investment_id;
                                                    $level7_roi['created_on'] = $entryDate;

                                                    levelEarningLogsModel::insert($level7_roi);
                                                    // 
                                                    DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level7_amount)) where id = '" . $level7['sponser_id'] . "'");
                                                }
                                            }

                                            $level8 = getRefferer($level7['sponser_id']);
                                            if (isset($level8['sponser_id']) && $level8['sponser_id'] > 0) {
                                                if ($level8['active_direct'] >= 6 && $level8['ad_viewed'] >= 1) {
                                                    $level8_amount = $final_amount = ($roi_amount * $roiLevel['8']) / 100;

                                                    $totalMaxReturn = getUserMaxReturn($level8['sponser_id']);
                                                    $totalIncome = getIncome($level8['sponser_id']);
                                                    $flush_amount = 0;

                                                    if (($totalIncome + $level8_amount) >= $totalMaxReturn) {
                                                        $level8_amount = ($totalMaxReturn - $totalIncome);

                                                        $flush_amount = ($final_amount - $level8_amount);

                                                        userPlansModel::where(['user_id' => $level8['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                                    }

                                                    if ($level8_amount > 0) {
                                                        $level8_roi = array();
                                                        $level8_roi['user_id'] = $level8['sponser_id'];
                                                        $level8_roi['amount'] = $level8_amount;
                                                        $level8_roi['flush_amount'] = $flush_amount;
                                                        $level8_roi['tag'] = "LEVEL8-ROI";
                                                        $level8_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                        $level8_roi['refrence_id'] = $investment_id;
                                                        $level8_roi['created_on'] = $entryDate;

                                                        levelEarningLogsModel::insert($level8_roi);
                                                        // 
                                                        DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level8_amount)) where id = '" . $level8['sponser_id'] . "'");
                                                    }
                                                }

                                                $level9 = getRefferer($level8['sponser_id']);
                                                if (isset($level9['sponser_id']) && $level9['sponser_id'] > 0) {
                                                    if ($level9['active_direct'] >= 6 && $level9['ad_viewed'] >= 1) {
                                                        $level9_amount = $final_amount = ($roi_amount * $roiLevel['9']) / 100;

                                                        $totalMaxReturn = getUserMaxReturn($level9['sponser_id']);
                                                        $totalIncome = getIncome($level9['sponser_id']);
                                                        $flush_amount = 0;

                                                        if (($totalIncome + $level9_amount) >= $totalMaxReturn) {
                                                            $level9_amount = ($totalMaxReturn - $totalIncome);

                                                            $flush_amount = ($final_amount - $level9_amount);

                                                            userPlansModel::where(['user_id' => $level9['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                                        }

                                                        if ($level9_amount > 0) {
                                                            $level9_roi = array();
                                                            $level9_roi['user_id'] = $level9['sponser_id'];
                                                            $level9_roi['amount'] = $level9_amount;
                                                            $level9_roi['flush_amount'] = $flush_amount;
                                                            $level9_roi['tag'] = "LEVEL9-ROI";
                                                            $level9_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                            $level9_roi['refrence_id'] = $investment_id;
                                                            $level9_roi['created_on'] = $entryDate;

                                                            levelEarningLogsModel::insert($level9_roi);
                                                            // 
                                                            DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level9_amount)) where id = '" . $level9['sponser_id'] . "'");
                                                        }
                                                    }

                                                    $level10 = getRefferer($level9['sponser_id']);
                                                    if (isset($level10['sponser_id']) && $level10['sponser_id'] > 0) {
                                                        if ($level10['active_direct'] >= 6 && $level10['ad_viewed'] >= 1) {
                                                            $level10_amount = $final_amount = ($roi_amount * $roiLevel['10']) / 100;

                                                            $totalMaxReturn = getUserMaxReturn($level10['sponser_id']);
                                                            $totalIncome = getIncome($level10['sponser_id']);
                                                            $flush_amount = 0;

                                                            if (($totalIncome + $level10_amount) >= $totalMaxReturn) {
                                                                $level10_amount = ($totalMaxReturn - $totalIncome);

                                                                $flush_amount = ($final_amount - $level10_amount);

                                                                userPlansModel::where(['user_id' => $level10['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                                            }

                                                            if ($level10_amount > 0) {
                                                                $level10_roi = array();
                                                                $level10_roi['user_id'] = $level10['sponser_id'];
                                                                $level10_roi['amount'] = $level10_amount;
                                                                $level10_roi['flush_amount'] = $flush_amount;
                                                                $level10_roi['tag'] = "LEVEL10-ROI";
                                                                $level10_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                                $level10_roi['refrence_id'] = $investment_id;
                                                                $level10_roi['created_on'] = $entryDate;

                                                                levelEarningLogsModel::insert($level10_roi);
                                                                // 
                                                                DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level10_amount)) where id = '" . $level10['sponser_id'] . "'");
                                                            }
                                                        }

                                                        $level11 = getRefferer($level10['sponser_id']);
                                                        if (isset($level11['sponser_id']) && $level11['sponser_id'] > 0) {
                                                            if ($level11['active_direct'] >= 7 && $level11['ad_viewed'] >= 1) {
                                                                $level11_amount = $final_amount = ($roi_amount * $roiLevel['11']) / 100;

                                                                $totalMaxReturn = getUserMaxReturn($level11['sponser_id']);
                                                                $totalIncome = getIncome($level11['sponser_id']);
                                                                $flush_amount = 0;

                                                                if (($totalIncome + $level11_amount) >= $totalMaxReturn) {
                                                                    $level11_amount = ($totalMaxReturn - $totalIncome);

                                                                    $flush_amount = ($final_amount - $level11_amount);

                                                                    userPlansModel::where(['user_id' => $level11['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                                                }

                                                                if ($level11_amount > 0) {
                                                                    $level11_roi = array();
                                                                    $level11_roi['user_id'] = $level11['sponser_id'];
                                                                    $level11_roi['amount'] = $level11_amount;
                                                                    $level11_roi['flush_amount'] = $flush_amount;
                                                                    $level11_roi['tag'] = "LEVEL11-ROI";
                                                                    $level11_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                                    $level11_roi['refrence_id'] = $investment_id;
                                                                    $level11_roi['created_on'] = $entryDate;

                                                                    levelEarningLogsModel::insert($level11_roi);
                                                                    // 
                                                                    DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level11_amount)) where id = '" . $level11['sponser_id'] . "'");
                                                                }
                                                            }

                                                            $level12 = getRefferer($level11['sponser_id']);
                                                            if (isset($level12['sponser_id']) && $level12['sponser_id'] > 0) {
                                                                if ($level12['active_direct'] >= 7 && $level12['ad_viewed'] >= 1) {
                                                                    $level12_amount = $final_amount = ($roi_amount * $roiLevel['12']) / 100;

                                                                    $totalMaxReturn = getUserMaxReturn($level12['sponser_id']);
                                                                    $totalIncome = getIncome($level12['sponser_id']);
                                                                    $flush_amount = 0;

                                                                    if (($totalIncome + $level12_amount) >= $totalMaxReturn) {
                                                                        $level12_amount = ($totalMaxReturn - $totalIncome);

                                                                        $flush_amount = ($final_amount - $level12_amount);

                                                                        userPlansModel::where(['user_id' => $level12['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                                                    }

                                                                    if ($level12_amount > 0) {
                                                                        $level12_roi = array();
                                                                        $level12_roi['user_id'] = $level12['sponser_id'];
                                                                        $level12_roi['amount'] = $level12_amount;
                                                                        $level12_roi['flush_amount'] = $flush_amount;
                                                                        $level12_roi['tag'] = "LEVEL12-ROI";
                                                                        $level12_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                                        $level12_roi['refrence_id'] = $investment_id;
                                                                        $level12_roi['created_on'] = $entryDate;

                                                                        levelEarningLogsModel::insert($level12_roi);
                                                                        // 
                                                                        DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level12_amount)) where id = '" . $level12['sponser_id'] . "'");
                                                                    }
                                                                }

                                                                $level13 = getRefferer($level12['sponser_id']);
                                                                if (isset($level13['sponser_id']) && $level13['sponser_id'] > 0) {
                                                                    if ($level13['active_direct'] >= 7 && $level13['ad_viewed'] >= 1) {
                                                                        $level13_amount = $final_amount = ($roi_amount * $roiLevel['13']) / 100;

                                                                        $totalMaxReturn = getUserMaxReturn($level13['sponser_id']);
                                                                        $totalIncome = getIncome($level13['sponser_id']);
                                                                        $flush_amount = 0;

                                                                        if (($totalIncome + $level13_amount) >= $totalMaxReturn) {
                                                                            $level13_amount = ($totalMaxReturn - $totalIncome);

                                                                            $flush_amount = ($final_amount - $level13_amount);

                                                                            userPlansModel::where(['user_id' => $level13['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                                                        }

                                                                        if ($level13_amount > 0) {
                                                                            $level13_roi = array();
                                                                            $level13_roi['user_id'] = $level13['sponser_id'];
                                                                            $level13_roi['amount'] = $level13_amount;
                                                                            $level13_roi['flush_amount'] = $flush_amount;
                                                                            $level13_roi['tag'] = "LEVEL13-ROI";
                                                                            $level13_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                                            $level13_roi['refrence_id'] = $investment_id;
                                                                            $level13_roi['created_on'] = $entryDate;

                                                                            levelEarningLogsModel::insert($level13_roi);
                                                                            // 
                                                                            DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level13_amount)) where id = '" . $level13['sponser_id'] . "'");
                                                                        }
                                                                    }

                                                                    $level14 = getRefferer($level13['sponser_id']);
                                                                    if (isset($level14['sponser_id']) && $level14['sponser_id'] > 0) {
                                                                        if ($level14['active_direct'] >= 7 && $level14['ad_viewed'] >= 1) {
                                                                            $level14_amount = $final_amount = ($roi_amount * $roiLevel['14']) / 100;

                                                                            $totalMaxReturn = getUserMaxReturn($level14['sponser_id']);
                                                                            $totalIncome = getIncome($level14['sponser_id']);
                                                                            $flush_amount = 0;

                                                                            if (($totalIncome + $level14_amount) >= $totalMaxReturn) {
                                                                                $level14_amount = ($totalMaxReturn - $totalIncome);

                                                                                $flush_amount = ($final_amount - $level14_amount);

                                                                                userPlansModel::where(['user_id' => $level14['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                                                            }

                                                                            if ($level14_amount > 0) {
                                                                                $level14_roi = array();
                                                                                $level14_roi['user_id'] = $level14['sponser_id'];
                                                                                $level14_roi['amount'] = $level14_amount;
                                                                                $level14_roi['flush_amount'] = $flush_amount;
                                                                                $level14_roi['tag'] = "LEVEL14-ROI";
                                                                                $level14_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                                                $level14_roi['refrence_id'] = $investment_id;
                                                                                $level14_roi['created_on'] = $entryDate;

                                                                                levelEarningLogsModel::insert($level14_roi);
                                                                                // 
                                                                                DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level14_amount)) where id = '" . $level14['sponser_id'] . "'");
                                                                            }
                                                                        }

                                                                        $level15 = getRefferer($level14['sponser_id']);
                                                                        if (isset($level15['sponser_id']) && $level15['sponser_id'] > 0) {
                                                                            if ($level15['active_direct'] >= 7 && $level15['ad_viewed'] >= 1) {
                                                                                $level15_amount = $final_amount = ($roi_amount * $roiLevel['15']) / 100;

                                                                                $totalMaxReturn = getUserMaxReturn($level15['sponser_id']);
                                                                                $totalIncome = getIncome($level15['sponser_id']);
                                                                                $flush_amount = 0;

                                                                                if (($totalIncome + $level15_amount) >= $totalMaxReturn) {
                                                                                    $level15_amount = ($totalMaxReturn - $totalIncome);

                                                                                    $flush_amount = ($final_amount - $level15_amount);

                                                                                    userPlansModel::where(['user_id' => $level15['sponser_id'], 'status' => 1])->update(['status' => 2, 'completed_on' => date('Y-m-d H:i:s')]);
                                                                                }

                                                                                if ($level15_amount > 0) {
                                                                                    $level15_roi = array();
                                                                                    $level15_roi['user_id'] = $level15['sponser_id'];
                                                                                    $level15_roi['amount'] = $level15_amount;
                                                                                    $level15_roi['flush_amount'] = $flush_amount;
                                                                                    $level15_roi['tag'] = "LEVEL15-ROI";
                                                                                    $level15_roi['refrence'] = $roiUser['0']['refferal_code'];
                                                                                    $level15_roi['refrence_id'] = $investment_id;
                                                                                    $level15_roi['created_on'] = $entryDate;

                                                                                    levelEarningLogsModel::insert($level15_roi);
                                                                                    // 
                                                                                    DB::statement("UPDATE users set level_income = (IFNULL(level_income,0) + ($level15_amount)) where id = '" . $level15['sponser_id'] . "'");
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        usersModel::whereRaw("1 = 1")->update(['ad_viewed' => 0]);
    }

}
