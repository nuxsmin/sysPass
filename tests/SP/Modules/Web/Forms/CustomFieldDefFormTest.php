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

namespace SP\Tests\Modules\Web\Forms;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Domain\Http\Ports\RequestService;
use SP\Modules\Web\Forms\CustomFieldDefForm;
use SP\Tests\UnitaryTestCase;

/**
 * Class CustomFieldDefFormTest
 */
#[Group('unitary')]
class CustomFieldDefFormTest extends UnitaryTestCase
{
    private RequestService|MockObject $request;

    #[Test]
    public function missingFieldNameRaisesTranslatedValidationError(): void
    {
        $this->request->method('analyzeString')->willReturn('');
        $this->request->method('analyzeInt')->willReturn(0);
        $this->request->method('analyzeBool')->willReturn(false);

        $form = new CustomFieldDefForm($this->config, $this->context, $this->request);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Field name not set');

        $form->validateFor(AclActionsInterface::CUSTOMFIELD_CREATE);
    }

    public static function invalidRequiredIdProvider(): array
    {
        return [
            'zero type' => [0, 1, 'Field type not set'],
            'zero module' => [1, 0, 'Field module not set'],
        ];
    }

    #[Test]
    #[DataProvider('invalidRequiredIdProvider')]
    public function zeroRequiredIdRaisesValidationError(int $type, int $module, string $message): void
    {
        $this->request->method('analyzeString')->willReturn('name');
        $this->request->method('analyzeInt')->willReturnCallback(
            static fn(string $param): int => $param === 'type' ? $type : $module
        );
        $this->request->method('analyzeBool')->willReturn(false);

        $form = new CustomFieldDefForm($this->config, $this->context, $this->request);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($message);

        $form->validateFor(AclActionsInterface::CUSTOMFIELD_CREATE);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->request = $this->createMock(RequestService::class);
    }
}
