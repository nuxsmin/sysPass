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

namespace SP\Plugin;

use Defuse\Crypto\Exception\CryptoException;
use Psr\Container\ContainerInterface;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\NoSuchPropertyException;
use SP\Core\Exceptions\QueryException;
use SP\Services\Plugin\PluginService;
use SP\Services\ServiceException;

/**
 * Class PluginBase
 *
 * @package SP\Plugin
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
     * @var PluginOperation
     */
    protected $pluginOperation;
    /**
     * @var PluginService
     */
    private $pluginService;

    /**
     * PluginBase constructor.
     *
     * @param ContainerInterface $dic
     * @param PluginOperation    $pluginOperation
     */
    public final function __construct(ContainerInterface $dic, PluginOperation $pluginOperation)
    {
        $this->pluginService = $dic->get(PluginService::class);
        $this->pluginOperation = $pluginOperation;
        $this->init($dic);
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
     * @param int   $id
     * @param mixed $data
     *
     * @throws CryptoException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws ServiceException
     */
    final public function saveData(int $id, $data)
    {
        if ($this->data === null) {
            $this->pluginOperation->create($id, $data);
        } else {
            $this->pluginOperation->update($id, $data);
        }

        $this->data = $data;
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

    /**
     * @return string
     */
    public function getBase()
    {
        return $this->base;
    }
}