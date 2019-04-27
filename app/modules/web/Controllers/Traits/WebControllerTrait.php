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

namespace SP\Modules\Web\Controllers\Traits;

use Klein\Klein;
use Psr\Container\ContainerInterface;
use SP\Config\Config;
use SP\Config\ConfigData;
use SP\Core\Acl\Acl;
use SP\Core\Context\ContextInterface;
use SP\Core\Context\SessionContext;
use SP\Core\Events\EventDispatcher;
use SP\Core\Exceptions\SPException;
use SP\Core\PhpExtensionChecker;
use SP\Core\UI\ThemeInterface;
use SP\Http\Request;
use SP\Mvc\Controller\ControllerTrait;

/**
 * Trait ControllerTratit
 *
 * @package SP\Modules\Web\Controllers
 */
trait WebControllerTrait
{
    use ControllerTrait;

    /**
     * @var string Nombre del controlador
     */
    protected $controllerName;
    /**
     * @var  EventDispatcher
     */
    protected $eventDispatcher;
    /**
     * @var  Config
     */
    protected $config;
    /**
     * @var  SessionContext
     */
    protected $session;
    /**
     * @var  ThemeInterface
     */
    protected $theme;
    /**
     * @var string
     */
    protected $actionName;
    /**
     * @var Klein
     */
    protected $router;
    /**
     * @var Acl
     */
    protected $acl;
    /**
     * @var ConfigData
     */
    protected $configData;
    /**
     * @var Request
     */
    protected $request;
    /**
     * @var PhpExtensionChecker
     */
    protected $extensionChecker;
    /**
     * @var bool
     */
    private $setup = false;

    /**
     * Returns the signed URI component after validating its signature.
     * This component is used for deep linking
     *
     * @return null|string
     */
    final protected function getSignedUriFromRequest()
    {
        if (!$this->setup) {
            return null;
        }

        $from = $this->request->analyzeString('from');

        if ($from) {
            try {
                $this->request->verifySignature($this->configData->getPasswordSalt(), 'from');
            } catch (SPException $e) {
                processException($e);

                $from = null;
            }
        }

        return $from;
    }

    /**
     * @param ContainerInterface $dic
     */
    private function setUp(ContainerInterface $dic)
    {
        $this->controllerName = $this->getControllerName();

        $this->config = $dic->get(Config::class);
        $this->configData = $this->config->getConfigData();
        $this->session = $dic->get(ContextInterface::class);
        $this->theme = $dic->get(ThemeInterface::class);
        $this->eventDispatcher = $dic->get(EventDispatcher::class);
        $this->router = $dic->get(Klein::class);
        $this->request = $dic->get(Request::class);
        $this->acl = $dic->get(Acl::class);
        $this->extensionChecker = $dic->get(PhpExtensionChecker::class);

        $this->setup = true;
    }
}