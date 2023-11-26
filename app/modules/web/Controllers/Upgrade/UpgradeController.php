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

namespace SP\Modules\Web\Controllers\Upgrade;

use Exception;
use JsonException;
use SP\Core\Application;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Domain\Persistence\Ports\UpgradeDatabaseServiceInterface;
use SP\Domain\Upgrade\Services\UpgradeAppService;
use SP\Domain\Upgrade\Services\UpgradeDatabaseService;
use SP\Domain\Upgrade\Services\UpgradeException;
use SP\Http\JsonResponse;
use SP\Infrastructure\File\FileException;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class UpgradeController
 *
 * @package SP\Modules\Web\Controllers
 */
final class UpgradeController extends ControllerBase
{
    use JsonTrait;

    private UpgradeDatabaseServiceInterface $upgradeDatabaseService;
    private UpgradeAppService               $upgradeAppService;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        UpgradeDatabaseServiceInterface $upgradeDatabaseService,
        UpgradeAppService $upgradeAppService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->upgradeDatabaseService = $upgradeDatabaseService;
        $this->upgradeAppService = $upgradeAppService;
    }

    /**
     * @return bool
     * @throws JsonException
     */
    public function upgradeAction(): bool
    {
        try {
            $this->checkEnvironment();
            $this->handleDatabase();
            $this->handleApplication();

            $this->configData->setUpgradeKey(null);
            $this->config->saveConfig($this->configData, false);

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('Application successfully updated'),
                [__u('You will be redirected to log in within 5 seconds')]
            );
        } catch (ValidationException $e) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, $e->getMessage());
        } catch (Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @return void
     * @throws ValidationException
     */
    private function checkEnvironment(): void
    {
        if ($this->request->analyzeBool('chkConfirm', false) === false) {
            throw new ValidationException(SPException::ERROR, __u('The updating need to be confirmed'));
        }

        if ($this->request->analyzeString('key') !== $this->configData->getUpgradeKey()) {
            throw new ValidationException(SPException::ERROR, __u('Wrong security code'));
        }
    }

    /**
     * @return void
     */
    private function handleDatabase(): void
    {
        $dbVersion = $this->configData->getDatabaseVersion();
        $dbVersion = empty($dbVersion) ? '0.0' : $dbVersion;

        if (UpgradeDatabaseService::needsUpgrade($dbVersion)) {
            $this->upgradeDatabaseService->upgrade($dbVersion, $this->configData);
        }
    }

    /**
     * @return void
     * @throws UpgradeException
     * @throws FileException
     */
    private function handleApplication(): void
    {
        $appVersion = $this->configData->getAppVersion();
        $appVersion = empty($appVersion) ? '0.0' : $appVersion;

        if (UpgradeAppService::needsUpgrade($appVersion)) {
            $this->upgradeAppService->upgrade($appVersion, $this->configData);
        }
    }
}
