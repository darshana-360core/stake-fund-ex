<?php
$host = '127.0.0.1';
$user = 'root';
$password = 'Sh44RLR7Bb4lBU6';
$dbname = 'orbitx';

$conn = mysqli_connect($host, $user, $password, $dbname);

date_default_timezone_set("Asia/Kolkata");

$currentTime = new DateTime();
$cutoffTime = new DateTime("today 16:30");

if ($currentTime > $cutoffTime) {
    $effectiveDate = $currentTime->modify("+1 day")->format("Y-m-d H:i:s");
} else {
    $effectiveDate = $currentTime->format("Y-m-d H:i:s");
}

$checkingDate = date("Y-m-d", strtotime($effectiveDate . " -31 days"));

$userAddress = [];
$getAllUsers = mysqli_query($conn, "SELECT id, wallet_address FROM users");
while ($fetAllUsers = mysqli_fetch_assoc($getAllUsers)) {
    $userAddress[$fetAllUsers["id"]] = $fetAllUsers["wallet_address"];
}

$newCompound = [];
$getNewCompound = mysqli_query($conn, "SELECT * FROM user_plans WHERE transaction_hash not like '%0x8c8bc84749017ab6d6b13e36d92b%' and DATE_FORMAT(created_on, '%Y-%m-%d') = '$checkingDate'");
while ($fetNewCompound = mysqli_fetch_assoc($getNewCompound)) {
    $newCompound[$fetNewCompound["user_id"]] += $fetNewCompound["compound_amount"];
}

$newInvestments = [];
$getNewInvestment = mysqli_query($conn, "SELECT * FROM user_plans WHERE transaction_hash not like '%0x8c8bc84749017ab6d6b13e36d92b%' and DATE_FORMAT(created_on, '%Y-%m-%d') = '$checkingDate'");
while ($fetNewInvestment = mysqli_fetch_assoc($getNewInvestment)) {
    $newInvestments[$fetNewInvestment["user_id"]] += $fetNewInvestment["amount"];
}

