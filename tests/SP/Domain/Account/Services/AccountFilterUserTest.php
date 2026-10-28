<?php

declare(strict_types=1);
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

namespace SP\Tests\Domain\Account\Services;

use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\QueryFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Account\Services\Builders\AccountFilter;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\User\Dtos\UserDto;
use SP\Tests\Generators\UserDataGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class AccountFilterUserTest
 *
 */
#[Group('unitary')]
class AccountFilterUserTest extends UnitaryTestCase
{
    private const WHERE_HASHES = [
        'Account' => [
            'withoutGlobalSearch' => 'c14bbb066e19a0a9072fc047617779580f6bd104',
            'withoutGlobalSearchAndFullGroupAccess' => '0d7be1abdfae8c3f5d99dd169142d734a0219820',
            'withGlobalSearchPrivate' => '0213f7fd63d743b480dea2760bd0f952e1f022af',
            'withGlobalSearchPrivateGroup' => '4a87f998abe0e0bd423496fb61a0a8abc1ac1be6',
        ],
        'AccountHistory' => [
            'withoutGlobalSearch' => 'f175e5a053e2db8013193669d8f6144f8067fa69',
            'withoutGlobalSearchAndFullGroupAccess' => '231a8527427568438079bcda1a6bf29a2b50a025',
            'withGlobalSearchPrivate' => '1579e1c2fb88f57c5fe8613870043e3f19bdea8a',
            'withGlobalSearchPrivateGroup' => 'd3214ff2771ddd85aba2108bfc48da20b1c0064d',
        ],
    ];
    private AccountFilter $accountFilter;
    private QueryFactory|MockObject $queryFactory;

    public function testBuildFilter()
    {
        $this->setExpectationWithoutGlobalSearch('Account');

        $this->accountFilter->buildFilter();
    }

    /**
     * @param string $tableName
     * @param array|null $whereConsecutiveArgs
     *
     * @return void
     * @throws Exception
     */
    private function setExpectationWithoutGlobalSearch(
        string $tableName,
        ?array $whereConsecutiveArgs = null,
        bool   $fullGroupAccess = false
    ): void
    {
        $whereConsecutiveArgs = $whereConsecutiveArgs ?? $this->buildConsecutiveArgsFor($tableName);

        $select = $this->createMock(SelectInterface::class);
        $this->queryFactory->expects(self::once())->method('newSelect')->willReturn($select);

        $select->expects(self::exactly(1))->method('from')->with($tableName)->willReturnSelf();
        $select->expects(self::exactly(3))
               ->method('where')
               ->with(...self::withConsecutive(...$whereConsecutiveArgs))
               ->willReturnSelf();
        $select->expects(self::exactly(1))->method('bindValues')
               ->with($this->buildBindValues($fullGroupAccess));
    }

    private function buildConsecutiveArgsFor(string $tableName): array
    {
        return [
            [
                new Callback(static fn($where) => sha1($where)
                                                  === self::WHERE_HASHES[$tableName]['withoutGlobalSearch']),
            ],
            [
                new Callback(static fn($where) => sha1($where)
                                                  === self::WHERE_HASHES[$tableName]['withGlobalSearchPrivate']),
            ],
            [
                new Callback(static fn($where) => sha1($where)
                                                  === self::WHERE_HASHES[$tableName]['withGlobalSearchPrivateGroup']),
            ],
        ];
    }

    private function buildBindValues(bool $fullGroupAccess = false): array
    {
        $values = [
            'userIdOwner' => $this->context->getUserData()->id,
            'userGroupIdOwner' => $this->context->getUserData()->userGroupId,
            'userIdAccountUser' => $this->context->getUserData()->id,
            'userGroupIdAccountGroup' => $this->context->getUserData()->userGroupId,
            'userIdGroupMembership' => $this->context->getUserData()->id,
            'userIdPrivate' => $this->context->getUserData()->id,
            'userGroupIdPrivate' => $this->context->getUserData()->userGroupId,
        ];

        if ($fullGroupAccess) {
            $values['userIdFullGroup'] = $this->context->getUserData()->id;
        }

        return $values;
    }

    private function buildPrivateBindValues(): array
    {
        return [
            'userIdPrivate' => $this->context->getUserData()->id,
            'userGroupIdPrivate' => $this->context->getUserData()->userGroupId,
        ];
    }

    public function testBuildFilterWithFullGroupAccess()
    {
        $this->config->getConfigData()->setAccountFullGroupAccess(true);

        $whereConsecutiveArgs = $this->buildConsecutiveArgsFor('Account');

        $whereConsecutiveArgsWithFullGroupAccess = array_replace(
            $whereConsecutiveArgs,
            [
                [
                    new Callback(static fn($where) => sha1($where)
                                                      ===
                                                      self::WHERE_HASHES['Account']['withoutGlobalSearchAndFullGroupAccess']
                    ),
                ],
            ]
        );

        $this->setExpectationWithoutGlobalSearch('Account', $whereConsecutiveArgsWithFullGroupAccess, true);

        $this->accountFilter->buildFilter();
    }

