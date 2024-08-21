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

use Exception;
use JsonException;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Http\Dtos\JsonMessage;
use SP\Infrastructure\File\FileSystem;
use SP\Modules\Web\Controllers\Traits\JsonTrait;

use function SP\__u;
use function SP\processException;

/**
 * Class ViewController
 *
 * @package SP\Modules\Web\Controllers
 */
final class ViewController extends AccountFileBase
{
    private const MIME_VIEW = ['text/plain'];

    use JsonTrait;

    /**
     * View action
     *
     * @param int $id
     *
     * @return bool
     * @throws JsonException
     * @throws SPException
     */
    public function viewAction(int $id): bool
    {
        try {
            $fileDto = $this->accountFileService->getById($id);

            $this->view->addTemplate('file', 'itemshow');

            if (FileSystem::isImage($fileDto->type)) {
                $this->view->assign('data', chunk_split(base64_encode($fileDto->content)));
                $this->view->assign('fileData', $fileDto);
                $this->view->assign('isImage', 1);

                $this->eventDispatcher->notify(
                    'show.accountFile',
                    new Event(
                        $this,
                        EventMessage::build()
                            ->addDescription(__u('File viewed'))
                            ->addDetail(__u('File'), $fileDto->name)
                    )
                );

                return $this->returnJsonResponseData(['html' => $this->render()]);
            }

            $type = strtolower($fileDto->type);

            if (in_array($type, self::MIME_VIEW)) {
                $this->view->assign('mime', $type);
                $this->view->assign('data', htmlentities($fileDto->content));

                $this->eventDispatcher->notify(
                    'show.accountFile',
                    new Event(
                        $this,
                        EventMessage::build()
                            ->addDescription(__u('File viewed'))
                            ->addDetail(__u('File'), $fileDto->name)
                    )
                );

                return $this->returnJsonResponseData(['html' => $this->render()]);
            }
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }

        return $this->returnJsonResponse(JsonMessage::JSON_WARNING, __u('File not supported for preview'));
    }
}
