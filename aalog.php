<?php
/*
aaLog: A logging class for PHP
Author: A. Alfred Ayache
Email: alfred@notoriouswebmaster.com
Documentation: http://notoriouswebmaster.com

Creative Commons Attribution-ShareAlike 3.0 Unported
CC BY-SA 3.0
http://creativecommons.org/licenses/by-sa/3.0/
*/

class aaLog {

  const COOKIE_NAME = 'aalog::debug';
	
	protected $fp;
	protected $bt_line;
	protected $bDebug = true;
	protected $benchStart;
	protected $benchLast;
	protected $benchNum = 0;

	function aaLog($logName) {
		
		$this->bt_line = '';
		
		$this->bDebug = isset($_COOKIE[self::COOKIE_NAME]);
		if (empty($this->fp)) {
			$this->fp = fopen($logName, 'a+');
		}
		// echo "\n" . 'fp: ' . $this->fp . "\n";
	}
  
  function isDebug() {
    
    return isset($_COOKIE[self::COOKIE_NAME]);
  }
	
	function getFunc($n=0) {

		$arr = debug_backtrace();
		// fwrite($this->fp, var_export($arr, true)); xxx
		
		$i = 0;
		$sz = sizeof($arr);
		while ($i < $sz && isset($arr[$i]['class']) && $arr[$i]['class'] == 'aaLog') {
			++$i;
		}
		$i += $n - 1;
		// fwrite($this->fp, 'getFunc()->$i: ' . $i . "\n");
				
		$parts = pathinfo($arr[$i]['file']);
		$this->bt_file = $parts['basename'];
		$this->bt_line = $arr[$i]['line'];
		$this->outline = $this->bt_file . "[" . $this->bt_line ."]";
	}
	
	function logerr($msg, $n=0) {
		$this->getFunc($n);
		$this->logit('ERR', $msg);
	}
	
	function logtrace($n=0) {
		$this->getFunc($n);
		$aTrace = debug_backtrace();
		$sTrace = "";
		foreach ($aTrace as $k => $v) {
			// file, line, class, type, function
			$sTrace .= "\n\t" . $v['file'] . '[' . $v['line'] . '] - ' . (isset($v['class']) ? @$v['class'] . @$v['type'] : '') . @$v['function'];
		}
		$this->logit('TRACE', $sTrace);
	}
	
	function loginfo($msg, $n=0) {
		$this->getFunc($n);
		$this->logit('INFO', $msg);
	}
	
	function logdbg($msg, $n=0) {
		
		if ($this->bDebug) {
			$this->getFunc($n);
			$this->logit('DEBUG', $msg);
		}
	}
	
	function logbench($msg = NULL, $n=0) {
		
		if ($this->bDebug) {
			$this->getFunc($n);
			$time = $this->getMicrotime();
			if ($this->benchStart == NULL) {
			  $this->benchStart = $time;
			}
			if ($msg == NULL) {
			  $msg = ++$this->benchNum;
			}
			$msg = 'id: ' . $msg . ' - diff: ' . ($this->benchLast === NULL ? '0' : $time - $this->benchLast)
			  . ' - len: ' . ($time - $this->benchStart);
			$this->benchLast = $time;
			$this->logit('BENCH', $msg);
		}
	}
	
	function logrow($name, $row, $n=0) {
		
		if ($this->bDebug) {
			$this->logdbg($name . ' - ' . print_r($row, true), $n);
			@reset($row);
		}
	}
	
	function logdeftype($msg, $val, $type=null) {
		
		$def = $val;
		$this->logtype($msg, $def, $type);
	}
	
	function logtype($msg, &$val, $type=null) {
		
		if ($this->bDebug) {
			$retval = '';
			if (is_bool($val) || $type == 'bln') {
				$type = 'bln';
				$retval = ($val ? 'true' : 'false');
			} elseif (is_array($val) || $type == 'arr') {
				$type = 'arr';
				$retval = print_r($val, true);
			} elseif (is_object($val) || $type == 'obj') {
				$type = 'obj';
				$retval = print_r($val, true);
			} elseif (is_numeric($val) || $type == 'num') {
				$type = 'num';
				$retval = $val;
			} elseif (is_string($val) || $type == 'str') {
				$type = 'str';
				$retval = $val;
			} elseif (is_resource($val) || $type == 'res') {
				$type = 'res';
				$retval = $val;
			} elseif (is_null($val) || $type == 'nul') {
				$type = 'nul';
				$retval = $val;
			} else {
				$type = 'oth';
				$retval = $val;
			}
			$this->getFunc();
			$this->logit('TYPE:' . $type, $msg . ' - ' . $retval);
		}
	}
	
	function logit($type, $msg) {
		
		fwrite($this->fp, date('Y-m-d H:i:s') . ' - ' . $_SERVER['REMOTE_ADDR'] . ' - ' . $type . ' - ' . substr($_SERVER['PHP_SELF'], 1) . ' - ' . $this->outline . ' - ' . $msg . "\n");
		fflush($this->fp);
		$this->bt_line = '';
	}
	
	function lograw($msg) {
		
		if ($this->bDebug) {
  		fwrite($this->fp, $msg . "\n");
  		fflush($this->fp);
  		$this->bt_line = '';
		}
	}
	
	function setCookie($val=1) {
		
		$this->logdbg('Setting Log Cookie', -1);
		setcookie(self::COOKIE_NAME, $val, time() + 60 * 60 * 24 * 360, '/');
		$this->bDebug = true;
	}
	
	function unsetCookie() {
		
		$this->logdbg('Unsetting Log Cookie', -1);
		setcookie(self::COOKIE_NAME, true, time() - 3600, '/');
		$this->bDebug = false;
	}
	
	function logSession() {
		
		$this->logrow('SESSION', $_SESSION);
	}
	
	function logServer() {
		
		$this->logrow('SERVER', $_SERVER);
	}
	
	function logRequest() {
		
		$this->logrow('REQUEST', $_REQUEST);
	}
	
	function logascii($msg, $var) {
		
		if ($this->bDebug) {
			$this->logit('ASCII', $msg);
			$sz = sizeof($var);
			for ($i = 0; $i < $sz; ++$i) {
				$c = substr($var, $i, 1);
				fwrite($this->fp, ($i + 1) . ' - ' . ord($c) . ' - ' . $c . "\n");
			}
		}
	}
	
	function close() {
		fclose($this->fp);
	}
	
  function getMicrotime() {
    $microtime = explode(' ', microtime());
    return $microtime[1] . substr($microtime[0], 1);
  }

}
