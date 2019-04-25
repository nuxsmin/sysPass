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

namespace SP\Tests\Services\Account;

use Closure;
use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\Core\Context\ContextInterface;
use SP\Core\Exceptions\ConstraintException;
use SP\Core\Exceptions\QueryException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\UserPreferencesData;
use SP\Mvc\Model\QueryCondition;
use SP\Services\Account\AccountSearchFilter;
use SP\Services\Account\AccountSearchItem;
use SP\Services\Account\AccountSearchService;
use SP\Services\User\UserLoginResponse;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Storage\Database\QueryResult;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class AccountSearchServiceTest
 *
 * @package SP\Tests\Services
 */
class AccountSearchServiceTest extends DatabaseTestCase
{
    /**
     * @var AccountSearchService
     */
    private static $service;
    /**
     * @var Closure
     */
    private static $setupUser;

    /**
     * @throws NotFoundException
     * @throws ContextException
     * @throws DependencyException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        self::$dataset = 'syspass_accountSearch.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);

        // Inicializar el servicio
        self::$service = $dic->get(AccountSearchService::class);

        $context = $dic->get(ContextInterface::class);

        self::$setupUser = function (UserLoginResponse $response) use ($context) {
            $response->setLastUpdate(time());

            $context->setUserData($response);
        };
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testProcessSearchResultsForUserAdmin()
    {
        $userData = new UserLoginResponse();
        $userData->setId(1);
        $userData->setUserGroupId(1);
        $userData->setIsAdminApp(1);
        $userData->setPreferences(new UserPreferencesData());

        self::$setupUser->call($this, $userData);

        $this->checkCategoryById(1, [1]);
        $this->checkNonExistantCategory();
        $this->checkClientById(1, [1]);
        $this->checkClientById(2, [2]);
        $this->checkClientAndCategory(2, 2, [2]);
        $this->checkClientAndCategory(2, 1, [2, 1], QueryCondition::CONDITION_OR);
        $this->checkNonExistantClient();
        $this->checkString('apple.com', [2]);
        $this->checkString('aaaa', [1]);
        $this->checkString('github');
        $this->checkString('google', [1]);
        $this->checkString('slack');
        $this->checkString('is:private');
        $this->checkString('not:private', [2, 1]);
        $this->checkString('user:admin', [2, 1]);
        $this->checkString('user:user_a', [2, 1]);
        $this->checkString('owner:user_a');
        $this->checkString('owner:user_b');
        $this->checkString('group:Admins', [2, 1]);
        $this->checkString('group:Usuarios', [2]);
        $this->checkString('maingroup:Admins', [2, 1]);
        $this->checkString('maingroup:Usuarios');
        $this->checkString('file:"Clock 3"', [2]);
        $this->checkString('file:"syspass"', [1]);
        $this->checkString('id:1', [1]);
        $this->checkString('id:3');
        $this->checkString('name_regex:"le$"', [2, 1]);
        $this->checkString('name_regex:"^Gi"');
        $this->checkString('name_regex:"^Go"', [1]);
        $this->checkString('name_regex:"[eu]+"', [2, 1]);
        $this->checkString('name_regex:"[eu]+" category:"Web"', [1]);
        $this->checkString('name_regex:"[eu]+" category:"Web" client:"Google"', [1]);
        $this->checkString('name_regex:"[eu]+" category:"Web" client:"Google" op:or', [2, 1]);
        $this->checkString('name_regex:"[eu]+" category:"SSH"');
        $this->checkFavorites(1, [1]);
        $this->checkTags([1, 3], [2]);
        $this->checkTags([1, 3], [2, 1], QueryCondition::CONDITION_OR);
        $this->checkTags([2], [1]);
    }

    /**
     * @param int   $id Category Id
     * @param array $accountsId
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    private function checkCategoryById($id, array $accountsId = [])
    {
        $rows = count($accountsId);

        $searchFilter = new AccountSearchFilter();
        $searchFilter->setLimitCount(10);
        $searchFilter->setCategoryId($id);

        // Comprobar un Id de categoría
        $result = self::$service->processSearchResults($searchFilter);
        $this->assertInstanceOf(QueryResult::class, $result);

        if ($rows > 0) {
            /** @var AccountSearchItem[] $data */
            $data = $result->getDataAsArray();

            $i = 0;

