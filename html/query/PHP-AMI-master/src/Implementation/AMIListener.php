<?php
/*
 * A functional AMI Listener.
 *
 * PHP Version 5
 *
 * @category PHP-AMI
 * @package  Listener
 * @author   Jaime Zúñiga <jaime.ziga@gmail.com>
 * @license  http://github.com/Adrian0350/PHP-AMI/ Apache License 2.0
 * @version  SVN: $Id$
 * @link     http://github.com/Adrian0350/PHP-AMI/
 *
 * Copyright 2011 Marcelo Gornstein <marcelog@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */

require_once dirname(__FILE__) . '/../PHPAMI/Message/Event/EventMessage.php';
require_once dirname(__FILE__) . '/../PHPAMI/Listener/IEventListener.php';
require_once dirname(__FILE__) . '/../PHPAMI/Message/Event/DialEvent.php';

class AMIListener implements IEventListener
{
	public function handle(EventMessage $event)
	{
		if ($event instanceof DialEvent && $event->getSubEvent() == 'Begin')
		{
			$this->dispatchEvent($event);
		}
	}

	public function dispatchEvent($event)
	{
		echo $event->getRawContent() . "\n\n\n\n";
	}
}
