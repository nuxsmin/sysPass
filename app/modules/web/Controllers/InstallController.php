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

namespace SP\Modules\Web\Controllers;

use Exception;
use Klein\Klein;
use SP\Core\Acl\Acl;
use SP\Core\Application;
use SP\Core\Exceptions\SPException;
use SP\Core\Language;
use SP\Core\PhpExtensionChecker;
use SP\Core\UI\ThemeInterface;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Mvc\View\Template;
use SP\Providers\Auth\Browser\Browser;
use SP\Services\Install\InstallData;
use SP\Services\Install\Installer;

/**
 * Class InstallController
 *
 * @package SP\Modules\Web\Controllers
 */
final class InstallController extends ControllerBase
{
    use JsonTrait;

    private Installer $installer;

    public function __construct(
        Application $application,
        ThemeInterface $theme,
        Klein $router,
        Acl $acl,
        Request $request,
        PhpExtensionChecker $extensionChecker,
        Template $template,
        Browser $browser,
        LayoutHelper $layoutHelper,
        Installer $installer
    ) {
        parent::__construct(
            $application,
            $theme,
            $router,
            $acl,
            $request,
            $extensionChecker,
            $template,
            $browser,
            $layoutHelper
        );

        $this->installer = $installer;
    }

    public function indexAction(): void
    {
        if ($this->configData->isInstalled()) {
            $this->router->response()
                ->redirect('index.php?r=login');

            return;
        }

        $this->layoutHelper->getPublicLayout('index', 'install');

        $errors = [];

        foreach ($this->extensionChecker->getMissing() as $module) {
            $errors[] = [
                'type'        => SPException::WARNING,
                'description' => sprintf('%s (%s)', __('Module unavailable'), $module),
                'hint'        => __('Without this module the application could not run correctly'),
            ];
        }

        $this->view->assign('errors', $errors);
        $this->view->assign(
            'langs',
            SelectItemAdapter::factory(Language::getAvailableLanguages())
                ->getItemsFromArraySelected([Language::$globalLang])
        );

        $this->view();
    }

    /**
     * @return bool
     * @throws \JsonException
     */
    public function installAction(): bool
    {
        $installData = new InstallData();
        $installData->setSiteLang($this->request->analyzeString('sitelang', 'en_US'));
        $installData->setAdminLogin($this->request->analyzeString('adminlogin', 'admin'));
        $installData->setAdminPass($this->request->analyzeEncrypted('adminpass'));
        $installData->setMasterPassword($this->request->analyzeEncrypted('masterpassword'));
        $installData->setDbAdminUser($this->request->analyzeString('dbuser', 'root'));
        $installData->setDbAdminPass($this->request->analyzeEncrypted('dbpass'));
        $installData->setDbName($this->request->analyzeString('dbname', 'syspass'));
        $installData->setDbHost($this->request->analyzeString('dbhost', 'localhost'));
        $installData->setHostingMode($this->request->analyzeBool('hostingmode', false));

        try {
            $this->installer->run($installData);

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('Installation finished')
            );
        } catch (Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }
}