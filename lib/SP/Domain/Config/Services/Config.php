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

namespace SP\Domain\Config\Services;

use Exception;
use SP\Core\Application;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Dtos\ConfigRequest;
use SP\Domain\Config\Models\Config as ConfigModel;
use SP\Domain\Config\Ports\ConfigRepository;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;

use function SP\__;
use function SP\processException;

/**
 * Class Config
 */
final class Config extends Service implements ConfigService
{

    public function __construct(Application $application, private readonly ConfigRepository $configRepository)
    {
        parent::__construct($application);
    }

    /**
     * @param string $param
     * @param null $default
     * @return string|null
     * @throws NoSuchItemException
     * @throws SPException
     * @throws ServiceException
     */
    public function getByParam(string $param, $default = null): ?string
    {
        try {
            $result = $this->configRepository->getByParam($param);
        } catch (Exception $e) {
            throw new ServiceException(
                $e->getMessage(),
                SPException::ERROR,
                null,
                $e->getCode(),
                $e
            );
        }


        if ($result->getNumRows() === 0 && $default === null) {
            throw new NoSuchItemException(
                sprintf(__('Parameter not found (%s)'), $param)
            );
        }

        return $result->getData(ConfigModel::class)->getValue() ?? $default;
    }

    /**
     * @param ConfigRequest $configRequest
     *
     * @throws ServiceException
     */
    public function saveBatch(ConfigRequest $configRequest): void
    {
        try {
            $this->configRepository->transactionAware(
                function () use ($configRequest) {
                    foreach ($configRequest->getData() as $param => $value) {
                        $this->save($param, $value);
                    }
                },
                $this
            );
        } catch (Exception $e) {
            processException($e);

            throw new ServiceException(
                $e->getMessage(),
                SPException::ERROR,
                null,
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function save(string $param, string $value): bool
    {
        $config = new ConfigModel(['parameter' => $param, 'value' => $value]);

        if (!$this->configRepository->has($param)) {
            return $this->configRepository->create($config)->getLastId() > 0;
        }

        return $this->configRepository->update($config)->getAffectedNumRows() === 1;
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(ConfigModel $config): int
    {
        return $this->configRepository->create($config)->getLastId();
    }
}
