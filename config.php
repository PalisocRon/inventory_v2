<?php
// ---- Edit these ----
const SHEETS_URL   = 'https://script.google.com/macros/s/AKfycbw5_soPdtKV7ftdtZbgA2hIX0s4RocE7qF_6oMWcXpmcxf68OtVA47NyUQGlq-UG7ILSw/exec';   // ends with /exec
const SHEETS_TOKEN = '1xcyiJ8ERixWKbcAX8e1jox-DxVkHMw3u5Htyz_z29L8';          // must match TOKEN in Code.gs
const CURRENCY     = 'PHP';   // ISO code used for display, e.g. USD, PHP, EUR
const LOW_STOCK    = 5;       // quantities at or below this are highlighted

/** Send one request to the Google Sheets backend (Apps Script web app). */
function sheets(array $payload): array {
    $payload['token'] = SHEETS_TOKEN;
    $ch = curl_init(SHEETS_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,   // Apps Script answers with a redirect
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_TIMEOUT        => 30,
    ]);
    $body = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($body === false) throw new RuntimeException('Could not reach Google: ' . $err);
    $json = json_decode($body, true);
    if (!is_array($json)) {
        throw new RuntimeException('Google did not return JSON. Check SHEETS_URL and that the web app access is set to "Anyone".');
    }
    return $json;
}
