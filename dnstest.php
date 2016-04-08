<?php
# Dynect API REST Examples - PHP
# All examples utilize JSON for the data formating. 
# Output assumes use of PHP CLI
require 'php-statsd/libraries/statsd.php';

# global stuff
$base_url = 'https://api2.dynect.net/REST';
date_default_timezone_set('UTC');


$DEBUG=false;
$failed="";
$message="";
$token="";
$startdate=mktime();;

$statsall = new StatsD('graphite.bigg33k.net', 8125);


$statsall->time_this('dns_php', function() {
	$statsfunc = new StatsD('graphite.bigg33k.net', 8125);
	$statsfunc->time_this('login', function() {
		global $base_url, $failed, $token,  $startdate, $DEBUG;
		$date = mktime();
		$startdate=$date;
		print "\nDNSAPI Starting: ".$date;
		/* ##########################
		  Logging In
		  ------------
		  To log in to the DynECT API you must first create a session via a POST command. 
		  Some Returned Values 
			status - success or failure
			data->token - to be used with all other commands
			** Complete Documentations can be found at https://help.dynect.net/ 
		########################## */

		# Create an associative array with the required arguments
		$create_session = array(
			'customer_name' => 'CUSTNAME',
			'user_name' => 'USER',
			'password' => 'PASSWORD');


		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  # TRUE to return the transfer as a string of the return value of curl_exec() instead of outputting it out directly. 
		curl_setopt($ch, CURLOPT_FAILONERROR, false); # Do not fail silently. We want a response regardless
		curl_setopt($ch, CURLOPT_HEADER, false); # disables the response header and only returns the response body 
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json')); # Set the content type of the post body
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_URL, $base_url.'/Session/'); # Where this action is going,
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($create_session));

		$http_result = curl_exec($ch); 

		$decoded_result = json_decode($http_result); # Decode from JSON as our results are in the same format as our request
		$date = mktime();
		if($decoded_result->status == 'success'){
			print "\nDNSAUTH Success: ".$date;
			$token = $decoded_result->data->token;
		} else { 
			print "\nDNSAUTH Failed: ".$date;
			$failed="dnsapi.failed.auth 1 ".$date."\n";
		}

		/* ##########################
		  Checking if still Logged In
 		 -------------
		 To verify if your session is still active you must send a GET request with your Auth-Token in the header
		############################# */

	#	global $base_url, $failed, $token,  $startdate;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  # TRUE to return the transfer as a string of the return value of curl_exec() instead of outputting it out directly.
		curl_setopt($ch, CURLOPT_FAILONERROR, false); # Do not fail silently. We want a response regardless
		curl_setopt($ch, CURLOPT_HEADER, false); # disables the response header and only returns the response body
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Auth-Token: '.$token)); # Set the token and the content type so we know the response format
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($ch, CURLOPT_URL, $base_url.'/Session/'); # Where this action is going,

		$http_result = curl_exec($ch);
		$decoded_result = json_decode($http_result); # Decode from JSON as our results are in the same format as our request
	});
	$statsfunc->time_this('get_txt', function() {
		/* ###########################
		  Get a Specific Zone TXT Record detail
		  ----------------
		  To get the A Records of a zone you must send a GET request with the session token in the header under 'Auth-Token'
		########################### */
		global $base_url, $failed, $token, $myrecord, $startdate, $DEBUG;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  # TRUE to return the transfer as a string of the return value of curl_exec() instead of outputting it out directly.
		curl_setopt($ch, CURLOPT_FAILONERROR, false); # Do not fail silently. We want a response regardless
		curl_setopt($ch, CURLOPT_HEADER, false); # disables the response header and only returns the response body
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Auth-Token: '.$token)); # Set the token and the content type so we know the response format
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($ch, CURLOPT_URL, $base_url.'/TXTRecord/geo.bigg33k.net/test.geo.bigg33k.net/'); # Where this action is going,

		$http_result = curl_exec($ch);

		# Decode from JSON as our results are in the same format as our request
		$decoded_result = json_decode($http_result);
	
		if($DEBUG){
		 	echo '<pre>';
	 		print_r($decoded_result->data);
		 	echo '</pre_v>'; # NOTE: The "_v" has been added to this tag for WordPress visibility. Remove "_v" for proper functionality of <pre_v> tag.
 
		 	foreach($decoded_result->msgs as $message){
				print $message->LVL.": ".($message->ERR_CD != '' ? '('.$message->ERR_CD.') ' : '').$message->SOURCE." - ".$message->INFO."<br/>";
 			}
		}

		if($decoded_result->status == 'success'){
        		# Array containing REST URIs for each A record in a node by record ID
        		# Example: /REST/ARecord/test.com/foo.test.com/1234567
	        	$records = $decoded_result->data;
        		$last_txt_record = $records[count($records)-1];
			$myrecord=$records[0];
			#print "RECORD->".$myrecord;
		}

	});
        $statsfunc->time_this('add_txt', function() {
		/* ###########################
		  Add an TXT  Record to a node
		  ----------------
		  To add an A record to a zone you must send a POST request
		  with an associatve rdata array with the session token in the header under 'Auth-Token' 
		  Adding an A record to a node that doesn't exist will create the node and then add the A Record
		  Note: Zones MUST be published for these changes to take effect.
		########################### */
		global $base_url, $failed, $token, $myrecord, $startdate, $DEBUG;
		$date = mktime();
		$post_fields = array(
				'rdata' => array(
					'txtdata' => $date,
							),
					'ttl'	=> '60'							
				);
		print  "\nDNSREC TXTRecord: ".$date;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  # TRUE to return the transfer as a string of the return value of curl_exec() instead of outputting it out directly.
		curl_setopt($ch, CURLOPT_FAILONERROR, false); # Do not fail silently. We want a response regardless
		curl_setopt($ch, CURLOPT_HEADER, false); # disables the response header and only returns the response body
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Auth-Token: '.$token)); # Set the token and the content type so we know the response format
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_URL, 'https://api2.dynect.net'.$myrecord); # Where this action is going,
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_fields));

		$http_result = curl_exec($ch);

		$decoded_result = json_decode($http_result); # Decode from JSON as our results are in the same format as our request

		if($decoded_result->status == 'success'){
			$zone = $decoded_result->data; # Associative Array containing zone data: zone_type, serial_style, serial, zone 
		} else print  "\nSET ERR->".$decoded_result->status;
	});
        $statsfunc->time_this('publish', function() {
		/* ###########################
		  Publish all Zone Changes
		  ----------------
		  To publish changes to a Zone you must send a PUT request with the session token in the header under 'Auth-Token'
		########################### */
		global $base_url, $failed, $token,  $startdate, $DEBUG;
		$put_fields = array(
				'publish' => 1
				);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  # TRUE to return the transfer as a string of the return value of curl_exec() instead of outputting it out directly.
		curl_setopt($ch, CURLOPT_FAILONERROR, false); # Do not fail silently. We want a response regardless
		curl_setopt($ch, CURLOPT_HEADER, false); # disables the response header and only returns the response body
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Auth-Token: '.$token)); # Set the token and the content type so we know the response format
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($ch, CURLOPT_URL, $base_url.'/Zone/geo.bigg33k.net/'); # Where this action is going,
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($put_fields));

		$http_result = curl_exec($ch);

		$decoded_result = json_decode($http_result); # Decode from JSON as our results are in the same format as our request

		if($decoded_result->status == 'success'){
			$date = mktime();
			print "\nDNSPUB Success: ".$date;
			$publish_data = $decoded_result->data; # Associative Array containing zone data: zone_type, serial_style, serial, zone 
			#print_r($publish_data);
		} else {
			print "\nDNSPUB Failed: ".$date."\n";
			print_r($publish_data);
			$failed="dnsapi.failed.pub 1 ".$date."\n";
		}
	});
        $statsfunc->time_this('get_txt', function() {
		/* ###########################
		  Get a Specific Zone A Record detail
		  ----------------
 		 To get the A Records of a zone you must send a GET request with the session token in the header under 'Auth-Token'
		########################### */
		global $base_url, $failed, $token,  $startdate, $DEBUG;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  # TRUE to return the transfer as a string of the return value of curl_exec() instead of outputting it out directly.
		curl_setopt($ch, CURLOPT_FAILONERROR, false); # Do not fail silently. We want a response regardless
		curl_setopt($ch, CURLOPT_HEADER, false); # disables the response header and only returns the response body
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Auth-Token: '.$token)); # Set the token and the content type so we know the response format
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($ch, CURLOPT_URL, $base_url.'/TXTRecord/geo.bigg33k.net/test.geo.bigg33k.net/'); # Where this action is going,

		$http_result = curl_exec($ch);

		# Decode from JSON as our results are in the same format as our request
		$decoded_result = json_decode($http_result);

		if($decoded_result->status == 'success'){
			# Array containing REST URIs for each A record in a node by record ID
			# Example: /REST/ARecord/test.com/foo.test.com/1234567 
			$records = $decoded_result->data; 
			$last_a_record = $records[count($records)-1];
		}
	});
        $statsfunc->time_this('logout', function() {

		/* ###########################
		  Logging Out
		  -------------
 		 To logout you must send a DELETE request with the session Token in header under 'Auth-Token'
		############################# */
		global $base_url, $failed, $token, $message,  $startdate, $DEBUG;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  # TRUE to return the transfer as a string of the return value of curl_exec() instead of outputting it out directly. 
		curl_setopt($ch, CURLOPT_FAILONERROR, false); # Do not fail silently. We want a response regardless
		curl_setopt($ch, CURLOPT_HEADER, false); # disables the response header and only returns the response body 
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Auth-Token: '.$token)); # Set the token and the content type so we know the response format 
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');	
		curl_setopt($ch, CURLOPT_URL, $base_url.'/Session/'); # Where this action is going,

		$http_result = curl_exec($ch); 

		$decoded_result = json_decode($http_result); # Decode from JSON as our results are in the same format as our request

		$date = mktime();
		$enddate=$date;
		print "\nDNSAPI Stopping: ".$date."\n";
		print "\nDNSAPI Duration: ". (string)($enddate-$startdate)."\n";
		$message = "dnsapi.duration ".(string)($enddate-$startdate)." ".$date."\n".$failed;
	        print $message;

	});
        $statsfunc->time_this('send_metrics', function() {

		global $base_url, $failed, $token, $message, $startdate, $DEBUG;
		$host = "graphite.bigg33k.net";
		$port = "2003";
		$timeout = 25;  //timeout in seconds

		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)  or die("Unable to create socket\n");
		try {
			socket_connect($socket, $host, $port);
			$sent = socket_write($socket,$message,strlen($message));
			print ("\nSent Metric: ".(string)$message);
			socket_close($socket);
		} catch (Exception $e) {
		   	echo 'Caught exception: ',  $e->getMessage(), "\n";
		}
	});
});
?>
