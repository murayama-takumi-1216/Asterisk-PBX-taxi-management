<?php

require_once dirname(__FILE__) . '/EventMessage.php';

class ChannelReloadEvent extends EventMessage
{
	public function getRawContent()
	{
		return $this->getKey('RawContent');
	}
}
