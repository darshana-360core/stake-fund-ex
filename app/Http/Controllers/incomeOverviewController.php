<?php

namespace App\Http\Controllers;

use App\Models\earningLogsModel;
use App\Models\levelEarningLogsModel;
use App\Models\rankingModel;
use App\Models\usersModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use function App\Helpers\is_mobile;
use function App\Helpers\rtxPrice;

class incomeOverviewController extends Controller
{
    public function index(Request $request)
    {
        ini_set('memory_limit', '512M');
        
        $type = $request->input("type");
        if($type == "API")
        {
            $user_id = $request->input("user_id");
        }else
        {
            $user_id = $request->session()->get("user_id");
        }
        
        $og_start_date = $start_date = $request->input("start_date");
        $og_end_date = $end_date = $request->input("end_date");
        // $user_id = $request->session()->get("user_id");

        // Build unique cache key (user_id + filters)
        $cacheKey = "income_overview_{$user_id}_{$og_start_date}_{$og_end_date}";

        // Cache for 1 hour
        $res = Cache::remember($cacheKey, 3600, function () use ($request, $user_id, $og_start_date, $og_end_date) {

            $user = usersModel::where("id", $user_id)->get()->toArray();

            // Filter earningLogs if start_date and end_date are provided
            $earningLogsQuery = earningLogsModel::where("user_id", $user_id);
            if (!empty($og_start_date) && !empty($og_end_date)) {
                $start_date = date("Y-m-d", strtotime($og_start_date));
                $end_date = date("Y-m-d", strtotime($og_end_date));
                $earningLogsQuery->whereRaw("DATE_FORMAT(created_on, '%Y-%m-%d') BETWEEN ? AND ?", [$start_date, $end_date]);
            }
            $earningLogs = $earningLogsQuery->orderBy('id', 'desc')->get()->toArray();

            // Filter levelEarningLogs if start_date and end_date are provided
            $levelEarningLogsQuery = levelEarningLogsModel::selectRaw("SUM(amount) as amount, created_on")->where("user_id", $user_id);
            if (!empty($og_start_date) && !empty($og_end_date)) {
                $start_date = date("Y-m-d", strtotime($og_start_date));
                $end_date = date("Y-m-d", strtotime($og_end_date));
                $levelEarningLogsQuery->whereRaw("DATE_FORMAT(created_on, '%Y-%m-%d') BETWEEN ? AND ?", [$start_date, $end_date]);
            }
            $levelEarningLogs = $levelEarningLogsQuery
                ->groupBy(DB::raw("DATE_FORMAT(created_on, '%Y-%m-%d %H')"))
                ->orderBy('id', 'desc')
                ->get()
                ->toArray();

            $ranking = rankingModel::get()->toArray();

            $rankArray = [];
            $incomeRankArray = [];

            foreach ($ranking as $value) {
                $rankArray[$value['id']] = $value['name'];
                $incomeRankArray[$value['income']] = $value['name'];
            }

            $dailyPoolWinners = earningLogsModel::selectRaw("IFNULL(SUM(amount), 0) as dailyPool")
                ->where('tag', 'DAILY-POOL')
                ->where('user_id', '=', $user_id)
                ->get()
                ->toArray();

            $monthlyPoolWinners = earningLogsModel::selectRaw("IFNULL(SUM(amount), 0) as monthlyPool")
                ->where('tag', 'MONTHLY-POOL')
                ->where('user_id', '=', $user_id)
                ->get()
                ->toArray();

            return [
                'status_code' => 1,
                'message' => "Success",
                'user' => $user[0],
                'earningLogs' => $earningLogs,
                'levelEarningLogs' => $levelEarningLogs,
                'start_date' => $og_start_date,
                'end_date' => $og_end_date,
                'ranks' => $rankArray,
                'incomeRanks' => $incomeRankArray,
                'dailyPoolWinners' => $dailyPoolWinners[0]['dailyPool'],
                'monthlyPoolWinners' => $monthlyPoolWinners[0]['monthlyPool'],
                'rtxPrice' => rtxPrice(),
            ];
        });

        return is_mobile($type, "pages.income_overview", $res, "view");
    }
    // public function index(Request $request)
    // {
    //     ini_set('memory_limit', '512M');
        
    //     $type = $request->input("type");
    //     $og_start_date = $start_date = $request->input("start_date");
    //     $og_end_date = $end_date = $request->input("end_date");
    //     $user_id = $request->session()->get("user_id");

