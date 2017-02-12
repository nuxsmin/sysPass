<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\DataModel;

/**
 * Class PluginData
 *
 * @package SP\DataModel
 */
class PluginData extends DataModelBase implements DataModelInterface
{
    /**
     * @var int
     */
    public $plugin_id;
    /**
     * @var string
     */
    public $plugin_name;
    /**
     * @var string
     */
    public $plugin_data;
    /**
     * @var int
     */
    public $plugin_enabled = 0;

    /**
     * @return int
     */
    public function getId()
    {
        return (int)$this->plugin_id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->plugin_name;
    }

    /**
     * @return int
     */
    public function getPluginId()
    {
        return (int)$this->plugin_id;
    }

    /**
     * @param int $plugin_id
     */
    public function setPluginId($plugin_id)
    {
        $this->plugin_id = (int)$plugin_id;
    }

    /**
     * @return string
     */
    public function getPluginName()
    {
        return $this->plugin_name;
    }

    /**
     * @param string $plugin_name
     */
    public function setPluginName($plugin_name)
    {
        $this->plugin_name = $plugin_name;
    }

    /**
     * @return string
     */
    public function getPluginData()
    {
        return $this->plugin_data;
    }

    /**
     * @param string $plugin_data
     */
    public function setPluginData($plugin_data)
    {
        $this->plugin_data = $plugin_data;
    }

    /**
     * @return int
     */
    public function getPluginEnabled()
    {
        return (int)$this->plugin_enabled;
    }

    /**
     * @param int $plugin_enabled
     */
    public function setPluginEnabled($plugin_enabled)
    {
        $this->plugin_enabled = (int)$plugin_enabled;
    }
}