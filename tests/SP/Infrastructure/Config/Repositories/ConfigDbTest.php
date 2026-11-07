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

namespace SP\Tests\Infrastructure\Config\Repositories;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Domain\Config\Models\Config as ConfigModel;
use SP\Infrastructure\Config\Repositories\Config;
use SP\Tests\DatabaseRepositoryTestCase;

/**
 * DB-backed tests for the Config repository queries.
 */
#[Group('integration')]
final class ConfigDbTest extends DatabaseRepositoryTestCase
{
    private Config $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new Config($this->db, $this->context, $this->eventDispatcher, $this->queryFactory);
    }

    #[Test]
    public function createPersistsTheParameter(): void
    {
        $this->config->create(new ConfigModel([
            'parameter' => 'test_param',
            'value' => 'test_value',
        ]));

        self::assertTrue($this->config->has('test_param'));

        $config = $this->config->getByParam('test_param')->getData(ConfigModel::class);

        self::assertSame('test_param', $config->getParameter());
        self::assertSame('test_value', $config->getValue());
    }

    #[Test]
    public function updateChangesTheValueOfAnExistingParameter(): void
    {
        $this->config->update(new ConfigModel([
            'parameter' => 'version',
            'value' => '4.0.0',
        ]));

        self::assertSame(
            '4.0.0',
            $this->config->getByParam('version')->getData(ConfigModel::class)->getValue()
        );
    }

    #[Test]
    public function getByParamReturnsTheParameter(): void
    {
        $config = $this->config->getByParam('version')->getData(ConfigModel::class);

        self::assertSame('version', $config->getParameter());
        self::assertSame('300.18053101', $config->getValue());
        self::assertSame(0, $this->config->getByParam('missing')->getNumRows());
    }

    #[Test]
    public function hasReportsTheExistenceOfTheParameter(): void
    {
        self::assertTrue($this->config->has('version'));
        self::assertFalse($this->config->has('missing'));
    }
}