    //     $user = usersModel::where("id", $user_id)->get()->toArray();

    //     // Filter earningLogs if start_date and end_date are provided
    //     $earningLogsQuery = earningLogsModel::where("user_id", $user_id);
    //     if (!empty($start_date) && !empty($end_date)) {
    //         $start_date = date("Y-m-d", strtotime($request->input("start_date")));
    //         $end_date = date("Y-m-d", strtotime($request->input("end_date")));
    //         $earningLogsQuery->whereRaw("DATE_FORMAT(created_on, '%Y-%m-%d') BETWEEN ? AND ?", [$start_date, $end_date]);
    //     }
    //     $earningLogs = $earningLogsQuery->orderBy('id', 'desc')->get()->toArray();

    //     // Filter levelEarningLogs if start_date and end_date are provided
    //     $levelEarningLogsQuery = levelEarningLogsModel::selectRaw("SUM(amount) as amount, created_on")->where("user_id", $user_id);
    //     if (!empty($start_date) && !empty($end_date)) {
    //         $start_date = date("Y-m-d", strtotime($request->input("start_date")));
    //         $end_date = date("Y-m-d", strtotime($request->input("end_date")));
    //         $levelEarningLogsQuery->whereRaw("DATE_FORMAT(created_on, '%Y-%m-%d') BETWEEN ? AND ?", [$start_date, $end_date]);
    //     }
    //     $levelEarningLogs = $levelEarningLogsQuery->groupBy(DB::raw("DATE_FORMAT(created_on, '%Y-%m-%d %H')"))->orderBy('id', 'desc')->get()->toArray();

    //     $ranking = rankingModel::get()->toArray();

    //     $rankArray = array();
    //     $incomeRankArray = array();

    //     foreach ($ranking as $key => $value) {
    //         $rankArray[$value['id']] = $value['name'];
    //         $incomeRankArray[$value['income']] = $value['name'];
    //     }

    //     $res['status_code'] = 1;
    //     $res['message'] = "Success";
    //     $res['user'] = $user['0'];
    //     $res['earningLogs'] = $earningLogs;
    //     $res['levelEarningLogs'] = $levelEarningLogs;
    //     $res['start_date'] = $og_start_date;
    //     $res['end_date'] = $og_end_date;
    //     $res['ranks'] = $rankArray;
    //     $res['incomeRanks'] = $incomeRankArray;

    //     $dailyPoolWinners = earningLogsModel::selectRaw("IFNULL(SUM(amount), 0) as dailyPool")->where('tag', 'DAILY-POOL')->where('user_id', '=', $user_id)->get()->toArray();

    //     $monthlyPoolWinners = earningLogsModel::selectRaw("IFNULL(SUM(amount), 0) as monthlyPool")->where('tag', 'MONTHLY-POOL')->where('user_id', '=', $user_id)->get()->toArray();

    //     $res['dailyPoolWinners'] = $dailyPoolWinners['0']['dailyPool'];
    //     $res['monthlyPoolWinners'] = $monthlyPoolWinners['0']['monthlyPool'];
    //     $res['rtxPrice'] = rtxPrice();

    //     return is_mobile($type, "pages.income_overview", $res, "view");
    // }

    // public function levelIndex(Request $request)
    // {

    //     ini_set('memory_limit', '512M');
        
    //     $type = $request->input("type");
    //     $og_start_date = $start_date = $request->input("start_date");
    //     $og_end_date = $end_date = $request->input("end_date");
    //     $user_id = $request->session()->get("user_id");

    //     // Fetch user details
    //     $user = usersModel::where("id", $user_id)->first();  // Use first() instead of get()->toArray() for a single user

    //     // Initialize levelEarningLogs query
    //     $levelEarningLogsQuery = levelEarningLogsModel::where("user_id", $user_id);

    //     // Apply date filter if both start_date and end_date are provided
    //     if (!empty($start_date) && !empty($end_date)) {
    //         $start_date = date("Y-m-d", strtotime($request->input("start_date")));
    //         $end_date = date("Y-m-d", strtotime($request->input("end_date")));
    //         $levelEarningLogsQuery->whereRaw("DATE_FORMAT(created_on, '%Y-%m-%d') BETWEEN ? AND ?", [$start_date, $end_date]);
    //     }

    //     // Set the number of records per page (use $perPage from request or default to 10)
    //     $perPage = $request->input('per_page', 100);

