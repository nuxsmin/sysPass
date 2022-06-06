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

namespace SP\Modules\Web\Controllers\AccountFile;

use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\SPException;
use SP\Domain\Account\AccountFileServiceInterface;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Controllers\Traits\JsonTrait;
use SP\Mvc\Controller\WebControllerHelper;
use SP\Util\FileUtil;

/**
 * Class ViewController
 *
 * @package SP\Modules\Web\Controllers
 */
final class ViewController extends ControllerBase
{
    private const MIME_VIEW = ['text/plain'];

    use JsonTrait;

    private AccountFileServiceInterface $accountFileService;

    public function __construct(
        Application $application,
        WebControllerHelper $webControllerHelper,
        AccountFileServiceInterface $accountFileService
    ) {
        parent::__construct($application, $webControllerHelper);

        $this->checkLoggedIn();

        $this->accountFileService = $accountFileService;
    }

    /**
     * View action
     *
     * @param  int  $id
     *
     * @return bool
     * @throws \JsonException
     */
    public function viewAction(int $id): bool
    {
        try {
            if (null === ($fileData = $this->accountFileService->getById($id))) {
                throw new SPException(__u('File does not exist'), SPException::INFO);
            }

            $this->view->addTemplate('file', 'itemshow');

            if (FileUtil::isImage($fileData)) {
                $this->view->assign('data', chunk_split(base64_encode($fileData->getContent())));
                $this->view->assign('fileData', $fileData);
                $this->view->assign('isImage', 1);

                $this->eventDispatcher->notifyEvent(
                    'show.accountFile',
                    new Event(
                        $this,
                        EventMessage::factory()
                            ->addDescription(__u('File viewed'))
                            ->addDetail(__u('File'), $fileData->getName())
                    )
                );

                return $this->returnJsonResponseData(['html' => $this->render()]);
            }

            $type = strtolower($fileData->getType());

            if (in_array($type, self::MIME_VIEW)) {
                $this->view->assign('mime', $type);
                $this->view->assign('data', htmlentities($fileData->getContent()));

                $this->eventDispatcher->notifyEvent(
                    'show.accountFile',
                    new Event(
                        $this,
                        EventMessage::factory()
                            ->addDescription(__u('File viewed'))
                            ->addDetail(__u('File'), $fileData->getName())
                    )
                );

                return $this->returnJsonResponseData(['html' => $this->render()]);
            }
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }

        return $this->returnJsonResponse(JsonResponse::JSON_WARNING, __u('File not supported for preview'));
    }
}
