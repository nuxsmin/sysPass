<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers\AccountFile;

use Exception;
use JsonException;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\SPException;
use SP\DataModel\FileData;
use SP\Domain\Account\Ports\AccountFileServiceInterface;
use SP\Domain\Account\Ports\AccountServiceInterface;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileHandler;
use SP\Infrastructure\File\FileHandlerInterface;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\WebControllerHelper;

/**
 * Class UploadController
 *
 * @package SP\Modules\Web\Controllers
 */
final class UploadController extends ControllerBase
{
    use JsonTrait;

    private AccountFileServiceInterface $accountFileService;
    private AccountServiceInterface     $accountService;

    public function __construct(
        Application             $application,
        WebControllerHelper     $webControllerHelper,
        AccountFileServiceInterface $accountFileService,
        AccountServiceInterface $accountService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();

        $this->accountFileService = $accountFileService;
        $this->accountService = $accountService;
    }

    /**
     * Upload action
     *
     * @param int $accountId
     *
     * @return bool
     * @throws JsonException
     */
    public function uploadAction(int $accountId): bool
    {
        try {
            $file = $this->router->request()->files()->get('inFile');

            if ($accountId === 0 || null === $file) {
                throw new SPException(__u('INVALID QUERY'), SPException::ERROR);
            }

            $filesAllowedMime = $this->configData->getFilesAllowedMime();

            if (count($filesAllowedMime) === 0) {
                throw new SPException(__u('There aren\'t any allowed MIME types'));
            }

            try {
                $fileHandler = new FileHandler($file['tmp_name']);

                $fileData = new FileData();
                $fileData->setAccountId($accountId);
                $fileData->setName(htmlspecialchars($file['name'], ENT_QUOTES));
                $fileData->setSize($file['size']);
                $fileData->setType($file['type']);
                $fileData->setExtension(mb_strtoupper(pathinfo($fileData->getName(), PATHINFO_EXTENSION)));

                if ($fileData->getName() === '') {
                    throw new SPException(
                        __u('Invalid file'),
                        SPException::ERROR,
                        sprintf(__u('File: %s'), $fileData->getName())
                    );
                }

                $fileHandler->checkFileExists();

                $fileData->setType($this->checkAllowedMimeType($fileData, $fileHandler));

                $allowedSize = $this->configData->getFilesAllowedSize();

                if ($fileData->getSize() > ($allowedSize * 1000)) {
                    throw new SPException(
                        __u('File size exceeded'),
                        SPException::ERROR,
                        sprintf(__u('Maximum size: %d KB'), $fileData->getRoundSize())
                    );
                }

                $fileData->setContent($fileHandler->readToString());
            } catch (FileException $e) {
                throw new SPException(__u('Internal error while reading the file'));
            }

            $this->accountFileService->create($fileData);

            $account = $this->accountService->getByIdEnriched($accountId)->getAccountVData();

            $this->eventDispatcher->notify(
                'upload.accountFile',
                new Event(
                    $this,
                    EventMessage::factory()
                                ->addDescription(__u('File saved'))
                                ->addDetail(__u('File'), $fileData->getName())
                                ->addDetail(__u('Account'), $account->getName())
                                ->addDetail(__u('Client'), $account->getClientName())
                                ->addDetail(__u('Type'), $fileData->getType())
                                ->addDetail(__u('Size'), $fileData->getRoundSize() . 'KB')
                )
            );

            return $this->returnJsonResponse(0, __u('File saved'));
        } catch (SPException $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponse(1, $e->getMessage(), [$e->getHint()]);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @param FileData $fileData
     * @param FileHandlerInterface $fileHandler
     *
     * @return string
     * @throws SPException
     * @throws FileException
     */
    private function checkAllowedMimeType(FileData $fileData, FileHandlerInterface $fileHandler): string
    {
        if (in_array($fileData->getType(), $this->configData->getFilesAllowedMime(), true)) {
            return $fileData->getType();
        }

        if (in_array($fileHandler->getFileType(), $this->configData->getFilesAllowedMime(), true)) {
            return $fileHandler->getFileType();
        }

        throw new SPException(
            __u('File type not allowed'),
            SPException::ERROR,
            sprintf(__('MIME type: %s'), $fileData->getType())
        );
    }
}
