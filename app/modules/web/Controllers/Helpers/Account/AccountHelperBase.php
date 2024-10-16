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

namespace SP\Modules\Web\Controllers\Helpers\Account;

use SP\Core\Application;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Acl\UnauthorizedPageException;
use SP\Domain\Crypt\Ports\MasterPassService;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\User\Services\UpdatedMasterPassException;
use SP\Modules\Web\Controllers\Helpers\HelperBase;
use SP\Mvc\View\TemplateInterface;

use function SP\__u;

/**
 * Class AccountHelperBase
 */
abstract class AccountHelperBase extends HelperBase
{
    protected ?int $actionId      = null;
    protected bool $isView        = false;
    protected bool $actionGranted = false;

    public function __construct(
        Application                             $application,
        TemplateInterface                       $template,
        RequestService                          $request,
        protected readonly AclInterface         $acl,
        protected readonly AccountActionsHelper $accountActionsHelper,
        private readonly MasterPassService      $masterPassService
    ) {
        parent::__construct($application, $template, $request);
    }

    /**
     * @param bool $isView
     */
    public function setIsView(bool $isView): void
    {
        $this->isView = $isView;
    }

    /**
     * @throws UnauthorizedPageException
     * @throws UpdatedMasterPassException
     */
    final public function initializeFor(int $actionId): void
    {
        if (!$this->acl->checkUserAccess($actionId)) {
            throw UnauthorizedPageException::info($actionId);
        }

        if (!$this->masterPassService->checkUserUpdateMPass($this->context->getUserData()->lastUpdateMPass)
        ) {
            throw UpdatedMasterPassException::info(__u('The master password needs to be updated'));
        }

        $this->actionId = $actionId;
        $this->actionGranted = true;
    }
}
