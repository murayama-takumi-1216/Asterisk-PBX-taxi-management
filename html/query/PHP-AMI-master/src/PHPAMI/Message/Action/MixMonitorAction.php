<?php
/**
 * MixMonitor action message.
 *
 * PHP Version 5
 *
 * @category   PHPAMI
 * @package    Message
 * @subpackage Action
 * @author     Matt Styles <mstyleshk@gmail.com>
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
require_once dirname(__FILE__) . '/ActionMessage.php';

/**
 * MixMonitor action message.
 *
 * PHP Version 5
 *
 * @category   PHPAMI
 * @package    Message
 * @subpackage Action
 * @author     Matt Styles <mstyleshk@gmail.com>
 * @license    http://github.com/Adrian0350/PHP-AMI/ Apache License 2.0
 * @link       http://github.com/Adrian0350/PHP-AMI/
 */
class MixMonitorAction extends ActionMessage
{
    /**
     * Sets File key.
     *
     * @param string $file File to use.
     *
     * @return void
     */
    public function setFile($file)
    {
        $this->setKey('File', $file);
    }

    /**
     * Sets Options key.
     *
     * @param string[] $options List of options to use.
     *
     * @return void
     */
    public function setOptions(array $options)
    {
        $this->setKey('Options', implode('', $options));
    }

    /**
     * Constructor.
     *
     * @param string $channel Channel on which to act.
     *
     * @return void
     */
    public function __construct($channel)
    {
        parent::__construct('MixMonitor');
        $this->setKey('Channel', $channel);
    }
}
