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
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\SPException;
use SP\Modules\Web\Controllers\Traits\JsonTrait;

/**
 * Class DownloadController
 *
 * @package SP\Modules\Web\Controllers
 */
final class DownloadController extends AccountFileBase
{
    use JsonTrait;
    
    /**
     * Download action
     *
     * @param  int  $id
     *
     * @return string
     */
    public function downloadAction(int $id): string
    {
        try {
            if (null === ($fileData = $this->accountFileService->getById($id))) {
                throw new SPException(__u('File does not exist'), SPException::INFO);
            }

            $this->eventDispatcher->notify(
                'download.accountFile',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('File downloaded'))
                        ->addDetail(__u('File'), $fileData->getName())
                )
            );

            $response = $this->router->response();
            $response->header('Content-Length', $fileData->getSize());
            $response->header('Content-Type', $fileData->getType());
            $response->header('Content-Description', ' sysPass file');
            $response->header('Content-Transfer-Encoding', 'binary');
            $response->header('Accept-Ranges', 'bytes');

            $type = strtolower($fileData->getType());

            if ($type === 'application/pdf') {
                $disposition = sprintf('inline; filename="%s"', $fileData->getName());
            } else {
                $disposition = sprintf('attachment; filename="%s"', $fileData->getName());
                $response->header('Set-Cookie', 'fileDownload=true; path=/');
            }

            $response->header('Content-Disposition', $disposition);

            $response->body($fileData->getContent());
            $response->send(true);
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));
        }

        return '';
    }
}
