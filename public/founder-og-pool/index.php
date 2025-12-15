<?php
$host = '127.0.0.1';
$user = 'root';
$password = 'JdNW3a4m6P6BUGoa';
$dbname = 'stakefundx';

$conn = mysqli_connect($host, $user, $password, $dbname);

date_default_timezone_set("Asia/Kolkata");

$currentTime = new DateTime();
$cutoffTime = new DateTime("today 16:30");

if ($currentTime > $cutoffTime) {
    $effectiveDate = $currentTime->modify("+1 day")->format("Y-m-d H:i:s");
} else {
    $effectiveDate = $currentTime->format("Y-m-d H:i:s");
}

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
foreach ($userAddress as $key => $value) {
    $reward = ($newInvestments[$key] ?? 0) - ($newUnstake[$key] ?? 0);
    $reward = $reward > 0 ? $reward : 0;
    $totalReward += round($reward * 0.15, 6);
}

$lastDayPoolMember = 0;
$lastDayPoolArray = array();
$lastDayPool = mysqli_query($conn, "SELECT amount, date_format(created_on, '%Y-%m-%d') as created_on FROM other_pools WHERE pool = 'FOUNDER'");
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

// NEW CODE (unchanged)
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://stakefundx.truepoints.io/founder-pool/api.php',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => array('type' => 'API','pool' => 'FOUNDER-POOL'),
));
$response = curl_exec($curl);
curl_close($curl);
$newResponse = json_decode($response, true);
$totalReward = $newResponse['total_reward'];
$reward_per_user = $newResponse['reward_per_user'];
// NEW CODE
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Founder Pools</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link rel="icon" href="https://defi.orbitx.world/assets/images/favico.ico" />
  <style>
    :root{
      --card: rgba(255,255,255,0.04);
      --card-border: rgba(255,255,255,0.10);
      --glow: 0 10px 40px rgba(91, 75, 255, 0.35);
      --grid: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.07) 1px, transparent 0);
    }
    body{font-family:"Poppins",system-ui,sans-serif;}
    .bg-hero{
      background:
        radial-gradient(60rem 60rem at 110% -10%, rgba(168,85,247,0.20), transparent 40%),
        radial-gradient(50rem 50rem at -10% 110%, rgba(59,130,246,0.18), transparent 40%),
        #050509;
    }
    .grid-overlay{background-image:var(--grid);background-size:24px 24px;mask-image:linear-gradient(to bottom, rgba(0,0,0,0.7), transparent);opacity:.35;pointer-events:none;}
    .card{background:linear-gradient(180deg, rgba(255,255,255,.05), rgba(255,255,255,.02));border:1px solid var(--card-border);backdrop-filter:blur(8px);transition:transform .2s ease, box-shadow .2s ease, border-color .2s ease;}
    .card:hover{transform:translateY(-4px);box-shadow:var(--glow);border-color:rgba(255,255,255,0.18);}
    .pill{border:1px solid rgba(255,255,255,0.15);background:rgba(255,255,255,0.06);backdrop-filter:blur(6px);}
    .shine{position:relative;overflow:hidden;}
    .shine::after{content:"";position:absolute;inset:-200% -50% auto -50%;height:200%;transform:rotate(25deg);background:linear-gradient(90deg, transparent, rgba(255,255,255,.12), transparent);animation:shine 6s linear infinite;}
    @keyframes shine{0%{left:-120%}100%{left:120%}}
    .tag{font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;}
    .icon-wrap{width:64px;height:64px;opacity:.85;}
    .bg-lines{position:absolute;inset:0;pointer-events:none;opacity:.30}
  </style>
</head>

