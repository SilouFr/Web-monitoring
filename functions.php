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

?>