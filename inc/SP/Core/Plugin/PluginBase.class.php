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

namespace SP\Core\Plugin;

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
     * @var int
     */
    protected $enabled;

    /**
     * PluginBase constructor.
     */
    public final function __construct()
    {
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

    /**
     * @return int
     */
    public function getEnabled()
    {
        return (int)$this->enabled;
    }

    /**
     * @param int $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (int)$enabled;
    }

    /**
     * Establecer las locales del plugin
     */
    protected function setLocales()
    {
        $locales = $this->getBase() . DIRECTORY_SEPARATOR . 'locales';
        $name = strtolower($this->getName());

        bindtextdomain($name, $locales);
        bind_textdomain_codeset($name, 'UTF-8');
    }
}