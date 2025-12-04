<?php

namespace App\Http\Controllers;

use App\Imports\roiDistributionImport;
use App\Models\roiDistributionModel;
use App\Models\usersModel;
use Illuminate\Http\Request;
use Excel;
use Illuminate\Support\Facades\DB;

use function App\Helpers\is_mobile;

class roiDistributionController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->input('type');

        $data = roiDistributionModel::orderBy('id', 'desc')->get()->toArray();

        $res['status_code'] = 1;
        $res['message'] = "Success";
        $res['data'] = $data;

        return is_mobile($type, 'roi_distribution_excel_import', $res, 'view');
    }



    public function store(Request $request)
    {
        $updateData = $request->except('_method', '_token', 'submit');
        $type = $request->input('type');
        $date = $request->input('date');

        $path = $request->file('file')->getRealPath();

        $file_name = "";
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalname = $file->getClientOriginalName();
            $name = "excelfile" . '_' . date('Y-m-d-His');
            $ext = \File::extension($originalname);
            $file_name = $name . '.' . $ext;
            $path = $file->storeAs('public/', $file_name);
        }

        if ($ext != 'xlsx') {
            $res['status_code'] = 0;
            $res['message'] = "Only excel file is allowed.";

            return is_mobile($type, "roi-distribution-import.index", $res);
        }

        $data = Excel::toArray(new roiDistributionImport, request()->file('file'));

        if (count($data) > 0) {
            foreach ($data['0'] as $key => $value) {
                if ($key > 0) {
                    if ($value['0'] != '') {
                        $newRow = array();
                        $newRow['roi'] = $value['0'];
                        $newRow['file_name'] = $file_name;
                        $newRow['date'] = date('Y-m-d', strtotime($value['1'] . "-" . $value['2'] . "-" . $value['3']));
                        $newRow['created_on'] = date('Y-m-d H:i:s');

                        roiDistributionModel::insert($newRow);
                    }
                }
            }
        }

        $res['status_code'] = 1;
        $res['message'] = "Roi Excel Imported Successfully.";

        return is_mobile($type, "roi-distribution-import.index", $res);
    }
}
