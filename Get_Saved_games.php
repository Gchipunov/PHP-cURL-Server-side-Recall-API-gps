// https://developer.android.com/games/pgs/recall

<?php
// Get_Saved_games.php

### ⚠️ STEP 1: DEFINE YOUR ACCESS TOKEN ⚠️

// This is the access token you received after successfully completing the 
// server-side OAuth 2.0 exchange process. 
$ACCESS_TOKEN = 'YOUR_VALID_ACCESS_TOKEN_HERE';

// Set this to the maximum number of snapshots you want to retrieve.
$MAX_RESULTS = 25; 

// The endpoint for listing snapshots for the currently authenticated player ('me')
$api_url = "https://games.googleapis.com/games/v1/players/me/snapshots?maxResults=" . $MAX_RESULTS;

### STEP 2: EXECUTE THE cURL REQUEST

// Initialize cURL session
$ch = curl_init();

// Set the API URL
curl_setopt($ch, CURLOPT_URL, $api_url);

// Tell cURL to return the response string
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Set the Authorization header with the Access Token
$headers = [
    "Authorization: Bearer $ACCESS_TOKEN",
    "Content-Type: application/json"
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

### STEP 3: PROCESS THE RESPONSE

$result = json_decode($response, true);

if ($http_code === 200 && isset($result['items'])) {
    echo "✅ Successfully retrieved " . count($result['items']) . " saved game snapshots.\n\n";
    echo "--- Snapshots List ---\n";
    
    // Iterate through the list of snapshots
    foreach ($result['items'] as $snapshot) {
        echo "Snapshot ID: **" . $snapshot['id'] . "**\n";
        echo "  File Name: " . $snapshot['fileName'] . "\n";
        echo "  Description: " . $snapshot['description'] . "\n";
        echo "  Last Modified: " . date('Y-m-d H:i:s', $snapshot['lastModifiedMillis'] / 1000) . "\n";
        echo "  Duration (ms): " . $snapshot['durationMillis'] . "\n";
        echo "--------------------------\n";
    }

    // Check for pagination
    if (isset($result['nextPageToken'])) {
        echo "\nNote: There are more snapshots. Use the nextPageToken in a subsequent request to load the next page.\n";
    }

} else {
    echo "❌ Failed to retrieve snapshots (HTTP $http_code).\n\n";
    echo "--- Error Details ---\n";
    print_r($result);
}
?>
