<?php
class Timer{
	private $lastTime = 0;
	private $firstTime = 0;
	private $timingMessages;
	private $timingsEnabled = false;

	public function Timer($startTime){
		global $configArray;
		if ($configArray){
			if (isset($configArray['System']['timings'])) {
				$this->timingsEnabled = $configArray['System']['timings'];
			}
		}else{
			$this->timingsEnabled = true;
		}
		$startTime = microtime(true);
		$this->lastTime = $startTime;
		$this->firstTime = $startTime;
		$this->timingMessages = array();
	}
	public function logTime($message){
		if ($this->timingsEnabled){
			$curTime = microtime(true);
			$elapsedTime = round($curTime - $this->lastTime, 4);
			if ($elapsedTime > 0){
				$this->timingMessages[] = "$message: $curTime ($elapsedTime sec)";
			}
			$this->lastTime = $curTime;
		}
	}

	public function enableTimings($enable){
		$this->timingsEnabled = $enable;
	}

	function writeTimings(){
		if ($this->timingsEnabled){
			$curTime = microtime(true);
			$elapsedTime = round($curTime - $this->lastTime, 4);
			//if ($elapsedTime > 0){
				$this->timingMessages[] = "Finished run: $curTime ($elapsedTime sec)";
			//}
			$this->lastTime = $curTime;
			global $logger;
			$totalElapsedTime =round(microtime(true) - $this->firstTime, 4);
			$timingInfo = "\r\nTiming for: " . $_SERVER['REQUEST_URI'] . "\r\n";
			$timingInfo .= implode("\r\n", $this->timingMessages);
			$timingInfo .= "\r\nTotal Elapsed time was: $totalElapsedTime seconds.\r\n";
			$logger->log($timingInfo, PEAR_LOG_NOTICE);
		}
	}

	function __destruct() {
		if ($this->timingsEnabled){
			global $logger;
			$totalElapsedTime =round(microtime(true) - $this->firstTime, 4);
			$timingInfo = "\r\nTiming for: " . $_SERVER['REQUEST_URI'] . "\r\n";
			$timingInfo .= implode("\r\n", $this->timingMessages);
			$timingInfo .= "\r\nTotal Elapsed time was: $totalElapsedTime seconds.\r\n";
			$logger->log($timingInfo, PEAR_LOG_NOTICE);
		}
	}
}