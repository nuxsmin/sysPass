<?php

declare(strict_types=1);

namespace SP\Tests\Architecture;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use SP\Tests\DatabaseRepositoryTestCase;
use RuntimeException;

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
     * Monotonic limit for the method allowlist. It may only be lowered.
     */
    private const UNCOVERED_METHOD_ALLOWLIST_LIMIT = 127;

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
        $repositories = self::repositories();
        $missing = [];
        $stale = [];
        $invalid = [];

        self::assertNotEmpty($repositories, 'No repositories were discovered; the repository path is wrong.');

        foreach ($repositories as $name => $repository) {
            if ($repository['testFile'] === null) {
                if (!in_array($name, self::MISSING_CLASS_ALLOWLIST, true)) {
                    $missing[] = $name;
                }

                continue;
            }

            if (in_array($name, self::MISSING_CLASS_ALLOWLIST, true)) {
                $stale[] = $name;
            }

            $testClass = $repository['testClass'];

            if (!class_exists($testClass)
                || !is_subclass_of($testClass, DatabaseRepositoryTestCase::class)
            ) {
                $invalid[] = sprintf('%s must extend %s', $testClass, DatabaseRepositoryTestCase::class);
                continue;
            }

            $groups = array_map(
                static fn($attribute) => $attribute->newInstance()->name(),
                (new ReflectionClass($testClass))->getAttributes(Group::class)
            );

            if (!in_array('integration', $groups, true)) {
                $invalid[] = sprintf('%s must be in the integration group', $testClass);
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

        self::assertSame(
            [],
            $invalid,
            sprintf(
                "These *DbTest classes are not DB-backed integration tests:\n%s",
                implode("\n", $invalid)
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
                self::referencedMethods($repository['testFile'], $repository['fqcn'])
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

    #[Test]
    public function methodAllowlistOnlyShrinks(): void
    {
        $count = 0;

        foreach (self::UNCOVERED_METHOD_ALLOWLIST as $methods) {
            $count += count($methods);
        }

        self::assertLessThanOrEqual(
            self::UNCOVERED_METHOD_ALLOWLIST_LIMIT,
            $count,
            'The uncovered-method allowlist grew; cover or remove entries instead of adding new ones.'
        );
    }

    #[Test]
    public function referencedMethodsOnlyCountCallsOnTheRepository(): void
    {
        $source = <<<'PHP'
        $this->delete();
        $this->config = new Config($this->db);
        $this->config->delete();
        $local = new Config($this->db);
        $local->getByParam('version');
        $other->orphanMethod();
        PHP;

        self::assertSame(
            ['delete', 'getByParam'],
            self::calledMethods($source, 'SP\Infrastructure\Config\Repositories\Config')
        );
    }

    /**
     * @return array<string, array{fqcn: string, testClass: string, testFile: string|null, methods: string[]}>
     */
    private static function repositories(): array
    {
        $root = dirname(__DIR__, 3);
        $repositories = [];
        $files = glob($root . '/lib/SP/Infrastructure/*/Repositories/*.php') ?: [];

        if ($files === []) {
            throw new RuntimeException('No repositories were discovered under lib/SP/Infrastructure.');
        }

        foreach ($files as $file) {
            $name = basename($file, '.php');

            if (in_array($name, self::PLUMBING, true)) {
                continue;
            }

            $parts = explode('/', substr($file, strlen($root . '/lib/')));
            $domain = $parts[2];
            $testClass = sprintf(
                'SP\\Tests\\Infrastructure\\%s\\Repositories\\%sDbTest',
                $domain,
                $name
            );
            $testFile = sprintf(
                '%s/tests/SP/Infrastructure/%s/Repositories/%sDbTest.php',
                $root,
                $domain,
                $name
            );

            $repositories[$name] = [
                'fqcn' => sprintf('SP\\Infrastructure\\%s\\Repositories\\%s', $domain, $name),
                'testClass' => $testClass,
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
    private static function referencedMethods(string $testFile, string $repositoryClass): array
    {
        $source = file_get_contents($testFile) ?: '';

        return self::calledMethods($source, $repositoryClass);
    }

    /**
     * Finds calls made on the repository instance itself, not on any object.
     *
     * @return string[]
     */
    private static function calledMethods(string $source, string $repositoryClass): array
    {
        $shortName = substr($repositoryClass, (int)strrpos($repositoryClass, '\\') + 1);
        $patterns = [
            sprintf(
                '/\$this->([A-Za-z_]\w*)\s*=\s*new\s+(?:\\\\?[A-Za-z_]\w*\\\\)*%s\s*\(/',
                preg_quote($shortName, '/')
            ),
            sprintf(
                '/\$([A-Za-z_]\w*)\s*=\s*new\s+(?:\\\\?[A-Za-z_]\w*\\\\)*%s\s*\(/',
                preg_quote($shortName, '/')
            ),
        ];

        // Local variables and properties share the same capture; keep the property syntax.
        preg_match_all($patterns[0], $source, $propertyMatches);
        preg_match_all($patterns[1], $source, $localMatches);
        $references = array_merge(
            array_map(static fn(string $name) => '$this->' . $name, $propertyMatches[1]),
            array_map(static fn(string $name) => '$' . $name, $localMatches[1])
        );

        $calls = [];

        foreach (array_unique($references) as $reference) {
            preg_match_all(
                '/' . preg_quote($reference, '/') . '\s*->\s*(\w+)\s*\(/',
                $source,
                $matches
            );

            $calls = array_merge($calls, $matches[1]);
        }

        $calls = array_values(array_unique($calls));
        sort($calls);

        return $calls;
    }
}
