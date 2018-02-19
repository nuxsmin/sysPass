<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Core\Events;


/**
 * Class Event
 *
 * @package SP\Core\Events
 */
class Event
{
    /**
     * @var object
     */
    private $source;
    /**
     * @var array
     */
    private $data;

    /**
     * Event constructor.
     *
     * @param object $source
     * @param array  $data
     */
    public function __construct($source, array $data = [])
    {
        $this->source = $source;
        $this->data = $data;
    }

    /**
     * @return object
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

}