<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Core\Messages;

/**
 * Class MessageBase
 *
 * @package SP\Core\Messages
 */
abstract class MessageBase implements MessageInterface
{
    /**
     * @var string
     */
    protected $title;
    /**
     * @var array
     */
    protected $footer = [];
    /**
     * @var array
     */
    protected $description = [];

    /**
     * @return static
     */
    public static function factory()
    {
        return new static();
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return MessageBase
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @param FormatterInterface $formatter
     * @param bool               $translate
     *
     * @return string
     */
    public abstract function getDescription(FormatterInterface $formatter, $translate = false): string;

    /**
     * @param array $description
     *
     * @return MessageBase
     */
    public function setDescription(array $description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @param string $description
     *
     * @return MessageBase
     */
    public function addDescription($description)
    {
        $this->description[] = $description;

        return $this;
    }

    /**
     * @return array
     */
    public function getFooter()
    {
        return $this->footer;
    }

    /**
     * @param array $footer
     *
     * @return MessageBase
     */
    public function setFooter(array $footer)
    {
        $this->footer = $footer;

        return $this;
    }
}