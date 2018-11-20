<?php

function read_json()
{
	$str = file_get_contents('./sites.json');
	$json = json_decode($str, true);

	return $json;
}

function save_json($json_data)
{
	$encoded_json = json_encode($json_data, JSON_PRETTY_PRINT);
	file_put_contents('./sites.json', $encoded_json);	
}

function val_sort($array,$key)
{
	//Loop through and get the values of our specified key
	foreach($array as $k=>$v) {
		$b[] = strtolower($v[$key]);
	}
	
	asort($b);
	
	foreach($b as $k=>$v) {
		$c[] = $array[$k];
	}
	
	return $c;
}

function send_mail($reason, $contact, $complete_domain_name, $info)
{
	$to = $contact;
	$headers = "From: monitor@web-monitoring.git";

	if($reason == "offline")
	{
		$subject = "[Warning] ".$complete_domain_name." is offline";
		$txt = "Your monitoring tool detected that ".$complete_domain_name." is offline.\r\n
		The website responded with error code ".$info;
	}

	else if($reason == "certExpiry")
	{
		$subject = "[Warning] ".$complete_domain_name." SSL cert will expire soon";
		$txt = "Your monitoring tool detected that the SSL certificate for ".$complete_domain_name." will expire soon.\r\n
		The expiration date is ".date('d/m/Y', $info);
	}

	else if($reason == "certState")
	{
		$subject = "[Warning] ".$complete_domain_name." SSL cert is invalid";
		$txt = "Your monitoring tool detected that the SSL certificate for ".$complete_domain_name." is invalid.";
	}

	mail($to,$subject,$txt,$headers);
}
?>
