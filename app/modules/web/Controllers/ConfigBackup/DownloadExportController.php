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

namespace SP\Modules\Web\Controllers\ConfigBackup;


use Klein\Response;
use SP\Core\Application;
use SP\Core\Bootstrap\Path;
use SP\Core\Bootstrap\PathsContext;
use SP\Core\Context\Session;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseStatus;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Export\Dtos\BackupFile as BackupFileDto;
use SP\Domain\Export\Dtos\BackupType;
use SP\Infrastructure\File\FileHandler;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Mvc\Controller\SimpleControllerHelper;

use function SP\__u;

/**
 * Class DownloadExportController
 */
final class DownloadExportController extends SimpleControllerBase
{
    public function __construct(
        Application                   $application,
        SimpleControllerHelper        $simpleControllerHelper,
        private readonly PathsContext $pathsContext
    ) {
        parent::__construct($application, $simpleControllerHelper);
    }

    #[Action(ResponseType::CALLBACK)]
    public function downloadExportAction(): ActionResponse
    {
        Session::close();

        $filePath = (string)new BackupFileDto(
            BackupType::export,
            $this->configData->getExportHash() ?: '',
            $this->pathsContext[Path::BACKUP],
            'gz'
        );

        $file = new FileHandler($filePath);

        $this->eventDispatcher->notify(
            'download.exportFile',
            new Event(
                $this,
                EventMessage::build(__u('File downloaded'))
                            ->addDetail(__u('File'), str_replace(APP_ROOT, '', $file->getFile()))
            )
        );

        return new ActionResponse(
            ResponseStatus::OK,
            function (Response $response) use ($file) {
                $response->header('Cache-Control', 'max-age=60, must-revalidate')
                         ->header('Content-length', $file->getFileSize())
                         ->header('Content-type', $file->getFileType())
                         ->header('Content-Description', ' sysPass file')
                         ->header('Content-transfer-encoding', 'chunked')
                         ->header('Content-Disposition', 'attachment; filename="' . basename($file->getFile()) . '"')
                         ->header('Set-Cookie', 'fileDownload=true; path=/')
                         ->send();

                $file->readChunked();
            }
        );
    }

    /**
     * initialize
     *
     * @throws SPException
     * @throws SessionTimeout
     */
    protected function initialize(): void
    {
        $this->checks();
        $this->checkAccess(AclActionsInterface::CONFIG_BACKUP);
    }
}
