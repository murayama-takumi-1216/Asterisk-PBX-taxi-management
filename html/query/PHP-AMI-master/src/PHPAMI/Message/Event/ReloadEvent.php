<?php

require_once dirname(__FILE__) . '/EventMessage.php';

class ReloadEvent extends EventMessage
{
	public function getRawContent()
	{
		return $this->getKey('RawContent');
	}
}