    //     // Paginate the results instead of get()
    //     $levelEarningLogs = $levelEarningLogsQuery->orderBy('id', 'desc')->paginate($perPage);

    //     // Prepare the response
    //     $res['status_code'] = 1;
    //     $res['message'] = "Success";
    //     $res['user'] = $user;
    //     $res['levelEarningLogs'] = $levelEarningLogs;
    //     $res['start_date'] = $og_start_date;
    //     $res['end_date'] = $og_end_date;
    //     $res['rtxPrice'] = rtxPrice();

    //     // Return response based on mobile view condition
    //     return is_mobile($type, "pages.level_income_overview", $res, "view");
    // }

    public function levelIndex(Request $request)
    {
        ini_set('memory_limit', '512M');

        $type = $request->input("type");
        $og_start_date = $start_date = $request->input("start_date");
        $og_end_date = $end_date = $request->input("end_date");
        $user_id = $request->session()->get("user_id");

        $perPage = $request->input('per_page', 100);
        $page = $request->input('page', 1);

        // Build unique cache key (user_id + filters + pagination)
        $cacheKey = "level_index_{$user_id}_{$og_start_date}_{$og_end_date}_per{$perPage}_page{$page}";

        // Cache for 1 hour
        $res = Cache::remember($cacheKey, 3600, function () use ($request, $user_id, $og_start_date, $og_end_date, $perPage) {

            // Fetch user details
            $user = usersModel::where("id", $user_id)->first();

            // Initialize query
            $levelEarningLogsQuery = levelEarningLogsModel::where("user_id", $user_id);

            // Apply date filter
            if (!empty($og_start_date) && !empty($og_end_date)) {
                $start_date = date("Y-m-d", strtotime($og_start_date));
                $end_date = date("Y-m-d", strtotime($og_end_date));
                $levelEarningLogsQuery->whereRaw("DATE_FORMAT(created_on, '%Y-%m-%d') BETWEEN ? AND ?", [$start_date, $end_date]);
            }

            // Paginate results
            $levelEarningLogs = $levelEarningLogsQuery->orderBy('id', 'desc')->paginate($perPage);

            return [
                'status_code' => 1,
                'message' => "Success",
                'user' => $user,
                'levelEarningLogs' => $levelEarningLogs,
                'start_date' => $og_start_date,
                'end_date' => $og_end_date,
                'rtxPrice' => rtxPrice(),
            ];
        });

        // Return response
        return is_mobile($type, "pages.level_income_overview", $res, "view");
    }

    

    public function syncEarningLogs(Request $request)
    {
        $type = "API";

        $data = earningLogsModel::select(
            'earning_logs.id',
            'earning_logs.user_id',
            'users.wallet_address',
            'earning_logs.amount',
            'earning_logs.tag',
            'earning_logs.refrence',
            'earning_logs.refrence_id',
            'earning_logs.isSynced',
            'earning_logs.contract_stakeid',
            'earning_logs.lock_period',
            'earning_logs.transaction_hash',
            'earning_logs.created_on'
        )
            ->join('users', 'users.id', '=', 'earning_logs.user_id')
            ->where('earning_logs.isSynced', 0)
            ->orderBy('earning_logs.id', 'asc')
            ->limit(100)
            ->get()
            ->toArray();

        if (count($data) > 0) {
            $res['status_code'] = 1;
            $res['message'] = "Earning logs fetched successfully.";
            $res['data'] = $data;
            $res['first_eid'] = $data[0]['id'];
            $res['last_eid'] = $data[count($data) - 1]['id'];
        } else {
            $res['status_code'] = 0;
            $res['message'] = "No unsynced earning logs found.";
        }

        return is_mobile($type, "pages.income_overview", $res, "view");
    }

    public function syncEarningLogsUpdate(Request $request)
    {
        $type = "API";

        $data = $request->validate([
            'first_id'        => 'required|integer|min:1',
            'last_id'         => 'required|integer|min:1',
            'transaction_hash' => 'required|string|max:255',
            'claim_hash'       => 'required|string|max:255',
        ]);

        $firstId = min($data['first_id'], $data['last_id']);
        $lastId  = max($data['first_id'], $data['last_id']);

        $affected = earningLogsModel::whereBetween('id', [$firstId, $lastId])
            ->where('isSynced', 0)
            ->update([
                'transaction_hash' => $data['transaction_hash'],
                'isSynced'         => 1,
            ]);

        $res = [
            'status_code' => 1,
            'message'     => $affected > 0 ? 'Earning logs updated successfully.' : 'No matching records found.',
            'first_id'   => $firstId,
            'last_id'    => $lastId,
            'updated'     => $affected,
        ];

        return is_mobile($type, "pages.income_overview", $res, "view");
    }

