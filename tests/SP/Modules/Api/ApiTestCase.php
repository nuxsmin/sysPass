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

namespace SP\Tests\Modules\Api;

use Defuse\Crypto\Exception\CryptoException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use DI\ContainerBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use JsonException;
use Klein\Klein;
use Klein\Request;
use Klein\Response;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use SP\Core\Bootstrap\BootstrapApi;
use SP\Domain\Api\Services\ApiRequest;
use SP\Domain\Auth\Models\AuthToken;
use SP\Domain\Auth\Services\AuthToken;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Context\Context;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Database\Ports\DbStorageHandler;
use SP\Infrastructure\Database\DatabaseConnectionData;
use SP\Infrastructure\Database\MysqlHandler;
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
        AclActionsInterface::ACCOUNT_CREATE    => 'account/create',
        AclActionsInterface::ACCOUNT_VIEW      => 'account/view',
        AclActionsInterface::ACCOUNT_VIEW_PASS => 'account/viewPass',
        AclActionsInterface::ACCOUNT_EDIT_PASS => 'account/editPass',
        AclActionsInterface::ACCOUNT_EDIT      => 'account/edit',
        AclActionsInterface::ACCOUNT_SEARCH    => 'account/search',
        AclActionsInterface::ACCOUNT_DELETE    => 'account/delete',
        AclActionsInterface::CATEGORY_VIEW     => 'category/view',
        AclActionsInterface::CATEGORY_CREATE   => 'category/create',
        AclActionsInterface::CATEGORY_EDIT     => 'category/edit',
        AclActionsInterface::CATEGORY_DELETE   => 'category/delete',
        AclActionsInterface::CATEGORY_SEARCH   => 'category/search',
        AclActionsInterface::CLIENT_VIEW => 'clientService/view',
        AclActionsInterface::CLIENT_CREATE => 'clientService/create',
        AclActionsInterface::CLIENT_EDIT => 'clientService/edit',
        AclActionsInterface::CLIENT_DELETE => 'clientService/delete',
        AclActionsInterface::CLIENT_SEARCH => 'clientService/search',
        AclActionsInterface::TAG_VIEW          => 'tag/view',
        AclActionsInterface::TAG_CREATE        => 'tag/create',
        AclActionsInterface::TAG_EDIT          => 'tag/edit',
        AclActionsInterface::TAG_DELETE        => 'tag/delete',
        AclActionsInterface::TAG_SEARCH        => 'tag/search',
        AclActionsInterface::GROUP_VIEW        => 'userGroup/view',
        AclActionsInterface::GROUP_CREATE      => 'userGroup/create',
        AclActionsInterface::GROUP_EDIT        => 'userGroup/edit',
        AclActionsInterface::GROUP_DELETE      => 'userGroup/delete',
        AclActionsInterface::GROUP_SEARCH      => 'userGroup/search',
        AclActionsInterface::CONFIG_BACKUP_RUN => 'config/backup',
        AclActionsInterface::CONFIG_EXPORT_RUN => 'config/export',
    ];
    protected static ?ConfigDataInterface $configData = null;

    /**
     * @throws JsonException
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
     * @throws DependencyException
     * @throws NotFoundException
     * @throws Exception
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
                            $c->get(AuthToken::class),
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
                    DbStorageHandler::class => create(MysqlHandler::class)
                        ->constructor($databaseConnectionData),
                    ConfigDataInterface::class => static function (ConfigFileService $config) use (
                        $databaseConnectionData
                    ) {
                        $configData = $config->getConfigData()
                            ->setDbHost($databaseConnectionData->getDbHost())
                            ->setDbName($databaseConnectionData->getDbName())
                            ->setDbUser($databaseConnectionData->getDbUser())
                            ->setDbPass($databaseConnectionData->getDbPass())
                            ->setInstalled(true);

                        // Update ConfigData instance
                        $config->update($configData);

                        return $configData;
                    },
                ]
            )
            ->build();

        $context = $dic->get(Context::class);
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
        $request = $dic->get(\SP\Domain\Http\Services\Request::class);

        $bs = new BootstrapApi(self::$configData, $router, $request);
        $router->dispatch($request, null, false);

        return $router->response();
    }

    /**
     * @throws QueryException
     * @throws ConstraintException
     * @throws CryptoException
     * @throws EnvironmentIsBrokenException
     * @throws SPException
     */
    private static function createApiToken(
        AuthToken $service,
        int       $actionId
    ): AuthToken
    {
        $data = new AuthToken();
        $data->setActionId($actionId);
        $data->setCreatedBy(1);
        $data->setHash(self::AUTH_TOKEN_PASS);
        $data->setUserId(1);

        $service->create($data);

        return $data;
    }
}
