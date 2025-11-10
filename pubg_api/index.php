<?php
header('Content-Type: text/html; charset=utf-8');

$apiKey = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJqdGkiOiJiYzg3NzlmMC05MWY0LTAxM2UtOWVmZi0zMmQ4YzlkZGVmMzkiLCJpc3MiOiJnYW1lbG9ja2VyIiwiaWF0IjoxNzYxMTkyODg3LCJwdWIiOiJibHVlaG9sZSIsInRpdGxlIjoicHViZyIsImFwcCI6Ii1lMmUxY2Q3MC0wMjdmLTRmNjctYWJmYS1kNGEzNGE0ZDRlMDIifQ.kdxtNyNxgiS6wFP_2QvB-KgExR-wRTznBhrelxoa8NI';
$playerName = $_GET['player'] ?? 'shroud'; // Streamer sets their name in URL

function getPlayerStats($playerName, $apiKey) {
    $url = "https://api.pubg.com/shards/steam/players?filter[playerNames]={$playerName}";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/vnd.api+json',
        'Authorization: Bearer ' . $apiKey
    ]);

    
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

$stats = getPlayerStats($playerName, $apiKey);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            margin: 0;
            padding: 20px;
            background: transparent; /* Transparent for OBS */
            font-family: 'Arial Black', sans-serif;
            color: white;
        }
        .overlay {
            background: rgba(0, 0, 0, 0.7);
            padding: 20px;
            border-radius: 10px;
            border: 3px solid #00ff00;
            max-width: 300px;
        }
        .stat {
            margin: 10px 0;
            font-size: 18px;
            text-shadow: 2px 2px 4px #000;
        }
        .label {
            color: #00ff00;
            font-weight: bold;
        }
    </style>
    <script>
        // Auto-refresh every 60 seconds
        setTimeout(function(){ location.reload(); }, 60000);
    </script>
</head>
<body>
    <div class="overlay">
        <h2><?= htmlspecialchars($playerName) ?></h2>
        <?php if ($stats && isset($stats['data'][0])): ?>
            <div class="stat">
                <span class="label">Recent Matches:</span> 
                <?= count($stats['data'][0]['relationships']['matches']['data']) ?>
            </div>
            <div class="stat">
                <span class="label">Player ID:</span> 
                <?= substr($stats['data'][0]['id'], 0, 20) ?>...
            </div>
        <?php else: ?>
            <p>Loading stats...</p>
        <?php endif; ?>
    </div>
</body>
</html>
