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

namespace SP\Modules\Web\Controllers\Install;


use Exception;
use SP\Core\Application;
use SP\Domain\Install\In\InstallData;
use SP\Domain\Install\InstallerServiceInterface;
use SP\Domain\Install\Services\InstallerService;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class InstallController
 */
final class InstallController extends ControllerBase
{
    use JsonTrait;

    private InstallerServiceInterface $installer;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        InstallerServiceInterface $installer
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->installer = $installer;
    }

    /**
     * @return bool
     * @throws \JsonException
     */
    public function installAction(): bool
    {
        $installData = $this->getInstallDataFromRequest();

        try {
            $this->installer->run(InstallerService::getDatabaseSetup($installData, $this->configData), $installData);

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Installation finished'));
        } catch (Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @return \SP\Domain\Install\In\InstallData
     */
    private function getInstallDataFromRequest(): InstallData
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

        return $installData;
    }
}