            foreach ($data as $searchItem) {
                $this->assertEquals($accountsId[$i], $searchItem->getAccountSearchVData()->getId());
                $i++;
            }
        }
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    private function checkNonExistantCategory()
    {
        $searchFilter = new AccountSearchFilter();
        $searchFilter->setLimitCount(10);
        $searchFilter->setCategoryId(10);

        $result = self::$service->processSearchResults($searchFilter);
        $this->assertInstanceOf(QueryResult::class, $result);
        $this->assertEquals(0, $result->getNumRows());
        $this->assertCount(0, $result->getDataAsArray());
    }

    /**
     * @param int   $id Client Id
     * @param array $accountsId
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    private function checkClientById($id, array $accountsId = [])
    {
        $rows = count($accountsId);

        $searchFilter = new AccountSearchFilter();
        $searchFilter->setLimitCount(10);
        $searchFilter->setClientId($id);

        $result = self::$service->processSearchResults($searchFilter);
        $this->assertInstanceOf(QueryResult::class, $result);
        $this->assertEquals($rows, $result->getNumRows());

        if ($rows > 0) {
            /** @var AccountSearchItem[] $data */
            $data = $result->getDataAsArray();

            $i = 0;

            foreach ($data as $searchItem) {
                $this->assertEquals($accountsId[$i], $searchItem->getAccountSearchVData()->getId());
                $i++;
            }
        }
    }

    /**
     * @param int    $clientId
     * @param int    $categoryId
     * @param array  $accountsId
     * @param string $operator
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    private function checkClientAndCategory($clientId, $categoryId, array $accountsId = [], $operator = null)
    {
        $rows = count($accountsId);

        $searchFilter = new AccountSearchFilter();
        $searchFilter->setLimitCount(10);
        $searchFilter->setFilterOperator($operator);
        $searchFilter->setClientId($clientId);
        $searchFilter->setCategoryId($categoryId);

        $result = self::$service->processSearchResults($searchFilter);
        $this->assertInstanceOf(QueryResult::class, $result);
        $this->assertEquals($rows, $result->getNumRows());

        $i = 0;

        /** @var AccountSearchItem $item */
        foreach ($result->getDataAsArray() as $item) {
            $this->assertEquals($accountsId[$i], $item->getAccountSearchVData()->getId());
            $i++;
        }
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    private function checkNonExistantClient()
    {
        $searchFilter = new AccountSearchFilter();
        $searchFilter->setLimitCount(10);
        $searchFilter->setClientId(10);

        $result = self::$service->processSearchResults($searchFilter);
        $this->assertInstanceOf(QueryResult::class, $result);
        $this->assertEquals(0, $result->getNumRows());
        $this->assertCount(0, $result->getDataAsArray());
    }

    /**
     * @param string $string
     * @param array  $accountsId
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    private function checkString($string, array $accountsId = [])
    {
        $rows = count($accountsId);

        $searchFilter = new AccountSearchFilter();
        $searchFilter->setLimitCount(10);
        $searchFilter->setTxtSearch($string);

        $result = self::$service->processSearchResults($searchFilter);
        $this->assertInstanceOf(QueryResult::class, $result);

        $this->assertEquals($rows, $result->getNumRows());

        $i = 0;

        /** @var AccountSearchItem $item */
        foreach ($result->getDataAsArray() as $item) {
            $this->assertEquals($accountsId[$i], $item->getAccountSearchVData()->getId());

            $i++;
        }
    }

    /**
     * @param int   $rows Expected rows
     * @param array $accountsId
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    private function checkFavorites($rows, array $accountsId = [])
    {
        $searchFilter = new AccountSearchFilter();
        $searchFilter->setLimitCount(10);
        $searchFilter->setSearchFavorites(true);

        $result = self::$service->processSearchResults($searchFilter);

        $this->assertInstanceOf(QueryResult::class, $result);
        $this->assertEquals($rows, $result->getNumRows());

        $i = 0;

        /** @var AccountSearchItem $item */
        foreach ($result->getDataAsArray() as $item) {
            $this->assertEquals($accountsId[$i], $item->getAccountSearchVData()->getId());
            $i++;
        }
    }

    /**
     * @param array  $tagsId
     * @param array  $accountsId
     * @param string $operator
     *
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    private function checkTags(array $tagsId, array $accountsId = [], $operator = null)
    {
        $rows = count($accountsId);

        $searchFilter = new AccountSearchFilter();
        $searchFilter->setLimitCount(10);
        $searchFilter->setFilterOperator($operator);
        $searchFilter->setTagsId($tagsId);

        $result = self::$service->processSearchResults($searchFilter);
        $this->assertInstanceOf(QueryResult::class, $result);

        /** @var AccountSearchItem[] $data */
        $data = $result->getDataAsArray();

        $this->assertEquals($rows, $result->getNumRows());
        $this->assertCount($rows, $data);

        $i = 0;

        foreach ($data as $item) {
            $this->assertEquals($accountsId[$i], $item->getAccountSearchVData()->getId());
            $i++;
        }
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testProcessSearchResultsForUserDemo()
    {
        AccountSearchItem::$publicLinkEnabled = false;

        $userData = new UserLoginResponse();
        $userData->setId(2);
        $userData->setUserGroupId(2);
        $userData->setPreferences(new UserPreferencesData());

        self::$setupUser->call($this, $userData);

        $this->checkCategoryById(1, [1]);
        $this->checkNonExistantCategory();
        $this->checkClientById(1, [1]);
        $this->checkClientById(2, [2]);
        $this->checkClientAndCategory(2, 2, [2]);
        $this->checkClientAndCategory(2, 1, [2, 1], QueryCondition::CONDITION_OR);
        $this->checkNonExistantClient();
        $this->checkString('apple.com', [2]);
        $this->checkString('github');
        $this->checkString('google', [1]);
        $this->checkString('slack');
        $this->checkString('is:private');
        $this->checkString('not:private', [2, 1]);
        $this->checkString('user:admin', [2, 1]);
        $this->checkString('user:user_a', [2, 1]);
        $this->checkString('owner:user_a');
        $this->checkString('owner:user_b');
        $this->checkString('group:Admins', [2, 1]);
        $this->checkString('group:Usuarios', [2]);
        $this->checkString('maingroup:Admins', [2, 1]);
        $this->checkString('maingroup:Usuarios');
        $this->checkString('file:"Clock 3"', [2]);
        $this->checkString('file:"syspass"', [1]);
        $this->checkString('id:1', [1]);
        $this->checkString('id:3');
        $this->checkFavorites(1, [2]);
        $this->checkTags([1, 3], [2]);
        $this->checkTags([1, 3], [2, 1], QueryCondition::CONDITION_OR);
        $this->checkTags([2], [1]);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testProcessSearchResultsForUserA()
    {
        AccountSearchItem::$publicLinkEnabled = false;

        $userData = new UserLoginResponse();
        $userData->setId(3);
        $userData->setUserGroupId(3);
        $userData->setPreferences(new UserPreferencesData());

        self::$setupUser->call($this, $userData);

        $this->checkCategoryById(1, [1]);
        $this->checkNonExistantCategory();
        $this->checkClientById(1, [1]);
        $this->checkClientById(2, [2, 3]);
        $this->checkClientAndCategory(2, 2, [2, 3]);
        $this->checkClientAndCategory(2, 1, [2, 3, 1], QueryCondition::CONDITION_OR);
        $this->checkNonExistantClient();
        $this->checkString('apple.com', [2]);
        $this->checkString('github', [3]);
        $this->checkString('google', [1]);
        $this->checkString('slack', [4]);
        $this->checkString('is:private', [3, 4]);
        $this->checkString('user:admin', [2, 1]);
        $this->checkString('user:user_a', [2, 3, 1, 4]);
        $this->checkString('owner:user_a', [3, 4]);
        $this->checkString('owner:user_b');
        $this->checkString('group:Admins', [2, 1]);
        $this->checkString('group:Usuarios', [2, 3, 4]);
        $this->checkString('maingroup:Admins', [2, 1]);
        $this->checkString('maingroup:Usuarios', [3, 4]);
        $this->checkString('file:"Clock 3"', [2]);
        $this->checkString('file:"syspass"', [1]);
        $this->checkString('id:1', [1]);
        $this->checkString('id:3', [3]);
        $this->checkFavorites(2, [2, 1]);
        $this->checkTags([1, 3], [2]);
        $this->checkTags([1, 3], [2, 1], QueryCondition::CONDITION_OR);
        $this->checkTags([2], [1]);
    }

    /**
     * @throws ConstraintException
     * @throws QueryException
     * @throws SPException
     */
    public function testProcessSearchResultsForUserB()
    {
        AccountSearchItem::$publicLinkEnabled = false;

        $userData = new UserLoginResponse();
        $userData->setId(4);
        $userData->setUserGroupId(3);
        $userData->setPreferences(new UserPreferencesData());

        self::$setupUser->call($this, $userData);

        $this->checkCategoryById(1);
        $this->checkNonExistantCategory();
        $this->checkClientById(1);
        $this->checkClientById(2, [2]);
        $this->checkClientAndCategory(2, 2, [2]);
        $this->checkClientAndCategory(2, 1, [2], QueryCondition::CONDITION_OR);
        $this->checkNonExistantClient();
        $this->checkString('apple.com', [2]);
        $this->checkString('github');
        $this->checkString('google');
        $this->checkString('slack', [4]);
        $this->checkString('is:private', [4]);
        $this->checkString('not:private', [2]);
        $this->checkString('user:admin', [2]);
        $this->checkString('user:user_a', [2, 4]);
        $this->checkString('owner:user_a', [4]);
        $this->checkString('owner:user_b');
        $this->checkString('group:Admins', [2]);
        $this->checkString('group:Usuarios', [2, 4]);
        $this->checkString('maingroup:Admins', [2]);
        $this->checkString('maingroup:Usuarios', [4]);
        $this->checkString('file:"Clock 3"', [2]);
        $this->checkString('file:"syspass"');
        $this->checkString('id:1');
        $this->checkString('id:3');
        $this->checkFavorites(0);
        $this->checkTags([1, 3], [2]);
        $this->checkTags([1, 3], [2], QueryCondition::CONDITION_OR);
        $this->checkTags([2]);
    }
}
