<?php

use App\Http\Controllers\incomeOverviewController;
use App\Http\Controllers\loginController;
use App\Http\Controllers\packagesController;
use App\Http\Controllers\profileController;
use App\Http\Controllers\registerController;
use App\Http\Controllers\supportTicketController;
use App\Http\Controllers\teamController;
use App\Http\Controllers\withdrawController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\admin\loginController as BackendLoginController;
use App\Http\Controllers\admin\withdrawController as BackendwithdrawController;
use App\Http\Controllers\admin\packageController as BackendpackageController;
use App\Http\Controllers\admin\usersController as BackendusersController;
use App\Http\Controllers\admin\levelRoiController as BackendlevelRoiController;
use App\Http\Controllers\admin\settingController as BackendsettingController;
use App\Http\Controllers\admin\orbitxPoolsController as BackendorbitxPoolsController;
use App\Http\Controllers\admin\rankBonusController as BackendrankBonusController;
use App\Http\Controllers\admin\roiDistributionController as BackendroiDistributionController;

use App\Http\Controllers\scriptController as scriptController;

use App\Http\Controllers\TestController;



// // BACKEND ROUTES

Route::get('BHg1XsS2/', function (Request $request) {
    $user_id = $request->session()->get('admin_user_id');
    if (!empty($user_id)) {
        return redirect()->route('dashboard');
    } else {
        return view('login');
    }
})->name('index');

Route::post('BHg1XsS2/login', [BackendLoginController::class, 'index'])->name('login');
Route::any('BHg1XsS2/logout', [BackendLoginController::class, 'logout'])->name('logout');
Route::any('BHg1XsS2/dashboard', [BackendLoginController::class, 'dashboard'])->name('dashboard')->middleware('adminsession');
Route::any('BHg1XsS2/workshop-archiver', [BackendLoginController::class, 'workshop_archiver'])->name('workshop_archiver')->middleware('adminsession');

Route::any('BHg1XsS2/search-member', [BackendpackageController::class, 'searchMember'])->name('searchMember')->middleware('adminsession');
Route::any('BHg1XsS2/investment-process-report', [BackendusersController::class, 'investmentReportt'])->name('investmentReport')->middleware('adminsession');
Route::any('BHg1XsS2/pool-process-report', [BackendusersController::class, 'pool_Reportt'])->name('pool_Reportt')->middleware('adminsession');
Route::any('BHg1XsS2/withdraw-process-report', [BackendusersController::class, 'withdrawReport'])->name('withdrawReport')->middleware('adminsession');
Route::any('BHg1XsS2/turbine-process-report', [BackendusersController::class, 'turbineReport'])->name('turbineReport')->middleware('adminsession');
Route::any('BHg1XsS2/release-process-report', [BackendusersController::class, 'releaseReport'])->name('releaseReport')->middleware('adminsession');
Route::any('BHg1XsS2/level-income-report', [BackendusersController::class, 'level_income_report'])->name('level_income_report')->middleware('adminsession');
Route::any('BHg1XsS2/orbitx-pool-repor-process', [BackendusersController::class, 'orbitx_pool_report'])->name('orbitx_pool_report')->middleware('adminsession');

Route::get('BHg1XsS2/orbitx-pool-report', function (Request $request) {
    return view('orbitx_pool');
})->name('orbitx_pool');

Route::group(['prefix' => 'BHg1XsS2', 'middleware' => ['adminsession']], function () {
    Route::resource('users', BackendusersController::class);
});
