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

namespace SP\Modules\Web\Controllers;

use Exception;
use SP\Core\Exceptions\SPException;
use SP\Core\Language;
use SP\Core\PhpExtensionChecker;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\View\Components\SelectItemAdapter;
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

    /**
     * indexAction
     */
    public function indexAction()
    {
        $layoutHelper = $this->dic->get(LayoutHelper::class);
        $layoutHelper->getPublicLayout('index', 'install');

        $errors = [];

        foreach ($this->dic->get(PhpExtensionChecker::class)->getMissing() as $module) {
            $error[] = [
                'type' => SPException::WARNING,
                'description' => sprintf('%s (%s)', __('Module unavailable'), $module),
                'hint' => __('Without this module the application could not run correctly')
            ];
        }

        $this->view->assign('errors', $errors);
        $this->view->assign('langs', SelectItemAdapter::factory(Language::getAvailableLanguages())->getItemsFromArraySelected([Language::$globalLang]));

        $this->view();
    }

    /**
     * Performs sysPass installation
     */
    public function installAction()
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
            $this->dic->get(Installer::class)->run($installData);

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Installation finished'));
        } catch (Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @return void
     */
    protected function initialize()
    {
        // TODO: Implement initialize() method.
    }
}