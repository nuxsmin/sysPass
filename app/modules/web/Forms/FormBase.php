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

namespace SP\Modules\Web\Forms;

use Psr\Container\ContainerInterface;
use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Core\Context\ContextInterface;
use SP\Core\Context\SessionContext;
use SP\Http\Request;

/**
 * Class FormBase
 *
 * @package SP\Modules\Web\Forms
 */
abstract class FormBase
{
    /**
     * @var int
     */
    protected $itemId;
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var ConfigData
     */
    protected $configData;
    /**
     * @var SessionContext
     */
    protected $context;
    /**
     * @var Request
     */
    protected $request;

    /**
     * FormBase constructor.
     *
     * @param ContainerInterface $container
     * @param int                $itemId
     */
    public function __construct(ContainerInterface $container, $itemId = null)
    {
        $this->config = $container->get(Config::class);
        $this->configData = $this->config->getConfigData();
        $this->context = $container->get(ContextInterface::class);
        $this->request = $container->get(Request::class);

        $this->itemId = $itemId;

        if (method_exists($this, 'initialize')) {
            $this->initialize($container);
        }
    }

    /**
     * Analizar los datos de la petición HTTP
     *
     * @return void
     */
    abstract protected function analyzeRequestData();
}