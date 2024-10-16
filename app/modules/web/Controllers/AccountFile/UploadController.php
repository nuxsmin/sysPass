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

namespace SP\Modules\Web\Controllers\AccountFile;

use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Account\Models\File;
use SP\Domain\Account\Ports\AccountFileService;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\File\Ports\FileHandlerInterface;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileHandler;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Mvc\Controller\WebControllerHelper;

use function SP\__;
use function SP\__u;

/**
 * Class UploadController
 *
 * @package SP\Modules\Web\Controllers
 */
final class UploadController extends ControllerBase
{
    /**
     * @throws AuthException
     * @throws SessionTimeout
     */
    public function __construct(
        Application                         $application,
        WebControllerHelper                 $webControllerHelper,
        private readonly AccountFileService $accountFileService,
        private readonly AccountService     $accountService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();
    }

    /**
     * Upload action
     *
     * @param int $accountId
     *
     * @return ActionResponse
     * @throws SPException
     */
    #[Action(ResponseType::JSON)]
    public function uploadAction(int $accountId): ActionResponse
    {
        $file = $this->router->request()->files()->get('inFile');

        if ($accountId === 0 || null === $file) {
            throw new SPException(__u('INVALID QUERY'), SPException::ERROR);
        }

        $filesAllowedMime = $this->configData->getFilesAllowedMime();

        if (count($filesAllowedMime) === 0) {
            throw new SPException(__u('There aren\'t any allowed MIME types'));
        }

        try {
            $fileName = htmlspecialchars($file['name'] ?? '', ENT_QUOTES);

            if (empty($fileName)) {
                throw SPException::error(__u('Invalid file'), sprintf(__u('File: %s'), $fileName));
            }

            $allowedSize = $this->configData->getFilesAllowedSize();

            if ($file['size'] > ($allowedSize * 1000)) {
                throw SPException::error(
                    __u('File size exceeded'),
                    sprintf(__u('Maximum size: %f KB'), round($allowedSize / 1000, 2))
                );
            }

            $fileHandler = new FileHandler($file['tmp_name']);

            $fileData = [
                'accountId' => $accountId,
                'name' => $fileName,
                'size' => $file['size'],
                'type' => $this->checkAllowedMimeType($file['type'], $fileHandler),
                'extension' => mb_strtoupper(pathinfo($fileName, PATHINFO_EXTENSION)),
                'content' => $fileHandler->readToString()
            ];
        } catch (FileException $e) {
            $this->eventDispatcher->notify('exception', new Event($e));

            throw SPException::error(__u('Internal error while reading the file'));
        }

        $this->accountFileService->create(new File($fileData));

        $this->eventDispatcher->notify(
            'upload.accountFile',
            new Event(
                $this,
                static function () use ($accountId, $fileData): EventMessage {
                    $account = $this->accountService->getByIdEnriched($accountId);

                    return EventMessage::build()
                                       ->addDescription(__u('File saved'))
                                       ->addDetail(__u('File'), $fileData['name'])
                                       ->addDetail(__u('Account'), $account->getName())
                                       ->addDetail(__u('Client'), $account->getClientName())
                                       ->addDetail(__u('Type'), $fileData['type'])
                                       ->addDetail(
                                           __u('Size'),
                                           sprintf('%f KB', round($fileData['size'] / 1000))
                                       );
                }
            )
        );

        return ActionResponse::ok(__u('File saved'));
    }

    /**
     * @param string $type
     * @param FileHandlerInterface $fileHandler
     *
     * @return string
     * @throws FileException
     * @throws SPException
     */
    private function checkAllowedMimeType(string $type, FileHandlerInterface $fileHandler): string
    {
        if (in_array($type, $this->configData->getFilesAllowedMime(), true)) {
            return $type;
        }

        if (in_array($fileHandler->getFileType(), $this->configData->getFilesAllowedMime(), true)) {
            return $fileHandler->getFileType();
        }

        throw SPException::error(__u('File type not allowed'), sprintf(__('MIME type: %s'), $type));
    }
}
