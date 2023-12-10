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

namespace SP\Modules\Web\Controllers\Helpers\Account;


use SP\Core\Acl\Acl;
use SP\Core\Application;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Acl\UnauthorizedPageException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Crypt\Ports\MasterPassServiceInterface;
use SP\Domain\Http\RequestInterface;
use SP\Domain\User\Services\UpdatedMasterPassException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Modules\Web\Controllers\Helpers\HelperBase;
use SP\Mvc\View\TemplateInterface;

/**
 * Class AccountHelperBase
 */
abstract class AccountHelperBase extends HelperBase
{
    protected ?int                     $actionId = null;
    protected AccountActionsHelper     $accountActionsHelper;
    protected bool                     $isView   = false;
    protected Acl                      $acl;
    private MasterPassServiceInterface $masterPassService;

    public function __construct(
        Application          $application,
        TemplateInterface    $template,
        RequestInterface     $request,
        AclInterface         $acl,
        AccountActionsHelper $accountActionsHelper,
        MasterPassServiceInterface $masterPassService
    ) {
        parent::__construct($application, $template, $request);

        $this->acl = $acl;
        $this->accountActionsHelper = $accountActionsHelper;
        $this->masterPassService = $masterPassService;
    }

    /**
     * @param  bool  $isView
     */
    public function setIsView(bool $isView): void
    {
        $this->isView = $isView;
    }

    /**
     * @throws NoSuchItemException
     * @throws UnauthorizedPageException
     * @throws UpdatedMasterPassException
     * @throws ServiceException
     */
    final protected function checkActionAccess(): void
    {
        if (!$this->acl->checkUserAccess($this->actionId)) {
            throw new UnauthorizedPageException(SPException::INFO);
        }

        if (!$this->masterPassService->checkUserUpdateMPass($this->context->getUserData()->getLastUpdateMPass())
        ) {
            throw new UpdatedMasterPassException(SPException::INFO);
        }
    }
}