<body class="bg-hero text-white min-h-full relative">
  <!-- Decorative grid -->
  <div class="grid-overlay absolute inset-0"></div>

  <section class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="text-center mb-10">
      <div class="inline-flex items-center gap-3 mb-5">
        <span class="w-2 h-10 rounded-full bg-gradient-to-b from-gray-400 to-white-600"></span>
        <h1 class="text-4xl md:text-5xl font-extrabold">Founder Pool Rewards</h1>
        <span class="w-2 h-10 rounded-full bg-gradient-to-b from-gray-400 to-white-600"></span>
      </div>
      <p class="text-base sm:text-xl text-gray-300 max-w-3xl mx-auto">
        11 Pool Members And Pool Amount for <?php echo date("d-m-Y", strtotime($checkingDate)); ?> — <?php echo round(($totalReward), 2); ?> SDX
      </p>
      <div class="mt-4 inline-flex items-center gap-2 pill rounded-full px-4 py-2 text-sm text-gray-300">
        <span class="w-2 h-2 rounded-full bg-green-500"></span>
        Live contract data
      </div>
    </div>

    <div class="w-full bg-[#171531] rounded-xl relative overflow-hidden">
      <!-- subtle decorative lines background -->
      <svg class="bg-lines" viewBox="0 0 600 200" preserveAspectRatio="none" aria-hidden="true">
        <defs>
          <linearGradient id="gridStroke" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#ffffff" stop-opacity=".12"/>
            <stop offset="1" stop-color="#ffffff" stop-opacity=".04"/>
          </linearGradient>
        </defs>
        <g fill="none" stroke="url(#gridStroke)" stroke-width="1">
          <line x1="0" y1="20" x2="600" y2="20"/>
          <line x1="0" y1="60" x2="600" y2="60"/>
          <line x1="0" y1="100" x2="600" y2="100"/>
          <line x1="0" y1="140" x2="600" y2="140"/>
        </g>
      </svg>

      <!-- Stat Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 p-6">
        <!-- Pool Allocation -->
        <div class="card rounded-2xl p-6 shine">
          <div class="flex items-center justify-between">
            <div>
              <div class="tag text-gray-300 mb-2">Pool Allocation</div>
              <div class="text-3xl font-extrabold">7.5%</div>
            </div>
            <!-- SVG Donut -->
            <svg class="icon-wrap" viewBox="0 0 48 48" fill="none" aria-hidden="true">
              <defs>
                <linearGradient id="grad1" x1="0" y1="0" x2="48" y2="48">
                  <stop stop-color="#0BF4C8"/><stop offset="1" stop-color="#22d3ee"/>
                </linearGradient>
              </defs>
              <circle cx="24" cy="24" r="18" stroke="url(#grad1)" stroke-width="6" opacity=".35"/>
              <path d="M24 6a18 18 0 1 1-12.73 30.73L24 24V6z" fill="url(#grad1)"/>
              <circle cx="24" cy="24" r="8" fill="#171531"/>
            </svg>
          </div>
          <div class="mt-4 h-2 w-full bg-white bg-opacity-10 rounded-full overflow-hidden">
            <div class="h-full bg-gradient-to-r from-emerald-400 to-cyan-300" style="width:7.5%;"></div>
          </div>
        </div>

        <!-- Pool Amount -->
        <div class="card rounded-2xl p-6 shine">
          <div class="flex items-center justify-between">
            <div>
              <div class="tag text-gray-300 mb-2">Pool Amount — <?php echo date("d-m-Y", strtotime($checkingDate)); ?></div>
              <div class="text-3xl font-extrabold"><?php echo round(($totalReward), 2); ?> SDX</div>
            </div>
            <!-- SVG Coins -->
            <svg class="icon-wrap" viewBox="0 0 64 64" fill="none" aria-hidden="true">
              <defs>
                <linearGradient id="grad2" x1="0" y1="0" x2="64" y2="64">
                  <stop stop-color="#fde047"/><stop offset="1" stop-color="#f59e0b"/>
                </linearGradient>
              </defs>
              <ellipse cx="32" cy="16" rx="16" ry="6" fill="url(#grad2)" opacity=".9"/>
              <path d="M16 16v8c0 3.3 7.2 6 16 6s16-2.7 16-6v-8" fill="url(#grad2)" opacity=".65"/>
              <ellipse cx="32" cy="24" rx="16" ry="6" fill="url(#grad2)" opacity=".95"/>
              <path d="M16 24v8c0 3.3 7.2 6 16 6s16-2.7 16-6v-8" fill="url(#grad2)" opacity=".6"/>
              <ellipse cx="32" cy="32" rx="16" ry="6" fill="url(#grad2)" opacity=".95"/>
            </svg>
          </div>
          <p class="mt-3 text-sm text-gray-300">Auto-updated from live calculations.</p>
        </div>

        <!-- Total Per Member Amount -->
        <div class="card rounded-2xl p-6 shine">
          <div class="flex items-center justify-between">
            <div>
              <div class="tag text-gray-300 mb-2">Total Per Member Amount</div>
              <div class="text-3xl font-extrabold"><?php echo round($lastDayPoolMember,2); ?> SDX</div>
            </div>
            <!-- SVG Gauge -->
            <svg class="icon-wrap" viewBox="0 0 48 48" fill="none" aria-hidden="true">
              <defs>
                <linearGradient id="grad3" x1="0" y1="0" x2="48" y2="48">
                  <stop stop-color="#e879f9"/><stop offset="1" stop-color="#a78bfa"/>
                </linearGradient>
              </defs>
              <path d="M8 32a16 16 0 1 1 32 0" stroke="url(#grad3)" stroke-width="4" fill="none" stroke-linecap="round"/>
              <circle cx="24" cy="32" r="3" fill="url(#grad3)"/>
              <path d="M24 32 L36 20" stroke="url(#grad3)" stroke-width="3" stroke-linecap="round"/>
            </svg>
          </div>
        </div>

        <!-- Per Member Amount -->
        <div class="card rounded-2xl p-6 shine">
          <div class="flex items-center justify-between">
            <div>
              <div class="tag text-gray-300 mb-2">Per Member Amount</div>
              <div class="text-3xl font-extrabold"><?php echo round(($totalReward) / 5,2); ?> SDX</div>
            </div>
            <!-- SVG Users -->
            <svg class="icon-wrap" viewBox="0 0 48 48" fill="none" aria-hidden="true">
              <defs>
                <linearGradient id="grad4" x1="0" y1="0" x2="48" y2="48">
                  <stop stop-color="#fb7185"/><stop offset="1" stop-color="#f97316"/>
                </linearGradient>
              </defs>
              <circle cx="16" cy="18" r="6" stroke="url(#grad4)" stroke-width="2" fill="none"/>
              <circle cx="32" cy="18" r="6" stroke="url(#grad4)" stroke-width="2" fill="none" opacity=".9"/>
              <path d="M6 36c0-5.5 4.5-10 10-10s10 4.5 10 10" stroke="url(#grad4)" stroke-width="2" fill="none"/>
              <path d="M22 36c0-5.5 4.5-10 10-10s10 4.5 10 10" stroke="url(#grad4)" stroke-width="2" fill="none" opacity=".9"/>
            </svg>
          </div>
        </div>
      </div>

      <!-- Table (center aligned) -->
      <div class="hidden overflow-x-auto px-6 pb-6">
        <table class="min-w-full bg-gray-900 rounded-lg overflow-hidden">
          <thead class="bg-gray-700 text-gray-300 text-sm uppercase tracking-wider">
            <tr>
              <th class="px-6 py-3 text-center">Sr NO.</th>
              <th class="px-6 py-3 text-center">User ID</th>
              <th class="px-6 py-3 text-center">Staked</th>
              <th class="px-6 py-3 text-center">Unstaked</th>
              <th class="px-6 py-3 text-center">Reward (5%)</th>
            </tr>
          </thead>
          <tbody id="reward-table" class="text-gray-100 divide-y divide-gray-700 text-sm">
            <?php $srNo = $offset + 1; ?>
            <?php foreach ($paginatedKeys as $key): ?>
            <tr class="hover:bg-white hover:bg-opacity-5 transition">
              <td class="px-6 py-4 text-center"><?php echo $srNo++; ?></td>
              <td class="px-6 py-4 text-center font-mono">
                <?php echo substr($userAddress[$key], 0, 6) . "..." . substr($userAddress[$key], -6); ?>
              </td>
              <td class="px-6 py-4 text-center"><?php echo $newInvestments[$key] ?? 0; ?></td>
              <td class="px-6 py-4 text-center"><?php echo $newUnstake[$key] ?? 0; ?></td>
              <td class="px-6 py-4 text-center">
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
      <div class="text-center pb-8">
        <button id="loadMoreBtn" data-offset="<?php echo $offset + $limit; ?>" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg">
          Load More
        </button>
      </div>
      <?php endif; ?>
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
