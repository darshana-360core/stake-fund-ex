<?php
$host = '127.0.0.1';
$user = 'root';
$password = 'Truepoints@@987$$';
$dbname = 'stakefundx';

$conn = mysqli_connect($host, $user, $password, $dbname);

$userAddress = array();
$thresholdOn16 = array();

$getAllUsers = mysqli_query($conn, "SELECT id,wallet_address FROM users");
while ($fetAllUsers = mysqli_fetch_assoc($getAllUsers)) {
  $userAddress[$fetAllUsers['id']] = $fetAllUsers['wallet_address'];
  $thresholdOn16[$fetAllUsers['id']] = 0;
}

$newCompound = array();
$getNewCompound = mysqli_query($conn, "SELECT * FROM user_plans ");
while ($fetNewCompound = mysqli_fetch_assoc($getNewCompound)) {
  if (!isset($newCompound[$fetNewCompound['user_id']])) {
    $newCompound[$fetNewCompound['user_id']] = 0;
  }
  $newCompound[$fetNewCompound['user_id']] += $fetNewCompound['compound_amount'];
}

$newInvestments = array();
$getNewInvestment = mysqli_query($conn, "SELECT * FROM user_plans");
while ($fetNewInvestment = mysqli_fetch_assoc($getNewInvestment)) {
  if (!isset($newInvestments[$fetNewInvestment['user_id']])) {
    $newInvestments[$fetNewInvestment['user_id']] = 0;
  }
  $newInvestments[$fetNewInvestment['user_id']] += $fetNewInvestment['amount'];
}

$newUnstake = array();
$getNewUnstake = mysqli_query($conn, "SELECT * FROM withdraw where withdraw_type = 'UNSTAKE' and status = 1");
while ($fetNewUnstake = mysqli_fetch_assoc($getNewUnstake)) {
  if (!isset($newUnstake[$fetNewUnstake['user_id']])) {
    $newUnstake[$fetNewUnstake['user_id']] = 0;
  }
  $newUnstake[$fetNewUnstake['user_id']] += $fetNewUnstake['amount'];
}

$totalReward = 0;
foreach ($userAddress as $key => $value) {
  $prev = $thresholdOn16[$key] ?? 0;
  $new = $newInvestments[$key] ?? 0;
  $reward = ($new > $prev) ? round(($new - $prev) * 0.01, 6) : 0;
  $totalReward += $reward;
}

$getDevEntry = mysqli_query($conn, "SELECT SUM(amount) as amount FROM developer_pools WHERE pool = 'DEVELOPER-OG-POOL'");
$fetDevEntry = mysqli_fetch_assoc($getDevEntry);
$totalReward = ($totalReward - $fetDevEntry['amount']);

if (isset($_POST['type']) && $_POST['type'] == "API") {
  echo json_encode([
    'status_code' => 1,
    'message' => 'Success',
    'pool_amount' => $totalReward
  ]);
  die;
}

$allocationPct = 1;
$poolMembers = 1;
?>
<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Developer OG Pool Rewards</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet" />
  <link rel="icon" href="https://defi.orbitx.world/assets/images/favico.ico" />

  <style>
    :root {
      --card-border: rgba(255, 255, 255, 0.29);
      --glow: 0 8px 40px rgba(91, 75, 255, 0.35);
      --shine: linear-gradient(90deg, transparent, rgba(255, 255, 255, .2), transparent);
      --grid: radial-gradient(circle at 1px 1px, rgba(255, 255, 255, 0.06) 1px, transparent 0);
    }

    body {
      font-family: "Poppins", system-ui, sans-serif;
      background: #0b0b0e;
    }

    .bg-hero {
      background:
        radial-gradient(60rem 60rem at 110% -10%, rgba(168, 85, 247, .15), transparent 40%),
        radial-gradient(50rem 50rem at -10% 110%, rgba(59, 130, 246, .15), transparent 40%),
        #050509;
    }

    .grid-overlay {
      background-image: var(--grid);
      background-size: 24px 24px;
      opacity: .25;
      pointer-events: none;
    }

    .card-dark {
      position: relative;
      background: #111216;
      border: 1px solid var(--card-border);
      border-radius: 18px;
      padding: 24px;
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, .05);
      transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
      overflow: hidden;
    }

    .card-dark:hover {
      transform: translateY(-4px);
      box-shadow: var(--glow);
      border-color: rgba(255, 255, 255, 0.2);
    }

    .card-dark.shine::after {
      content: "";
      position: absolute;
      inset: -200% -50% auto -50%;
      height: 200%;
      background: var(--shine);
      transform: rotate(25deg);
      animation: shine 6s linear infinite;
    }

    @keyframes shine {
      0% {
        left: -120%
      }

      100% {
        left: 120%
      }
    }

    .label {
      font-size: .75rem;
      letter-spacing: .18em;
      text-transform: uppercase;
      color: rgba(255, 255, 255, .7);
    }

    .value {
      font-weight: 800;
      color: #fff;
      line-height: 1;
    }

    .bar {
      height: 8px;
      width: 100%;
      border-radius: 999px;
      background: rgba(255, 255, 255, .15);
    }

    .bar>span {
      display: block;
      height: 100%;
      border-radius: 999px;
      background: rgba(255, 255, 255, .35);
    }

    .icon-spot {
      width: 64px;
      height: 64px;
      opacity: .85;
    }
  </style>
