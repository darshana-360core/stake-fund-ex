<?php
$host = '127.0.0.1';
$user = 'root';
$password = 'JdNW3a4m6P6BUGoa';
$dbname = 'stakefundx';

$conn = mysqli_connect($host, $user, $password, $dbname);

$pool = $_POST['pool'] ?? null;

// Validate inputs
if (empty($pool)) {
    $res = [
        'status_code' => 0,
        'message' => 'Missing parameters: amount or transaction_hash'
    ];
    echo json_encode($res, true);
    die;
}

date_default_timezone_set("Asia/Kolkata");

if (!empty($_POST['date'])) {
    // Agar API se date aa gayi hai to use lo
    $effectiveDate = date("Y-m-d H:i:s", strtotime($_POST['date']));
} else {
    // Nahi aayi to apna cutoff time wala logic chalao
    $currentTime = new DateTime();
    $cutoffTime = new DateTime("today 16:30");

    if ($currentTime > $cutoffTime) {
        $effectiveDate = $currentTime->modify("+1 day")->format("Y-m-d H:i:s");
    } else {
        $effectiveDate = $currentTime->format("Y-m-d H:i:s");
    }
}

// 31 din purani date calculate karo
$checkingDate = date("Y-m-d", strtotime($effectiveDate . " -1 days"));

$userAddress = [];
$getAllUsers = mysqli_query($conn, "SELECT id, wallet_address FROM users");
while ($fetAllUsers = mysqli_fetch_assoc($getAllUsers)) {
    $userAddress[$fetAllUsers["id"]] = $fetAllUsers["wallet_address"];
}

$newCompound = [];
$getNewCompound = mysqli_query($conn, "SELECT * FROM user_plans WHERE transaction_hash not like '%0x8c8bc84749017ab6d6b13e36d92b%' and DATE_FORMAT(created_on, '%Y-%m-%d') = '$checkingDate'");
while ($fetNewCompound = mysqli_fetch_assoc($getNewCompound)) {
    if (!isset($newCompound[$fetNewCompound["user_id"]])) {
        $newCompound[$fetNewCompound["user_id"]] = 0;
    }
    $newCompound[$fetNewCompound["user_id"]] += $fetNewCompound["compound_amount"];
}

$newInvestments = [];
$getNewInvestment = mysqli_query($conn, "SELECT * FROM user_plans WHERE transaction_hash not like '%0x8c8bc84749017ab6d6b13e36d92b%' and DATE_FORMAT(created_on, '%Y-%m-%d') = '$checkingDate'");
while ($fetNewInvestment = mysqli_fetch_assoc($getNewInvestment)) {
    if (!isset($newInvestments[$fetNewInvestment["user_id"]])) {
        $newInvestments[$fetNewInvestment["user_id"]] = 0;
    }
    $newInvestments[$fetNewInvestment["user_id"]] += $fetNewInvestment["amount"];
}

$newUnstake = [];
$getNewUnstake = mysqli_query($conn, "SELECT * FROM withdraw WHERE DATE_FORMAT(created_on, '%Y-%m-%d') >= '$checkingDate' AND withdraw_type = 'UNSTAKE' AND status = 1");
while ($fetNewUnstake = mysqli_fetch_assoc($getNewUnstake)) {
    if (!isset($newUnstake[$fetNewUnstake["user_id"]])) {
        $newUnstake[$fetNewUnstake["user_id"]] = 0;
    }
    $newUnstake[$fetNewUnstake["user_id"]] += $fetNewUnstake["amount"];
}

// Handle pagination
$limit = 15;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$investmentKeys = array_keys($newInvestments);
$totalRows = count($investmentKeys);
$paginatedKeys = array_slice($investmentKeys, $offset, $limit);
$loadMore = ($offset + $limit) < $totalRows;

// Calculate total reward
$totalReward = 0;
$totalDayStake = 0;
foreach ($userAddress as $key => $value) {
    $reward = ($newInvestments[$key] ?? 0);
    $reward = $reward > 0 ? $reward : 0;
    $totalReward += round($reward, 6);

    $totalDayStake += ($newInvestments[$key] ?? 0);
}

