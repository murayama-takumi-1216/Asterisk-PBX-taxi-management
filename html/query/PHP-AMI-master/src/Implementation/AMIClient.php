<?php
/*
 * A functional AMI Client.
 *
 * PHP Version 5
 *
 * @category   PHP-AMI
 * @package    Client
 * @subpackage Implementation
 * @author     Jaime Zúñiga <jaime.ziga@gmail.com>
 * @license    http://github.com/Adrian0350/PHP-AMI/ Apache License 2.0
 * @version    SVN: $Id$
 * @link       http://github.com/Adrian0350/PHP-AMI/
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

declare(ticks=1);

require_once dirname(__FILE__) . '/../PHPAMI/Message/Message.php';
require_once dirname(__FILE__) . '/../PHPAMI/Message/OutgoingMessage.php';
require_once dirname(__FILE__) . '/../PHPAMI/Message/IncomingMessage.php';
require_once dirname(__FILE__) . '/../PHPAMI/Message/Action/LoginAction.php';
require_once dirname(__FILE__) . '/../PHPAMI/Message/Response/ResponseMessage.php';
require_once dirname(__FILE__) . '/../PHPAMI/Message/Event/Factory/Impl/EventFactoryImpl.php';
require_once dirname(__FILE__) . '/../PHPAMI/Listener/IEventListener.php';
require_once dirname(__FILE__) . '/../PHPAMI/Client/Exception/ClientException.php';
require_once dirname(__FILE__) . '/../PHPAMI/Client/IClient.php';
require_once dirname(__FILE__) . '/../Psr/Log/NullLogger.php';
require_once dirname(__FILE__) . '/../Psr/Log/LoggerInterface.php';

/**
 * TCP Client implementation for AMI.
 *
 * PHP Version ^5.1.6
 *
 * @category   Pami
 * @package    Client
 * @subpackage Implementation
 * @author     Adrián Zúñiga <jaime.ziga@gmail.com>
 * @license	   http://github.com/Adrian0350/PHP-AMI/ Apache License 2.0
 * @link       http://github.com/Adrian0350/PHP-AMI/
 */
class AMIClient implements IClient
{
	/**
	 * PSR-3 logger.
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * Hostname
	 * @var string
	 */
	private $host;

	/**
	 * TCP Port.
	 * @var integer
	 */
	private $port;

	/**
	 * Username
	 * @var string
	 */
	private $user;

	/**
	 * passwordword
	 * @var string
	 */
	private $password;

	/**
	 * Connection timeout, in seconds.
	 * @var integer
	 */
	private $connect_timeout = 10;

	/**
	 * Connection scheme, like tcp:// or tls://
	 * @var string
	 */
	private $scheme = 'tcp://';

	/**
	 * Event factory.
	 * @var EventFactoryImpl
	 */
	private $eventFactory;

	/**
	 * R/W timeout, in milliseconds.
	 * @var integer
	 */
	private $read_timeout;

	/**
	 * Our stream socket resource.
	 * @var resource
	 */
	private $socket;

	/**
	 * Our stream context resource.
	 * @var resource
	 */
	private $context;

	/**
	 * Our event listeners
	 * @var IEventListener[]
	 */
	private $eventListeners = array();

	/**
	 * The receiving queue.
	 * @var IncomingMessage[]
	 */
	private $incomingQueue = array();

	/**
	 * Our current received message. May be incomplete, will be completed
	 * eventually with an EOM.
	 * @var string
	 */
	private $currentProcessingMessage;

	/**
	 * This should not happen. Asterisk may send responses without a
	 * corresponding ActionId.
	 * @var string
	 */
	private $lastActionId = false;

	/**
	 * Event mask to apply on login action.
	 * @var string|null
	 */
	private $eventMask = null;


	/**
	 * Constructor.
	 *
	 * @param string[] $options Options for ami client.
	 *
	 */
	public function __construct(array $options)
	{

		$this->host            = (string) $options['host'];
		$this->port            = (int) $options['port'];
		$this->user            = (string) $options['username'];
		$this->password        = (string) $options['password'];
		$this->connect_timeout = (int) $options['connect_timeout'];
		$this->read_timeout    = (int) $options['read_timeout'];
		$this->scheme          = isset($options['scheme']) ? $options['scheme'] : 'tcp://';
		$this->eventMask       = isset($options['event_mask']) ? $options['event_mask'] : null;
		$this->logger          = new NullLogger();
		$this->eventFactory    = new EventFactoryImpl();
	}

