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

namespace SP\Services\Config;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\ConfigData;
use SP\DataModel\Dto\ConfigRequest;
use SP\Repositories\Config\ConfigRepository;
use SP\Repositories\NoSuchItemException;
use SP\Services\Service;
use SP\Services\ServiceException;

/**
 * Class ConfigService
 *
 * @package SP\Services\Config
 */
final class ConfigService extends Service
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @param string $param
     * @param mixed  $default
     *
     * @return mixed
     * @throws NoSuchItemException
     * @throws ServiceException
     */
    public function getByParam($param, $default = null)
    {
        try {
            $result = $this->configRepository->getByParam($param);
        } catch (Exception $e) {
            throw new ServiceException($e->getMessage(), ServiceException::ERROR, null, $e->getCode(), $e);
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
     * @param ConfigData $configData
     *
     * @return int
     * @throws ConstraintException
     * @throws QueryException
     */
    public function create(ConfigData $configData)
    {
        return $this->configRepository->create($configData);
    }

    /**
     * @param ConfigRequest $configRequest
     *
     * @throws ServiceException
     */
    public function saveBatch(ConfigRequest $configRequest)
    {
        try {
            $this->transactionAware(function () use ($configRequest) {
                foreach ($configRequest->getData() as $param => $value) {
                    $this->save($param, $value);
                }
            });
        } catch (Exception $e) {
            processException($e);

            throw new ServiceException($e->getMessage(), ServiceException::ERROR, null, $e->getCode(), $e);
        }
    }

    /**
     * @param string $param
     * @param string $value
     *
     * @return bool
     * @throws ConstraintException
     * @throws QueryException
     */
    public function save($param, $value)
    {
        if (!$this->configRepository->has($param)) {
            return $this->configRepository->create(new ConfigData($param, $value));
        }

        return $this->configRepository->update(new ConfigData($param, $value));
    }

    /**
     * Obtener un array con la configuración almacenada en la BBDD.
     *
     * @return ConfigData[]
     * @throws ConstraintException
     * @throws QueryException
     */
    public function getAll()
    {
        return $this->configRepository->getAll()->getDataAsArray();
    }

    /**
     * @param $param
     *
     * @return void
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function deleteByParam($param)
    {
        if ($this->configRepository->deleteByParam($param) === 0) {
            throw new NoSuchItemException(sprintf(__('Parameter not found (%s)'), $param));
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->configRepository = $this->dic->get(ConfigRepository::class);
    }
}