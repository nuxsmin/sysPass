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

namespace SP\Modules\Web\Controllers\Helpers;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Psr\Container\ContainerInterface;
use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Core\Context\ContextInterface;
use SP\Core\Context\SessionContext;
use SP\Core\Events\EventDispatcher;
use SP\Http\Request;
use SP\Mvc\View\Template;

/**
 * Class HelperBase
 *
 * @package SP\Modules\Web\Controllers\Helpers
 */
abstract class HelperBase
{
    /**
     * @var Template
     */
    protected $view;
    /**
     * @var ConfigData
     */
    protected $configData;
    /**
     * @var SessionContext
     */
    protected $context;
    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var ContainerInterface
     */
    protected $dic;
    /**
     * @var Request
     */
    protected $request;

    /**
     * Constructor
     *
     * @param Template         $template
     * @param Config           $config
     * @param ContextInterface $context
     * @param EventDispatcher  $eventDispatcher
     * @param Container        $container
     *
     * @throws DependencyException
     * @throws NotFoundException
     */
    final public function __construct(Template $template, Config $config, ContextInterface $context, EventDispatcher $eventDispatcher, Container $container)
    {
        $this->dic = $container;
        $this->request = $this->dic->get(Request::class);
        $this->view = $template;
        $this->config = $config;
        $this->configData = $config->getConfigData();
        $this->context = $context;
        $this->eventDispatcher = $eventDispatcher;

        if (method_exists($this, 'initialize')) {
            $this->initialize();
        }
    }
}