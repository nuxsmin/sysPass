<?php

declare(strict_types=1);
/**
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

namespace SP\Domain\Account\Services;

use SP\Core\Application;
use SP\Domain\Account\Dtos\AccountCreateDto;
use SP\Domain\Account\Dtos\AccountUpdateDto;
use SP\Domain\Account\Ports\AccountItemsService;
use SP\Domain\Account\Ports\AccountToTagRepository;
use SP\Domain\Account\Ports\AccountToUserGroupRepository;
use SP\Domain\Account\Ports\AccountToUserRepository;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;

use function SP\processException;

/**
 * Class AccountItems
 */
final class AccountItems extends Service implements AccountItemsService
{
    public function __construct(
        Application                                   $application,
        private readonly AccountToUserGroupRepository $accountToUserGroupRepository,
        private readonly AccountToUserRepository      $accountToUserRepository,
        private readonly AccountToTagRepository       $accountToTagRepository,
    ) {
        parent::__construct($application);
    }

    /**
     * Updates external items for the account
     *
     * @throws QueryException
     * @throws ConstraintException
     * @throws ServiceException
     */
    public function updateItems(
        bool $userCanChangePermissions,
        int  $accountId,
        AccountUpdateDto $accountUpdateDto
    ): void {
        if ($userCanChangePermissions) {
            if (null === $accountUpdateDto->userGroupsView) {
                $this->accountToUserGroupRepository->deleteTypeByAccountId($accountId, false);
            } elseif (count($accountUpdateDto->userGroupsView) > 0) {
                $this->accountToUserGroupRepository->transactionAware(
                    function () use ($accountUpdateDto, $accountId) {
                        $this->accountToUserGroupRepository
                            ->deleteTypeByAccountId($accountId, false);
                        $this->accountToUserGroupRepository
                            ->addByType($accountId, $accountUpdateDto->userGroupsView);
                    },
                    $this
                );
            }

            if (null === $accountUpdateDto->userGroupsEdit) {
                $this->accountToUserGroupRepository->deleteTypeByAccountId($accountId, true);
            } elseif (count($accountUpdateDto->userGroupsEdit) > 0) {
                $this->accountToUserGroupRepository->transactionAware(
                    function () use ($accountUpdateDto, $accountId) {
                        $this->accountToUserGroupRepository
                            ->deleteTypeByAccountId($accountId, true);
                        $this->accountToUserGroupRepository
                            ->addByType($accountId, $accountUpdateDto->userGroupsEdit, true);
                    },
                    $this
                );
            }

            if (null === $accountUpdateDto->usersView) {
                $this->accountToUserRepository->deleteTypeByAccountId($accountId, false);
            } elseif (count($accountUpdateDto->usersView) > 0) {
                $this->accountToUserRepository->transactionAware(
                    function () use ($accountUpdateDto, $accountId) {
                        $this->accountToUserRepository
                            ->deleteTypeByAccountId($accountId, false);
                        $this->accountToUserRepository
                            ->addByType($accountId, $accountUpdateDto->usersView);
                    },
                    $this
                );
            }

            if (null === $accountUpdateDto->usersEdit) {
                $this->accountToUserRepository->deleteTypeByAccountId($accountId, true);
            } elseif (count($accountUpdateDto->usersEdit) > 0) {
                $this->accountToUserRepository->transactionAware(
                    function () use ($accountUpdateDto, $accountId) {
                        $this->accountToUserRepository
                            ->deleteTypeByAccountId($accountId, true);
                        $this->accountToUserRepository
                            ->addByType($accountId, $accountUpdateDto->usersEdit, true);
                    },
                    $this
                );
            }
        }

        if (null === $accountUpdateDto->tags) {
            $this->accountToTagRepository->deleteByAccountId($accountId);
        } elseif (count($accountUpdateDto->tags) > 0) {
            $this->accountToTagRepository->transactionAware(
                function () use ($accountUpdateDto, $accountId) {
                    $this->accountToTagRepository->deleteByAccountId($accountId);
                    $this->accountToTagRepository->add($accountId, $accountUpdateDto->tags);
                },
                $this
            );
        }
    }

    /**
     * Adds external items to the account
     */
    public function addItems(bool $userCanChangePermissions, int $accountId, AccountCreateDto $accountCreateDto): void
    {
        try {
            if ($userCanChangePermissions) {
                if (null !== $accountCreateDto->userGroupsView
                    && count($accountCreateDto->userGroupsView) > 0
                ) {
                    $this->accountToUserGroupRepository->addByType(
                        $accountId,
                        $accountCreateDto->userGroupsView
                    );
                }

                if (null !== $accountCreateDto->userGroupsEdit
                    && count($accountCreateDto->userGroupsEdit) > 0
                ) {
                    $this->accountToUserGroupRepository->addByType(
                        $accountId,
                        $accountCreateDto->userGroupsEdit,
                        true
                    );
                }

                if (null !== $accountCreateDto->usersView && count($accountCreateDto->usersView) > 0) {
                    $this->accountToUserRepository->addByType($accountId, $accountCreateDto->usersView);
                }

                if (null !== $accountCreateDto->usersEdit && count($accountCreateDto->usersEdit) > 0) {
                    $this->accountToUserRepository->addByType($accountId, $accountCreateDto->usersEdit, true);
                }
            }

            if (null !== $accountCreateDto->tags && count($accountCreateDto->tags) > 0) {
                $this->accountToTagRepository->add($accountId, $accountCreateDto->tags);
            }
        } catch (SPException $e) {
            processException($e);
        }
    }
}
