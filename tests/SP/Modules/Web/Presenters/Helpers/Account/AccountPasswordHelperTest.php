<?php
/*
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

declare(strict_types=1);

namespace SP\Tests\Modules\Web\Presenters\Helpers\Account;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Account\Adapters\AccountPassItemWithIdAndName;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Acl\AclInterface;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Crypt\Ports\MasterKeyStore;
use SP\Domain\Crypt\Ports\MasterPassService;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\Image\Ports\ImageService;
use SP\Modules\Web\Mvc\View\TemplateInterface;
use SP\Modules\Web\Presenters\Helpers\Account\AccountPasswordHelper;
use SP\Tests\UnitaryTestCase;

#[Group('unitary')]
final class AccountPasswordHelperTest extends UnitaryTestCase
{
    #[Test]
    public function passwordDisplayRejectsAnUnvalidatedMasterKey(): void
    {
        $context = $this->createStub(\SP\Domain\Core\Context\SessionContext::class);
        $context->method('getUserData')->willReturn(\SP\Domain\User\Dtos\UserDto::fromArray(['lastUpdateMPass' => 1]));
        $vault = $this->createStub(\SP\Domain\Core\Crypt\VaultInterface::class);
        $vault->method('getData')->willReturn('synthetic key');
        $context->method('getVault')->willReturn($vault);
        $acl = $this->createStub(AclInterface::class);
        $acl->method('checkUserAccess')->willReturn(true);
        $masterPass = $this->createStub(MasterPassService::class);
        $masterPass->method('checkUserUpdateMPass')->willReturn(true);
        $store = $this->createMock(MasterKeyStore::class);
        $store->expects(self::once())->method('get')->willThrowException(ServiceException::error('stale key'));
        $crypt = $this->createMock(CryptInterface::class);
        $crypt->expects(self::never())->method('decrypt');
        $helper = new AccountPasswordHelper(
            $this->config, $context, $this->eventDispatcherStub,
            $this->createStub(TemplateInterface::class), $this->createStub(RequestService::class),
            $acl, $this->createStub(ImageService::class), $masterPass, $crypt, $store
        );
        $this->expectException(ServiceException::class);
        $helper->getPasswordClear(new AccountPassItemWithIdAndName(['pass' => 'ciphertext', 'key' => 'key']));
    }
}
