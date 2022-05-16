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

namespace SP\Tests\Modules\Api;

use DI\ContainerBuilder;
use Klein\Klein;
use Klein\Request;
use Klein\Response;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use SP\Config\Config;
use SP\Config\ConfigDataInterface;
use SP\Core\Acl\ActionsInterface;
use SP\Core\Bootstrap\BootstrapApi;
use SP\Core\Context\ContextInterface;
use SP\DataModel\AuthTokenData;
use SP\Services\Api\ApiRequest;
use SP\Services\AuthToken\AuthTokenService;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Storage\Database\DBStorageInterface;
use SP\Storage\Database\MySQLHandler;
use SP\Tests\DatabaseTrait;
use stdClass;
use function DI\create;
use const SP\Tests\APP_DEFINITIONS_FILE;

define('APP_MODULE', 'api');

/**
 * Class WebTestCase
 */
abstract class ApiTestCase extends TestCase
{
    private const AUTH_TOKEN_PASS = 123456;

    use DatabaseTrait;

    private const METHOD_ACTION_MAP = [
        ActionsInterface::ACCOUNT_CREATE    => 'account/create',
        ActionsInterface::ACCOUNT_VIEW      => 'account/view',
        ActionsInterface::ACCOUNT_VIEW_PASS => 'account/viewPass',
        ActionsInterface::ACCOUNT_EDIT_PASS => 'account/editPass',
        ActionsInterface::ACCOUNT_EDIT      => 'account/edit',
        ActionsInterface::ACCOUNT_SEARCH    => 'account/search',
        ActionsInterface::ACCOUNT_DELETE    => 'account/delete',
        ActionsInterface::CATEGORY_VIEW     => 'category/view',
        ActionsInterface::CATEGORY_CREATE   => 'category/create',
        ActionsInterface::CATEGORY_EDIT     => 'category/edit',
        ActionsInterface::CATEGORY_DELETE   => 'category/delete',
        ActionsInterface::CATEGORY_SEARCH   => 'category/search',
        ActionsInterface::CLIENT_VIEW       => 'client/view',
        ActionsInterface::CLIENT_CREATE     => 'client/create',
        ActionsInterface::CLIENT_EDIT       => 'client/edit',
        ActionsInterface::CLIENT_DELETE     => 'client/delete',
        ActionsInterface::CLIENT_SEARCH     => 'client/search',
        ActionsInterface::TAG_VIEW          => 'tag/view',
        ActionsInterface::TAG_CREATE        => 'tag/create',
        ActionsInterface::TAG_EDIT          => 'tag/edit',
        ActionsInterface::TAG_DELETE        => 'tag/delete',
        ActionsInterface::TAG_SEARCH        => 'tag/search',
        ActionsInterface::GROUP_VIEW        => 'userGroup/view',
        ActionsInterface::GROUP_CREATE      => 'userGroup/create',
        ActionsInterface::GROUP_EDIT        => 'userGroup/edit',
        ActionsInterface::GROUP_DELETE      => 'userGroup/delete',
        ActionsInterface::GROUP_SEARCH      => 'userGroup/search',
        ActionsInterface::CONFIG_BACKUP_RUN => 'config/backup',
        ActionsInterface::CONFIG_EXPORT_RUN => 'config/export',
    ];
    protected static ?ConfigDataInterface $configData = null;

    /**
     * @throws \JsonException
     */
    protected static function processJsonResponse(
        Response $response,
        bool $exceptionOnError = true
    ): stdClass {
        if ($exceptionOnError && $response->status()->getCode() !== 200) {
            throw new RuntimeException($response->status()->getMessage());
        }

        return json_decode(
            $response->body(),
            false,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    protected function setUp(): void
    {
        self::loadFixtures();

        self::truncateTable('AuthToken');

        parent::setUp();
    }

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Exception
     */
    final protected function callApi(int $actionId, array $params): Response
    {
        $databaseConnectionData = DatabaseConnectionData::getFromEnvironment();

        $dic = (new ContainerBuilder())
            ->addDefinitions(
                APP_DEFINITIONS_FILE,
                [
                    ApiRequest::class          => function (ContainerInterface $c) use ($actionId, $params) {
                        $token = self::createApiToken(
                            $c->get(AuthTokenService::class),
                            $actionId
                        );

                        $data = [
                            'jsonrpc' => '2.0',
                            'method'  => self::METHOD_ACTION_MAP[$actionId],
                            'params'  => array_merge(
                                [
                                    'authToken' => $token->getToken(),
                                    'tokenPass' => self::AUTH_TOKEN_PASS,
                                ],
                                $params
                            ),
                            'id'      => 1,
                        ];

                        return new ApiRequest(json_encode($data, JSON_THROW_ON_ERROR));
                    },
                    DBStorageInterface::class  => create(MySQLHandler::class)
                        ->constructor($databaseConnectionData),
                    ConfigDataInterface::class => static function (Config $config) use ($databaseConnectionData) {
                        $configData = $config->getConfigData()
                            ->setDbHost($databaseConnectionData->getDbHost())
                            ->setDbName($databaseConnectionData->getDbName())
                            ->setDbUser($databaseConnectionData->getDbUser())
                            ->setDbPass($databaseConnectionData->getDbPass())
                            ->setInstalled(true);

                        // Update ConfigData instance
                        $config->updateConfig($configData);

                        return $configData;
                    },
                ]
            )
            ->build();

        $context = $dic->get(ContextInterface::class);
        $context->initialize();
        $context->setTrasientKey('_masterpass', '12345678900');

        self::$configData = $dic->get(ConfigDataInterface::class);

        $request = new Request(
            [],
            [],
            [],
            [
                'HTTP_HOST'            => 'localhost:8080',
                'HTTP_ACCEPT'          => 'application/json, text/javascript, */*; q=0.01',
                'HTTP_USER_AGENT'      => 'Mozilla/5.0 (X11; Linux x86_64; rv:78.0) Gecko/20100101 Firefox/78.0',
                'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.5',
                'HTTP_ACCEPT_ENCODING' => 'gzip, deflate',
                'REQUEST_URI'          => '/api.php',
                'REQUEST_METHOD'       => 'POST',
                'HTTP_CONTENT_TYPE'    => 'application/json',
            ],
            [],
            null
        );

        $router = $dic->get(Klein::class);
        $request = $dic->get(\SP\Http\Request::class);

        $bs = new BootstrapApi(self::$configData, $router, $request);
        $router->dispatch($request, null, false);

        return $router->response();
    }

    /**
     * @throws \SP\Core\Exceptions\QueryException
     * @throws \SP\Core\Exceptions\ConstraintException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
     * @throws \SP\Core\Exceptions\SPException
     */
    private static function createApiToken(
        AuthTokenService $service,
        int $actionId
    ): AuthTokenData {
        $data = new AuthTokenData();
        $data->setActionId($actionId);
        $data->setCreatedBy(1);
        $data->setHash(self::AUTH_TOKEN_PASS);
        $data->setUserId(1);

        $service->create($data);

        return $data;
    }
}