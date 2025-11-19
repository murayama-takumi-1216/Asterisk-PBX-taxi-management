<?php
/**
 * UpdateConfig action message.
 *
 * PHP Version 5
 *
 * @category   PHPAMI
 * @package    Message
 * @subpackage Action
 * @author     Jaime Ziga <jaime.ziga@gmail.com>
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
 *  UpdateConfig action message.
 *
 * PHP Version 5
 *
 * @category   PHPAMI
 * @package    Message
 * @subpackage Action
 * @author     Denis Rybakov <shinomontaz@gmail.com>
 * @license    http://github.com/Adrian0350/PHP-AMI/ Apache License 2.0
 * @link       http://github.com/Adrian0350/PHP-AMI/
 */
class UpdateConfigAction extends ActionMessage
{
    protected static $counter = -1;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct('UpdateConfig');
        self::$counter = -1;
    }

    /**
     * Sets Src filename key.
     *
     * @param string $filename.
     *
     * @return void
     */
    public function setSrcFilename($filename)
    {
        $this->setKey('SrcFilename', $filename);
    }

    /**
     * Sets Dst Filename key.
     *
     * @param string $filename.
     *
     * @return void
     */
    public function setDstFilename($filename)
    {
        $this->setKey('DstFilename', $filename);
    }

     /**
     * Sets Reload key.
     *
     * @param string $input.
     *
     * @return void
     */
    public function setReload($reload)
    {
        $this->setKey('Reload', $reload ? 'yes' : 'no');
    }

    /**
     * Sets Action-XXXXXX key.
     *
     * @param string $input.
     *
     * @return void
     */

    public function setAction($input)
    {
        UpdateConfigAction::$counter++;
        $this->setKey('Action-'.$this->getPaddedCounter(), $input);
    }

    /**
     * Sets Cat-XXXXXX key.
     *
     * @param string $cat.
     *
     * @return void
     */
    public function setCat($input)
    {
        $this->setKey('Cat-'.$this->getPaddedCounter(), $input);
    }

    /**
     * Sets Var-XXXXXX key.
     *
     * @param string $input.
     *
     * @return void
     */
    public function setVar($input)
    {
        $this->setKey('Var-'.$this->getPaddedCounter(), $input);
    }

    /**
     * Sets Value-XXXXXX key.
     *
     * @param string $input.
     *
     * @return void
     */
    public function setValue($input)
    {
        $this->setKey('Value-'.$this->getPaddedCounter(), $input);
    }

     /**
     * Sets Match-XXXXXX key.
     *
     * @param string $input.
     *
     * @return void
     */
    public function setMatch($input)
    {
        $this->setKey('Match-'.$this->getPaddedCounter(), $input);
    }

     /**
     * Sets Line-XXXXXX key.
     *
     * @param string $input.
     *
     * @return void
     */
    public function setLine($input)
    {
        $this->setKey('Line-'.$this->getPaddedCounter(), $input);
    }

    /**
     * Returns the string representation for counter with leading zeroes in UpdateConfig action format.
     *
     * @return string
     */
    protected function getPaddedCounter()
    {
        return str_pad(UpdateConfigAction::$counter, 6, '0', STR_PAD_LEFT);
    }
}
