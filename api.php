<?php
session_start();

// Detect if request comes from URL parameters (GET) or form (POST)
$is_api_mode = !empty($_GET);
$input = $is_api_mode ? $_GET : $_POST;

if ($input) {
    $baud   = $input['baud'] ?? '9600';
    $action = $input['action'] ?? 'raw';
    $port   = $_GET['port'] ?? 'COM4';
	$cmd = $_GET['cmd'] ?? 'D';

	if($action==="raw" && ($cmd==='P' || $cmd==='D')){ 
	   if(!empty($cmd)){	
			$cmd = '/1m50h10j4V1600L400z4000'. $cmd .'4000R';
			// Build the command string
                       // if (1===1) throw new Exception("pumpAPI.exe $port $baud $action $cmd");
			$command = "pumpAPI.exe $port $baud $action $cmd";
			//$command = "pumpAPI.exe $port $baud $action $volume $tubing $direction $speed";
			
			// Execute the command
			$output = [];
			$return_var = 0;
			exec($command, $output, $return_var);

			// Check results
			if ($return_var === 0){ //Success
				echo "ok:Pump $cmd command successful:\n";
				echo implode("\n", $output);
				
				
			} else {	//Error
				echo "error:Pump command failed with $cmd code $return_var:\n";
				echo implode("\n", $output);
			}
        } 
    }
	else if(intval($cmd)>0){
		$_SESSION['factor'] = intval(4000/intval($cmd));
		echo $_SESSION['factor'];
	}
}

?>