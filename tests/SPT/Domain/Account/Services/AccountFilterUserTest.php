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

namespace SPT\Domain\Account\Services;

use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\QueryFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Account\Services\Builders\AccountFilter;
use SPT\UnitaryTestCase;

/**
 * Class AccountFilterUserTest
 *
 */
#[Group('unitary')]
class AccountFilterUserTest extends UnitaryTestCase
{
    private const WHERE_HASHES = [
        'Account' => [
            'withoutGlobalSearch' => '12fc7a2aa62477d11938d028b5648670252e66dc',
            'withoutGlobalSearchAndFullGroupAccess' => '703fc200665cebcd102714035189780515eeff39',
            'withGlobalSearchPrivate' => 'fa13ffe26c4c597a1a7e54486409927a8c722176',
            'withGlobalSearchPrivateGroup' => '26a211bd03e2a150bd1f2dffd6d78638a94634ea',
        ],
        'AccountHistory' => [
            'withoutGlobalSearch' => 'f5dc834955371863301a516ee7606436d522abdd',
            'withoutGlobalSearchAndFullGroupAccess' => '3ef96426906a1c96ba6c4e5d768dd182acc35b93',
            'withGlobalSearchPrivate' => '4daf2520c5b791b66dadf0e83b681d7fea6df17f',
            'withGlobalSearchPrivateGroup' => 'c5282775ef23fd9130e60223e3613d999fa89094',
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
    private function setExpectationWithoutGlobalSearch(string $tableName, ?array $whereConsecutiveArgs = null): void
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
               ->with([
                          'userId' => $this->context->getUserData()->getId(),
                          'userGroupId' => $this->context->getUserData()->getUserGroupId(),
                      ]);
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

        $this->setExpectationWithoutGlobalSearch('Account', $whereConsecutiveArgsWithFullGroupAccess);

        $this->accountFilter->buildFilter();
    }

    public function testBuildFilterWithQueryProvided()
    {
        $this->queryFactory->expects(self::never())->method('newSelect');

        $select = $this->createMock(SelectInterface::class);
        $select->expects(self::never())->method('from');
        $select->expects(self::exactly(3))->method('where')->willReturnSelf();
        $select->expects(self::exactly(1))->method('bindValues')
               ->with([
                          'userId' => $this->context->getUserData()->getId(),
                          'userGroupId' => $this->context->getUserData()->getUserGroupId(),
                      ]);

        $this->accountFilter->buildFilter(false, $select);
    }

    public function testBuildFilterWithGlobalSearchForAdminAcc()
    {
        $this->context->getUserData()->setIsAdminAcc(true);

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
               ->with([
                          'userId' => $this->context->getUserData()->getId(),
                          'userGroupId' => $this->context->getUserData()->getUserGroupId(),
                      ]);
    }

    public function testBuildFilterWithGlobalSearchForAdminApp()
    {
        $this->context->getUserData()->setIsAdminApp(true);

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

    public function testBuildFilterHistoryWithGlobalSearchForAdminAcc()
    {
        $this->context->getUserData()->setIsAdminAcc(true);

        $this->setExpectationForGlobalSearch('AccountHistory');

        $this->accountFilter->buildFilterHistory();
    }

    public function testBuildFilterHistoryWithGlobalSearchForAdminApp()
    {
        $this->context->getUserData()->setIsAdminApp(true);

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

    public function testBuildFilterHistoryWithQueryProvided()
    {
        $this->queryFactory->expects(self::never())->method('newSelect');

        $select = $this->createMock(SelectInterface::class);
        $select->expects(self::never())->method('from');
        $select->expects(self::exactly(3))->method('where')->willReturnSelf();
        $select->expects(self::exactly(1))->method('bindValues')
               ->with([
                          'userId' => $this->context->getUserData()->getId(),
                          'userGroupId' => $this->context->getUserData()->getUserGroupId(),
                      ]);

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

        $this->setExpectationWithoutGlobalSearch('AccountHistory', $whereConsecutiveArgsWithFullGroupAccess);

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
