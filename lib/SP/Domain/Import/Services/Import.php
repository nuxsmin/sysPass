<?php
declare(strict_types=1);

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

namespace SP\Domain\Import\Services;

use SP\Core\Application;
use SP\Domain\Common\Ports\Repository;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Import\Dtos\ImportParamsDto;
use SP\Domain\Import\Ports\ImportService;
use SP\Domain\Import\Ports\ImportStrategyService;
use SP\Domain\Import\Ports\ItemsImportService;

/**
 * Import assets from CSV or XML files
 */
final class Import extends Service implements ImportService
{
    public function __construct(
        Application                            $application,
        private readonly ImportStrategyService $importStrategy,
        private readonly Repository            $repository
    ) {
        parent::__construct($application);
    }


    /**
     * Iniciar la importación de cuentas.
     *
     * @param ImportParamsDto $importParams
     * @return ItemsImportService
     * @throws ServiceException
     */
    public function doImport(ImportParamsDto $importParams): ItemsImportService
    {
        set_time_limit(0);

        return $this->repository->transactionAware(
            fn(): ItemsImportService => $this->importStrategy->buildImport($importParams)->doImport($importParams),
            $this
        );
    }
}
