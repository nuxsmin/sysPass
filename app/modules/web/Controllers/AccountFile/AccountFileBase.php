<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Domain\Account\Dtos\AccountAclDto;
use SP\Domain\Account\Dtos\AccountEnrichedDto;
use SP\Domain\Account\Ports\AccountAclService;
use SP\Domain\Account\Ports\AccountFileService;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Auth\Services\AuthException;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\NoSuchItemException;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\File\Ports\FileSystemService;
use SP\Modules\Web\Controllers\ControllerBase;
use SP\Modules\Web\Mvc\Controller\WebControllerHelper;

use function SP\__u;

/**
 * Class AccountFileBase
 */
abstract class AccountFileBase extends ControllerBase
{

    /**
     * @throws AuthException
     * @throws SessionTimeout
     */
    public function __construct(
        WebControllerHelper                   $webControllerHelper,
        protected readonly AccountFileService $accountFileService,
        protected readonly FileSystemService  $fileSystem,
        protected readonly AccountService     $accountService,
        protected readonly AccountAclService  $accountAclService
    ) {
        parent::__construct($webControllerHelper);

        $this->checkLoggedIn();
    }

    /**
     * Authorizes the access to the files of an account.
     *
     * Checks, in order: the profile actions, the visibility of the account and the access level of the
     * user on the account (view access, or edit access when the files are going to be modified).
     *
     * @param int $fileAction One of the AclActionsInterface::ACCOUNT_FILE_* actions
     * @param int $accountId The account owning the files
     * @param bool $modify Whether the files are going to be modified (it requires edit access)
     *
     * @return ActionResponse|null An error response when the access is denied; null when allowed
     * @throws SPException
     */
    protected function authorizeAccountFiles(int $fileAction, int $accountId, bool $modify = false): ?ActionResponse
    {
        return $this->checkProfile($fileAction, $modify) ?? $this->checkAccount($accountId, $modify);
    }

    /**
     * Authorizes the access to existing files by their ids.
     *
     * The files are resolved to their accounts, and every account is authorized. Missing files and files
     * the user cannot access produce exactly the same response. Nothing must be read or modified when
     * an error response is returned (all or nothing).
     *
     * @param int $fileAction One of the AclActionsInterface::ACCOUNT_FILE_* actions
     * @param int[] $fileIds The file ids
     * @param bool $modify Whether the files are going to be modified (it requires edit access)
     *
     * @return ActionResponse|null An error response when the access is denied; null when allowed
     * @throws SPException
     */
    protected function authorizeFiles(int $fileAction, array $fileIds, bool $modify = false): ?ActionResponse
    {
        $response = $this->checkProfile($fileAction, $modify);

        if ($response !== null) {
            return $response;
        }

        $accountIds = [];

        foreach ($fileIds as $fileId) {
            try {
                $accountIds[$this->accountFileService->getAccountIdById((int)$fileId)] = true;
            } catch (NoSuchItemException) {
                return $this->accountDenied();
            }
        }

        foreach (array_keys($accountIds) as $accountId) {
            $response = $this->checkAccount($accountId, $modify);

            if ($response !== null) {
                return $response;
            }
        }

        return null;
    }

    private function checkProfile(int $fileAction, bool $modify): ?ActionResponse
    {
        if (!$this->acl->checkUserAccess($fileAction)
            || ($modify && !$this->acl->checkUserAccess(AclActionsInterface::ACCOUNT_EDIT))
        ) {
            return ActionResponse::error(__u('You don\'t have permission to do this operation'));
        }

        return null;
    }

    /**
     * @throws SPException
     */
    private function checkAccount(int $accountId, bool $modify): ?ActionResponse
    {
        if (!$this->accountService->isVisible($accountId)) {
            return $this->accountDenied();
        }

        $actionId = $modify ? AclActionsInterface::ACCOUNT_EDIT : AclActionsInterface::ACCOUNT_VIEW;

        try {
            $account = new AccountEnrichedDto($this->accountService->getByIdEnriched($accountId));
            $account = $this->accountService->withUsers($account);
            $account = $this->accountService->withUserGroups($account);
        } catch (NoSuchItemException) {
            return $this->accountDenied();
        }

        $permission = $this->accountAclService->getAcl($actionId, AccountAclDto::makeFromAccount($account));

        return $permission->checkAccountAccess($actionId) ? null : $this->accountDenied();
    }

    private function accountDenied(): ActionResponse
    {
        return ActionResponse::error(__u('You don\'t have permission to access this account'));
    }
}
