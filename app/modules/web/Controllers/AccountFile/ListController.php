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
use SP\Core\Events\Event;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Modules\Web\Util\ErrorUtil;

use function SP\__;
use function SP\processException;

/**
 * Class ListController
 *
 * @package SP\Modules\Web\Controllers
 */
final class ListController extends AccountFileBase
{
    /**
     * Obtener los datos para la vista de archivos de una cuenta
     *
     * @param int $accountId Account's ID
     */
    public function listAction(int $accountId): void
    {
        if (!$this->configData->isFilesEnabled()) {
            echo __('Files management disabled');

            return;
        }

        try {
            $this->view->addTemplate('files-list', 'account');

            $files = $this->accountFileService->getByAccountId($accountId);

            $this->view->assign('deleteEnabled', $this->request->analyzeInt('del', false));
            $this->view->assign('files', $files);
            $this->view->assign('fileViewRoute', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_FILE_VIEW));
            $this->view->assign(
                'fileDownloadRoute',
                $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_FILE_DOWNLOAD)
            );
            $this->view->assign('fileDeleteRoute', $this->acl->getRouteFor(AclActionsInterface::ACCOUNT_FILE_DELETE));

            if (count($files) === 0) {
                $this->view->addTemplate('no_records_found', '_partials');

                $this->view->assign('message', __('There are no linked files for the account'));

                $this->view();

                return;
            }

            $this->eventDispatcher->notify('list.accountFile', new Event($this));
        } catch (Exception $e) {
            processException($e);

            ErrorUtil::showErrorInView(
                $this->view,
                ErrorUtil::ERR_EXCEPTION,
                true,
                'files-list'
            );
        }

        $this->view();
    }
}
