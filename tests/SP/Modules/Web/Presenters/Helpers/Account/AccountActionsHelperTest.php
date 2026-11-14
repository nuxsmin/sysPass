<?php
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

declare(strict_types=1);

namespace SP\Tests\Modules\Web\Presenters\Helpers\Account;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Http\Ports\RequestService;
use SP\Modules\Web\Mvc\View\TemplateInterface;
use SP\Modules\Web\Presenters\Helpers\Account\AccountActionsHelper;
use SP\Modules\Web\UI\ThemeIcons;
use SP\Tests\UnitaryTestCase;

use function PHPUnit\Framework\once;
/**
 * Class AccountActionsHelperTest
 *
 */
#[Group('unitary')]
class AccountActionsHelperTest extends UnitaryTestCase
{
    /**
     * @var AclInterface|MockObject
     */
    private AclInterface|MockObject $acl;

    #[Test]
    public function deleteActionAdvertisesSaveDeleteRouteWithDeleteHandler(): void
    {
        $this->acl
            ->expects(once())
            ->method('getRouteFor')
            ->with(AclActionsInterface::ACCOUNT_DELETE)
            ->willReturn('account/delete');

        $helper = new AccountActionsHelper(
            $this->config,
            $this->context,
            $this->eventDispatcherStub,
            $this->createStub(TemplateInterface::class),
            $this->createStub(RequestService::class),
            new ThemeIcons(),
            $this->acl
        );

        $action = $helper->getDeleteAction();
        $data = $action->getData();

        self::assertSame('account/saveDelete', $data['action-route']);
        self::assertSame('account/delete', $data['onclick']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->config
            ->expects(once())
            ->method('getConfigData');

        $this->acl = $this->createMock(AclInterface::class);
    }
}
