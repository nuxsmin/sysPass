<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Exceptions\SPException;
use SP\Core\Language;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\View\Components\SelectItemAdapter;
use SP\Services\Install\InstallData;
use SP\Services\Install\Installer;
use SP\Util\Checks;

/**
 * Class InstallController
 *
 * @package SP\Modules\Web\Controllers
 */
class InstallController extends ControllerBase
{
    use JsonTrait;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function indexAction()
    {
        $layoutHelper = $this->dic->get(LayoutHelper::class);
        $layoutHelper->getPublicLayout('index', 'install');

        $errors = [];

        if (!Checks::checkPhpVersion()) {
            $errors[] = [
                'type' => SPException::CRITICAL,
                'description' => __('Versión de PHP requerida >= ') . ' 5.6.0 <= 7.0',
                'hint' => __('Actualice la versión de PHP para que la aplicación funcione correctamente')
            ];
        }

        $modules = Checks::checkModules();

        if (count($modules) > 0) {
            foreach ($modules as $module) {
                $error[] = [
                    'type' => SPException::WARNING,
                    'description' => sprintf('%s (%s)', __('Módulo no disponible'), $module),
                    'hint' => __('Sin este módulo la aplicación puede no funcionar correctamente.')
                ];
            }
        }

        if (@file_exists(__FILE__ . "\0Nullbyte")) {
            $errors[] = [
                'type' => SPException::WARNING,
                'description' => __('La version de PHP es vulnerable al ataque NULL Byte (CVE-2006-7243)'),
                'hint' => __('Actualice la versión de PHP para usar sysPass de forma segura')];
        }

        if (!Checks::secureRNGIsAvailable()) {
            $errors[] = [
                'type' => SPException::WARNING,
                'description' => __('No se encuentra el generador de números aleatorios.'),
                'hint' => __('Sin esta función un atacante puede utilizar su cuenta al resetear la clave')];
        }

        $this->view->assign('errors', $errors);
        $this->view->assign('langs', SelectItemAdapter::factory(Language::getAvailableLanguages())->getItemsFromArraySelected([Language::$globalLang]));

        $this->view();
    }

    /**
     * Performs sysPass installation
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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

            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Instalación finalizada'));
        } catch (\Exception $e) {
            processException($e);

            $this->returnJsonResponseException($e);
        }
    }
}