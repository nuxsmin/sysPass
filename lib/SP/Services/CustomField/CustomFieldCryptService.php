<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
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

namespace SP\Services\CustomField;

defined('APP_ROOT') || die();

use SP\Core\Crypt\Crypt;
use SP\Core\Events\Event;
use SP\Core\Exceptions\SPException;
use SP\Core\OldCrypt;
use SP\Core\TaskFactory;
use SP\DataModel\CustomFieldData;
use SP\Services\Crypt\UpdateMasterPassRequest;
use SP\Services\Service;
use SP\Services\ServiceException;

/**
 * Class CustomFieldCryptService
 *
 * @package SP\Mgmt\CustomFields
 */
class CustomFieldCryptService extends Service
{
    /**
     * @var CustomFieldService
     */
    protected $customFieldService;
    /**
     * @var UpdateMasterPassRequest
     */
    protected $request;

    /**
     * Actualizar los datos encriptados con una nueva clave
     *
     * @param UpdateMasterPassRequest $request
     * @return array
     * @throws ServiceException
     */
    public function updateMasterPasswordOld(UpdateMasterPassRequest $request)
    {
        $this->request = $request;

        try {
            return $this->processUpdateMasterPassword(function (CustomFieldData $customFieldData) {
                return OldCrypt::getDecrypt($customFieldData->getData(), $customFieldData->getKey(), $this->request->getCurrentMasterPass());
            });
        } catch (ServiceException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ServiceException(__u('Errores al actualizar datos de campos personalizados'), SPException::ERROR, null, $e->getCode(), $e);
        }
    }

    /**
     * @param callable $decryptor
     * @return array
     * @throws ServiceException
     * @throws \SP\Core\Exceptions\InvalidArgumentException
     */
    protected function processUpdateMasterPassword(callable $decryptor)
    {
        $messages = [];
        $customFields = $this->customFieldService->getAll();

        if (count($customFields) === 0) {
            throw new ServiceException(__u('No hay datos de campos personalizados'), SPException::INFO);
        }

        $this->eventDispatcher->notifyEvent('update.masterPassword.customFields.start', new Event($this, [__u('Actualizar Clave Maestra')]));

        $taskId = $this->request->getTask()->getTaskId();

        TaskFactory::update($taskId, TaskFactory::createMessage($taskId, __('Actualizar Clave Maestra'))->setMessage(__u('Actualizando datos encriptados')));

        $errors = [];
        $success = [];

        foreach ($customFields as $customField) {
            try {
                $customField->setData($decryptor($customField));

                $this->customFieldService->updateMasterPass($customField, $this->request->getNewMasterPass());

                $success[] = $customField->getId();
            } catch (\Exception $e) {
                processException($e);

                $errors[] = $customField->getId();
            }
        }

        $messages[] = __u('Registros no actualizados');
        $messages[] = implode(',', $errors);
        $messages[] = __u('Registros actualizados');
        $messages[] = implode(',', $success);

        $this->eventDispatcher->notifyEvent('update.masterPassword.customFields.end', new Event($this, [__u('Actualizar Clave Maestra')]));

        return $messages;
    }

    /**
     * Actualizar los datos encriptados con una nueva clave
     *
     * @param UpdateMasterPassRequest $request
     * @return array
     * @throws ServiceException
     */
    public function updateMasterPassword(UpdateMasterPassRequest $request)
    {
        try {
            $this->request = $request;

            return $this->processUpdateMasterPassword(function (CustomFieldData $customFieldData) {
                return Crypt::decrypt(
                    $customFieldData->getData(),
                    Crypt::unlockSecuredKey($customFieldData->getKey(), $this->request->getCurrentMasterPass()),
                    $this->request->getCurrentMasterPass());
            });
        } catch (ServiceException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ServiceException(__u('Errores al actualizar datos de campos personalizados'), SPException::ERROR, null, $e->getCode(), $e);
        }
    }

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function initialize()
    {
        $this->customFieldService = $this->dic->get(CustomFieldService::class);

    }
}