    public function testBuildFilterWithQueryProvided()
    {
        $this->queryFactory->expects(self::never())->method('newSelect');

        $select = $this->createMock(SelectInterface::class);
        $select->expects(self::never())->method('from');
        $select->expects(self::exactly(3))->method('where')->willReturnSelf();
        $select->expects(self::exactly(1))->method('bindValues')
               ->with($this->buildBindValues());

        $this->accountFilter->buildFilter(false, $select);
    }

    /**
     * @throws Exception
     * @throws SPException
     */
    public function testBuildFilterWithGlobalSearchForAdminAcc()
    {
        $this->context->setUserData(
            UserDto::fromModel(UserDataGenerator::factory()->buildUserData()->mutate(['isAdminAcc' => true]))
        );

        $this->setExpectationForGlobalSearch('Account');

        $this->accountFilter->buildFilter();
    }

    /**
     * @param string $tableName
     *
     * @return void
     * @throws Exception
     */
    private function setExpectationForGlobalSearch(string $tableName): void
    {
        $whereConsecutiveArgs = array_slice($this->buildConsecutiveArgsFor($tableName), 1, 2);

        $select = $this->createMock(SelectInterface::class);
        $this->queryFactory->expects(self::once())->method('newSelect')->willReturn($select);

        $select->expects(self::exactly(1))->method('from')->with($tableName)->willReturnSelf();
        $select->expects(self::exactly(2))
               ->method('where')
               ->with(...self::withConsecutive(...$whereConsecutiveArgs))
               ->willReturnSelf();
        $select->expects(self::exactly(1))->method('bindValues')
               ->with($this->buildPrivateBindValues());
    }

    /**
     * @throws Exception
     * @throws SPException
     */
    public function testBuildFilterWithGlobalSearchForAdminApp()
    {
        $this->context->setUserData(
            UserDto::fromModel(UserDataGenerator::factory()->buildUserData()->mutate(['isAdminApp' => true]))
        );

        $this->setExpectationForGlobalSearch('Account');

        $this->accountFilter->buildFilter();
    }

    public function testBuildFilterWithGlobalSearchForGlobalSearch()
    {
        $this->config->getConfigData()->setGlobalSearch(true);
        $this->context->getUserProfile()->setAccGlobalSearch(true);

        $this->setExpectationForGlobalSearch('Account');

        $this->accountFilter->buildFilter(true);
    }

    public function testBuildFilterHistory()
    {
        $this->setExpectationWithoutGlobalSearch('AccountHistory');

        $this->accountFilter->buildFilterHistory();
    }

    /**
     * @throws Exception
     * @throws SPException
     */
    public function testBuildFilterHistoryWithGlobalSearchForAdminAcc()
    {
        $this->context->setUserData(
            UserDto::fromModel(UserDataGenerator::factory()->buildUserData()->mutate(['isAdminAcc' => true]))
        );

        $this->setExpectationForGlobalSearch('AccountHistory');

        $this->accountFilter->buildFilterHistory();
    }

    /**
     * @throws Exception
     * @throws SPException
     */
    public function testBuildFilterHistoryWithGlobalSearchForAdminApp()
    {
        $this->context->setUserData(
            UserDto::fromModel(UserDataGenerator::factory()->buildUserData()->mutate(['isAdminApp' => true]))
        );

        $this->setExpectationForGlobalSearch('AccountHistory');

        $this->accountFilter->buildFilterHistory();
    }

    public function testBuildFilterHistoryWithGlobalSearchForGlobalSearch()
    {
        $this->config->getConfigData()->setGlobalSearch(true);
        $this->context->getUserProfile()->setAccGlobalSearch(true);

        $this->setExpectationForGlobalSearch('AccountHistory');

        $this->accountFilter->buildFilterHistory(true);
    }

    /**
     * @throws Exception
     */
    public function testBuildFilterHistoryWithQueryProvided()
    {
        $this->queryFactory->expects(self::never())->method('newSelect');

        $select = $this->createMock(SelectInterface::class);
        $select->expects(self::never())->method('from');
        $select->expects(self::exactly(3))->method('where')->willReturnSelf();
        $select->expects(self::exactly(1))->method('bindValues')
               ->with($this->buildBindValues());

        $this->accountFilter->buildFilterHistory(false, $select);
    }

    public function testBuildFilterHistoryWithFullGroupAccess()
    {
        $this->config->getConfigData()->setAccountFullGroupAccess(true);

        $whereConsecutiveArgs = $this->buildConsecutiveArgsFor('AccountHistory');

        $whereConsecutiveArgsWithFullGroupAccess = array_replace(
            $whereConsecutiveArgs,
            [
                [
                    new Callback(static fn($where) => sha1($where)
                                                      ===
                                                      self::WHERE_HASHES['AccountHistory']['withoutGlobalSearchAndFullGroupAccess']
                    ),
                ],
            ]
        );

        $this->setExpectationWithoutGlobalSearch('AccountHistory', $whereConsecutiveArgsWithFullGroupAccess, true);

        $this->accountFilter->buildFilterHistory();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryFactory = $this->createMock(QueryFactory::class);

        $this->accountFilter = new AccountFilter(
            $this->context,
            $this->application->getConfig()->getConfigData(),
            $this->queryFactory
        );
    }
}
