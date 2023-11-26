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

namespace SP\Domain\Config\Services;

use Exception;
use SP\Core\Application;
use SP\DataModel\ConfigData;
use SP\DataModel\Dto\ConfigRequest;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigRepositoryInterface;
use SP\Domain\Config\Ports\ConfigServiceInterface;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Config\Repositories\ConfigRepository;

/**
 * Class ConfigService
 *
 * @package SP\Domain\Config\Services
 */
final class ConfigService extends Service implements ConfigServiceInterface
{
    private ConfigRepository $configRepository;

    public function __construct(Application $application, ConfigRepositoryInterface $configRepository)
    {
        parent::__construct($application);

        $this->configRepository = $configRepository;
    }

    /**
     * @throws NoSuchItemException
     * @throws ServiceException
     */
    public function getByParam(string $param, $default = null)
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


        if ($result->getNumRows() === 0) {
            if ($default === null) {
                throw new NoSuchItemException(
                    sprintf(__('Parameter not found (%s)'), $param)
                );
            }

            return $default;
        }

        /** @var ConfigData $data */
        $data = $result->getData();

        return empty($data->value) ? $default : $data->value;
    }

    /**
     * @param ConfigRequest $configRequest
     *
     * @throws ServiceException
     */
    public function saveBatch(ConfigRequest $configRequest): void
    {
        try {
            $this->transactionAware(
                function () use ($configRequest) {
                    foreach ($configRequest->getData() as $param => $value) {
                        $this->save($param, $value);
                    }
                }
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
    public function save(string $param, $value): bool
    {
        if (!$this->configRepository->has($param)) {
            return $this->configRepository->create(new ConfigData($param, $value));
        }

        return $this->configRepository->update(new ConfigData($param, $value));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(ConfigData $configData): int
    {
        return $this->configRepository->create($configData);
    }

    /**
     * Obtener un array con la configuración almacenada en la BBDD.
     *
     * @return ConfigData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll(): array
    {
        return $this->configRepository->getAll()->getDataAsArray();
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws NoSuchItemException
     */
    public function deleteByParam(string $param): void
    {
        if ($this->configRepository->deleteByParam($param) === 0) {
            throw new NoSuchItemException(
                sprintf(__('Parameter not found (%s)'), $param)
            );
        }
    }
}
