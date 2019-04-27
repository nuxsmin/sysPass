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

use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use SP\Core\Acl\Actions;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Services\Upgrade\UpgradeAppService;
use SP\Services\Upgrade\UpgradeDatabaseService;
use SP\Storage\File\FileException;

/**
 * Class UpgradeController
 *
 * @package SP\Modules\Web\Controllers
 */
final class UpgradeController extends ControllerBase
{
    use JsonTrait;

    /**
     * indexAction
     *
     * @throws DependencyException
     * @throws NotFoundException
     * @throws FileException
     */
    public function indexAction()
    {
        $layoutHelper = $this->dic->get(LayoutHelper::class);
        $layoutHelper->getPublicLayout('index', 'upgrade');

        $this->dic->get(Actions::class)->reset();

        $this->view();
    }

    /**
     * upgradeAction
     */
    public function upgradeAction()
    {
        if ($this->request->analyzeBool('chkConfirm', false) === false) {
            return $this->returnJsonResponse(
                JsonResponse::JSON_ERROR,
                __u('The updating need to be confirmed')
            );
        }

        if ($this->request->analyzeString('key') !== $this->configData->getUpgradeKey()) {
            return $this->returnJsonResponse(
                JsonResponse::JSON_ERROR,
                __u('Wrong security code')
            );
        }

        try {
            $dbVersion = $this->configData->getDatabaseVersion();
            $dbVersion = empty($dbVersion) ? '0.0' : $dbVersion;

            if (UpgradeDatabaseService::needsUpgrade($dbVersion)) {
                $this->dic->get(UpgradeDatabaseService::class)
                    ->upgrade($dbVersion, $this->configData);
            }

            $appVersion = $this->configData->getAppVersion();
            $appVersion = empty($appVersion) ? '0.0' : $appVersion;

            if (UpgradeAppService::needsUpgrade($appVersion)) {
                $this->dic->get(UpgradeAppService::class)
                    ->upgrade($appVersion, $this->configData);
            }

            $this->configData->setUpgradeKey(null);
            $this->config->saveConfig($this->configData, false);

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('Application successfully updated'),
                [__u('You will be redirected to log in within 5 seconds')]
            );
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