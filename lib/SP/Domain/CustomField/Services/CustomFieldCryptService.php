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

namespace SP\Domain\CustomField\Services;

defined('APP_ROOT') || die();

use Exception;
use SP\Core\Application;
use SP\Core\Crypt\Crypt;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\CustomFieldData;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Crypt\Services\UpdateMasterPassRequest;
use SP\Domain\CustomField\Ports\CustomFieldCryptServiceInterface;
use SP\Domain\CustomField\Ports\CustomFieldServiceInterface;
use SP\Domain\Task\Services\TaskFactory;

/**
 * Class CustomFieldCryptService
 *
 * @package SP\Mgmt\CustomFields
 */
final class CustomFieldCryptService extends Service implements CustomFieldCryptServiceInterface
{
    private CustomFieldService       $customFieldService;
    private ?UpdateMasterPassRequest $request = null;

    public function __construct(Application $application, CustomFieldServiceInterface $customFieldService)
    {
        parent::__construct($application);

        $this->customFieldService = $customFieldService;
    }

    /**
     * Actualizar los datos encriptados con una nueva clave
     *
     * @param  UpdateMasterPassRequest  $request
     *
     * @throws ServiceException
     */
    public function updateMasterPassword(UpdateMasterPassRequest $request): void
    {
        try {
            $this->request = $request;

            $this->processUpdateMasterPassword(
                function (CustomFieldData $customFieldData) {
                    return Crypt::decrypt(
                        $customFieldData->getData(),
                        $customFieldData->getKey(),
                        $this->request->getCurrentMasterPass()
                    );
                }
            );
        } catch (Exception $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            throw new ServiceException(
                __u('Error while updating the custom fields data'),
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
    protected function processUpdateMasterPassword(callable $decryptor): void
    {
        $customFields = $this->customFieldService->getAllEncrypted();

        if (count($customFields) === 0) {
            $this->eventDispatcher->notifyEvent(
                'update.masterPassword.customFields',
                new Event(
                    $this, EventMessage::factory()
                    ->addDescription(__u('Update Master Password'))
                    ->addDescription(__u('There aren\'t any data from custom fields'))
                )
            );

            return;
        }

        $this->eventDispatcher->notifyEvent(
            'update.masterPassword.customFields.start',
            new Event(
                $this, EventMessage::factory()
                ->addDescription(__u('Update Master Password'))
                ->addDescription(__u('Updating encrypted data'))
            )
        );

        if ($this->request->useTask()) {
            $task = $this->request->getTask();

            TaskFactory::update(
                $task,
                TaskFactory::createMessage(
                    $task->getTaskId(),
                    __('Update Master Password')
                )->setMessage(__('Updating encrypted data'))
            );
        }

        $errors = [];
        $success = [];

        foreach ($customFields as $customField) {
            try {
                $customField->setData($decryptor($customField));

                $this->customFieldService->updateMasterPass(
                    $customField,
                    $this->request->getNewMasterPass()
                );

                $success[] = $customField->getId();
            } catch (Exception $e) {
                processException($e);

                $this->eventDispatcher->notifyEvent('exception', new Event($e));

                $errors[] = $customField->getId();
            }
        }

        $this->eventDispatcher->notifyEvent(
            'update.masterPassword.customFields.end',
            new Event(
                $this, EventMessage::factory()
                ->addDescription(__u('Update Master Password'))
                ->addDetail(__u('Records updated'), implode(',', $success))
                ->addDetail(__u('Records not updated'), implode(',', $errors))
            )
        );
    }
}
