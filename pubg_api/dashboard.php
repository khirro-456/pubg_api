<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$username = htmlspecialchars($_SESSION['username']);
require_once '/src/api_functions.php';

// Handle search, results, etc. (same logic as before, just themed text below)
$search_result = null;
$recent_matches = [];
$search_error = null;
$season_stats = null;
$season_error = null;

if (isset($_GET['search_player'])) {
    $player_name = trim($_GET['search_player']);
    $platform = $_GET['platform'] ?? 'steam';
    $game_mode_filter = $_GET['game_mode'] ?? '';

    $playerData = getPubgPlayer($player_name, $platform);
    if ($playerData && isset($playerData['data'][0])) {
        $search_result = $playerData['data'][0]['attributes'];
        $all_matches = getRecentMatches($playerData, $platform, 20);
        if ($game_mode_filter) {
            $filtered_matches = array_filter($all_matches, function($match) use ($game_mode_filter) {
                $mode = strtolower($match['mode'] ?? '');
                return strpos($mode, strtolower($game_mode_filter)) !== false;
            });
            $recent_matches = array_slice($filtered_matches, 0, 5);
        } else {
            $recent_matches = array_slice($all_matches, 0, 5);
        }
        $playerId = $playerData['data'][0]['id'];
        $seasonId = getCurrentSeason($platform);
        if ($seasonId) {
            $seasonData = getPlayerSeasonStats($playerId, $seasonId, $platform);
            if ($seasonData && isset($seasonData['data']['attributes']['gameModeStats'])) {
                $season_stats = $seasonData['data']['attributes']['gameModeStats'];
            } else {
                $season_error = "No season stats found for this player.";
            }
        } else {
            $season_error = "Current season could not be retrieved.";
        }
    } else {
        $search_error = "Player \"$player_name\" not found or stats unavailable.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>PUBG Player Finder</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&family=Montserrat:wght@400;700&display=swap" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
        min-height: 100vh;
        margin: 0;
        font-family: 'Montserrat', Arial, sans-serif;
        background: url('public/img/main_dash.jpg') center/cover no-repeat fixed;
        color: #eaf6fa;
    }
    .dash-bg-mask {
        position: fixed;
        z-index: 0;
        inset: 0;
        background: rgba(22,28,44, 0.63);
        pointer-events: none;
    }
    .dashboard-main {
        max-width: 950px;
        margin: 40px auto;
        padding: 30px 24px 40px 24px;
        position: relative;
        z-index: 1;
    }
    .glass-card {
        background: rgba(24,31,47,0.88);
        border-radius: 17px;
        box-shadow: 0 4px 36px 0 #242b3866, 0 1px 1.5px 0 #fff1;
        padding: 38px 36px 28px 36px;
        margin-bottom: 34px;
        border: 1.5px solid #2a3754bb;
        color: #f8fafd;
        text-shadow: 0 1px 4px #11203498;
    }
    .dashboard-title {
        text-align: center;
        font-size: 2.1em;
        font-family: 'Orbitron',sans-serif;
        font-weight: 700;
        letter-spacing: 1px;
        color: #6edaff;
        margin-bottom: 10px;
        text-shadow: 0 2px 16px #14b3d933;
    }
    .dashboard-welcome {
        text-align: center;
        color: #def1ff;
        font-size: 1.15em;
        margin-bottom: 24px;
        opacity: 0.96;
        text-shadow: 0 1px 7px #0e224481;
    }
    .logout-btn {
        display: block;
        margin: 0 auto 22px auto;
        min-width: 110px;
        padding: 11px 32px;
        border-radius: 7px;
        border: none;
        background: linear-gradient(87deg,#80eaff 30%,#256cfb 95%);
        color: #fff;
        font-weight: 600;
        font-size: 1.1em;
        letter-spacing: 0.04em;
        cursor: pointer;
        transition: filter 0.17s;
    }
    .logout-btn:hover {
        filter: brightness(1.08) drop-shadow(0 0 10px #25bffd60);
    }
    .filter-form {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        justify-content: center;
        margin-bottom: 22px;
        background: #27344f88;
        border-radius: 10px;
        box-shadow: 0 1.5px 10px #232f4380;
        padding: 14px 14px 12px 14px;
        color: #f7fdff;
    }
    .filter-form input[type="text"], .filter-form select {
        background: #1d2236cc;
        color: #e0f2ff;
        border: 1.4px solid #42e0ff55;
        border-radius: 7px;
        padding: 12px 14px;
        min-width: 150px;
        font-size: 1em;
        box-shadow: 0 1px 4px #25284421;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .filter-form input[type="text"]:focus,
    .filter-form select:focus {
        border-color: #00b8e6;
        outline: none;
        box-shadow: 0 0 10px #29bfc630;
    }
    .filter-form button {
        min-width: 100px;
        font-size: 1em;
        font-family: 'Orbitron',sans-serif;
        padding: 11px 19px;
        border: none;
        border-radius: 7px;
        background: linear-gradient(90deg, #47d9ff 24%, #157ffb 95%);
        color: #fff;
        font-weight: 600;
        letter-spacing: 0.09em;
        box-shadow: 0 2px 8px #13218336;
        cursor: pointer;
        transition: background 0.17s, filter 0.14s;
    }
    .filter-form button:hover {
        filter: brightness(1.15);
    }
    .stats-section-title {
        font-size: 1.22em;
        color: #37ffe9;
        font-family: 'Orbitron', sans-serif;
        margin-bottom: 13px;
        text-align: left;
        font-weight: 700;
        letter-spacing: 0.08em;
        margin-top: 1.5em;
        text-shadow: 0 1px 7px #0e224481;
    }
    .table-wrap {
        border-radius: 10px;
        background: #1b202e87;
        margin-bottom: 28px;
        padding: 14px 12px 10px 12px;
        box-shadow: 0 1.5px 12px #15161a2a;
        color: #f8fafd;
        text-shadow: 0 1px 4px #11203498;
    }
    table {
        border-collapse: collapse;
        width: 100%;
        color: #f8fafd;
        background: transparent;
        font-size: 0.98em;
        text-shadow: 0 1px 9px #222b4390;
    }
    th, td {
        padding: 10px 13px;
        border-bottom: 1px solid #26577b44;
        text-align: center;
    }
    th {
        background: #182641;
        color: #49e9f8;
        font-size: 1em;
        font-family: 'Orbitron',sans-serif;
        letter-spacing: 0.09em;
        text-shadow: 0 1px 8px #14306a90;
    }
    td {
        background: #1a273ac0;
        color: #f8fafd;
        text-shadow: 0 1px 9px #222b4390;
    }
    .mini-panel {
        border: 1.5px solid #ff386059;
        background: #ff4c4c09;
        border-radius: 10px;
        color: #ffeeee;
        margin: 16px auto 10px auto;
        padding: 14px 16px;
        max-width: 420px;
        font-size: 1.08em;
        letter-spacing: 0.019em;
    }
    @media (max-width: 720px) {
        .dashboard-main, .glass-card { padding: 14px 5vw; }
        .table-wrap {padding: 7px 2px 3px 2px;}
    }
    @media (max-width: 480px) {
        .dashboard-title { font-size: 1.2em; }
        .glass-card {padding: 7vw 2vw 12vw 2vw;}
        .table-wrap th, .table-wrap td { font-size: 0.93em; padding: 7px 3px;}
    }
  </style>
</head>
<body>
  <div class="dash-bg-mask"></div>
  <div class="dashboard-main">
    <div class="glass-card">
      <h2 class="dashboard-title">PUBG Player Finder</h2>
      <div class="dashboard-welcome">
        Welcome to PUBG Player Finder.<br>
        Enter any battle-hardened PUBG gamertag below and discover their match stats, game modes and more!
      </div>
      <form action="logout.php" method="post">
        <button class="logout-btn" type="submit">Sign Out</button>
      </form>

      <form method="GET" autocomplete="off" class="filter-form" aria-label="Player Lookup Form">
        <input type="text" name="search_player" placeholder="Search any PUBG player..." value="<?= htmlspecialchars($_GET['search_player'] ?? '') ?>" required>
        <select name="platform">
          <option value="steam" <?= (($_GET['platform'] ?? '') === 'steam') ? 'selected' : '' ?>>PC (Steam)</option>
          <option value="psn" <?= (($_GET['platform'] ?? '') === 'psn') ? 'selected' : '' ?>>PlayStation</option>
          <option value="xbox" <?= (($_GET['platform'] ?? '') === 'xbox') ? 'selected' : '' ?>>Xbox</option>
          <option value="kakao" <?= (($_GET['platform'] ?? '') === 'kakao') ? 'selected' : '' ?>>Kakao</option>
        </select>
        <select name="game_mode">
          <option value="" <?= empty($_GET['game_mode']) ? 'selected' : '' ?>>All Modes</option>
          <option value="solo" <?= (($_GET['game_mode'] ?? '') === 'solo') ? 'selected' : '' ?>>Solo</option>
          <option value="duo" <?= (($_GET['game_mode'] ?? '') === 'duo') ? 'selected' : '' ?>>Duo</option>
          <option value="squad" <?= (($_GET['game_mode'] ?? '') === 'squad') ? 'selected' : '' ?>>Squad</option>
          <option value="fpp" <?= (($_GET['game_mode'] ?? '') === 'fpp') ? 'selected' : '' ?>>FPP</option>
          <option value="tpp" <?= (($_GET['game_mode'] ?? '') === 'tpp') ? 'selected' : '' ?>>TPP</option>
        </select>
        <button type="submit">Search</button>
      </form>

      <?php if ($search_result): ?>
        <div class="glass-card" style="margin:18px auto 24px auto;">
          <h3>
            <?= htmlspecialchars($search_result['name']) ?>
            <span style="font-size:0.75em;color:#89eafd;">
              (<?= htmlspecialchars($_GET['platform'] ?? 'steam') ?>)
            </span>
          </h3>
        </div>
      <?php elseif ($search_error): ?>
        <div class="mini-panel">
          <strong>Not Found</strong><br>
          <?= htmlspecialchars($search_error) ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($recent_matches)): ?>
        <div class="table-wrap">
          <div class="stats-section-title">Recent Matches</div>
          <table>
            <tr>
              <th>Date</th>
              <th>Map</th>
              <th>Mode</th>
              <th>Placement</th>
              <th>Kills</th>
            </tr>
            <?php foreach ($recent_matches as $m): ?>
            <tr>
              <td><?= htmlspecialchars($m['date']) ?></td>
              <td><?= htmlspecialchars($m['map']) ?></td>
              <td><?= htmlspecialchars($m['mode']) ?></td>
              <td><?= htmlspecialchars($m['placement']) ?></td>
              <td><?= htmlspecialchars($m['kills']) ?></td>
            </tr>
            <?php endforeach; ?>
          </table>
        </div>
      <?php endif; ?>

      <?php if ($season_stats): ?>
        <div class="table-wrap">
          <div class="stats-section-title">Full Season Summary</div>
          <table>
            <tr>
              <th>Mode</th>
              <th>Matches</th>
              <th>Wins</th>
              <th>Top 10</th>
              <th>Best Kills</th>
              <th>Most Damage</th>
              <th>Rank Points</th>
            </tr>
            <?php
            $modes = ['squad-fpp','duo-fpp','solo-fpp','squad','duo','solo'];
            foreach($modes as $mode) {
                if (!empty($season_stats[$mode])) {
                    $stat = $season_stats[$mode];
                    echo "<tr>
                        <td>".htmlspecialchars(strtoupper($mode))."</td>
                        <td>".htmlspecialchars($stat['roundsPlayed'] ?? '-')."</td>
                        <td>".htmlspecialchars($stat['wins'] ?? '-')."</td>
                        <td>".htmlspecialchars($stat['top10s'] ?? '-')."</td>
                        <td>".htmlspecialchars($stat['maxKills'] ?? '-')."</td>
                        <td>".htmlspecialchars($stat['mostDamage'] ?? '-')."</td>
                        <td>".htmlspecialchars($stat['rankPoints'] ?? '-')."</td>
                    </tr>";
                }
            }
            ?>
          </table>
        </div>
      <?php elseif ($season_error): ?>
        <div class="mini-panel">
          <b>Season Error:</b> <?= htmlspecialchars($season_error) ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($recent_matches)): ?>
        <div class="table-wrap">
          <div class="stats-section-title">Per-Match Stats Chart</div>
          <canvas id="matchStatsChart" style="width:100%;height:300px;"></canvas>
        </div>
        <script>
          const ctx = document.getElementById('matchStatsChart').getContext('2d');
          const labels = <?= json_encode(array_map(fn($m, $i) => 'Match '.($i+1).' ('.$m['date'].')', $recent_matches, array_keys($recent_matches))) ?>;
          const killsData = <?= json_encode(array_map(fn($m) => $m['kills'], $recent_matches)) ?>;
          const damageData = <?= json_encode(array_map(fn($m) => $m['damage'] ?? 0, $recent_matches)) ?>;
          new Chart(ctx, {
            type: 'line',
            data: {
              labels: labels,
              datasets: [
                {
                  label: 'Kills',
                  data: killsData,
                  borderColor: 'rgba(54, 162, 235, 0.8)',
                  backgroundColor: 'rgba(54, 162, 235, 0.4)',
                  fill: false,
                  tension: 0.1,
                  yAxisID: 'y',
                },
                {
                  label: 'Damage',
                  data: damageData,
                  borderColor: 'rgba(255, 99, 132, 0.8)',
                  backgroundColor: 'rgba(255, 99, 132, 0.4)',
                  fill: false,
                  tension: 0.1,
                  yAxisID: 'y1',
                }
              ]
            },
            options: {
              responsive: true,
              interaction: { mode: 'index', intersect: false },
              stacked: false,
              scales: {
                y: {
                  type: 'linear',
                  display: true,
                  position: 'left',
                  title: { display: true, text: 'Kills' }
                },
                y1: {
                  type: 'linear',
                  display: true,
                  position: 'right',
                  grid: { drawOnChartArea: false },
                  title: { display: true, text: 'Damage' }
                }
              }
            }
          });
        </script>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