    public function syncLevelEarningLogs(Request $request)
    {
        $type = "API";

        $data = levelEarningLogsModel::select(
            'level_earning_logs.id',
            'level_earning_logs.user_id',
            'users.wallet_address',
            'level_earning_logs.amount',
            'level_earning_logs.tag',
            'level_earning_logs.refrence',
            'level_earning_logs.refrence_id',
            'level_earning_logs.isSynced',
            'level_earning_logs.transaction_hash',
            'level_earning_logs.created_on'
        )
            ->join('users', 'users.id', '=', 'level_earning_logs.user_id')
            ->where('level_earning_logs.isSynced', 0)
            ->orderBy('level_earning_logs.id', 'asc')
            ->limit(100)
            ->get()
            ->toArray();

        if (count($data) > 0) {
            $res['status_code'] = 1;
            $res['message'] = "Earning logs fetched successfully.";
            $res['data'] = $data;
            $res['first_id'] = $data[0]['id'];
            $res['last_id'] = $data[count($data) - 1]['id'];
        } else {
            $res['status_code'] = 0;
            $res['message'] = "No unsynced earning logs found.";
        }

        return is_mobile($type, "pages.income_overview", $res, "view");
    }

    public function syncLevelEarningLogsUpdate(Request $request)
    {
        $type = "API";

        $data = $request->validate([
            'first_id'        => 'required|integer|min:1',
            'last_id'         => 'required|integer|min:1',
            'transaction_hash' => 'required|string|max:255',
            'claim_hash'       => 'required|string|max:255',
        ]);

        $firstId = min($data['first_id'], $data['last_id']);
        $lastId  = max($data['first_id'], $data['last_id']);

        $affected = levelEarningLogsModel::whereBetween('id', [$firstId, $lastId])
            ->where('isSynced', 0)
            ->update([
                'transaction_hash' => $data['transaction_hash'],
                'isSynced'         => 1,
            ]);

        $res = [
            'status_code' => 1,
            'message'     => $affected > 0 ? 'Earning logs updated successfully.' : 'No matching records found.',
            'first_id'   => $firstId,
            'last_id'    => $lastId,
            'updated'     => $affected,
        ];

        return is_mobile($type, "pages.income_overview", $res, "view");
    }


    public function getRankBonus(Request $request)
    {
        $type = $request->input("type");
        $userId = $request->input("user_id");

        $cutoff = now()->subHours(24);

        // $data = earningLogsModel::select('id', 'user_id', 'amount', 'tag', 'created_on')
        //                                 ->where('user_id', $userId)
        //                                 ->where('tag','DIFF-TEAM-BONUS')
        //                                 ->where('created_on', '>=', $cutoff)
        //                                 ->orderBy('id', 'desc')  
        //                                 // ->limit(100)
        //                                 ->get()
        //                                 ->toArray();
         $data = earningLogsModel::selectRaw('
                                        MIN(id) as id,
                                        user_id,
                                        SUM(amount) as amount,
                                        tag,
                                        created_on
                                    ')
                                    ->where('user_id', $userId)
                                    ->where('tag', 'DIFF-TEAM-BONUS')
                                    ->where('created_on', '>=', $cutoff)
                                    ->groupBy('user_id', 'tag', 'created_on')
                                    ->orderBy('id', 'desc')
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

        // $data = levelEarningLogsModel::select('id', 'user_id', 'amount', 'tag', 'created_on')
        //                                 ->where('user_id', $userId)
        //                                 ->where('tag', 'LIKE', '%LEVEL%')
        //                                 ->where('created_on', '>=', $cutoff)
        //                                 ->orderBy('id', 'desc')  
        //                                 ->get()
        //                                 ->toArray();

        $data = levelEarningLogsModel::selectRaw('
                                        MIN(id) as id,
                                        user_id,
                                        SUM(amount) as amount,
                                        tag,
                                        created_on
                                    ')
                                    ->where('user_id', $userId)
                                    ->where('tag', 'LIKE', '%LEVEL%')
                                    ->where('created_on', '>=', $cutoff)
                                    ->groupBy('user_id', 'tag', 'created_on')
                                    ->orderBy('id', 'desc')
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
