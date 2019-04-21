<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Tests\Repositories;

use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\DataModel\ConfigData;
use SP\Repositories\Config\ConfigRepository;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class ConfigRepositoryTest
 *
 * @package SP\Tests\Repositories
 */
class ConfigRepositoryTest extends DatabaseTestCase
{
    /**
     * @var ConfigRepository
     */
    private static $repository;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ContextException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        self::$dataset = 'syspass_config.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el repositorio
        self::$repository = $dic->get(ConfigRepository::class);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testCreate()
    {
        $data = new ConfigData('test', uniqid());

        $this->assertEquals(1, self::$repository->create($data));

        $result = self::$repository->getByParam($data->getParameter());
        $this->assertEquals(1, $result->getNumRows());

        $resultData = $result->getData();

        $this->assertEquals($data->getParameter(), $resultData->parameter);
        $this->assertEquals($data->getValue(), $resultData->value);

    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testHas()
    {
        $this->assertTrue(self::$repository->has('config_backup_date'));
        $this->assertFalse(self::$repository->has('test'));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testUpdate()
    {
        $data = new ConfigData('config_backup_date', time());

        $this->assertEquals(1, self::$repository->update($data));

        $result = self::$repository->getByParam($data->getParameter());
        $this->assertEquals(1, $result->getNumRows());

        $resultData = $result->getData();

        $this->assertEquals($data->getParameter(), $resultData->parameter);
        $this->assertEquals($data->getValue(), $resultData->value);

        $data = new ConfigData('test', time());

        $this->assertEquals(0, self::$repository->update($data));
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetAll()
    {
        $result = self::$repository->getAll();

        $this->assertEquals($this->conn->getRowCount('Config'), $result->getNumRows());

        $data = $result->getDataAsArray();

        $this->assertEquals('config_backup', $data[0]->parameter);
        $this->assertEquals('789ca558db6edb3810edb718c5be6c6b88ba4b45814de3a497a48d374e832cb0404191944d589654916a9216f9f71d5292633ba1a4621f628bf11cce68e6cc85bc886d2b9e2ce6ff1e1779ca97edd70c4b3c89c328fe2562c78e272f9e13785197cb0a5376c6ee276f440cdba82f273089d3625ddff2353fc97192313a79c3634b019c21c0d72afb2d0520ff0e0bb68b7107318255bf059863217601d110e00bde305162b2675768426514973396e23a93efaba22e95b710205c6b04625e1529cf588731bf4c591577f7a3e3a1a517acfab1ef2ad42b3f2f2aa9b60eadd01a963e0cc3c0de0731307ab3ac938ce76bf119df5d73762b94414e2f977610577ca35de95bd628c88e3ffbbd8f0929ea5c1eab0fada0d7fbadf439a818de9aac18597f2d29964c6c03db2f9d1792931d6963da13fdf8018bd5a42524b6701a440125a1635386b01721124436b27c9fb1d0775ddb4d9dc8f394bc6d0c124d3e14422a1904968a7b4816215ed3e435653f74788dd4a7c9a2206b2677b8d0a74665a29209b65a86101dd1901f4f2e681cf0facbf787e4e5d9fd59fc6b08db515a6145f9cd4b506061d7717c8cc9a0de36791cc752e4b0cda58f25f5724445659be230dd8dea550911475956dc327a7227c101384661fcabc5c593f9ec74cbc478f269fe5eadec66f5fee3e936c940f28bfecd6d5617b32bb5f2bad5624bfe7832bb3856ab40314baf6e74f1687ebc39d7a251bb5a9c6bf5ad35d78b995eb6e65cdd6825a8b5e77871ad97ad41ef8ecef4b2b5e87236d7cbd6a4b3937ff4b2b5e9f8b2d92a68f57e06bd0f633db7e03f9b426cd96e6f126ad0e8dab1cc8a04670b862bb2da86d2582d792e24ce7637ee6d235df7b481b294bc55ac41afe021ad18e325568f45b5ecef937a1f9ed38efe1e70b7e6f42da61b9ebf22f9db1a7e10eaa12d6ce2d5ff55d56529b4e205231593c87634d2eb43ce9feb3bc60c5288830cb28dad5f091f51311c1f25d8b6f9471b8c0558493fb6611b76054f4db5eba6addfa6a39ca6de1a4b598dd35a2c0fd9697e1f90fdc1f2a666d85dc900064054a0254dbb1d2030ca8e69934fc6eab6c13c3baae58a8d9d5414e0d05823e395f069556cba220d2d53fed53686ad1f7bd1dbf600cefbe3d3cbdbb5fc13fdbd621723906d81b755e5718dfe54a297ec7b0d9689c3fa6d8cb002411ed41597f7a322dc003a5e2957a8ff4c7fcb15bbddee5947fafdef4878c93be2a08e38cf6ff630f42e8f14dceeb44fb83efee412d8961336ec6765d16d51d105cef404e28383528a2c2f24d84a129c467e40ad10852ea3b6cd984d42eaf98107bf92089e228252db09e02f85f19da6fd6eaa9880095f1c89635ced9416a3bc6042f02257436c51cba6253683ac318e824b768e731d2f7030cbfb4771257eb562cd4c851cc51ac92a8eb3d74956b3fe02dccc92d7d009c0c62e851ccb9aa2d0f21c64a181131f7c24d0ac86363006fab9f3a891534af894677264ab684e894b56ef1f5f8dbe50f24d433f400c4ce2f0d84c18f0be8ee7f8be1a795c23a83ba67cdc806d8745d2f8320926ebbafcb6da99fa49845c3b0ac324a211c28e2274e83861905a5ee49000239be1d4c376bf97d85d0935707f633742294e08248b150528f1121b411e3976e0fbd8713d0b0784fa76d2dfe05652964f8aa5d1f95059f61b9cd55b901bf14b68b9928d2ec80de8e901da18aa06d0f5084f4fabe60b80768e52cde8aad0d11d6e940d8316b8b508dca3e7b3fe12c47252dd9772d15496edcdc4d039f7b4ce323de41c1138678a2de78c633486960fa32827072c758df5608b8069a1382f963c3f24f8307456a8ea3feac24988e2b93b1af35dd023e0e08ac69caeadf34eee4a5e3d4957a3a23d547781812cc70f2d28fe0fff0152be6bea', $data[0]->value);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testGetByParam()
    {
        $result = self::$repository->getByParam('config_backup_date');
        $this->assertEquals(1, $result->getNumRows());

        $resultData = $result->getData();

        $this->assertEquals('config_backup_date', $resultData->parameter);
        $this->assertEquals('1531353681', $resultData->value);

        $result = self::$repository->getByParam('test');
        $this->assertEquals(0, $result->getNumRows());
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     */
    public function testDeleteByParam()
    {
        $this->assertEquals(1, self::$repository->deleteByParam('tempmaster_passtime'));
        $this->assertEquals(0, self::$repository->deleteByParam('test'));
    }
}
