<?php
// save-game.php

### ⚠️ STEP 1: DEFINE YOUR CONFIGURATION ⚠️

// The Access Token obtained from the server-side OAuth 2.0 exchange.
$ACCESS_TOKEN = 'YOUR_VALID_ACCESS_TOKEN_HERE';

// The ID of the existing snapshot you want to update.
$SNAPSHOT_ID = 'SNAPSHOT_ID_TO_UPDATE'; 

// Game data to save (will be stored as a JSON string in the snapshot file)
$game_data = [
    'player_level2' => 50,
    'player_xp'     => 1000,
    'timestamp'     => time()
];
$game_data_json = json_encode($game_data);

// Snapshot metadata updates
$update_description = 'Level 2 progress update (XP: 1000)';
// Duration should be the total playtime of the game up to this point, in milliseconds.
$total_play_time_ms = 1200000; // Example: 20 minutes

### STEP 2: BUILD THE API REQUEST

// The endpoint for patching (updating) a specific snapshot
$api_url = "https://games.googleapis.com/games/v1/snapshots/{$SNAPSHOT_ID}";

// The metadata to send in the PATCH request body
$metadata_payload = [
    'description'    => $update_description,
    'durationMillis' => $total_play_time_ms,
];

// The total payload structure, including the base64 encoded game data
// The 'snapshotContents' field is where the actual game file data goes.
$full_payload = [
    'snapshot'         => $metadata_payload,
    // Base64 encode the game data for transfer
    'snapshotContents' => base64_encode($game_data_json), 
];

// Convert PHP array to JSON string for the request body
$json_payload = json_encode($full_payload);

### STEP 3: EXECUTE THE cURL REQUEST

// Initialize cURL session
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Set the request method to PATCH
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH'); 
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);

// Set the Authorization header and Content-Type
$headers = [
    "Authorization: Bearer $ACCESS_TOKEN",
    "Content-Type: application/json",
    "Content-Length: " . strlen($json_payload)
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// Execute the request
$response = curl_exec($ch);

// Get HTTP status code and check for cURL errors
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo 'cURL Error: ' . curl_error($ch);
    curl_close($ch);
    exit;
}

// Close cURL session
curl_close($ch);

### STEP 4: PROCESS THE RESPONSE

$result = json_decode($response, true);

if ($http_code === 200) {
    echo "✅ Successfully updated snapshot (ID: {$SNAPSHOT_ID})!\n\n";
    echo "--- Updated Snapshot Details ---\n";
    echo "File Name: " . $result['fileName'] . "\n";
    echo "Description: " . $result['description'] . "\n";
    echo "Size (bytes): " . $result['fileSize'] . "\n";
    echo "---------------------------------\n";
} else {
    echo "❌ Failed to save snapshot (HTTP $http_code).\n\n";
    echo "--- Error Details ---\n";
    print_r($result);
}
?>
