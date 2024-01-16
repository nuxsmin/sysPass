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
 * Class AccountItemsService
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
            if (null === $accountUpdateDto->getUserGroupsView()) {
                $this->accountToUserGroupRepository->deleteTypeByAccountId($accountId, false);
            } elseif (count($accountUpdateDto->getUserGroupsView()) > 0) {
                $this->accountToUserGroupRepository->transactionAware(
                    function () use ($accountUpdateDto, $accountId) {
                        $this->accountToUserGroupRepository
                            ->deleteTypeByAccountId($accountId, false);
                        $this->accountToUserGroupRepository
                            ->addByType($accountId, $accountUpdateDto->getUserGroupsView());
                    },
                    $this
                );
            }

            if (null === $accountUpdateDto->getUserGroupsEdit()) {
                $this->accountToUserGroupRepository->deleteTypeByAccountId($accountId, true);
            } elseif (count($accountUpdateDto->getUserGroupsEdit()) > 0) {
                $this->accountToUserGroupRepository->transactionAware(
                    function () use ($accountUpdateDto, $accountId) {
                        $this->accountToUserGroupRepository
                            ->deleteTypeByAccountId($accountId, true);
                        $this->accountToUserGroupRepository
                            ->addByType($accountId, $accountUpdateDto->getUserGroupsEdit(), true);
                    },
                    $this
                );
            }

            if (null === $accountUpdateDto->getUsersView()) {
                $this->accountToUserRepository->deleteTypeByAccountId($accountId, false);
            } elseif (count($accountUpdateDto->getUsersView()) > 0) {
                $this->accountToUserRepository->transactionAware(
                    function () use ($accountUpdateDto, $accountId) {
                        $this->accountToUserRepository
                            ->deleteTypeByAccountId($accountId, false);
                        $this->accountToUserRepository
                            ->addByType($accountId, $accountUpdateDto->getUsersView());
                    },
                    $this
                );
            }

            if (null === $accountUpdateDto->getUsersEdit()) {
                $this->accountToUserRepository->deleteTypeByAccountId($accountId, true);
            } elseif (count($accountUpdateDto->getUsersEdit()) > 0) {
                $this->accountToUserRepository->transactionAware(
                    function () use ($accountUpdateDto, $accountId) {
                        $this->accountToUserRepository
                            ->deleteTypeByAccountId($accountId, true);
                        $this->accountToUserRepository
                            ->addByType($accountId, $accountUpdateDto->getUsersEdit(), true);
                    },
                    $this
                );
            }
        }

        if (null === $accountUpdateDto->getTags()) {
            $this->accountToTagRepository->deleteByAccountId($accountId);
        } elseif (count($accountUpdateDto->getTags()) > 0) {
            $this->accountToTagRepository->transactionAware(
                function () use ($accountUpdateDto, $accountId) {
                    $this->accountToTagRepository->deleteByAccountId($accountId);
                    $this->accountToTagRepository->add($accountId, $accountUpdateDto->getTags());
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
                if (null !== $accountCreateDto->getUserGroupsView()
                    && count($accountCreateDto->getUserGroupsView()) > 0
                ) {
                    $this->accountToUserGroupRepository->addByType(
                        $accountId,
                        $accountCreateDto->getUserGroupsView()
                    );
                }

                if (null !== $accountCreateDto->getUserGroupsEdit()
                    && count($accountCreateDto->getUserGroupsEdit()) > 0
                ) {
                    $this->accountToUserGroupRepository->addByType(
                        $accountId,
                        $accountCreateDto->getUserGroupsEdit(),
                        true
                    );
                }

                if (null !== $accountCreateDto->getUsersView() && count($accountCreateDto->getUsersView()) > 0) {
                    $this->accountToUserRepository->addByType($accountId, $accountCreateDto->getUsersView());
                }

                if (null !== $accountCreateDto->getUsersEdit() && count($accountCreateDto->getUsersEdit()) > 0) {
                    $this->accountToUserRepository->addByType($accountId, $accountCreateDto->getUsersEdit(), true);
                }
            }

            if (null !== $accountCreateDto->getTags() && count($accountCreateDto->getTags()) > 0) {
                $this->accountToTagRepository->add($accountId, $accountCreateDto->getTags());
            }
        } catch (SPException $e) {
            processException($e);
        }
    }
}
