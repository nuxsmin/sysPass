<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Tests\Modules\Cli;

use DI\ContainerBuilder;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Class CliTestCase
 *
 * @package SP\Tests\Modules\Cli
 */
abstract class CliTestCase extends TestCase
{
    /**
     * @var ContainerInterface
     */
    protected static $dic;

    /**
     * This method is called before the first test of this test class is run.
     *
     * @throws Exception
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $builder = new ContainerBuilder();
        $builder->addDefinitions(
            APP_ROOT . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'Definitions.php',
            MODULES_PATH . DIRECTORY_SEPARATOR . 'cli' . DIRECTORY_SEPARATOR . 'definitions.php'
        );

        self::$dic = $builder->build();
    }
}