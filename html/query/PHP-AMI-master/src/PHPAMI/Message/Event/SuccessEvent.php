<?php

require_once dirname(__FILE__) . '/EventMessage.php';

class SuccessEvent extends EventMessage
{
	public function getRawContent()
	{
		return $this->getKey('RawContent');
	}
}
