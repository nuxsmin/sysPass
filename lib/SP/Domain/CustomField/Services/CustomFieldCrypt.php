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

namespace SP\Domain\CustomField\Services;

use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Crypt\Dtos\UpdateMasterPassRequest;
use SP\Domain\CustomField\Models\CustomFieldData as CustomFieldDataModel;
use SP\Domain\CustomField\Ports\CustomFieldCryptService;
use SP\Domain\CustomField\Ports\CustomFieldDataService;
use SP\Domain\Task\Services\TaskFactory;

use function SP\__u;
use function SP\processException;

/**
 * Class CustomFieldCrypt
 */
final class CustomFieldCrypt extends Service implements CustomFieldCryptService
{
    public function __construct(
        Application                             $application,
        private readonly CustomFieldDataService $customFieldService,
        private readonly CryptInterface         $crypt
    ) {
        parent::__construct($application);
    }

    /**
     * Actualizar los datos encriptados con una nueva clave
     *
     * @throws ServiceException
     */
    public function updateMasterPassword(UpdateMasterPassRequest $request): void
    {
        try {
            $this->processUpdateMasterPassword(
                $request,
                function (CustomFieldDataModel $customFieldData) use ($request) {
                    return $this->crypt->decrypt(
                        $customFieldData->getData(),
                        $customFieldData->getKey(),
                        $request->getCurrentMasterPass()
                    );
                }
            );
        } catch (Exception $e) {
            $this->eventDispatcher->notify('exception', new Event($e));

            throw ServiceException::error(
                __u('Error while updating the custom fields data'),
                null,
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param UpdateMasterPassRequest $request
     * @param callable $decryptor
     * @throws ServiceException
     */
    private function processUpdateMasterPassword(UpdateMasterPassRequest $request, callable $decryptor): void
    {
        $customFieldsData = $this->customFieldService->getAllEncrypted();

        if (count($customFieldsData) === 0) {
            $this->eventDispatcher->notify(
                'update.masterPassword.customFieldsData',
                new Event(
                    $this,
                    EventMessage::factory()
                                ->addDescription(__u('Update Master Password'))
                                ->addDescription(__u('There aren\'t any data from custom fields'))
                )
            );

            return;
        }

        $this->eventDispatcher->notify(
            'update.masterPassword.customFieldsData.start',
            new Event(
                $this,
                EventMessage::factory()
                            ->addDescription(__u('Update Master Password'))
                            ->addDescription(__u('Updating encrypted data'))
            )
        );

        $errors = [];
        $success = [];

        foreach ($customFieldsData as $customFieldData) {
            try {
                $this->customFieldService->updateMasterPass(
                    $customFieldData->mutate(['data' => $decryptor($customFieldData)]),
                    $request->getNewMasterPass()
                );

                $success[] = $customFieldData->getItemId();
            } catch (Exception $e) {
                processException($e);

                $this->eventDispatcher->notify('exception', new Event($e));

                $errors[] = $customFieldData->getItemId();
            }
        }

        $this->eventDispatcher->notify(
            'update.masterPassword.customFieldsData.end',
            new Event(
                $this,
                EventMessage::factory()
                            ->addDescription(__u('Update Master Password'))
                            ->addDetail(__u('Records updated'), implode(',', $success))
                            ->addDetail(__u('Records not updated'), implode(',', $errors))
            )
        );
    }
}
