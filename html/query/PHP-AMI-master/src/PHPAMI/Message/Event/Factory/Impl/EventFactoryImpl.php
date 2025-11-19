<?php
/**
 * This factory knows which event to return according to a given raw message
 * from ami.
 *
 * PHP Version 5
 *
 * @category   PHPAMI
 * @package	Event
 * @subpackage Factory.Impl
 * @author	 Jaime Ziga <jaime.ziga@gmail.com>
 * @license	http://github.com/Adrian0350/PHP-AMI/ Apache License 2.0
 * @version	SVN: $Id$
 * @link	   http://github.com/Adrian0350/PHP-AMI/
 *
 * Copyright 2011 Marcelo Gornstein <marcelog@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *	 http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */

require_once dirname(__FILE__) . '/../../EventMessage.php';
require_once dirname(__FILE__) . '/../../../Message.php';
require_once dirname(__FILE__) . '/../../UnknownEvent.php';

function loadEventClass($className)
{
	return require_once dirname(__FILE__) . "/../../$className.php";
}

/**
 * This factory knows which event to return according to a given raw message
 * from ami.
 *
 * PHP Version 5
 *
 * @category   PHPAMI
 * @package    Event
 * @subpackage Factory.Impl
 * @author     Jaime Ziga <jaime.ziga@gmail.com>
 * @license    http://github.com/Adrian0350/PHP-AMI/ Apache License 2.0
 * @link       http://github.com/Adrian0350/PHP-AMI/
 */
class EventFactoryImpl
{
	/**
	 * This is our factory method.
	 *
	 * @param string $message Literall message as received from ami.
	 *
	 * @return EventMessage
	 */
	public static function createFromRaw($message)
	{
		$eventStart = strpos($message, 'Event: ') + 7;
		$eventEnd   = strpos($message, Message::EOL, $eventStart);

		if ($eventEnd === false)
		{
			$eventEnd = strlen($message);
		}

		$name       = substr($message, $eventStart, $eventEnd - $eventStart);
		$parts      = explode('_', $name);
		$totalParts = count($parts);

		for ($i = 0; $i < $totalParts; $i++)
		{
			$parts[$i] = ucfirst($parts[$i]);
		}

		$name      = implode($parts, '');
		$className = "{$name}Event";

		if (file_exists(dirname(__FILE__)."/../../$className.php"))
		{
			try
			{
				spl_autoload_register(loadEventClass($className), true, true);

				if (class_exists($className, true))
				{
					return new $className($message);
				}
			}
			catch (Exception $e)
			{
				return new UnknownEvent($message);
			}
		}

		return new UnknownEvent($message);
	}

	/**
	 * Constructor. Nothing to see here, move along.
	 *
	 * @return void
	 */
	public function __construct()
	{}
}