	/**
	 * Opens a tcp connection to AMI.
	 *
	 * @throws \PHPAMI\Client\Exception\ClientException
	 * @return void
	 */
	public function open()
	{
		$connection    = $this->scheme . $this->host . ':' . $this->port;
		$error_code    = 0;
		$error_message = '';
		$this->context = stream_context_create();
		$this->socket  = @stream_socket_client(
			$connection,
			$error_code,
			$error_message,
			$this->connect_timeout,
			STREAM_CLIENT_CONNECT, $this->context
		);

		if ($this->socket === false)
		{
			return $this->logger->log(PHP_EOL.'Error connecting to AMI: '.$error_message.PHP_EOL);
		}

		$msg = new LoginAction($this->user, $this->password, $this->eventMask);

		$asteriskId = @stream_get_line($this->socket, 1024, Message::EOL);

		if (strstr($asteriskId, 'Asterisk') === false)
		{
			return $this->logger->log(PHP_EOL.'Unknown peer. Is this an AMI?: '.$asteriskId.PHP_EOL);
		}

		$response = $this->send($msg);

		if ($response && !$response->isSuccess())
		{
			return $this->logger->log(PHP_EOL.'Could not connect: '.$response->getMessage().PHP_EOL);
		}

		@stream_set_blocking($this->socket, 0);
		$this->currentProcessingMessage = '';

		$this->logger->log(PHP_EOL.'Logged in successfully to AMI.'.PHP_EOL);

		$this->listen();
	}

	/**
	 * Basically the main loop.
	 *
	 * @return void
	 */
	private function listen()
	{
		$connected = true;

		while ($connected)
		{
			$this->process();
			usleep(1000);
		}
	}

	/**
	 * Registers the given listener so it can receive events. Returns the generated
	 * id for this new listener. You can password in a an IEventListener, a Closure,
	 * and an array containing the object and name of the method to invoke. Can specify
	 * an optional predicate to invoke before calling the callback.
	 *
	 * @param mixed $listener
	 * @param \Closure|null $predicate
	 *
	 * @return string
	 */
	public function registerEventListener($listener, $predicate = null)
	{
		$listenerId = uniqid('PamiListener');
		$this->eventListeners[$listenerId] = array($listener, $predicate);

		return $listenerId;
	}

	/**
	 * Unregisters an event listener.
	 *
	 * @param string $listenerId The id returned by registerEventListener.
	 *
	 * @return void
	 */
	public function unregisterEventListener($listenerId)
	{
		if (isset($this->eventListeners[$listenerId]))
		{
			unset($this->eventListeners[$listenerId]);
		}
	}

	/**
	 * Reads a complete message over the stream until EOM.
	 *
	 * @throws ClientException
	 * @return \string[]
	 */
	protected function getMessages()
	{
		$messages = array();
		$read     = @fread($this->socket, 65535);

		if ($read === false || @feof($this->socket))
		{
			$this->logger->log(PHP_EOL.'Error reading... Opening.'.PHP_EOL);
			if ($this->close())
			{
				$this->open();
			}
		}

		$this->currentProcessingMessage .= $read;

		// If we have a complete message, then return it. Save the rest for
		// later.
		while (($marker = strpos($this->currentProcessingMessage, Message::EOM)))
		{
			$msg = substr($this->currentProcessingMessage, 0, $marker);
			$this->currentProcessingMessage = substr($this->currentProcessingMessage, $marker + strlen(Message::EOM));

			$messages[] = $msg;
		}

		return $messages;
	}

	/**
	 * Main processing loop. Also called from send(), you should call this in
	 * your own application in order to continue reading events and responses
	 * from AMI.
	 */
	public function process()
	{
		$messages = (array) $this->getMessages();

		foreach ($messages as $message)
		{
			$response = strpos($message, 'Response:');
			$event    = strpos($message, 'Event:');

			if (($response !== false) && (($response < $event) || $event === false))
			{
				$response = $this->messageToResponse($message);
				$this->incomingQueue[$response->getActionId()] = $response;
			}
			elseif ($event !== false)
			{
				$event    = $this->messageToEvent($message);
				$response = $this->findResponse($event);

				if ($response === false || $response->isComplete())
				{
					$this->dispatch($event);
				}
				else
				{
					$response->addEvent($event);
				}
			}
			else
			{
				// broken AMI.. sending a response with events without
				// Event and ActionId
				$event_message   = 'Event: ResponseEvent'.PHP_EOL;
				$event_message  .= 'ActionId: '.$this->lastActionId.PHP_EOL.$message;
				$event           = $this->messageToEvent($event_message);
				$response        = $this->findResponse($event);

				$response->addEvent($event);
			}
		}
	}

