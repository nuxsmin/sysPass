<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Core\Acl\Acl;
use SP\Core\PhpExtensionChecker;
use SP\Core\UI\ThemeInterface;
use SP\Http\RequestInterface;

/**
 * Class SimpleControllerHelper
 */
final class SimpleControllerHelper
{
    private ThemeInterface      $theme;
    private Klein               $router;
    private Acl                 $acl;
    private RequestInterface    $request;
    private PhpExtensionChecker $extensionChecker;

    public function __construct(
        ThemeInterface $theme,
        Klein $router,
        Acl $acl,
        RequestInterface $request,
        PhpExtensionChecker $extensionChecker
    ) {
        $this->theme = $theme;
        $this->router = $router;
        $this->acl = $acl;
        $this->request = $request;
        $this->extensionChecker = $extensionChecker;
    }

    public function getTheme(): ThemeInterface
    {
        return $this->theme;
    }

    public function getRouter(): Klein
    {
        return $this->router;
    }

    public function getAcl(): Acl
    {
        return $this->acl;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    public function getExtensionChecker(): PhpExtensionChecker
    {
        return $this->extensionChecker;
    }
}