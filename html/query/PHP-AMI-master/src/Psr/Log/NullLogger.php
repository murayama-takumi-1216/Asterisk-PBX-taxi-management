<?php

require_once dirname(__FILE__) . '/LoggerInterface.php';

class NullLogger implements LoggerInterface
{
	public $debug = true;

	public function log($debug)
	{
		if ($this->debug)
		{
			echo $debug;
		}
	}
}
