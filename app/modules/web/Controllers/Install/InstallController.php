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
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Install\Adapters\InstallDataFactory;
use SP\Domain\Install\Ports\InstallerServiceInterface;
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
     * @throws SPException
     */
    public function installAction(): bool
    {
        $installData = InstallDataFactory::buildFromRequest($this->request);

        try {
            $this->installer->run($installData);

            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Installation finished'));
        } catch (Exception $e) {
            processException($e);

            return $this->returnJsonResponseException($e);
        }
    }
}