</head>

<body class="bg-hero text-white min-h-full relative">
  <div class="grid-overlay absolute inset-0"></div>

  <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <header class="text-center mb-10">
      <div class="inline-flex items-center gap-3 mb-5">
        <span class="w-2 h-10 rounded-full bg-gradient-to-b from-gray-400 to-white-600"></span>
        <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight">
          Developer OG Pool Rewards
        </h1>
        <span class="w-2 h-10 rounded-full bg-gradient-to-b from-gray-400 to-white-600"></span>
      </div>
      <p class="max-w-2xl mx-auto text-gray-300 text-lg">
        Participate in our reward distribution system powered by transaction fees.
      </p>
      <div
        class="mt-5 inline-flex items-center gap-2 border border-white/15 bg-black/40 rounded-full px-4 py-2 text-sm text-gray-300">
        <span class="w-2 h-2 rounded-full bg-green-500"></span>
        Live contract data
      </div>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="card-dark shine">
        <div class="flex items-center justify-between">
          <div>
            <div class="label mb-3">Pool Allocation</div>
            <div class="value text-3xl md:text-4xl"><?php echo $allocationPct; ?>%</div>
          </div>
          <svg class="icon-spot" viewBox="0 0 48 48" fill="none">
            <defs>
              <linearGradient id="alloc" x1="0" y1="0" x2="48" y2="48">
                <stop stop-color="#0BF4C8" />
                <stop offset="1" stop-color="#22d3ee" />
              </linearGradient>
            </defs>
            <circle cx="24" cy="24" r="18" stroke="url(#alloc)" stroke-width="6" opacity=".25" />
            <path d="M24 6a18 18 0 1 1-12.73 30.73L24 24V6z" fill="url(#alloc)" />
          </svg>
        </div>
        <div class="mt-5 bar"><span style="width:<?php echo $allocationPct; ?>%"></span></div>
      </div>

      <div class="card-dark shine">
        <div class="flex items-center justify-between">
          <div>
            <div class="label mb-3">Pool Amount</div>
            <div class="value text-3xl md:text-4xl"><?php echo number_format(round($totalReward, 2), 2); ?> SDX</div>
            <p class="mt-3 text-sm text-gray-400">Auto-updated from live calculations.</p>
          </div>
          <svg class="icon-spot" viewBox="0 0 64 64" fill="none">
            <defs>
              <linearGradient id="amt" x1="0" y1="0" x2="64" y2="64">
                <stop stop-color="#fde047" />
                <stop offset="1" stop-color="#f59e0b" />
              </linearGradient>
            </defs>
            <ellipse cx="32" cy="18" rx="16" ry="6" fill="url(#amt)" />
            <path d="M16 18v8c0 3.3 7.2 6 16 6s16-2.7 16-6v-8" fill="url(#amt)" opacity=".6" />
          </svg>
        </div>
      </div>

      <div class="card-dark shine">
        <div class="flex items-center justify-between">
          <div>
            <div class="label mb-3">Pool Members</div>
            <div class="value text-3xl md:text-4xl"><?php echo $poolMembers; ?></div>
          </div>
          <svg class="icon-spot" viewBox="0 0 48 48" fill="none">
            <defs>
              <linearGradient id="mem" x1="0" y1="0" x2="48" y2="48">
                <stop stop-color="#fb7185" />
                <stop offset="1" stop-color="#f97316" />
              </linearGradient>
            </defs>
            <circle cx="16" cy="18" r="6" stroke="url(#mem)" stroke-width="2" fill="none" />
            <circle cx="32" cy="18" r="6" stroke="url(#mem)" stroke-width="2" fill="none" opacity=".9" />
            <path d="M6 36c0-5.5 4.5-10 10-10s10 4.5 10 10" stroke="url(#mem)" stroke-width="2" fill="none" />
            <path d="M22 36c0-5.5 4.5-10 10-10s10 4.5 10 10" stroke="url(#mem)" stroke-width="2" fill="none"
              opacity=".9" />
          </svg>
        </div>
      </div>
    </div>
  </section>
</body>

</html>