$newUnstake = [];
$getNewUnstake = mysqli_query($conn, "SELECT * FROM withdraw WHERE DATE_FORMAT(created_on, '%Y-%m-%d') >= '$checkingDate' AND withdraw_type = 'UNSTAKE' AND status = 1");
while ($fetNewUnstake = mysqli_fetch_assoc($getNewUnstake)) {
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
foreach ($userAddress as $key => $value) {
    $reward = ($newInvestments[$key] ?? 0) - ($newUnstake[$key] ?? 0);
    $reward = $reward > 0 ? $reward : 0;
    $totalReward += round($reward * 0.05, 6);
}

$lastDayPoolMember = 0;
$lastDayPoolArray = array();
$lastDayPool = mysqli_query($conn, "SELECT amount, date_format(created_on, '%Y-%m-%d') as created_on FROM other_pools WHERE pool = 'FOUNDER'");
// $lastDayPoolAmount = mysqli_fetch_assoc($lastDayPool);
while($lastDayPoolAmount = mysqli_fetch_assoc($lastDayPool))
{
    $lastDayPoolArray[$lastDayPoolAmount['created_on']] = $lastDayPoolAmount['amount'];
};


foreach($lastDayPoolArray as $k => $v)
{
    $lastDayPoolMember += $v;
}

if($totalReward > 2700)
{
    $totalReward = 2700;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Founder Pools</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400..800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="icon" href="https://defi.orbitx.world/assets/images/favico.ico">
</head>
<body class="bg-black text-white">
<section class="py-4">
    <div class="container p-4 md:p-5 mx-auto">
        <div class="w-full bg-[#171531] rounded-xl">
            <div class="text-center mb-5 sm:mb-12">
                <div class="inline-flex items-center gap-3 mb-3 sm:mb-6">
                    <div class="w-2 h-12 bg-gradient-to-b from-blue-400 to-purple-600 rounded-full"></div>
                    <h1 class="text-3xl md:text-5xl font-bold text-white">Founder Pool Rewards</h1>
                    <div class="w-2 h-12 bg-gradient-to-b from-purple-600 to-pink-600 rounded-full"></div>
                </div>
                <p class="text-base sm:text-xl text-gray-300 max-w-3xl mx-auto">
                   27 Pool Members And Pool Amount for <?php echo date("d-m-Y", strtotime($checkingDate)); ?> — <?php echo round(($totalReward), 2); ?> RTX
                </p>
                <div class="mt-2 sm:mt-4 inline-flex items-center gap-2 bg-gray-800/50 rounded-full px-4 py-2 text-sm text-gray-400">
                    <div class="w-2 h-2 rounded-full bg-green-500"></div>
                    Live contract data
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12 overflow-hidden">
                            <div class="group relative flex flex-row items-center justify-between gap-2 md:gap-4 rounded-3xl px-6 pt-8 pb-12 text-left text-black border overflow-hidden" style="background-color: #0BF4C8; border-color: #0BF4C8;">
                                <div class="flex flex-col relative z-10">
                                    <p class="text-base lg:text-lg leading-none mb-6 font-semibold">Pool Allocation</p>
                                    <h4 class="text-lg lg:text-xl text-black font-semibold leading-none">5%</h4>
                                </div>
                                <div class="max-w-28 h-auto max-h-24 md:max-w-32 flex-shrink-0 absolute bottom-3 right-3 z-[9] pointer-events-none opacity-45">
                                    <img src="https://defi.orbitx.world/assets/images/icons/marketvalue.webp" alt="" class="object-contain w-full h-full rounded">
                                </div>
                                <div class="w-full h-full absolute top-0 left-0 p-2 z-0 pointer-events-none opacity-35">
                                    <img src="https://defi.orbitx.world/assets/images/boxbgline.svg" alt="boxbgline" class="w-auto h-auto object-contain mx-auto">
                                </div>
                                <div class="hidden md:flex w-10 h-full absolute top-0 -left-8 z-0 pointer-events-none flex-col items-center justify-center gap-6"><span class="bg-[#0f0f1c] w-full h-3.5 rounded-full block"></span><span class="bg-[#0f0f1c] w-full h-3.5 rounded-full block"></span><span class="bg-[#0f0f1c] w-full h-3.5 rounded-full block"></span></div>
                                <div class="hidden md:flex w-10 h-full absolute top-0 -right-8 z-0 pointer-events-none flex-col items-center justify-center gap-6"><span class="bg-[#0f0f1c] w-full h-3.5 rounded-full block"></span><span class="bg-[#0f0f1c] w-full h-3.5 rounded-full block"></span><span class="bg-[#0f0f1c] w-full h-3.5 rounded-full block"></span></div>
                        </div>

                        <div class="group relative flex flex-row items-center justify-between gap-2 md:gap-4 rounded-3xl px-6 pt-8 pb-12 text-left text-black border  overflow-hidden" style="background-color: #FAD85D; border-color: #FAD85D;">
                            <div class="flex flex-col relative z-10">
                                <p class="text-base lg:text-lg leading-none mb-6 font-semibold">Pool Amount - <?php echo date(
                                    "d-m-Y",
                                    strtotime($checkingDate)
                                ); ?></p>
                                <h4 class="text-lg lg:text-xl text-black font-semibold leading-none"><?php echo round(
                                    ($totalReward),
                                    2
                                ); ?> RTX</h4>
                            </div>
                            <div class="max-w-28 h-auto max-h-24 md:max-w-32 flex-shrink-0 absolute bottom-3 right-3 z-[9] pointer-events-none opacity-45">
                                <img src="https://defi.orbitx.world/assets/images/icons/totalsupply.webp" alt="" class="object-contain w-full h-full rounded">
                            </div>
                            <div class="w-full h-full absolute top-0 left-0 p-2 z-0 pointer-events-none opacity-35">
                                <img src="https://defi.orbitx.world/assets/images/boxbgline.svg" alt="boxbgline" class="w-auto h-auto object-contain mx-auto">
                            </div>
                            <div class="hidden md:flex w-10 h-full absolute top-0 -left-8 z-0 pointer-events-none flex-col items-center justify-center gap-6">
                                <span class="bg-[#0f0f1c] w-full h-3.5 rounded-full block"></span>
                                <span class="bg-[#0f0f1c] w-full h-3.5 rounded-full block"></span>
                                <span class="bg-[#0f0f1c] w-full h-3.5 rounded-full block"></span>
                            </div>
                            <div class="hidden md:flex w-10 h-full absolute top-0 -right-8 z-0 pointer-events-none flex-col items-center justify-center gap-6">
                                <span class="bg-[#0f0f1c] w-full h-3.5 rounded-full block"></span>
                                <span class="bg-[#0f0f1c] w-full h-3.5 rounded-full block"></span>
                                <span class="bg-[#0f0f1c] w-full h-3.5 rounded-full block"></span>
                            </div>
                        </div>
                        
                        <div class="group relative flex flex-row items-center justify-between gap-2 md:gap-4 rounded-3xl px-6 pt-8 pb-12 text-left text-black border  overflow-hidden" style="background-color: #F2A0FF; border-color: #F2A0FF;">
                            <div class="flex flex-col relative z-10">
                                <p class="text-base lg:text-lg leading-none mb-6 font-semibold">Total Per Member Amount</p>
                                <h4 class="text-lg lg:text-xl text-black font-semibold leading-none"><?php echo round($lastDayPoolMember,2); ?> RTX</h4>
                            </div>
                            <div class="max-w-28 h-auto max-h-24 md:max-w-32 flex-shrink-0 absolute bottom-3 right-3 z-[9] pointer-events-none opacity-45">
                                <img src="https://defi.orbitx.world/assets/images/icons/rtxprice.webp" alt="" class="object-contain w-full h-full rounded">
                            </div>
                            <div class="w-full h-full absolute top-0 left-0 p-2 z-0 pointer-events-none opacity-35">
                                <img src="https://defi.orbitx.world/assets/images/boxbgline.svg" alt="boxbgline" class="w-auto h-auto object-contain mx-auto">
                            </div>
                            <div class="hidden md:flex w-10 h-full absolute top-0 -left-8 z-0 pointer-events-none flex-col items-center justify-center gap-6"><span class="bg-[#0f0f1c] w-full h-3.5 rounded-full block"></span><span class="bg-[#0f0f1c] w-full h-3.5 rounded-full block"></span><span class="bg-[#0f0f1c] w-full h-3.5 rounded-full block"></span></div>
                            <div class="hidden md:flex w-10 h-full absolute top-0 -right-8 z-0 pointer-events-none flex-col items-center justify-center gap-6"><span class="bg-[#0f0f1c] w-full h-3.5 rounded-full block"></span><span class="bg-[#0f0f1c] w-full h-3.5 rounded-full block"></span><span class="bg-[#0f0f1c] w-full h-3.5 rounded-full block"></span></div>
                        </div>

                        <div class="group relative flex flex-row items-center justify-between gap-2 md:gap-4 rounded-3xl px-6 pt-8 pb-12 text-left text-black border  overflow-hidden" style="background-color: #f87171; border-color: #f87171;">
                            <div class="flex flex-col relative z-10">
                                <p class="text-base lg:text-lg leading-none mb-6 font-semibold">Per Member Amount</p>
                                <h4 class="text-lg lg:text-xl text-black font-semibold leading-none"><?php echo round(($totalReward) / 27,2); ?> RTX</h4>
                            </div>
                            <div class="max-w-28 h-auto max-h-24 md:max-w-32 flex-shrink-0 absolute bottom-3 right-3 z-[9] pointer-events-none opacity-45">
                                <img src="https://defi.orbitx.world/assets/images/icons/userface.webp" alt="" class="object-contain w-full h-full rounded">
                            </div>
                            <div class="w-full h-full absolute top-0 left-0 p-2 z-0 pointer-events-none opacity-35">
                                <img src="https://defi.orbitx.world/assets/images/boxbgline.svg" alt="boxbgline" class="w-auto h-auto object-contain mx-auto">
                            </div>
                            <div class="hidden md:flex w-10 h-full absolute top-0 -left-8 z-0 pointer-events-none flex-col items-center justify-center gap-6"><span class="bg-[#0f0f1c] w-full h-3.5 rounded-full block"></span><span class="bg-[#0f0f1c] w-full h-3.5 rounded-full block"></span><span class="bg-[#0f0f1c] w-full h-3.5 rounded-full block"></span></div>
                            <div class="hidden md:flex w-10 h-full absolute top-0 -right-8 z-0 pointer-events-none flex-col items-center justify-center gap-6"><span class="bg-[#0f0f1c] w-full h-3.5 rounded-full block"></span><span class="bg-[#0f0f1c] w-full h-3.5 rounded-full block"></span><span class="bg-[#0f0f1c] w-full h-3.5 rounded-full block"></span></div>
                        </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-gray-900 rounded-lg overflow-hidden">
                    <thead class="bg-gray-700 text-gray-300 text-sm uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3 text-left">Sr NO.</th>
                            <th class="px-6 py-3 text-left">User ID</th>
                            <th class="px-6 py-3 text-left">Staked</th>
                            <th class="px-6 py-3 text-left">Unstaked</th>
                            <th class="px-6 py-3 text-left">Reward (5%)</th>
                        </tr>
                    </thead>
                    <tbody id="reward-table" class="text-gray-100 divide-y divide-gray-700 text-sm">
                        <?php $srNo = $offset + 1; ?>
                        <?php foreach ($paginatedKeys as $key): ?>
                        <tr>
                            <td class="px-6 py-4"><?php echo $srNo++; ?></td>
                            <td class="px-6 py-4"><?php echo substr($userAddress[$key], 0, 6) . "..." . substr($userAddress[$key], -6); ?></td>
                            <td class="px-6 py-4"><?php echo $newInvestments[$key] ?? 0; ?></td>
                            <td class="px-6 py-4"><?php echo $newUnstake[$key] ?? 0; ?></td>
                            <td class="px-6 py-4">
                                <?php
                                $reward = ($newInvestments[$key] ?? 0) - ($newUnstake[$key] ?? 0);
                                $reward = $reward > 0 ? $reward : 0;
                                echo round($reward * 0.05, 6);
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($loadMore): ?>
            <div class="text-center mt-4">
                <button id="loadMoreBtn" data-offset="<?php echo $offset + $limit; ?>"
                    class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg">
                    Load More
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
document.getElementById("loadMoreBtn")?.addEventListener("click", function () {
    const button = this;
    const offset = button.dataset.offset;

    fetch(window.location.pathname + "?offset=" + offset)
        .then(response => response.text())
        .then(data => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(data, 'text/html');
            const newRows = doc.querySelectorAll('#reward-table tr');
            const rewardTable = document.querySelector('#reward-table');

            newRows.forEach(row => rewardTable.appendChild(row));

            const newButton = doc.querySelector('#loadMoreBtn');
            if (newButton) {
                button.dataset.offset = newButton.dataset.offset;
            } else {
                button.remove();
            }
        });
});
</script>
</body>
</html>
