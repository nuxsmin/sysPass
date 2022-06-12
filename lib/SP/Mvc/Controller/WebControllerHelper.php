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
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;
use SP\Mvc\View\TemplateInterface;
use SP\Providers\Auth\Browser\BrowserAuthInterface;

/**
 * Class WebControllerHelper
 */
final class WebControllerHelper
{
    private ThemeInterface       $theme;
    private Klein                $router;
    private Acl                  $acl;
    private RequestInterface     $request;
    private PhpExtensionChecker  $extensionChecker;
    private TemplateInterface    $template;
    private BrowserAuthInterface $browser;
    private LayoutHelper         $layoutHelper;

    public function __construct(
        ThemeInterface $theme,
        Klein $router,
        Acl $acl,
        RequestInterface $request,
        PhpExtensionChecker $extensionChecker,
        TemplateInterface $template,
        BrowserAuthInterface $browser,
        LayoutHelper $layoutHelper
    ) {
        $this->theme = $theme;
        $this->router = $router;
        $this->acl = $acl;
        $this->request = $request;
        $this->extensionChecker = $extensionChecker;
        $this->template = $template;
        $this->browser = $browser;
        $this->layoutHelper = $layoutHelper;
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

    public function getTemplate(): TemplateInterface
    {
        return $this->template;
    }

    public function getBrowser(): BrowserAuthInterface
    {
        return $this->browser;
    }

    public function getLayoutHelper(): LayoutHelper
    {
        return $this->layoutHelper;
    }
}