<?php
// Read-only, on the hosting account. Never print the token or database passwords.
$path='/home/u815362143/.api_token';
$value=trim((string)file_get_contents($path));
$json=json_decode($value,true);
if(is_array($json))$value=$json['api_token'] ?? $json['token'] ?? '';
if(!$value || preg_match('/[\r\n]/',$value)){echo "Hosting token format unsupported.\n";exit(2);}
$url='https://developers.hostinger.com/api/hosting/v1/accounts/u815362143/databases?search=ldrn&per_page=25';
$curl=curl_init($url);
curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HTTPHEADER=>['Authorization: Bearer '.$value,'Accept: application/json'],CURLOPT_TIMEOUT=>30]);
$response=curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);
echo 'Hosting API authorization status: '.$status."\n";
if($status===200) {
    $body=json_decode($response,true);
    foreach($body['data'] ?? [] as $database)echo json_encode(array_intersect_key($database,array_flip(['name','user','domain','host','port'])))."\n";
    echo 'Matching LDRN databases: '.count($body['data'] ?? [])."\n";
}else exit(3);
