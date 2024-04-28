<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Bootstrap\UriContextInterface;
use SP\Domain\Core\UI\ThemeInterface;
use SP\Domain\Http\Ports\RequestService;

/**
 * Class SimpleControllerHelper
 */
final readonly class SimpleControllerHelper
{

    public function __construct(
        private ThemeInterface      $theme,
        private Klein               $router,
        private AclInterface        $acl,
        private RequestService $request,
        private PhpExtensionChecker $extensionChecker,
        private UriContextInterface $uriContext
    ) {
    }

    public function getTheme(): ThemeInterface
    {
        return $this->theme;
    }

    public function getRouter(): Klein
    {
        return $this->router;
    }

    public function getAcl(): AclInterface
    {
        return $this->acl;
    }

    public function getRequest(): RequestService
    {
        return $this->request;
    }

    public function getExtensionChecker(): PhpExtensionChecker
    {
        return $this->extensionChecker;
    }

    public function getUriContext(): UriContextInterface
    {
        return $this->uriContext;
    }
}
