<?php

include("./functions.php");

$infos = read_json();

//every domain
for($i=0; $i < count($infos["domains"]); $i++)
{
	$domain = $infos["domains"][$i]["name"];
	$subdomain = $infos["domains"][$i]["subdomains"];

	//every subdomain
	for($j=0; $j < count($subdomain); $j++)
	{
		//main variables from the file
		$name = $subdomain["$j"]["name"];
		$check = $subdomain["$j"]["check"];
		$https = FALSE;
		$lastcheck = time();
		$contact = $subdomain["$j"]["contact"];
		$errorMessage = "";
		$state = "online";
		$responseCode = 0;

		//is the site HTTP or HTTPS
		if(array_key_exists("https", $subdomain["$j"])){
			if($name == "") {
				$url = "https://".$domain.$check;
			}
			else {
				$url = "https://".$subdomain["$j"]["name"].".".$domain.$check;
			}
			$https = TRUE;
		} else {
                        if($name == "") {
                                $url = "http://".$domain.$check;
                        }
                        else {
                                $url = "http://".$subdomain["$j"]["name"].".".$domain.$check;
                        }

		}
		echo "URL : ".$url."\r\n";

		//timeout for requests
		$timeout = 20;

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
		if(array_key_exists("headers", $subdomain["$j"]))
		{
			$requestOptions['http']['header'] = $requestOptions['http']['header'].$subdomain["$j"]["headers"]."\r\n";
		}

		//make the magic
		$context = stream_context_create($requestOptions);
		$result = FALSE;

		set_error_handler(function($errno, $errstr, $errfile, $errline, array $errcontext) {
		    // error was suppressed with the @-operator
		    if (0 === error_reporting()) {
		        return false;
		    }

		    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
		});

		try {
			$result = file_get_contents($url, FALSE, $context, -1, 40000);
		} catch (Exception $e) {
			$errorMessage = $e->getMessage();
			echo $errorMessage."<br/>";
			if( preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#",$e, $out ) )
		    	$responseCode = intval($out[1]);

			/*$state = "offline";

			//site is offline/error
			$infos["domains"][$i]["subdomains"][$j]["state"] = $state;*/
		}

		//no response
		if($result === FALSE)
		{
			$state = "offline";
			print "no response from $name";

			//site is offline/error
			$infos["domains"][$i]["subdomains"][$j]["state"] = $state;
			$infos["domains"][$i]["subdomains"][$j]["responsecode"] = $responseCode;
		}


		//site is online
		else
		{
			echo "<pre>";
			echo "<h1>$name</h1>";

			// http://php.net/manual/en/reserved.variables.httpresponseheader.php#117203
			foreach( $http_response_header as $k=>$v )
		    {
		        $t = explode( ':', $v, 2 );
		        if( isset( $t[1] ) )
		            $head[ trim($t[0]) ] = trim( $t[1] );
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
			    if($name == "") { 
			    	$read = stream_socket_client("ssl://".$domain.":443", $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $get);
			    }
			    else {
			    $read = stream_socket_client("ssl://".$name.".".$domain.":443", $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $get);
			    }

			    $cert = stream_context_get_params($read);
			    $certinfo = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);
			    $certExpiration = $certinfo["validTo_time_t"];

			    $validForSubject = FALSE;

			    foreach ($certinfo["subject"] as $CN => $CNvalue) {
			    	if($name != "") //if subdomain
			    	{
			    		if($CNvalue == $name.".".$domain)
						{
			    			$validForSubject = TRUE;
			    		}
			    	}

			    	else
			    	{
				    	if($CNvalue == $domain)
				    	{
				    		$validForSubject = TRUE;
				    	}
			    	}
			    }

			    foreach(explode(",", $certinfo["extensions"]["subjectAltName"]) as $ALT => $ALTValue)
			    {
			    	$ALTValue = trim(str_replace("DNS:", "" , $ALTValue));
			    	if($ALTValue == $name.".".$domain)
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
			}

			echo "</pre>";
		}
	}
}
//TODO: mailing
save_json($infos);
?>
