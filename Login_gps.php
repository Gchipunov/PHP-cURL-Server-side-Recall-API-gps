<?php

### ⚠️ STEP 1: DEFINE YOUR CONFIGURATION ⚠️

// These are placeholders. You MUST replace them with your actual credentials.
// Get these from your Google Cloud Console for the Web Application client ID 
// associated with your Play Games Services project.
$CLIENT_ID = 'YOUR_WEB_CLIENT_ID.apps.googleusercontent.com';
$CLIENT_SECRET = 'YOUR_WEB_CLIENT_SECRET';

// This is the code sent from the Android game client (via GamesSignInClient.requestServerSideAccess()).
$SERVER_AUTH_CODE = 'THE_ONE_TIME_CODE_FROM_GAME_CLIENT'; 

// The redirect URI used in the Android client's requestServerSideAccess call.
// For the server-side flow, this is often 'postmessage'.
$REDIRECT_URI = 'postmessage'; 

### STEP 2: BUILD THE REQUEST PAYLOAD

$postData = [
    'code'          => $SERVER_AUTH_CODE,
    'client_id'     => $CLIENT_ID,
    'client_secret' => $CLIENT_SECRET,
    'redirect_uri'  => $REDIRECT_URI,
    'grant_type'    => 'authorization_code',
];

// Google's OAuth 2.0 token endpoint
$token_url = 'https://oauth2.googleapis.com/token';

### STEP 3: EXECUTE THE cURL REQUEST

$ch = curl_init();

// Set the URL and POST data
curl_setopt($ch, CURLOPT_URL, $token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));

// Tell cURL to return the response string
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Set appropriate Content-Type header
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
]);

// Execute the request and get the response
$response = curl_exec($ch);

// Handle cURL errors
if (curl_errno($ch)) {
    echo 'cURL Error: ' . curl_error($ch);
    curl_close($ch);
    exit;
}

// Get HTTP status code
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Close cURL session
curl_close($ch);

### STEP 4: PROCESS THE RESPONSE

$result = json_decode($response, true);

if ($http_code === 200 && isset($result['access_token'])) {
    echo "✅ Successfully exchanged code for tokens!\n\n";
    echo "--- Token Response ---\n";
    print_r($result);
    
    // Store these in your session/database for future API calls!
    $access_token = $result['access_token'];
    $id_token     = $result['id_token']; // Contains basic player info
    $refresh_token= $result['refresh_token'] ?? null;
    
} else {
    echo "❌ Token Exchange Failed (HTTP $http_code):\n\n";
    print_r($result);
}

// ------------------------------------------------------------------
// --- OPTIONAL: Using the Access Token for a Player Verification API Call ---
// ------------------------------------------------------------------

if (isset($access_token)) {
    echo "\n\n--- Testing Access Token with Play Games API (players/me) ---\n";
    
    // The endpoint to retrieve the current player's profile (the 'me' player)
    $api_url = 'https://games.googleapis.com/games/v1/players/me';
    
    $ch_api = curl_init();
    
    // Add the Access Token to the Authorization header
    $headers = [
        "Authorization: Bearer $access_token",
    ];
    
    curl_setopt($ch_api, CURLOPT_URL, $api_url);
    curl_setopt($ch_api, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch_api, CURLOPT_HTTPHEADER, $headers);
    
    $api_response = curl_exec($ch_api);
    $api_http_code = curl_getinfo($ch_api, CURLINFO_HTTP_CODE);
    curl_close($ch_api);
    
    $api_result = json_decode($api_response, true);

    if ($api_http_code === 200) {
        echo "✅ Player Verification Successful (HTTP $api_http_code):\n";
        echo "Player ID: " . $api_result['playerId'] . "\n";
        echo "Display Name: " . $api_result['displayName'] . "\n";
    } else {
        echo "❌ Player Verification Failed (HTTP $api_http_code):\n";
        print_r($api_result);
    }
}
?>