	/**
	 * Tries to find an associated response for the given message.
	 *
	 * @param IncomingMessage $message Message sent by asterisk.
	 *
	 * @return \PHPAMI\Message\Response\ResponseMessage
	 */
	protected function findResponse(IncomingMessage $message)
	{
		$actionId = $message->getActionId();

		if (isset($this->incomingQueue[$actionId]))
		{
			return $this->incomingQueue[$actionId];
		}

		return false;
	}

	/**
	 * Dispatchs the incoming message to a handler.
	 *
	 * @param \PHPAMI\Message\IncomingMessage $message Message to dispatch.
	 *
	 * @return void
	 */
	protected function dispatch(IncomingMessage $message)
	{
		foreach ($this->eventListeners as $data)
		{
			$listener = $data[0];
			$predicate = $data[1];

			if (is_callable($predicate) && !call_user_func($predicate, $message))
			{
				continue;
			}
			if ($listener instanceof Closure)
			{
				$listener($message);
			}
			elseif (is_array($listener))
			{
				$listener[0]->{$listener[1]}($message);
			}
			else
			{
				$listener->handle($message);
			}
		}
	}

	/**
	 * Returns a ResponseMessage from a raw string that came from asterisk.
	 *
	 * @param string $msg Raw string.
	 *
	 * @return \PHPAMI\Message\Response\ResponseMessage
	 */
	private function messageToResponse($msg)
	{
		$response = new ResponseMessage($msg);
		$actionId = $response->getActionId();

		if (is_null($actionId))
		{
			$actionId = $this->lastActionId;

			$response->setActionId($this->lastActionId);
		}

		return $response;
	}

	/**
	 * Returns a EventMessage from a raw string that came from asterisk.
	 *
	 * @param string $msg Raw string.
	 *
	 * @return \PHPAMI\Message\Event\EventMessage
	 */
	private function messageToEvent($msg)
	{
		return $this->eventFactory->createFromRaw($msg);
	}

	/**
	 * Returns a message (response) related to the given message. This uses
	 * the ActionID tag (key).
	 *
	 * @todo not suitable for multithreaded applications.
	 *
	 * @return \PHPAMI\Message\IncomingMessage
	 */
	protected function getRelated(OutgoingMessage $message)
	{
		$id      = $message->getActionID('ActionID');
		$related = false;

		if (isset($this->incomingQueue[$id]))
		{
			$response = $this->incomingQueue[$id];

			if ($response->isComplete())
			{
				unset($this->incomingQueue[$id]);

				$related = $response;
			}
		}

		return $related;
	}

	/**
	 * Sends a message to AMI.
	 *
	 * @param \PHPAMI\Message\OutgoingMessage $message Message to send.
	 *
	 * @see ElastixWatcher::send()
	 * @throws \PHPAMI\Client\Exception\ClientException
	 * @return \PHPAMI\Message\Response\ResponseMessage
	 */
	public function send(OutgoingMessage $message)
	{
		$messageToSend = $message->serialize();

		$length = strlen($messageToSend);
		$this->logger->log(PHP_EOL.'---------------------- SENDING ----------------------'.PHP_EOL.$messageToSend);
		$this->lastActionId = $message->getActionId();

		if (@fwrite($this->socket, $messageToSend) < $length)
		{
			return $this->logger->log(PHP_EOL.'Could not send message...'.PHP_EOL);
		}

		$read = 0;
		while ($read <= $this->read_timeout)
		{
			$this->process();
			$response = $this->getRelated($message);

			if ($response != false)
			{
				$this->lastActionId = false;
				return $response;
			}

			usleep(1000); // 1ms delay

			if ($this->read_timeout > 0)
			{
				$read++;
			}
		}

		$this->logger->log(PHP_EOL.'Read timeout... Resending.'.PHP_EOL);
		$this->send($message);
	}

	/**
	 * Closes the connection to AMI.
	 *
	 * @return bool
	 */
	public function close()
	{
		try
		{
			// Try to close connection with and read out everything in input buffer.
			socket_shutdown($this->socket, STREAM_SHUT_WR);
			while(fgets($sock) !== false) { ; }

			// Close stream definately.
			$closed = fclose($this->socket);
			$this->logger->log(PHP_EOL.'Closing connection to asterisk '.$closed.PHP_EOL);
		}
		catch (Exception $e)
		{
			$this->logger->log(PHP_EOL.'Exception trying to close asterisk connection: '.$e->getMessage());
			$closed = false;
		}

		return $closed;
	}

	/**
	 * Sets the logger implementation.
	 *
	 * @param LoggerInterface $logger The PSR3-Logger
	 *
	 * @return void
	 */
	public function setLogger(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}
}
