<?php

namespace App\Http\Controllers;

use App\Models\adsModel;
use App\Models\usersModel;
use App\Models\userPlansModel;
use Illuminate\Http\Request;

use function App\Helpers\is_mobile;

class adsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $type = $request->input('type');

        $data = adsModel::get()->toArray();

        $res['status_code'] = 1;
        $res['message'] = "Success";
        $res['data'] = $data;

        return is_mobile($type, "ads", $res, 'view');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user_id = $request->session()->get('user_id');
        $updateData = $request->except('_method','_token','submit');
        
        $type = $request->input('type');

        $file_name = "";
		if ($request->hasFile('file')) {
			$file = $request->file('file');
			$originalname = $file->getClientOriginalName();
			$name = "ad-file".'_'.date('YmdHis');
			$ext = \File::extension($originalname);
			$file_name = $name . '.' . $ext;
			$path = $file->storeAs('public/', $file_name);
            $updateData['file'] = $file_name;
		}

        adsModel::insert($updateData);

        $res['status_code'] = 1;
        $res['message'] = "Insert successfully";

        return is_mobile($type, "ads.index", $res);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $type = $request->input('type');

        $editData = adsModel::where(['id' => $id])->get()->toArray();

        $res['status_code'] = 1;
        $res['message'] = "Success";
        $res['editData'] = $editData[0];

        return is_mobile($type, 'ads', $res, 'view');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $updateData = $request->except('_method','_token','submit');
        
        $type = $request->input('type');

        $file_name = "";
		if ($request->hasFile('file')) {
			$file = $request->file('file');
			$originalname = $file->getClientOriginalName();
			$name = "ad-file".'_'.date('YmdHis');
			$ext = \File::extension($originalname);
			$file_name = $name . '.' . $ext;
			$path = $file->storeAs('public/', $file_name);
            $updateData['file'] = $file_name;
		}

        adsModel::where(['id' => $id])->update($updateData);

        $res['status_code'] = 1;
        $res['message'] = "Updated successfully";

        return is_mobile($type, "ads.index", $res);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $type = $request->input('type');

        adsModel::where(['id' => $id])->delete();

        $res['status_code'] = 1;
        $res['message'] = "Deleted Successfully";

        return is_mobile($type, 'ads.index', $res);
    }

    public function adViewed(Request $request)
    {
        $type = "API";
        $user_id = $request->session()->get('user_id');
        $ad_id = $request->input('ad_id');

        adsModel::where(['id' => $ad_id])->increment('views', 1);

        $user = usersModel::where(['id' => $user_id])->get()->toArray();

        $investment = userPlansModel::selectRaw("SUM(amount) as investment")->where(['user_id' => $user_id, 'status' => 1])->get()->toArray();

        if($investment > 0)
        {
            $activeDirects = $user['0']['active_direct'];

            $maxAdRoi = $user['0']['active_direct'] + 2;

            if($user['0']['ad_viewed'] < 7)
            {
                if($user['0']['ad_viewed'] >= $maxAdRoi)
                {   
                    usersModel::where(['id' => $user_id])->update(['ad_viewed' => $maxAdRoi]);
                }else
                {
                    usersModel::where(['id' => $user_id])->increment('ad_viewed', 1);
                }
            }

        }
        $user = usersModel::where(['id' => $user_id])->get()->toArray();

        $res['ad_viewed'] =$user['0']['ad_viewed'];

        $res['status_code'] = 1;
        $res['message'] = "Ad Viewed Successfully";

        return is_mobile($type, 'ads.index', $res);
    }
}