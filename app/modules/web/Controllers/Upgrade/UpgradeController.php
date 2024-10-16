<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Core\Application;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Domain\Http\Dtos\JsonMessage;
use SP\Domain\Upgrade\Ports\UpgradeService;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\WebControllerHelper;

use function SP\__u;
use function SP\processException;

/**
 * Class UpgradeController
 *
 * @package SP\Modules\Web\Controllers
 */
final class UpgradeController extends ControllerBase
{
    use JsonTrait;

    public function __construct(
        Application                     $application,
        WebControllerHelper             $webControllerHelper,
        private readonly UpgradeService $upgradeService,
    ) {
        parent::__construct($application, $webControllerHelper);
    }

    /**
     * @return bool
     * @throws SPException
     */
    public function upgradeAction(): bool
    {
        try {
            $this->checkEnvironment();
            $this->upgradeService->upgrade($this->configData->getAppVersion(), $this->configData);

            $this->configData->setUpgradeKey(null);
            $this->config->save($this->configData);

            return $this->returnJsonResponse(
                JsonMessage::JSON_SUCCESS,
                __u('Application successfully updated'),
                [__u('You will be redirected to log in within 5 seconds')]
            );
        } catch (ValidationException $e) {
            return $this->returnJsonResponse(JsonMessage::JSON_ERROR, $e->getMessage());
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
}
