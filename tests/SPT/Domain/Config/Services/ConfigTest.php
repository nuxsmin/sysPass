<?php
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

namespace SPT\Domain\Config\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use SP\DataModel\Dto\ConfigRequest;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigRepository;
use SP\Domain\Config\Services\Config;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Infrastructure\Database\QueryResult;
use SPT\Generators\ConfigGenerator;
use SPT\UnitaryTestCase;

/**
 * Class ConfigTest
 *
 */
#[Group('unitary')]
class ConfigTest extends UnitaryTestCase
{

    private MockObject|ConfigRepository $configRepository;
    private Config                      $configService;

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCreate()
    {
        $config = ConfigGenerator::factory()->buildConfig();

        $queryResult = new QueryResult();
        $queryResult->setLastId(self::$faker->randomNumber());

        $this->configRepository
            ->expects(self::once())
            ->method('create')
            ->with($config)
            ->willReturn($queryResult);

        $out = $this->configService->create($config);

        $this->assertEquals($queryResult->getLastId(), $out);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSaveWithNoExistingParameter()
    {
        $config = ConfigGenerator::factory()->buildConfig();

        $this->configRepository
            ->expects(self::once())
            ->method('has')
            ->with($config->getParameter())
            ->willReturn(false);

        $queryResult = new QueryResult();
        $queryResult->setLastId(self::$faker->randomNumber());

        $this->configRepository
            ->expects(self::once())
            ->method('create')
            ->with($config)
            ->willReturn($queryResult);

        $this->configService->save($config->getParameter(), $config->getValue());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testSaveWithExistingParameter()
    {
        $config = ConfigGenerator::factory()->buildConfig();

        $this->configRepository
            ->expects(self::once())
            ->method('has')
            ->with($config->getParameter())
            ->willReturn(true);

        $this->configRepository
            ->expects(self::once())
            ->method('update')
            ->with($config)
            ->willReturn(new QueryResult([1]));

        $this->configService->save($config->getParameter(), $config->getValue());
    }

    /**
     * @throws NoSuchItemException
     * @throws ServiceException
     * @throws SPException
     */
    public function testGetByParam()
    {
        $parameter = self::$faker->colorName();

        $config = ConfigGenerator::factory()->buildConfig();

        $this->configRepository
            ->expects(self::once())
            ->method('getByParam')
            ->with($parameter)
            ->willReturn(new QueryResult([$config]));

        $out = $this->configService->getByParam($parameter);

        $this->assertEquals($config->getValue(), $out);
    }

    /**
     * @throws NoSuchItemException
     * @throws ServiceException
     * @throws SPException
     */
    public function testGetByParamWithDefaultValue()
    {
        $parameter = self::$faker->colorName();

        $config = ConfigGenerator::factory()->buildConfig()->mutate(['value' => null]);

        $this->configRepository
            ->expects(self::once())
            ->method('getByParam')
            ->with($parameter)
            ->willReturn(new QueryResult([$config]));

        $out = $this->configService->getByParam($parameter, 'test');

        $this->assertEquals('test', $out);
    }

    /**
     * @throws NoSuchItemException
     * @throws ServiceException
     * @throws SPException
     */
    public function testGetByParamWithNoFound()
    {
        $parameter = self::$faker->colorName();

        $this->configRepository
            ->expects(self::once())
            ->method('getByParam')
            ->with($parameter)
            ->willReturn(new QueryResult([]));

        $this->expectException(NoSuchItemException::class);
        $this->expectExceptionMessage(sprintf('Parameter not found (%s)', $parameter));

        $this->configService->getByParam($parameter);
    }

    /**
     * @throws NoSuchItemException
     * @throws ServiceException
     * @throws SPException
     */
    public function testGetByParamWithException()
    {
        $parameter = self::$faker->colorName();

        $this->configRepository
            ->expects(self::once())
            ->method('getByParam')
            ->with($parameter)
            ->willThrowException(new RuntimeException('test'));

        $this->expectException(SPException::class);
        $this->expectExceptionMessage('test');

        $this->configService->getByParam($parameter);
    }

    /**
     * @throws ServiceException
     */
    public function testSaveBatch()
    {
        $this->configRepository
            ->expects(self::once())
            ->method('transactionAware')
            ->with(self::withResolveCallableCallback());

        $this->configRepository
            ->expects(self::exactly(3))
            ->method('has')
            ->with(self::anything())
            ->willReturn(true);

        $this->configRepository
            ->expects(self::exactly(3))
            ->method('update')
            ->with(self::anything())
            ->willReturn(new QueryResult([1]));

        $configRequest = new ConfigRequest();
        $configRequest->add(self::$faker->colorName, self::$faker->text);
        $configRequest->add(self::$faker->colorName, self::$faker->text);
        $configRequest->add(self::$faker->colorName, self::$faker->text);

        $this->configService->saveBatch($configRequest);
    }

    /**
     * @throws ServiceException
     */
    public function testSaveBatchWithException()
    {
        $this->configRepository
            ->expects(self::once())
            ->method('transactionAware')
            ->with(self::anything())
            ->willThrowException(new RuntimeException('test'));

        $this->configRepository
            ->expects(self::never())
            ->method('has')
            ->with(self::anything());

        $this->configRepository
            ->expects(self::never())
            ->method('update')
            ->with(self::anything());

        $configRequest = new ConfigRequest();
        $configRequest->add(self::$faker->colorName, self::$faker->text);
        $configRequest->add(self::$faker->colorName, self::$faker->text);
        $configRequest->add(self::$faker->colorName, self::$faker->text);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('test');

        $this->configService->saveBatch($configRequest);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->configRepository = $this->createMock(ConfigRepository::class);

        $this->configService = new Config($this->application, $this->configRepository);
    }
}
