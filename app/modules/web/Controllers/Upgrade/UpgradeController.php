<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Common\Providers\Version;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Domain\Http\Method;
use SP\Domain\Upgrade\Ports\UpgradeService;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;

use function SP\__u;
use function SP\processException;

/**
 * Class UpgradeController
 *
 * @package SP\Modules\Web\Controllers
 */
final class UpgradeController extends ControllerBase
{
    public function __construct(
        WebControllerHelper             $webControllerHelper,
        private readonly UpgradeService $upgradeService,
    ) {
        parent::__construct($webControllerHelper);
    }

    #[Action(ResponseType::JSON)]
    public function upgradeAction(): ActionResponse
    {
        try {
            $this->checkEnvironment();
            $this->upgradeService->upgrade($this->resolveCurrentVersion(), $this->configData);

            $this->configData->setUpgradeKey(null);
            $this->config->save($this->configData);

            return ActionResponse::ok(
                __u('Application successfully updated'),
                [__u('You will be redirected to log in within 5 seconds')]
            );
        } catch (ValidationException $e) {
            return ActionResponse::error($e->getMessage());
        } catch (Exception $e) {
            processException($e);

            return ActionResponse::error(
                $e->getMessage(),
                $e instanceof SPException ? $e->getHint() : null
            );
        }
    }

    /**
     * @throws ValidationException
     */
    private function checkEnvironment(): void
    {
        if ($this->request->getMethod() !== Method::POST) {
            throw new ValidationException(__u('Invalid request method'), SPException::ERROR);
        }

        if ($this->request->analyzeBool('chkConfirm', false) === false) {
            throw new ValidationException(__u('The updating need to be confirmed'), SPException::ERROR);
        }

        $upgradeKey = $this->configData->getUpgradeKey();
        $providedKey = $this->request->analyzeString('key');

        if (empty($upgradeKey) || empty($providedKey) || !hash_equals($upgradeKey, $providedKey)) {
            throw new ValidationException(__u('Wrong security code'), SPException::ERROR);
        }
    }

    private function resolveCurrentVersion(): string
    {
        $candidates = array_values(
            array_filter(
                [
                    $this->configData->getAppVersion(),
                    $this->configData->getDatabaseVersion(),
                    (string)$this->configData->getConfigVersion(),
                ],
                static fn (string $version): bool => $version !== ''
            )
        );

        if ($candidates === []) {
            return '';
        }

        $lowest = $candidates[0];

        foreach (array_slice($candidates, 1) as $candidate) {
            if (Version::checkVersion($candidate, $lowest)) {
                $lowest = $candidate;
            }
        }

        return $lowest;
    }
}
