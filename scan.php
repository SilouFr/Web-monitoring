<?php

include("./functions.php");

$infos = read_json();
$old_infos = read_json();

//every domain
for($i=0; $i < count($infos["domains"]); $i++)
{
	$domain_name = $infos["domains"][$i]["name"]; //string
	$subdomains = $infos["domains"][$i]["subdomains"]; //array

	//every subdomain
	for($j=0; $j < count($subdomains); $j++)
	{
		//main variable
		$subdomain_name = $subdomains["$j"]["name"];
		$check = $subdomains["$j"]["check"];
		$contact = $subdomains["$j"]["contact"];

		$https = FALSE;
		$lastcheck = time();
		$errorMessage = "";
		$state = "online";
		$responseCode = 0;
		$timeout = 20; //seconds

		if($subdomain_name != "")
		{
			$complete_domain_name = $subdomain_name.".".$domain_name;
		} else {
			$complete_domain_name = $domain_name;
		}

		//is the site HTTP or HTTPS
		if(array_key_exists("https", $subdomains["$j"])){
			$url = "https://".$complete_domain_name.$check;
			$https = TRUE;
		} else {
        	$url = "http://".$complete_domain_name.$check;
        }

		echo "URL : ".$url."\r\n";

		//create the request with parameters
		$requestOptions = array("http" =>
				array(
					"method" => "GET",
					"header" => "Content-Type: text/xml\r\n",
					"timeout" => $timeout,
					"ssl" => array(
			        	"verify_peer" => FALSE,
			        	"verify_peer_name" => FALSE,
			        	"capture_peer_cert" => TRUE
    					)
				)
			);

		//special add for custom headers
		if(array_key_exists("headers", $subdomains["$j"]))
		{
			$requestOptions['http']['header'] .= $subdomains["$j"]["headers"]."\r\n";
		}

		//make the magic
		$context = stream_context_create($requestOptions);
		$result = FALSE;

		set_error_handler(function($errno, $errstr, $errfile, $errline, array $errcontext) {
		    if (0 === error_reporting()) {
		        return false;
		    }
		    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
		});

		try
		{
			$result = file_get_contents($url, FALSE, $context, -1, 40000);
		} catch (Exception $e) {
			$errorMessage = $e->getMessage();

			if( preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#",$e, $out ) ) {
		    	$responseCode = intval($out[1]);
			}
		}

		//no response -> site is offline
		if($result === FALSE)
		{
			$state = "offline";
			print "no response from $complete_domain_name";

			//site is offline/error
			$infos["domains"][$i]["subdomains"][$j]["state"] = $state;
			$infos["domains"][$i]["subdomains"][$j]["responsecode"] = $responseCode;

			//send a mail online -> offline
			if($old_infos["domains"][$i]["subdomains"][$j]["state"] == "online")
			{
				send_mail("offline", $contact, $complete_domain_name, $responseCode);
			}
		}

		//site is online
		else
		{
			echo "<pre>";
			echo "<h1>$complete_domain_name</h1>";

			//parser function to get formatted headers (with response code)
			//http://php.net/manual/en/reserved.variables.httpresponseheader.php#117203
			foreach( $http_response_header as $k=>$v )
		    {
		        $t = explode( ':', $v, 2 );
		        if( isset( $t[1] ) )
		            $head[ trim($t[0]) ] = trim( $t[1] ); //vanish
		        else
		        {
		            $head[] = $v;
		            if( preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#",$v, $out ) )
		                $responseCode = intval($out[1]);
		        }
		    }

		    //checking SSL cert
			if($https)
			{
			    $get = stream_context_create(array("ssl" => array("capture_peer_cert" => TRUE)));
			    $read = stream_socket_client("ssl://".$complete_domain_name.":443", $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $get);

			    $cert = stream_context_get_params($read);
			    $certinfo = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);
			    $certExpiration = $certinfo["validTo_time_t"];

			    $validForSubject = FALSE;

			    foreach ($certinfo["subject"] as $CN => $CNvalue) {
		    		if($CNvalue == $complete_domain_name)
					{
		    			$validForSubject = TRUE;
		    		}
			    }

			    foreach(explode(",", $certinfo["extensions"]["subjectAltName"]) as $ALT => $ALTValue)
			    {
			    	$ALTValue = trim(str_replace("DNS:", "" , $ALTValue));

			    	if($ALTValue == $complete_domain_name)
			    	{
			    		$validForSubject = TRUE;
			    	}
			    }
			}

			//results analysis
			$infos["domains"][$i]["subdomains"][$j]["lastcheck"] = $lastcheck;
			$infos["domains"][$i]["subdomains"][$j]["responsecode"] = $responseCode;
			$infos["domains"][$i]["subdomains"][$j]["state"] = $state;

			if($https)
			{
				$infos["domains"][$i]["subdomains"][$j]["https"]["expiry"] = $certExpiration;
				$infos["domains"][$i]["subdomains"][$j]["https"]["certstate"] = ($validForSubject == TRUE ? "ok" : "not ok");

				if($certExpiration <= strtotime("+1 week") && $old_infos["domains"][$i]["subdomains"][$j]["https"]["expiry"] > strtotime("+1 week")) //cert will expire in a week or less
				{
					send_mail("certExpiry", $contact, $complete_domain_name, $certExpiration);
				}

				if($validForSubject == FALSE && $old_infos["domains"][$i]["subdomains"][$j]["https"]["certstate"] == "ok") //cert not ok
				{
					send_mail("certState", $contact, $complete_domain_name, "RTFM AND LETS ENCRYPT");
				}
			}

			echo "</pre>";
		}
	}
}

save_json($infos);
?>