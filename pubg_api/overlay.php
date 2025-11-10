<?php
require_once '/src/api_functions.php';

// Get parameters from URL
$playerName = $_GET['player'] ?? '';
$platform = $_GET['platform'] ?? 'steam';
$theme = $_GET['theme'] ?? 'dark';

$playerData = null;
$seasonStats = null;
$error = null;

if (!empty($playerName)) {
    $playerData = getPubgPlayer($playerName, $platform);
    
    if ($playerData && isset($playerData['data'][0])) {
        $player = $playerData['data'][0];
        $playerId = $player['id'];
        
        // Get current season
        $seasonId = getCurrentSeason($platform);
        
        if ($seasonId) {
            $seasonStats = getPlayerSeasonStats($playerId, $seasonId, $platform);
        }
    } else {
        $error = "Player not found";
    }
}

// Theme colors
$themes = [
    'dark' => ['bg' => 'rgba(0, 0, 0, 0.85)', 'accent' => '#00ff00', 'text' => '#ffffff'],
    'green' => ['bg' => 'rgba(0, 50, 0, 0.85)', 'accent' => '#00ff00', 'text' => '#ffffff'],
    'blue' => ['bg' => 'rgba(0, 20, 50, 0.85)', 'accent' => '#00d4ff', 'text' => '#ffffff'],
    'red' => ['bg' => 'rgba(50, 0, 0, 0.85)', 'accent' => '#ff0000', 'text' => '#ffffff'],
];

$currentTheme = $themes[$theme] ?? $themes['dark'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PUBG Streamer Overlay</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: transparent;
            font-family: 'Arial Black', Arial, sans-serif;
            color: <?= $currentTheme['text'] ?>;
            padding: 20px;
        }
        
        .overlay-container {
            background: <?= $currentTheme['bg'] ?>;
            border: 3px solid <?= $currentTheme['accent'] ?>;
            border-radius: 15px;
            padding: 25px;
            max-width: 400px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .player-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid <?= $currentTheme['accent'] ?>;
            padding-bottom: 15px;
        }
        
        .player-name {
            font-size: 28px;
            color: <?= $currentTheme['accent'] ?>;
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.8);
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .platform-badge {
            display: inline-block;
            background: <?= $currentTheme['accent'] ?>;
            color: #000;
            padding: 3px 10px;
            border-radius: 5px;
            font-size: 12px;
            margin-top: 5px;
            font-weight: bold;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }
        
        .stat-box {
            background: rgba(0, 0, 0, 0.3);
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid <?= $currentTheme['accent'] ?>;
            text-align: center;
        }
        
        .stat-label {
            font-size: 12px;
            color: <?= $currentTheme['accent'] ?>;
            text-transform: uppercase;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
        }
        
        .recent-matches {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid <?= $currentTheme['accent'] ?>;
        }
        
        .section-title {
            font-size: 14px;
            color: <?= $currentTheme['accent'] ?>;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .match-count {
            font-size: 18px;
            text-align: center;
        }
        
        .error-message {
            text-align: center;
            color: #ff4444;
            font-size: 18px;
            padding: 20px;
        }
        
        .loading {
            text-align: center;
            color: <?= $currentTheme['accent'] ?>;
            font-size: 18px;
        }
        
        .live-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            background: #ff0000;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
            margin-right: 8px;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
        }
    </style>
    <script>
        // Auto-refresh every 60 seconds
        setTimeout(function() {
            location.reload();
        }, 60000);
    </script>
</head>
<body>
    <?php if ($error): ?>
        <div class="overlay-container">
            <div class="error-message">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        </div>
    <?php elseif (!$playerName): ?>
        <div class="overlay-container">
            <div class="loading">
                ⚙️ Add ?player=USERNAME to URL
            </div>
        </div>
    <?php elseif ($playerData && isset($playerData['data'][0])): ?>
        <?php 
        $player = $playerData['data'][0];
        $matchCount = count($player['relationships']['matches']['data']);
        
        // Calculate stats from season data
        $kills = 0;
        $wins = 0;
        $losses = 0;
        $damageDealt = 0;
        
        if ($seasonStats && isset($seasonStats['data']['attributes']['gameModeStats'])) {
            $gameModeStats = $seasonStats['data']['attributes']['gameModeStats'];
            
            // Try squad first, then duo, then solo
            $modes = ['squad', 'duo', 'solo'];
            foreach ($modes as $mode) {
                if (isset($gameModeStats[$mode])) {
                    $modeStats = $gameModeStats[$mode];
                    $kills = $modeStats['kills'] ?? 0;
                    $wins = $modeStats['wins'] ?? 0;
                    $losses = $modeStats['losses'] ?? 0;
                    $damageDealt = round($modeStats['damageDealt'] ?? 0);
                    break;
                }
            }
        }
        
        $kd = $losses > 0 ? round($kills / $losses, 2) : $kills;
        ?>
        
        <div class="overlay-container">
            <div class="player-header">
                <div class="live-indicator"></div>
                <div class="player-name"><?= htmlspecialchars($player['attributes']['name']) ?></div>
                <div class="platform-badge"><?= strtoupper($platform) ?></div>
            </div>
            
            <div class="stats-grid">
                <div class="stat-box">
                    <div class="stat-label">Kills</div>
                    <div class="stat-value"><?= number_format($kills) ?></div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-label">Wins</div>
                    <div class="stat-value"><?= number_format($wins) ?></div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-label">K/D Ratio</div>
                    <div class="stat-value"><?= $kd ?></div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-label">Damage</div>
                    <div class="stat-value"><?= number_format($damageDealt) ?></div>
                </div>
            </div>
            
            <div class="recent-matches">
                <div class="section-title">Recent Matches</div>
                <div class="match-count">🎮 <?= $matchCount ?> matches tracked</div>
            </div>
        </div>
    <?php else: ?>
        <div class="overlay-container">
            <div class="loading">Loading stats...</div>
        </div>
    <?php endif; ?>
</body>
</html>
