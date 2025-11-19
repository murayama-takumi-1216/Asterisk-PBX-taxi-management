# Introduction

PHP-AMI stands for PHP Asterisk Management Interface.
This is a downgraded version of Marcelog's [PAMI](https://github.com/marcelog/PAMI) for **PHP ^5.1.6**.
As its name suggests its just a
set of PHP classes that will let you issue commands to an AMI and/or receive
events, using an observer-listener pattern.

The idea behind this, is to easily implement operator consoles, monitors, etc.
either via SOA or ajax.

A port for nodejs is available at: http://marcelog.github.com/Nami
A port for erlang is available at: https://github.com/marcelog/erlami

# Resources

 * [API](http://pami.readthedocs.org/en/latest/ApiIndex/)
 * [Complete PAMI talk for the PHP Conference Argentina 2013](http://www.slideshare.net/mgornstein/phpconf-2013). Check the slide notes for the complete text :)

# PHP Version

Note: PHP-AMI Requires PHP 5.1.6+. PHP versions 5.3.9 and 5.3.10 WILL NOT WORK due
to a bug introduced in stream_get_line() in 5.3.9. Please use 5.3.11+ or up
to 5.3.8 (see README.PHP-5.3.9-and-5.3.10).

# Installing
Add this library to your [Composer](https://packagist.org/) configuration. In
composer.json:
```json
  "require": {
    "adrian0350/php-ami": "1.*"
  }
```

# QuickStart

For an in-depth tutorial: http://marcelog.github.com/articles/pami_introduction_tutorial_how_to_install.html

```php

require_once dirname(__FILE__) . '/src/Implementation/AMIListener.php';
require_once dirname(__FILE__) . '/src/Implementation/AMIClient.php';

// Configure as required.
$options = array(
	'host'            => '192.168.1.250',
	'username'        => 'root',
	'password'        => 'toor',
	'port'            => 5038,
	'connect_timeout' => 10,
	'read_timeout'    => 100,
	'scheme'          => 'tcp://'
);

$AMIClient   = new AMIClient($options);
$AMIListener = new AMIListener();

// Registering a closure ** NOT AVAILABLE FOR PHP VERSION 5.1.6 **
$AMIClient->registerEventListener(function($event){});

// Register a specific method of an object for event listening
$AMIClient->registerEventListener(array($AMIListener, 'handle'));

// Register an IEventListener:
$AMIClient->registerEventListener($AMIListener);
```

# Using Predicates
A second (optional) argument can be used when registering the event listener: a
closure that will be evaluated before calling the callback. The callback will
be called only if this predicate returns true:

```php
require_once dirname(__FILE__) . '/src/PHPAMI/Message/Event/DialEvent.php';

$AMIClient->registerEventListener(
    array($listener, 'dispatchEvent'),
    function ($event) {
        return $event instanceof DialEvent && $event->getSubEvent() == 'Begin';
    })
);
```

# Example

Please see ```/index.php``` for a very basic example.

## Have in mind it uses a higher PHP version
AsterTrace is a full application: https://github.com/marcelog/AsterTrace.

Also, you might want to look at this article: http://marcelog.github.com/articles/php_asterisk_listener_example_using_pami_and_ding.html

The [march edition](http://sdjournal.org/a-practical-introduction-to-functional-programming-with-php-sdj-issue-released/) of [Software Developer Journal](http://sdjournal.org/) features a complete article about writing telephony applications with [PAMI](https://github.com/MARCELOG/pami) and PAGI.

# Currently Supported Events

More events will be added with time. I can only add the ones I can test for and
use, so your contributions may make the difference! ;)

Unknown (not yet implemented) events will be reported as UnknownEvent, so you
can still catch them. If you catch one of these, please report it!

* AgentsComplete
* AgentConnect
* Agentlogin
* Agentlogoff
* AGIExec
* Bridge
* CEL
* ChannelUpdate
* ChannelReloadEvent
* ConfbridgeStart
* ConfbridgeEnd
* ConfbridgeJoin
* ConfbridgeLeave
* ConfbridgeMute
* ConfbridgeStart
* ConfbridgeTalking
* ConfbridgeUnmute
* CoreShowChannel
* CoreShowChannelComplete
* DAHDIChannel
* DAHDIShowChannel
* DAHDIShowChannelsComplete
* FullyBooted
* DongleSMSStatus
* DongleUSSDStatus
* DongleNewUSSD
* DongleNewUSSDBase64
* DongleNewCUSD
* DongleStatus
* DongleDeviceEntry
* DongleShowDevicesComplete
* DBGetResponse
* Dial
* DTMF
* Extension
* Hangup
* Hold
* JabberEvent
* Join
* Leave
* Link
* ListDialplan
* Masquerade
* MessageWaiting
* MusicOnHold
* NewAccountCode
* NewCallerid
* Newchannel
* Newexten
* Newstate
* OriginateResponse
* ParkedCall
* ParkedCallsComplete
* PeerEntry
* PeerlistComplete
* PeerStatus
* QueueMember
* QueueMemberAdded
* QueueMemberRemoved
* QueueMemberPause
* QueueMemberStatus
* QueueParams
* QueueStatusComplete
* QueueSummaryComplete
* Reload
* RegistrationsComplete
* Registry
* Rename
* RTCPReceived
* RTCPReceiver
* RTCPSent
* RTPReceiverStat
* RTPSenderStat
* ShowDialPlanComplete
* Success
* Status
* StatusComplete
* Transfer
* Unlink
* UnParkedCall
* UserEvent
* VarSet
* vgsm_me_state
* vgsm_net_state
* vgsm_sms_rx
* VoicemailUserEntry
* VoicemailUserEntryComplete

# Currently Supported Actions

* AbsoluteTimeout
* AGI
* Agents
* AgentLogoff
* Atxfer (asterisk 1.8?)
* Bridge
* ChangeMonitor
* Command
* ConfbridgeMute
* ConfbridgeUnmute
* CoreSettings
* CoreShowChannels
* CoreStatus
* DAHDIDialOffHookAction
* DAHDIHangup
* DAHDIRestart
* DAHDIShowChannels
* DAHDIDNDOn
* DAHDIDNDOff
* DBGet
* DBPut
* DBDel
* DBDelTree
* DongleSendSMS
* DongleSendUSSD
* DongleSendPDU
* DongleReload
* DongleStop
* DongleStart
* DongleRestart
* DongleReset
* DongleShowDevices
* ExtensionState
* CreateConfig
* GetConfig
* GetConfigJSON
* GetVar
* Hangup
* JabberSend
* LocalOptimizeAway
* Login
* Logoff
* ListCategories
* ListCommands
* MailboxCount
* MailboxStatus
* MeetmeList
* MeetmeMute
* MeetmeUnmute
* MixMonitor
* ModuleCheck
* ModuleLoad (split in ModuleLoad, ModuleUnload, and ModuleReload)
* Monitor
* Originate
* ParkedCalls
* PauseMonitor
* Ping
* PlayDTMF
* Queues
* QueueAdd
* Queue
* QueueLog
* QueuePause
* QueuePenalty
* QueueReload
* QueueRemove
* QueueReset
* QueueRule
* QueueSummary
* QueueStatus
* QueueUnpause
* Redirect
* Reload
* SendText
* SetVar
* ShowDialPlan
* Sipnotify
* Sippeers
* Sipqualifypeer
* Sipshowpeer
* Sipshowregistry
* Status
* StopMixMonitor
* StopMonitor
* UnpauseMonitor
* VGSM_SMS_TX
* VoicemailUsersList



## Debugging, logging

You can optionally set a [PSR-3](http://www.php-fig.org/psr/psr-3/) compatible logger:
```php
$AMIClient->setLogger($logger);
```

By default, the client will use the [NullLogger](http://www.php-fig.org/psr/psr-3/#1-4-helper-classes-and-interfaces).

## Contributing
To contribute:
 * Make sure you open a **concise** and **short** pull request.

LICENSE
=======
Copyright 2016 Marcelo Gornstein <marcelog@gmail.com> (For orignal repo)
Copyright 2017 Adrián Zúñiga <jaime.ziga@gmail.com> (For this repo only)

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
