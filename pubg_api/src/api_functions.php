<?php
require_once '/src/config.php';

function getPubgPlayer($playerName, $platform = 'steam') {
    $url = PUBG_API_URL . "/shards/{$platform}/players?filter[playerNames]={$playerName}";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/vnd.api+json',
        'Authorization: Bearer ' . PUBG_API_KEY
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return null;
    }
    
    return json_decode($response, true);
}

function getPlayerSeasonStats($playerId, $seasonId, $platform = 'steam') {
    $url = PUBG_API_URL . "/shards/{$platform}/players/{$playerId}/seasons/{$seasonId}";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/vnd.api+json',
        'Authorization: Bearer ' . PUBG_API_KEY
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return null;
    }
    
    return json_decode($response, true);
}

function getCurrentSeason($platform = 'steam') {
    $url = PUBG_API_URL . "/shards/{$platform}/seasons";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/vnd.api+json',
        'Authorization: Bearer ' . PUBG_API_KEY
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return null;
    }
    
    $data = json_decode($response, true);
    
    // Find current season
    if ($data && isset($data['data'])) {
        foreach ($data['data'] as $season) {
            if (isset($season['attributes']['isCurrentSeason']) && $season['attributes']['isCurrentSeason']) {
                return $season['id'];
            }
        }
        // Return most recent if no current season marked
        return $data['data'][0]['id'] ?? null;
    }
    
    return null;
}

function getMatch($matchId, $platform = 'steam') {
    $url = PUBG_API_URL . "/shards/{$platform}/matches/{$matchId}";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/vnd.api+json',
        'Authorization: Bearer ' . PUBG_API_KEY
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return null;
    }
    
    return json_decode($response, true);
}

function getRecentMatches($playerData, $platform = 'steam', $count = 5) {
    $recentMatches = [];

    if (isset($playerData['data'][0]['relationships']['matches']['data'])) {
        $matches = $playerData['data'][0]['relationships']['matches']['data'];
        $recentMatches = array_slice($matches, 0, $count); // Only get the most recent N
    }

    $result = [];
    foreach ($recentMatches as $matchInfo) {
        $matchId = $matchInfo['id'];
        $matchData = getMatch($matchId, $platform);

        // Find this player's stats in the match
        $playerId = $playerData['data'][0]['id'];
        $stats = null;
        if (isset($matchData['included'])) {
            foreach ($matchData['included'] as $item) {
                if ($item['type'] === 'participant' && isset($item['attributes']['stats'])) {
                    $s = $item['attributes']['stats'];
                    if (
                        (isset($s['playerId']) && $s['playerId'] === $playerId) ||
                        (isset($item['attributes']['actor']) && $item['attributes']['actor'] === $playerId)
                    ) {
                        $stats = $s;
                        break;
                    }
                }
            }
        }
        $result[] = [
            'date'      => isset($matchData['data']['attributes']['createdAt']) ? date("Y-m-d H:i", strtotime($matchData['data']['attributes']['createdAt'])) : '-',
            'map'       => $matchData['data']['attributes']['mapName'] ?? '-',
            'mode'      => $matchData['data']['attributes']['gameMode'] ?? '-',
            'placement' => $stats['winPlace'] ?? '-',
            'kills'     => $stats['kills'] ?? '-',
            'damage'    => $stats['damage'] ?? 0
        ];
    }
    return $result;
}