$res['status_code'] = 1;
$res['message'] = "Success";

$getPercenatge = getPercentageByDate($checkingDate);

if($pool == "FOUNDER")
{
    $total_reward = round(($totalReward * 0.15), 6);

    if($total_reward > 2700)
    {
        $total_reward = 2700;

        $total_reward = ($total_reward * $getPercenatge) / 100;
    }

    $reward_per_user = round(($total_reward), 6) / 11;
    $res['total_reward'] = $total_reward;
    $res['reward_per_user'] = $reward_per_user;
    $res['total_members'] = 11;
}else if($pool == "FOUNDER-POOL")
{
    $total_reward = round(($totalReward * 0.15), 6);

    if($total_reward > 2700)
    {
        $total_reward = 2700;

        $total_reward = ($total_reward * $getPercenatge) / 100;
    }

    $reward_per_user = round(($total_reward), 6) / 5;
    $res['total_reward'] = $total_reward;
    $res['reward_per_user'] = $reward_per_user;
    $res['total_members'] = 5;
}else if($pool == "MARKETING")
{
    //Changes started by shikhar on 22-08-2025
    $founder_total_reward = round(($totalReward * 0.05), 6);

    if($founder_total_reward > 2700)
    {
        $founder_total_reward = 2700;

        $founder_total_reward = ($founder_total_reward * $getPercenatge) / 100;
    }

    $total_reward = round(($founder_total_reward * 0.40), 6);
    //Changes end by shikhar on 22-08-2025

    // $total_reward = round(($totalReward * 0.01), 6);

    // if($total_reward > 500)
    // {
    //     $total_reward = 500;

    //     $total_reward = ($total_reward * $getPercenatge) / 100;
    // }

    $reward_per_user = round(($total_reward), 6) / 1;
    $res['total_reward'] = $total_reward;
    $res['reward_per_user'] = $reward_per_user;
    $res['total_members'] = 1;
}else if($pool == "PROMOTER")
{
    $total_reward = round(($totalReward * 0.01), 6);

    if($total_reward > 500)
    {
        $total_reward = 500;

        $total_reward = ($total_reward * $getPercenatge) / 100;
    }

    $reward_per_user = round(($total_reward), 6) / 1;
    $res['total_reward'] = $total_reward;
    $res['reward_per_user'] = $reward_per_user;
    $res['total_members'] = 1;
}else if($pool == "LIC")
{
    $total_reward = round(($totalReward * 0.01), 6);

    if($total_reward > 500)
    {
        $total_reward = 500;

        $total_reward = ($total_reward * $getPercenatge) / 100;
    }

    $reward_per_user = round(($total_reward), 6) / 1;
    $res['total_reward'] = $total_reward;
    $res['reward_per_user'] = $reward_per_user;
    $res['total_members'] = 1;
}else if($pool == "GIC")
{
    $total_reward = round(($totalDayStake * 0.15), 6);
    $reward_per_user = round(($total_reward), 6) / 5;
    $res['total_reward'] = $totalDayStake;
    $res['reward_per_user'] = $reward_per_user;
    $res['total_members'] = 5;
}
$res['date'] = $effectiveDate;
$res['distribution_date'] = $checkingDate;
$res['getPercenatge'] = $getPercenatge;

echo json_encode($res, true);

function getPercentageByDate($date)
{
    // Force format to dd-mm-yyyy
    $formattedDate = date('d-m-Y', strtotime($date));

    // Special case: 19-08-2025 always returns 80
    if ($formattedDate === '19-07-2025') {
        return 80;
    }

    // Create a consistent hash based on the date
    $hash = crc32($formattedDate); // or md5/sha1 if you want longer strings

    // Map hash to a number between 80 and 100
    $min = 80;
    $max = 100;

    // Convert hash to a number in the range
    $percentage = $min + ($hash % ($max - $min + 1));

    return $percentage;
}
?>