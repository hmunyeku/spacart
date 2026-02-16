<?php
$ch = curl_init('https://textbelt.com/text');
$data = array(
  'phone' => '+79270000001',
  'message' => 'Testing',
  'key' => 'GENERATE_YOUR_KEY',
);

curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

exit;