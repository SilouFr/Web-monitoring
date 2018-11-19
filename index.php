<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta property="og:site_name" content="monitor.silou.fr">
		<meta property="og:title" content="monitor.silou.fr">
		<meta property="og:description" content="Monitoring des sites hébergés sur le serveur Equestria">
		<meta property="og:locale" content="fr_FR">
		
		<!-- css area -->
		<link rel="stylesheet" href="css/readable.css">
		<link rel="stylesheet" href="css/style.css">
		<script type="text/javascript" src="js/jquery-3.2.1.min"></script>
		<script type="text/javascript" src="js/bootstrap.js"></script>
		<script type="text/javascript" src="js/scrolls.js"></script>

		<?php include("./functions.php"); ?>

		<title>Monitoring des sites hébergés sur le serveur Equestria</title>
	</head>

	<body>

		<div id="content">
		
			<div id="news-row" class="row" style="margin-top: 1em">
				<div class="col-md-1"></div>
				<div class="col-md-10">


					<?php

					date_default_timezone_set('Europe/Paris');
					include_once("functions.php");

					$infos = read_json();
					$infos = $infos["domains"];

					//every domain
					for($i=0; $i < count($infos); $i++)
						{
							$domain = $infos[$i]["name"];

					?>

					<div id="news" class="panel master-panel panel-primary">
						<div class="panel-heading">
							<h3 class="panel-title"><?php echo $domain ?></h3>
						</div>
						<div class="panel-body">

							<?php

							$subdomain = val_sort($infos[$i]["subdomains"], "name");

							//every subdomain
							for($j=0; $j < count($subdomain); $j++)
							{
								//get vars from the file
								$name = $subdomain["$j"]["name"];
								
								//root without subdomain
								if($name == "" ) {
									$name = "{root}";
									$url = "https://".$domain;
								}
								//else with subdomain
								else {
									$url = "https://".$name.".".$domain;
								}

								$lastcheck = date('d/m/Y', $subdomain["$j"]["lastcheck"]);
								$responseCode = $subdomain["$j"]["responsecode"];
								
								//site state for panel color
								if($subdomain["$j"]["state"] == "online"){
									$colorPanel = "panel-success";
								} else {
									$colorPanel = "panel-danger";
								}

								//if the website is HTTPS
								if(array_key_exists("https", $subdomain["$j"]))
								{
									$certState = $subdomain["$j"]["https"]["certstate"];
									$certExpiry = date('d/m/Y', $subdomain["$j"]["https"]["expiry"]);
									$certExpiry_ts = $subdomain["$j"]["https"]["expiry"];

									if($certState != "ok" && $colorPanel == "panel-success") {
										$colorPanel = "panel-warning";
									}

									if($certExpiry_ts < strtotime('+3 day', time()) && $colorPanel == "panel-success") {
										$colorPanel = "panel-warning";	
										echo strtotime('+3 day', time())." => ".$certExpiry_ts."<br/>";
									}
								}

								if((substr($responseCode, 0, 1) != "2") && $colorPanel == "panel-success") {
									$colorPanel = "panel-warning";
								}

								//print the mafic
								echo "<div class='col-md-2 site'>
									<div class='panel $colorPanel'>
										<div class='panel-heading hidden-xs'>
											<a href='$url' target='_blank'>
												<h3 class='panel-title'>$name</h3>
											</a>
										</div>
										<div class='panel-body'>
												<p>
													<b>Last check:</b> $lastcheck <br/>
													<b>Response code:</b> $responseCode <br/>";
													if(isset($certState)){
														echo "<b>Cert state:</b> $certState <br/>
														<b>Cert expiry:</b> $certExpiry <br/>";
													}
												echo "</p>
										</div>
									</div>
									</div>"; 

							}

							?>
							 
						</div>
					</div>

					<?php
						}
					?>

				</div>
				<div class="col-md-1"></div>
			</div>
			
		   
				</div>
				<div class="col-md-1"></div>
			</div>
			
			<div id="contact" style="height: 2em">&nbsp;</div>
			
			<!-- CONTACT
			<div id="contact-row" class="row" style="margin-top: 1em">
				<div class="col-md-1"></div>
				<div class="col-md-10">
					<div id="type_news" class="panel master-panel panel-info">
						<div class="panel-heading">
							<h3 class="panel-title">Contact</h3>
						</div>
						
						<div class='panel-body' style="background-color: #fff">
							<p class='site'>
								Vous souhaitez apporter une modification ou contribuer à ce portail ? Contactez-nous à l'adresse <span id="ad1"></span><span id="ad2"></span> !
							</p>
						</div>
					</div>
				</div>
				<div class="col-md-1"></div>
			</div>
			-->
		</div>
	
	</body>
</html>
