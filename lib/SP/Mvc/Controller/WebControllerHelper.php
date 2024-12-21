<?php

declare(strict_types=1);
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Mvc\Controller;

use Klein\Klein;
use SP\Core\PhpExtensionChecker;
use SP\Domain\Auth\Providers\Browser\BrowserAuthService;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Bootstrap\RouteContextData;
use SP\Domain\Core\Bootstrap\UriContextInterface;
use SP\Domain\Core\UI\ThemeInterface;
use SP\Domain\Http\Ports\RequestService;
use SP\Modules\Web\Controllers\Helpers\JsonResponseHandler;
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;
use SP\Mvc\View\TemplateInterface;

/**
 * Class WebControllerHelper
 */
final readonly class WebControllerHelper
{
    public function __construct(
        private SimpleControllerHelper $simpleControllerHelper,
        private TemplateInterface      $template,
        private BrowserAuthService     $browser,
        private LayoutHelper        $layoutHelper,
        private JsonResponseHandler $jsonResponseHandler
    ) {
    }

    public function getTheme(): ThemeInterface
    {
        return $this->simpleControllerHelper->getTheme();
    }

    public function getRouter(): Klein
    {
        return $this->simpleControllerHelper->getRouter();
    }

    public function getAcl(): AclInterface
    {
        return $this->simpleControllerHelper->getAcl();
    }

    public function getRequest(): RequestService
    {
        return $this->simpleControllerHelper->getRequest();
    }

    public function getExtensionChecker(): PhpExtensionChecker
    {
        return $this->simpleControllerHelper->getExtensionChecker();
    }

    public function getUriContext(): UriContextInterface
    {
        return $this->simpleControllerHelper->getUriContext();
    }

    public function getTemplate(): TemplateInterface
    {
        return $this->template;
    }

    public function getBrowser(): BrowserAuthService
    {
        return $this->browser;
    }

    public function getLayoutHelper(): LayoutHelper
    {
        return $this->layoutHelper;
    }

    public function getRouteContextData(): RouteContextData
    {
        return $this->simpleControllerHelper->getRouteContextData();
    }

    public function getJsonResponseHandler(): JsonResponseHandler
    {
        return $this->jsonResponseHandler;
    }
}
