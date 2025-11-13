<?php
// conflict-save.php - Resolves a save conflict by forcing the chosen version to the cloud.

### ⚠️ STEP 1: DEFINE YOUR CONFIGURATION ⚠️

// The Access Token obtained from the server-side OAuth 2.0 exchange.
$ACCESS_TOKEN = 'YOUR_VALID_ACCESS_TOKEN_HERE';

// 1. Get the Snapshot ID from the request (e.g., from the URL parameter)
$SNAPSHOT_ID = $_GET['id'] ?? null; 
if (!$SNAPSHOT_ID) {
    die("Error: Snapshot ID not provided in the URL.");
}

// 2. The ID of the current conflict session (retrieved from the 409 response)
$CONFLICT_ID = 'THE_CONFLICT_ID_FROM_409_RESPONSE'; 

// 3. The data of the save chosen by the player (JSON string, either local or one of the conflicts).
// For demonstration, we'll assume the player chose the original local save data.
$CHOSEN_SAVE_DATA = [
    'player_level' => 55, // Example of the chosen/resolved data
    'player_xp'    => 1200,
    'device'       => 'Chosen Local Save',
];
$chosen_game_data_json = json_encode($CHOSEN_SAVE_DATA);

// The player-chosen metadata for the final save
$chosen_metadata = [
    'description'    => 'Resolved Conflict: Player chose the newest save.',
    'durationMillis' => 1800000,
];

### STEP 2: BUILD THE RESOLUTION PAYLOAD

// The endpoint for patching (updating) a specific snapshot
$api_url = "https://games.googleapis.com/games/v1/snapshots/{$SNAPSHOT_ID}";

// The core resolution policy
$resolution_policy = 'KEEP_MANUAL'; // Use KEEP_MANUAL for server-side resolution

// The full payload structure, including the conflict resolution fields
$full_payload = [
    'snapshot'         => $chosen_metadata,
    'snapshotContents' => base64_encode($chosen_game_data_json), 
    
    // --- CONFLICT RESOLUTION FIELDS ---
    'conflictId'       => $CONFLICT_ID,
    'resolutionPolicy' => $resolution_policy,
];

// Convert PHP array to JSON string for the request body
$json_payload = json_encode($full_payload);

### STEP 3: EXECUTE THE cURL RESOLUTION REQUEST

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Use the PATCH method for updating/resolving
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
curl_close($ch);

### STEP 4: PROCESS THE FINAL RESPONSE

$result = json_decode($response, true);

if ($http_code === 200) {
    echo "✅ Conflict Resolution Successful (HTTP 200)!\n\n";
    echo "The chosen save has been forced to the cloud as the new master version.\n";
    echo "--- Resolved Snapshot Details ---\n";
    echo "File Name: " . $result['fileName'] . "\n";
    echo "Description: " . $result['description'] . "\n";
    echo "---------------------------------\n";
} else {
    echo "❌ Conflict Resolution Failed (HTTP $http_code).\n\n";
    echo "Note: If the conflict persists, check the server logs for error details.\n";
    print_r($result);
}
?>
