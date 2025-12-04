<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\userPlansModel;
use App\Models\earningLogsModel;
use App\Models\levelEarningLogsModel;
use App\Models\usersModel;
use App\Models\myTeamModel;
use App\Models\settingModel;
use App\Models\withdrawModel;
use Illuminate\Support\Facades\DB;
use function App\Helpers\is_mobile;

class TestController extends Controller
{
    // public function __construct()
    // {
    //     // echo "In constructor";
    // }

    public function getRankBonus(Request $request)
    {
        // dd($request->all());

        $type = $request->input("type");
        $userId = $request->input("user_id");

        $cutoff = now()->subHours(24);

        // SELECT SUM(amount) AS `level_income` FROM level_earning_logs WHERE user_id=47 AND tag LIKE 'LEVEL%-ROI'
        $data = earningLogsModel::select('id', 'user_id', 'amount', 'tag', 'created_on')
                                        ->where('user_id', $userId)
                                        ->where('tag','DIFF-TEAM-BONUS')
                                        ->where('created_on', '>=', $cutoff)
                                        ->orderBy('id', 'desc')  
                                        // ->limit(100)
                                        ->get()
                                        ->toArray();
        $sum = 0;
        foreach($data as $dval)
        {
            $sum += $dval['amount'];
        }
        $res = [
            'status_code' => (count($data)>0?1:0),
            'message'     => 'Success',
            'total'       => $sum,
            'cutoffdate'  => $cutoff,
            'data'        => $data
        ];

        return is_mobile($type, "", $res, "API");

    }

    public function getReferralBonus(Request $request)
    {

        $type = $request->input("type");
        $userId = $request->input("user_id");

        $cutoff = now()->subHours(24);

        // SELECT SUM(amount) AS `reward_bonus` FROM `earning_logs` where user_id=47 AND tag='DIFF-TEAM-BONUS'
        $data = levelEarningLogsModel::select('id', 'user_id', 'amount', 'tag', 'created_on')
                                        ->where('user_id', $userId)
                                        ->where('tag', 'LIKE', '%LEVEL%')
                                        ->where('created_on', '>=', $cutoff)
                                        ->orderBy('id', 'desc')  
                                        // ->limit(100)
                                        ->get()
                                        ->toArray();
        $sum = 0;
        foreach($data as $dval)
        {
            $sum += $dval['amount'];
        }
        $res = [
            'status_code' => (count($data)>0?1:0),
            'message'     => 'Success',
            'total'       => $sum,
            'cutoffdate'  => $cutoff,
            'data'        => $data
        ];

        return is_mobile($type, "", $res, "API");

    }
}
