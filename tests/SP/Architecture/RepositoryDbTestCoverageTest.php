<?php

declare(strict_types=1);

namespace SP\Tests\Architecture;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Enforces that every concrete repository has DB-backed coverage.
 *
 * Both allowlists are ratchets: an entry that is no longer needed fails the test.
 */
#[Group('unitary')]
final class RepositoryDbTestCoverageTest extends TestCase
{
    private const PLUMBING = [
        'BaseRepository',
        'SimpleRepository',
        'Query',
        'RepositoryItemTrait',
    ];

    /**
     * Repositories that do not have a *DbTest class yet.
     *
     * @var string[]
     */
    private const MISSING_CLASS_ALLOWLIST = [];

    /**
     * Public methods of repositories that already have a *DbTest class but are not
     * exercised by it yet. Class-allowlisted repositories are tracked by the class
     * allowlist instead.
     *
     * @var array<string, string[]>
     */
    private const UNCOVERED_METHOD_ALLOWLIST = [
        'Account' => [
            'createRemoved',
            'deleteByIdBatch',
            'editPassword',
            'getAccountsPassData',
            'getAll',
            'getAllForExport',
            'getByIdEnriched',
            'getDataForLink',
            'getForUser',
            'getLinked',
            'getPasswordForId',
            'getPasswordHistoryForId',
            'getTotalNumAccounts',
            'incrementDecryptCounter',
            'incrementViewCounter',
            'isVisible',
            'restoreModified',
            'search',
            'updateBulk',
            'updatePassword',
        ],
        'AccountFile' => [
            'create',
            'delete',
            'deleteByIdBatch',
            'getByAccountId',
        ],
        'AccountHistory' => [
            'create',
            'delete',
            'deleteByAccountIdBatch',
            'deleteByIdBatch',
            'getAccountsPassData',
            'getHistoryForAccount',
            'updatePassword',
        ],
        'AccountSearch' => [
            'withFilterForAccountId',
            'withFilterForAccountNameRegex',
            'withFilterForCategory',
            'withFilterForClient',
            'withFilterForFile',
            'withFilterForGroup',
            'withFilterForIsExpired',
            'withFilterForIsNotExpired',
            'withFilterForIsNotPrivate',
            'withFilterForIsPrivate',
            'withFilterForMainGroup',
            'withFilterForOwner',
            'withFilterForUser',
        ],
        'AccountToUser' => [
            'addByType',
            'deleteByAccountId',
            'deleteTypeByAccountId',
        ],
        'AccountToUserGroup' => [
            'addByType',
            'deleteByAccountId',
            'deleteByUserGroupId',
            'deleteTypeByAccountId',
            'getUserGroupsByUserGroupId',
        ],
        'AuthToken' => [
            'create',
            'delete',
            'deleteByIdBatch',
            'getAll',
            'getById',
            'getTokenByToken',
            'getTokenByUserId',
            'refreshTokenByUserId',
            'refreshVaultByUserId',
            'update',
        ],
        'CustomFieldData' => [
            'checkExists',
            'create',
            'deleteBatch',
            'getAll',
            'getAllEncrypted',
            'update',
        ],
        'CustomFieldDefinition' => [
            'create',
            'delete',
            'deleteByIdBatch',
            'getAll',
            'update',
        ],
        'Eventlog' => [
            'clear',
            'create',
        ],
        'ItemPreset' => [
            'delete',
            'deleteByIdBatch',
            'getAll',
            'getByFilter',
        ],
        'Notification' => [
            'create',
            'delete',
            'deleteAdmin',
            'deleteAdminBatch',
            'deleteByIdBatch',
            'getAll',
            'getAllActiveForAdmin',
            'getAllActiveForUserId',
            'getAllForUserId',
            'getById',
            'getByIdBatch',
            'getForUserIdByDate',
            'setCheckedById',
            'update',
        ],
        'PublicLink' => [
            'addLinkView',
            'create',
            'delete',
            'deleteByIdBatch',
            'getByHash',
            'getById',
            'getHashForItem',
            'refresh',
            'search',
            'update',
        ],
        'Track' => [
            'add',
            'clear',
            'getTracksForClientFromTime',
            'unlock',
        ],
        'User' => [
            'create',
            'delete',
            'deleteByIdBatch',
            'getAll',
            'getUserEmail',
            'getUserEmailById',
            'update',
            'updateLastLoginById',
            'updateMasterPassById',
            'updatePreferencesById',
        ],
        'UserGroup' => [
            'create',
            'delete',
            'deleteByIdBatch',
            'getAll',
            'getById',
            'getByName',
            'search',
            'update',
        ],
        'UserToUserGroup' => [
            'add',
            'delete',
        ],
    ];

