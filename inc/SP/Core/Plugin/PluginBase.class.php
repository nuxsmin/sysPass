<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2016, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Core\Plugin;

use SP\Core\DiFactory;

/**
 * Class PluginBase
 *
 * @package SP\Core\Plugin
 */
abstract class PluginBase implements PluginInterface
{
    /**
     * @var string Directorio base
     */
    protected $base;
    /**
     * @var string Tipo de plugin
     */
    protected $type;
    /**
     * @var string
     */
    protected $themeDir;
    /**
     * @var mixed
     */
    protected $data;

    /**
     * PluginBase constructor.
     */
    public final function __construct()
    {
        DiFactory::getEventDispatcher()->attach($this);

        $this->init();
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getBase()
    {
        return $this->base;
    }

    /**
     * @return string
     */
    public function getThemeDir()
    {
        return $this->themeDir;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }
}