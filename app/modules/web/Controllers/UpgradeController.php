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

use SP\Core\Acl\Actions;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Helpers\LayoutHelper;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Services\Upgrade\UpgradeAppService;
use SP\Services\Upgrade\UpgradeDatabaseService;
use SP\Services\Upgrade\UpgradeUtil;

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
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \SP\Storage\File\FileException
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
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('The updating need to be confirmed'));
        }

        if ($this->request->analyzeString('key') !== $this->configData->getUpgradeKey()) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Wrong security code'));
        }


        try {
            $dbVersion = $this->configData->getDatabaseVersion();
            $dbVersion = empty($dbVersion) ? '0.0' : $dbVersion;

            if (UpgradeDatabaseService::needsUpgrade($dbVersion)) {
                $this->dic->get(UpgradeDatabaseService::class)
                    ->upgrade($dbVersion, $this->configData);
            }

            if (UpgradeAppService::needsUpgrade(UpgradeUtil::fixVersionNumber($this->configData->getConfigVersion()))) {
                $this->dic->get(UpgradeAppService::class)
                    ->upgrade(UpgradeUtil::fixVersionNumber($this->configData->getConfigVersion()), $this->configData);
            }

            $this->configData->setUpgradeKey(null);
            $this->config->saveConfig($this->configData, false);

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Application successfully updated'), [__u('You will be redirected to log in within 5 seconds')]);
        } catch (\Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }
}