    #[Test]
    public function everyRepositoryHasADbTestClass(): void
    {
        $missing = [];
        $stale = [];

        foreach (self::repositories() as $name => $repository) {
            if ($repository['testFile'] === null) {
                if (!in_array($name, self::MISSING_CLASS_ALLOWLIST, true)) {
                    $missing[] = $name;
                }

                continue;
            }

            if (in_array($name, self::MISSING_CLASS_ALLOWLIST, true)) {
                $stale[] = $name;
            }
        }

        self::assertSame(
            [],
            $missing,
            sprintf(
                "These repositories have no *DbTest class:\n%s",
                implode("\n", $missing)
            )
        );

        self::assertSame(
            [],
            $stale,
            sprintf(
                "These repositories have a *DbTest class but are still allowlisted as missing:\n%s",
                implode("\n", $stale)
            )
        );
    }

    #[Test]
    public function everyRepositoryMethodIsCoveredOrAllowlisted(): void
    {
        $unexpected = [];
        $stale = [];
        $unknown = array_diff(
            array_keys(self::UNCOVERED_METHOD_ALLOWLIST),
            array_keys(self::repositories())
        );

        foreach (self::repositories() as $name => $repository) {
            if ($repository['testFile'] === null) {
                continue;
            }

            $allowed = self::UNCOVERED_METHOD_ALLOWLIST[$name] ?? [];
            $uncovered = array_values(array_diff(
                $repository['methods'],
                self::referencedMethods($repository['testFile'])
            ));

            foreach (array_diff($uncovered, $allowed) as $method) {
                $unexpected[] = sprintf('%s::%s', $name, $method);
            }

            foreach (array_diff($allowed, $uncovered) as $method) {
                $stale[] = sprintf('%s::%s', $name, $method);
            }
        }

        self::assertSame(
            [],
            $unknown,
            sprintf(
                "The method allowlist contains unknown repositories:\n%s",
                implode("\n", $unknown)
            )
        );

        self::assertSame(
            [],
            $unexpected,
            sprintf(
                "These repository methods have no DB-backed test:\n%s",
                implode("\n", $unexpected)
            )
        );

        self::assertSame(
            [],
            $stale,
            sprintf(
                "These method allowlist entries are already covered and must be removed:\n%s",
                implode("\n", $stale)
            )
        );
    }

    /**
     * @return array<string, array{fqcn: string, testFile: string|null, methods: string[]}>
     */
    private static function repositories(): array
    {
        $root = dirname(__DIR__, 3);
        $repositories = [];

        foreach (glob($root . '/lib/SP/Infrastructure/*/Repositories/*.php') ?: [] as $file) {
            $name = basename($file, '.php');

            if (in_array($name, self::PLUMBING, true)) {
                continue;
            }

            $parts = explode('/', substr($file, strlen($root . '/lib/')));
            $domain = $parts[2];
            $testFile = sprintf(
                '%s/tests/SP/Infrastructure/%s/Repositories/%sDbTest.php',
                $root,
                $domain,
                $name
            );

            $repositories[$name] = [
                'fqcn' => sprintf('SP\\Infrastructure\\%s\\Repositories\\%s', $domain, $name),
                'testFile' => is_file($testFile) ? $testFile : null,
                'methods' => self::publicMethods(
                    sprintf('SP\\Infrastructure\\%s\\Repositories\\%s', $domain, $name)
                ),
            ];
        }

        ksort($repositories);

        return $repositories;
    }

    /**
     * @return string[]
     */
    private static function publicMethods(string $class): array
    {
        $methods = [];

        foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $class
                || $method->isConstructor()
                || $method->isStatic()
            ) {
                continue;
            }

            $methods[] = $method->getName();
        }

        sort($methods);

        return $methods;
    }

    /**
     * @return string[]
     */
    private static function referencedMethods(string $testFile): array
    {
        $source = file_get_contents($testFile) ?: '';
        preg_match_all('/->\s*(\w+)\s*\(/', $source, $matches);

        return array_values(array_unique($matches[1]));
    }
}
