<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
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

namespace SP\Services\Config;

use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\Core\Traits\InjectableTrait;
use SP\DataModel\ConfigData;
use SP\DataModel\Dto\ConfigRequest;
use SP\Repositories\Config\ConfigRepository;
use SP\Services\Service;
use SP\Services\ServiceException;

/**
 * Class ConfigService
 *
 * @package SP\Services\Config
 */
class ConfigService extends Service
{
    use InjectableTrait;

    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->configRepository = $this->dic->get(ConfigRepository::class);
    }

    /**
     * @param string $param
     * @param null   $default
     * @return mixed
     * @throws ParameterNotFoundException
     */
    public function getByParam($param, $default = null)
    {
        $query = $this->configRepository->getByParam($param);

        if (empty($query)) {
            if ($default !== null) {
                return $default;
            }

            throw new ParameterNotFoundException(
                sprintf(__('Parámetro no encontrado (%s)'),
                    SPException::ERROR,
                    $param)
            );
        }

        return empty($query->value) ? $default : $query->value;
    }

    /**
     * @param ConfigData $configData
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function create(ConfigData $configData)
    {
        return $this->configRepository->create($configData);
    }

    /**
     * @param ConfigRequest $configRequest
     * @throws ServiceException
     */
    public function saveBatch(ConfigRequest $configRequest)
    {
        foreach ($configRequest->getData() as $param => $value) {
            try {
                $this->save($param, $value);
            } catch (ConstraintException $e) {
                processException($e);

                throw new ServiceException($e->getMessage(), $e->getType(), $e->getHint(), $e->getCode());
            } catch (QueryException $e) {
                processException($e);

                throw new ServiceException($e->getMessage(), $e->getType(), $e->getHint(), $e->getCode());
            }
        }
    }

    /**
     * @param string $param
     * @param string $value
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
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
     */
    public function getAll()
    {
        return $this->configRepository->getAll();
    }

    /**
     * @param $param
     * @return bool
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \SP\Core\Exceptions\QueryException
     */
    public function deleteByParam($param)
    {
        return $this->configRepository->deleteByParam($param);
    }
}