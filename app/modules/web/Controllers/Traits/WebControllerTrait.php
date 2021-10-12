<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Modules\Web\Controllers\Traits;

use Klein\Klein;
use Psr\Container\ContainerInterface;
use SP\Config\Config;
use SP\Config\ConfigDataInterface;
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
 */
trait WebControllerTrait
{
    use ControllerTrait;

    protected ?string $controllerName = null;
    protected ?EventDispatcher $eventDispatcher = null;
    protected ?Config $config = null;
    protected ?SessionContext $session = null;
    protected ?ThemeInterface $theme = null;
    protected ?string $actionName = null;
    protected ?Klein $router = null;
    protected ?Acl $acl = null;
    protected ConfigDataInterface $configData;
    protected ?Request $request = null;
    protected ?PhpExtensionChecker $extensionChecker = null;
    private bool $setup = false;

    /**
     * Returns the signed URI component after validating its signature.
     * This component is used for deep linking
     */
    final protected function getSignedUriFromRequest(): ?string
    {
        if (!$this->setup) {
            return null;
        }

        $from = $this->request->analyzeString('from');

        if ($from) {
            try {
                $this->request->verifySignature(
                    $this->configData->getPasswordSalt(),
                    'from'
                );
            } catch (SPException $e) {
                processException($e);

                $from = null;
            }
        }

        return $from;
    }

    private function setUp(ContainerInterface $dic): void
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