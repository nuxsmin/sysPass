<?php

declare(strict_types=1);

namespace SP\Tests\Infrastructure\Database;

use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use ReflectionProperty;
use SP\Domain\Account\Dtos\AccountHistoryCreateDto;
use SP\Domain\Account\Models\Account as AccountModel;
use SP\Domain\Account\Models\AccountHistory as AccountHistoryModel;
use SP\Domain\Account\Models\File as FileModel;
use SP\Domain\Account\Models\PublicLink as PublicLinkModel;
use SP\Domain\Account\Services\Builders\AccountFilter;
use SP\Domain\Auth\Models\AuthToken as AuthTokenModel;
use SP\Domain\Category\Models\Category as CategoryModel;
use SP\Domain\Client\Models\Client as ClientModel;
use SP\Domain\Common\Models\Model;
use SP\Domain\Common\Models\Simple;
use SP\Domain\Config\Models\Config as ConfigModel;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\CustomField\Models\CustomFieldData as CustomFieldDataModel;
use SP\Domain\CustomField\Models\CustomFieldDefinition as CustomFieldDefinitionModel;
use SP\Domain\ItemPreset\Models\ItemPreset as ItemPresetModel;
use SP\Domain\Notification\Models\Notification as NotificationModel;
use SP\Domain\Plugin\Models\Plugin as PluginModel;
use SP\Domain\Plugin\Models\PluginData as PluginDataModel;
use SP\Domain\Security\Models\Eventlog as EventlogModel;
use SP\Domain\Security\Models\Track as TrackModel;
use SP\Domain\Tag\Models\Tag as TagModel;
use SP\Domain\User\Models\User as UserModel;
use SP\Domain\User\Models\UserGroup as UserGroupModel;
use SP\Domain\User\Models\UserPassRecover as UserPassRecoverModel;
use SP\Domain\User\Models\UserProfile as UserProfileModel;
use SP\Infrastructure\Account\Repositories\Account as AccountRepository;
use SP\Infrastructure\Account\Repositories\AccountFile as AccountFileRepository;
use SP\Infrastructure\Account\Repositories\AccountHistory as AccountHistoryRepository;
use SP\Infrastructure\Account\Repositories\AccountToFavorite as AccountToFavoriteRepository;
use SP\Infrastructure\Account\Repositories\AccountToTag as AccountToTagRepository;
use SP\Infrastructure\Account\Repositories\AccountToUser as AccountToUserRepository;
use SP\Infrastructure\Account\Repositories\AccountToUserGroup as AccountToUserGroupRepository;
use SP\Infrastructure\Account\Repositories\PublicLink as PublicLinkRepository;
use SP\Infrastructure\Auth\Repositories\AuthToken as AuthTokenRepository;
use SP\Infrastructure\Category\Repositories\Category as CategoryRepository;
use SP\Infrastructure\Client\Repositories\Client as ClientRepository;
use SP\Infrastructure\Config\Repositories\Config as ConfigRepository;
use SP\Infrastructure\CustomField\Repositories\CustomFieldData as CustomFieldDataRepository;
use SP\Infrastructure\CustomField\Repositories\CustomFieldDefinition as CustomFieldDefinitionRepository;
use SP\Infrastructure\ItemPreset\Repositories\ItemPreset as ItemPresetRepository;
use SP\Infrastructure\Notification\Repositories\Notification as NotificationRepository;
use SP\Infrastructure\Plugin\Repositories\Plugin as PluginRepository;
use SP\Infrastructure\Plugin\Repositories\PluginData as PluginDataRepository;
use SP\Infrastructure\Security\Repositories\Eventlog as EventlogRepository;
use SP\Infrastructure\Security\Repositories\Track as TrackRepository;
use SP\Infrastructure\Tag\Repositories\Tag as TagRepository;
use SP\Infrastructure\User\Repositories\User as UserRepository;
use SP\Infrastructure\User\Repositories\UserGroup as UserGroupRepository;
use SP\Infrastructure\User\Repositories\UserPassRecover as UserPassRecoverRepository;
use SP\Infrastructure\User\Repositories\UserProfile as UserProfileRepository;
use SP\Infrastructure\User\Repositories\UserToUserGroup as UserToUserGroupRepository;
use SP\Tests\DatabaseRepositoryTestCase;
use SP\Infrastructure\Database\QueryData;

use function SP\Tests\getDbHandler;

/**
 * Contract between the database schema and the domain models/repositories.
 *
 * Scope is explicit: every model file under lib/SP/Domain/<domain>/Models must be listed
 * either as a mapped model or as an explicitly excluded class. Tables are never
 * inferred from class names or TABLE constants.
 */
#[Group('integration')]
final class SchemaContractTest extends DatabaseRepositoryTestCase
{
    private const VIEW_TABLES = [
        'account_data_v',
        'account_search_v',
    ];

    /**
     * Mapped model => database table/view. ENUMERATED BY THE SCOPE-RATCHET TEST.
     *
     * @var array<string, string>
     */
    private const MODEL_TABLES = [
        'SP\Domain\Account\Models\Account' => 'Account',
        'SP\Domain\Account\Models\AccountHistory' => 'AccountHistory',
        'SP\Domain\Account\Models\AccountSearchView' => 'account_search_v',
        'SP\Domain\Account\Models\AccountToUser' => 'AccountToUser',
        'SP\Domain\Account\Models\AccountToUserGroup' => 'AccountToUserGroup',
        'SP\Domain\Account\Models\AccountView' => 'account_data_v',
        'SP\Domain\Account\Models\File' => 'AccountFile',
        'SP\Domain\Account\Models\PublicLink' => 'PublicLink',
        'SP\Domain\Auth\Models\AuthToken' => 'AuthToken',
        'SP\Domain\Category\Models\Category' => 'Category',
        'SP\Domain\Client\Models\Client' => 'Client',
        'SP\Domain\Config\Models\Config' => 'Config',
        'SP\Domain\CustomField\Models\CustomFieldData' => 'CustomFieldData',
        'SP\Domain\CustomField\Models\CustomFieldDefinition' => 'CustomFieldDefinition',
        'SP\Domain\CustomField\Models\CustomFieldType' => 'CustomFieldType',
        'SP\Domain\ItemPreset\Models\ItemPreset' => 'ItemPreset',
        'SP\Domain\Notification\Models\Notification' => 'Notification',
        'SP\Domain\Plugin\Models\Plugin' => 'Plugin',
        'SP\Domain\Plugin\Models\PluginData' => 'PluginData',
        'SP\Domain\Security\Models\Eventlog' => 'EventLog',
        'SP\Domain\Security\Models\Track' => 'Track',
        'SP\Domain\Tag\Models\Tag' => 'Tag',
        'SP\Domain\User\Models\User' => 'User',
        'SP\Domain\User\Models\UserGroup' => 'UserGroup',
        'SP\Domain\User\Models\UserPassRecover' => 'UserPassRecover',
        'SP\Domain\User\Models\UserProfile' => 'UserProfile',
        'SP\Domain\User\Models\UserToUserGroup' => 'UserToUserGroup',
    ];

    /**
     * Model files that are not table-backed, with the reason they stay out of scope.
     *
     * @var array<string, string>
     */
    private const EXCLUDED_MODELS = [
        'SP\Domain\Account\Models\AccountItem' => 'Join/result DTO for account-user permission rows; no table of its own.',
        'SP\Domain\Account\Models\AccountUseCases' => 'Static Account factory/builder; not persisted.',
        'SP\Domain\Account\Models\PublicLinkList' => 'Joined list DTO extending PublicLink; no table of its own.',
        'SP\Domain\Auth\Models\AttributeCollection' => 'DataCollection payload; not a table row.',
        'SP\Domain\Common\Models\DataCollection' => 'Abstract collection payload base; not a table row.',
        'SP\Domain\Common\Models\HydratableModel' => 'Interface for serialized payload hydration; not persisted.',
        'SP\Domain\Common\Models\Item' => 'Base model for item-like rows; no table of its own.',
        'SP\Domain\Common\Models\ItemWithIdAndNameModel' => 'Interface contract for id/name models; not persisted.',
        'SP\Domain\Common\Models\Model' => 'Abstract base model; no table of its own.',
        'SP\Domain\Common\Models\SerializedModel' => 'Trait for serialized payload codecs; not persisted.',
        'SP\Domain\Common\Models\SerializesModelProperties' => 'Trait for serialized property handling; not persisted.',
        'SP\Domain\Common\Models\Simple' => 'Generic ad-hoc result-row model; not a table model.',
        'SP\Domain\Core\Models\Action' => 'ACL action value object; not persisted.',
        'SP\Domain\Core\Models\EncryptedModel' => 'Encryption behaviour mixin; not persisted.',
        'SP\Domain\ItemPreset\Models\AccountPermission' => 'Serialized preset payload; not a table row.',
        'SP\Domain\ItemPreset\Models\AccountPrivate' => 'Serialized preset payload; not a table row.',
        'SP\Domain\ItemPreset\Models\Password' => 'Serialized preset payload; not a table row.',
        'SP\Domain\ItemPreset\Models\SessionTimeout' => 'Serialized preset payload; not a table row.',
        'SP\Domain\User\Models\ProfileData' => 'Serialized profile payload; not a table row.',
        'SP\Domain\User\Models\UserPreferences' => 'Serialized preferences payload; not a table row.',
        'SP\Domain\User\Models\UserMasterPassStatus' => 'Master-password status enum; not persisted.',
    ];

    /**
     * Repositories that persist rows in a table without a Domain model.
     *
     * @var string[]
     */
    private const REPOSITORY_ONLY_TABLES = [
        'AccountToFavorite',
        'AccountToTag',
    ];

    /**
     * Model properties with no database column, documented as computed/join-only.
     *
     * @var array<string, string[]>
     */
    private const NON_COLUMN_PROPERTIES = [
        'SP\Domain\Account\Models\File' => ['accountName', 'clientName'],
        'SP\Domain\Auth\Models\AuthToken' => ['name', 'login'],
        'SP\Domain\ItemPreset\Models\ItemPreset' => ['score', 'userName', 'userProfileName', 'userGroupName'],
        'SP\Domain\Account\Models\PublicLink' => ['userName', 'userLogin', 'accountName'],
        'SP\Domain\Security\Models\Track' => ['dateTime', 'dateTimeUnlock', 'tracked'],
        'SP\Domain\User\Models\User' => ['userGroupName', 'userProfileName'],
        'SP\Domain\User\Models\UserGroup' => ['users'],
    ];

    /**
     * Columns without a model property, with the reason the model omits them.
     *
     * @var array<string, array<string, string>>
     */
    private const UNMODELLED_COLUMNS = [
        'AccountHistory' => [
            'mPassHash' => 'Supplied from AccountHistoryCreateDto::getMasterPassHash() on every create.',
        ],
        'AccountToUser' => [
            'accountId' => 'Raw array insert; no AccountToUser model properties.',
            'userId' => 'Raw array insert; no AccountToUser model properties.',
            'isEdit' => 'Raw array insert; no AccountToUser model properties.',
        ],
        'AccountToUserGroup' => [
            'accountId' => 'Raw array insert; no AccountToUserGroup model properties.',
            'userGroupId' => 'Raw array insert; no AccountToUserGroup model properties.',
            'isEdit' => 'Raw array insert; no AccountToUserGroup model properties.',
        ],
        'ItemPreset' => [
            'hash' => 'Computed by ItemPreset::getHash() and supplied by create/update.',
        ],
        'Plugin' => [
            'available' => 'Maintained by dedicated availability methods; not a model property.',
        ],
        'UserPassRecover' => [
            'id' => 'Auto-increment surrogate key; rows are identified by userId/hash/date.',
        ],
    ];

    /**
     * Column names whose casing differs from the model property. Explicit only.
     *
     * @var array<string, array<string, string>>
     */
    private const ALIASED_COLUMNS = [
        'PublicLink' => [
            'useinfo' => 'useInfo',
        ],
    ];

    /**
     * Insert journey identity => test method. Identity is a model FQCN or a
     * repository-only table name.
     *
     * @var array<string, string>
     */
    private const INSERT_JOURNEYS = [
        'SP\Domain\Account\Models\Account' => 'insertAccount',
        'SP\Domain\Account\Models\AccountHistory' => 'insertAccountHistory',
        'SP\Domain\Account\Models\AccountToUser' => 'insertAccountToUser',
        'SP\Domain\Account\Models\AccountToUserGroup' => 'insertAccountToUserGroup',
        'SP\Domain\Account\Models\File' => 'insertAccountFile',
        'SP\Domain\Account\Models\PublicLink' => 'insertPublicLink',
        'SP\Domain\Auth\Models\AuthToken' => 'insertAuthToken',
        'SP\Domain\Category\Models\Category' => 'insertCategory',
        'SP\Domain\Client\Models\Client' => 'insertClient',
        'SP\Domain\Config\Models\Config' => 'insertConfig',
        'SP\Domain\CustomField\Models\CustomFieldData' => 'insertCustomFieldData',
        'SP\Domain\CustomField\Models\CustomFieldDefinition' => 'insertCustomFieldDefinition',
        'SP\Domain\ItemPreset\Models\ItemPreset' => 'insertItemPreset',
        'SP\Domain\Notification\Models\Notification' => 'insertNotification',
        'SP\Domain\Plugin\Models\Plugin' => 'insertPlugin',
        'SP\Domain\Plugin\Models\PluginData' => 'insertPluginData',
        'SP\Domain\Security\Models\Eventlog' => 'insertEventlog',
        'SP\Domain\Security\Models\Track' => 'insertTrack',
        'SP\Domain\Tag\Models\Tag' => 'insertTag',
        'SP\Domain\User\Models\User' => 'insertUser',
        'SP\Domain\User\Models\UserGroup' => 'insertUserGroup',
        'SP\Domain\User\Models\UserPassRecover' => 'insertUserPassRecover',
        'SP\Domain\User\Models\UserProfile' => 'insertUserProfile',
        'SP\Domain\User\Models\UserToUserGroup' => 'insertUserToUserGroup',
        'AccountToFavorite' => 'insertAccountToFavorite',
        'AccountToTag' => 'insertAccountToTag',
    ];

    /**
     * Mapped models that are not insertable, with the reason.
     *
     * @var array<string, string>
     */
    private const INSERT_EXCLUDED = [
        'SP\Domain\Account\Models\AccountSearchView' => 'Read-only SQL view (account_search_v).',
        'SP\Domain\Account\Models\AccountView' => 'Read-only SQL data view (account_data_v).',
        'SP\Domain\CustomField\Models\CustomFieldType' => 'Lookup table has no create/add repository method.',
    ];

    private ?PDO $schemaConnection = null;

    /**
     * @var array<string, array{TABLE_NAME: string, TABLE_TYPE: string}>|null
     */
    private ?array $schemaObjects = null;

    /**
     * @var array<string, array<string, array{COLUMN_NAME: string, IS_NULLABLE: string, COLUMN_DEFAULT: mixed, EXTRA: string}>>
     */
    private array $schemaColumns = [];

    #[Test]
    public function everyModelFileIsExplicitlyMappedOrExcluded(): void
    {
        $models = self::modelClasses();
        $known = array_merge(array_keys(self::MODEL_TABLES), array_keys(self::EXCLUDED_MODELS));

        $unmapped = array_values(array_diff($models, $known));
        $staleMapped = array_values(array_diff(array_keys(self::MODEL_TABLES), $models));
        $staleExcluded = array_values(array_diff(array_keys(self::EXCLUDED_MODELS), $models));
        $overlap = array_values(array_intersect(
            array_keys(self::MODEL_TABLES),
            array_keys(self::EXCLUDED_MODELS)
        ));

        self::assertSame(
            [],
            $unmapped,
            "These model files are neither mapped nor explicitly excluded:\n" . implode("\n", $unmapped)
        );
        self::assertSame(
            [],
            $staleMapped,
            "These MODEL_TABLES entries no longer have a model file:\n" . implode("\n", $staleMapped)
        );
        self::assertSame(
            [],
            $staleExcluded,
            "These EXCLUDED_MODELS entries no longer have a model file:\n" . implode("\n", $staleExcluded)
        );
        self::assertSame(
            [],
            $overlap,
            "A model cannot be both mapped and excluded:\n" . implode("\n", $overlap)
        );
    }

    #[Test]
    public function mappedTablesExistWithTheExpectedObjectType(): void
    {
        $objects = $this->schemaObjects();
        $failures = [];

        foreach (self::MODEL_TABLES as $model => $table) {
            $expectedType = in_array($table, self::VIEW_TABLES, true) ? 'VIEW' : 'BASE TABLE';

            if (!isset($objects[$table])) {
                $failures[] = sprintf('%s maps to %s, which is missing from INFORMATION_SCHEMA', $model, $table);
                continue;
            }

            if ($objects[$table]['TABLE_TYPE'] !== $expectedType) {
                $failures[] = sprintf(
                    '%s maps to %s, expected %s but found %s',
                    $model,
                    $table,
                    $expectedType,
                    $objects[$table]['TABLE_TYPE']
                );
            }
        }

        self::assertSame([], $failures, implode("\n", $failures));
    }

    #[Test]
    public function everySchemaObjectIsMappedOrDocumentedRepositoryOnly(): void
    {
        $objects = $this->schemaObjects();
        $documented = array_merge(
            array_values(self::MODEL_TABLES),
            self::REPOSITORY_ONLY_TABLES,
            self::VIEW_TABLES
        );

        $unknown = array_values(array_diff(array_keys($objects), $documented));
        $staleRepositoryOnly = array_values(array_diff(
            self::REPOSITORY_ONLY_TABLES,
            array_keys($objects)
        ));

        self::assertSame(
            [],
            $unknown,
            "These schema objects are not mapped to a model or documented as repository-only:\n"
            . implode("\n", $unknown)
        );
        self::assertSame(
            [],
            $staleRepositoryOnly,
            "These repository-only tables are no longer present in the schema:\n"
            . implode("\n", $staleRepositoryOnly)
        );
    }

    #[Test]
    public function nullableSchemaColumnsAreModelledAsNullableProperties(): void
    {
        $failures = [];

        foreach (self::MODEL_TABLES as $model => $table) {
            if (in_array($table, self::VIEW_TABLES, true)) {
                continue;
            }

            $properties = self::modelProperties($model);
            $columns = $this->schemaColumns($table);

            foreach ($columns as $column => $metadata) {
                if ($metadata['IS_NULLABLE'] !== 'YES') {
                    continue;
                }

                $propertyName = self::ALIASED_COLUMNS[$table][$column] ?? $column;
                $property = $properties[$propertyName] ?? null;

                if ($property === null) {
                    if (!isset(self::UNMODELLED_COLUMNS[$table][$column])) {
                        $failures[] = sprintf(
                            '%s.%s is nullable, has no model property, and is not documented in UNMODELLED_COLUMNS',
                            $table,
                            $column
                        );
                    }

                    continue;
                }

                if (!self::propertyAllowsNull($property)) {
                    $failures[] = sprintf(
                        '%s::%s must be nullable because %s.%s is nullable',
                        self::shortClassName($model),
                        $propertyName,
                        $table,
                        $column
                    );
                }
            }
        }

        self::assertSame([], $failures, implode("\n", $failures));
    }

    #[Test]
    public function everyColumnIsModelledAliasedOrDocumentedAsUnmodelled(): void
    {
        $failures = [];
        $stale = [];

        foreach (self::MODEL_TABLES as $model => $table) {
            if (in_array($table, self::VIEW_TABLES, true)) {
                continue;
            }

            $properties = self::modelProperties($model);
            $columns = $this->schemaColumns($table);
            $aliases = self::ALIASED_COLUMNS[$table] ?? [];

            foreach ($columns as $column => $metadata) {
                if (isset($aliases[$column])) {
                    $target = $aliases[$column];

                    if (!isset($properties[$target])) {
                        $failures[] = sprintf(
                            'Alias %s.%s -> %s::%s target property does not exist',
                            $table,
                            $column,
                            self::shortClassName($model),
                            $target
                        );
                    }

                    continue;
                }

                if (isset($properties[$column])) {
                    continue;
                }

                if (!isset(self::UNMODELLED_COLUMNS[$table][$column])) {
                    $failures[] = sprintf(
                        '%s.%s is not modelled by %s and is not documented in UNMODELLED_COLUMNS',
                        $table,
                        $column,
                        $model
                    );
                }
            }
        }

        foreach (self::UNMODELLED_COLUMNS as $table => $documentedColumns) {
            $model = array_search($table, self::MODEL_TABLES, true);

            if ($model === false) {
                $stale[] = sprintf('UNMODELLED_COLUMNS documents table %s, which is not in MODEL_TABLES', $table);
                continue;
            }

            if (in_array($table, self::VIEW_TABLES, true)) {
                $stale[] = sprintf('UNMODELLED_COLUMNS documents view %s; views are existence-only', $table);
                continue;
            }

            $properties = self::modelProperties($model);
            $columns = $this->schemaColumns($table);
            $aliases = self::ALIASED_COLUMNS[$table] ?? [];

            foreach ($documentedColumns as $column => $reason) {
                if (!isset($columns[$column])) {
                    $stale[] = sprintf('UNMODELLED_COLUMNS documents %s.%s, which no longer exists', $table, $column);
                    continue;
                }

                if (isset($properties[$column]) || isset($aliases[$column])) {
                    $stale[] = sprintf(
                        'UNMODELLED_COLUMNS documents %s.%s, but it is now modelled by %s',
                        $table,
                        $column,
                        $model
                    );
                }
            }
        }

        self::assertSame([], $failures, implode("\n", $failures));
        self::assertSame([], $stale, implode("\n", $stale));
    }

    #[Test]
    public function everyModelPropertyIsAColumnOrDocumentedAsNonColumn(): void
    {
        $failures = [];
        $stale = [];

        foreach (self::MODEL_TABLES as $model => $table) {
            if (in_array($table, self::VIEW_TABLES, true)) {
                continue;
            }

            $properties = self::modelProperties($model);
            $columns = $this->schemaColumns($table);
            $aliases = array_flip(self::ALIASED_COLUMNS[$table] ?? []);
            $documented = self::NON_COLUMN_PROPERTIES[$model] ?? [];

            foreach ($properties as $propertyName => $property) {
                if (isset($columns[$propertyName]) || isset($aliases[$propertyName])) {
                    continue;
                }

                if (!in_array($propertyName, $documented, true)) {
                    $failures[] = sprintf(
                        '%s::%s has no %s column and is not documented in NON_COLUMN_PROPERTIES',
                        self::shortClassName($model),
                        $propertyName,
                        $table
                    );
                }
            }

            foreach ($documented as $propertyName) {
                if (!isset($properties[$propertyName])) {
                    $stale[] = sprintf('%s::%s is documented but no longer declared', self::shortClassName($model), $propertyName);
                    continue;
                }

                if (isset($columns[$propertyName]) || isset($aliases[$propertyName])) {
                    $stale[] = sprintf(
                        '%s::%s is documented as non-column but now maps to %s.%s',
                        self::shortClassName($model),
                        $propertyName,
                        $table,
                        $propertyName
                    );
                }
            }
        }

        self::assertSame([], $failures, implode("\n", $failures));
        self::assertSame([], $stale, implode("\n", $stale));
    }

    #[Test]
    public function aliasedColumnsResolveToDeclaredProperties(): void
    {
        $failures = [];

        foreach (self::ALIASED_COLUMNS as $table => $aliases) {
            if (!in_array($table, self::MODEL_TABLES, true)) {
                $failures[] = sprintf('Alias table %s is not in MODEL_TABLES', $table);
            }

            $columns = $this->schemaColumns($table);
            $model = array_search($table, self::MODEL_TABLES, true);

            if ($model === false) {
                continue;
            }

            $properties = self::modelProperties($model);

            foreach ($aliases as $column => $propertyName) {
                if (!isset($columns[$column])) {
                    $failures[] = sprintf('Aliased column %s.%s does not exist', $table, $column);
                }

                if (!isset($properties[$propertyName])) {
                    $failures[] = sprintf(
                        'Aliased column %s.%s targets missing property %s::%s',
                        $table,
                        $column,
                        self::shortClassName($model),
                        $propertyName
                    );
                }
            }
        }

        self::assertSame([], $failures, implode("\n", $failures));
    }

    #[Test]
    public function everyInsertableModelHasAnInsertJourney(): void
    {
        $models = array_keys(self::MODEL_TABLES);
        $journeys = array_keys(self::INSERT_JOURNEYS);
        $excluded = array_keys(self::INSERT_EXCLUDED);

        $missing = array_values(array_diff($models, $journeys, $excluded));
        $unknown = array_values(array_diff($journeys, $models, self::REPOSITORY_ONLY_TABLES));
        $staleExcluded = array_values(array_diff($excluded, $models));
        $overlap = array_values(array_intersect($journeys, $excluded));
        $missingRepositoryOnly = array_values(array_diff(self::REPOSITORY_ONLY_TABLES, $journeys));

        self::assertSame(
            [],
            $missing,
            "These mapped models have neither an insert journey nor an exclusion reason:\n"
            . implode("\n", $missing)
        );
        self::assertSame(
            [],
            $unknown,
            "These insert journey entries are neither mapped models nor repository-only tables:\n"
            . implode("\n", $unknown)
        );
        self::assertSame(
            [],
            $staleExcluded,
            "These INSERT_EXCLUDED entries are not mapped models:\n" . implode("\n", $staleExcluded)
        );
        self::assertSame(
            [],
            $overlap,
            "An insertable model cannot also be excluded:\n" . implode("\n", $overlap)
        );
        self::assertSame(
            [],
            $missingRepositoryOnly,
            "These repository-only tables have no insert journey:\n" . implode("\n", $missingRepositoryOnly)
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function insertJourneyProvider(): array
    {
        $data = [];

        foreach (array_keys(self::INSERT_JOURNEYS) as $identity) {
            $data[$identity] = [$identity];
        }

        return $data;
    }

    #[Test]
    #[DataProvider('insertJourneyProvider')]
    public function repositoryInsertJourneySucceedsInATransaction(string $identity): void
    {
        $method = self::INSERT_JOURNEYS[$identity];
        $table = self::MODEL_TABLES[$identity] ?? $identity;
        $before = $this->tableRowCount($table);

        $this->db->beginTransaction();

        try {
            $this->{$method}();

            self::assertSame(
                $before + 1,
                $this->tableRowCount($table),
                sprintf('The insert journey for %s must add exactly one row to %s', $identity, $table)
            );
        } finally {
            self::assertTrue(
                $this->db->rollbackTransaction(),
                sprintf('The insert journey for %s must roll back the %s transaction', $identity, $table)
            );
        }

        self::assertSame(
            $before,
            $this->tableRowCount($table),
            sprintf('The insert journey for %s must not leave rows in %s', $identity, $table)
        );
    }

    #[Test]
    public function publicLinkHydrationMapsUseinfoColumnToTheUseInfoProperty(): void
    {
        $repository = $this->repository(PublicLinkRepository::class);

        $this->db->beginTransaction();

        try {
            $this->db->runQueryRaw("UPDATE PublicLink SET useinfo = 'schema-use-info' WHERE id = 2");

            $link = $repository->getById(2)->getData(PublicLinkModel::class);

            self::assertInstanceOf(PublicLinkModel::class, $link);
            self::assertSame('schema-use-info', $link->getUseInfo());
        } finally {
            self::assertTrue(
                $this->db->rollbackTransaction(),
                'The PublicLink useInfo hydration test must roll back'
            );
        }

        $link = $repository->getById(2)->getData(PublicLinkModel::class);

        self::assertInstanceOf(PublicLinkModel::class, $link);
        self::assertNull($link->getUseInfo(), 'The PublicLink useInfo value must be restored by rollback');
    }

    private function insertAccount(): void
    {
        $accountFilter = new AccountFilter(
            $this->context,
            self::createStub(ConfigDataInterface::class),
            $this->queryFactory
        );

        $accountRepository = new AccountRepository(
            $this->db,
            $this->context,
            $this->queryFactory,
            $this->eventDispatcher,
            $accountFilter
        );

        $accountRepository->create(new AccountModel([
            'userGroupId' => 1,
            'userId' => 1,
            'userEditId' => 1,
            'clientId' => 1,
            'name' => 'Schema Contract Account',
            'categoryId' => 1,
            'login' => 'schema-contract-login',
            'url' => 'https://example.test',
            'pass' => 'schema-contract-pass',
            'key' => 'schema-contract-key',
            'notes' => 'schema-contract-notes',
        ]));
    }

    private function insertAccountFile(): void
    {
        $this->repository(AccountFileRepository::class)->create(new FileModel([
            'accountId' => 1,
            'name' => 'schema-contract.txt',
            'type' => 'text/plain',
            'size' => 16,
            'content' => 'schema-contract-content',
            'extension' => 'txt',
            'thumb' => null,
        ]));
    }

    private function insertAccountHistory(): void
    {
        $account = new AccountModel([
            'id' => 1,
            'clientId' => 1,
            'categoryId' => 1,
            'name' => 'Schema Contract History',
            'login' => 'schema-contract-login',
            'url' => 'https://example.test',
            'pass' => 'schema-contract-pass',
            'key' => 'schema-contract-key',
            'notes' => 'schema-contract-notes',
            'userId' => 1,
            'userGroupId' => 1,
            'userEditId' => 1,
            'isPrivate' => 0,
            'isPrivateGroup' => 0,
            'countView' => 0,
            'countDecrypt' => 0,
            'dateAdd' => '2018-06-05 22:49:34',
            'dateEdit' => null,
            'otherUserEdit' => 0,
            'otherUserGroupEdit' => 0,
            'passDate' => null,
            'passDateChange' => null,
            'parentId' => null,
        ]);

        $this->repository(AccountHistoryRepository::class)->create(
            new AccountHistoryCreateDto($account, true, false, 'schema-contract-mpass-hash')
        );
    }

    private function insertAccountToUser(): void
    {
        $this->repository(AccountToUserRepository::class)->addByType(3, [1]);
    }

    private function insertAccountToUserGroup(): void
    {
        $this->repository(AccountToUserGroupRepository::class)->addByType(3, [2]);
    }

    private function insertAccountToFavorite(): void
    {
        $this->repository(AccountToFavoriteRepository::class)->add(3, 2);
    }

    private function insertAccountToTag(): void
    {
        $this->repository(AccountToTagRepository::class)->add(3, [2]);
    }

    private function insertPublicLink(): void
    {
        $this->repository(PublicLinkRepository::class)->create(new PublicLinkModel([
            'itemId' => 3,
            'hash' => 'schema-contract-public-hash',
            'data' => null,
            'userId' => 1,
            'typeId' => 1,
            'notify' => false,
            'dateExpire' => time() + 3600,
            'maxCountViews' => 0,
        ]));
    }

    private function insertAuthToken(): void
    {
        $this->repository(AuthTokenRepository::class)->create(new AuthTokenModel([
            'userId' => 3,
            'token' => 'schema-contract-token',
            'actionId' => 2,
            'createdBy' => 1,
            'hash' => null,
            'vault' => null,
        ]));
    }

    private function insertCategory(): void
    {
        $this->repository(CategoryRepository::class)->create(new CategoryModel([
            'name' => 'Schema Contract Category',
            'description' => 'schema-contract-description',
        ]));
    }

    private function insertClient(): void
    {
        $this->repository(ClientRepository::class)->create(new ClientModel([
            'name' => 'Schema Contract Client',
            'description' => 'schema-contract-description',
            'isGlobal' => 0,
        ]));
    }

    private function insertConfig(): void
    {
        $this->repository(ConfigRepository::class)->create(new ConfigModel([
            'parameter' => 'schema_contract_parameter',
            'value' => 'schema-contract-value',
        ]));
    }

    private function insertCustomFieldData(): void
    {
        $this->repository(CustomFieldDataRepository::class)->create(new CustomFieldDataModel([
            'moduleId' => 1,
            'itemId' => 3,
            'definitionId' => 1,
            'data' => 'schema-contract-data',
            'key' => 'schema-contract-key',
        ]));
    }

    private function insertCustomFieldDefinition(): void
    {
        $this->repository(CustomFieldDefinitionRepository::class)->create(
            new CustomFieldDefinitionModel([
                'name' => 'Schema Contract Field',
                'moduleId' => 1,
                'required' => 0,
                'help' => null,
                'showInList' => 0,
                'typeId' => 1,
                'isEncrypted' => 1,
            ])
        );
    }

    private function insertItemPreset(): void
    {
        $this->repository(ItemPresetRepository::class)->create(new ItemPresetModel([
            'type' => 'schema_contract',
            'userId' => 3,
            'fixed' => 0,
            'priority' => 0,
            'data' => null,
        ]));
    }

    private function insertNotification(): void
    {
        $this->repository(NotificationRepository::class)->create(new NotificationModel([
            'type' => 'schema',
            'component' => 'SchemaContract',
            'description' => 'schema-contract-description',
            'checked' => false,
            'userId' => 1,
            'sticky' => false,
            'onlyAdmin' => false,
        ]));
    }

    private function insertPlugin(): void
    {
        $this->repository(PluginRepository::class)->create(new PluginModel([
            'name' => 'Schema Contract Plugin',
            'data' => null,
            'enabled' => false,
            'versionLevel' => '1.0',
        ]));
    }

    private function insertPluginData(): void
    {
        $this->repository(PluginDataRepository::class)->create(new PluginDataModel([
            'name' => 'Authenticator',
            'itemId' => 5,
            'data' => 'schema-contract-data',
            'key' => 'schema-contract-key',
        ]));
    }

    private function insertEventlog(): void
    {
        $this->repository(EventlogRepository::class)->create(new EventlogModel([
            'login' => 'schema-contract',
            'userId' => 1,
            'ipAddress' => '127.0.0.1',
            'action' => 'schema.contract',
            'description' => 'schema-contract-description',
            'level' => 'INFO',
        ]));
    }

    private function insertTrack(): void
    {
        $this->repository(TrackRepository::class)->add(new TrackModel([
            'userId' => 1,
            'source' => 'schema_contract',
            'timeUnlock' => null,
            'ipv4' => null,
            'ipv6' => null,
        ]));
    }

    private function insertTag(): void
    {
        $this->repository(TagRepository::class)->create(new TagModel([
            'name' => 'schema-contract-tag',
        ]));
    }

    private function insertUser(): void
    {
        $this->repository(UserRepository::class)->create(new UserModel([
            'name' => 'Schema Contract User',
            'userGroupId' => 2,
            'login' => 'schema_contract_user',
            'pass' => 'schema-contract-pass',
            'userProfileId' => 2,
        ]));
    }

    private function insertUserGroup(): void
    {
        $this->repository(UserGroupRepository::class)->create(new UserGroupModel([
            'name' => 'Schema Contract Group',
            'description' => 'schema-contract-description',
        ]));
    }

    private function insertUserPassRecover(): void
    {
        $this->repository(UserPassRecoverRepository::class)->add(2, 'schema-contract-recover-hash');
    }

    private function insertUserProfile(): void
    {
        $this->repository(UserProfileRepository::class)->create(new UserProfileModel([
            'name' => 'Schema Contract Profile',
            'profile' => 'schema-contract-profile-payload',
        ]));
    }

    private function insertUserToUserGroup(): void
    {
        $this->repository(UserToUserGroupRepository::class)->add(4, [1]);
    }

    /**
     * @param class-string $class
     */
    private function repository(string $class): object
    {
        return new $class($this->db, $this->context, $this->eventDispatcher, $this->queryFactory);
    }

    private function tableRowCount(string $table): int
    {
        $query = $this->queryFactory
            ->newSelect()
            ->from($table)
            ->cols(['COUNT(*) AS total']);

        $row = $this->db
            ->runQuery(QueryData::build($query)->setMapClassName(Simple::class))
            ->getData(Simple::class);

        return (int)($row?->total ?? 0);
    }

    /**
     * @return array<string, array{TABLE_NAME: string, TABLE_TYPE: string}>
     */
    private function schemaObjects(): array
    {
        if ($this->schemaObjects !== null) {
            return $this->schemaObjects;
        }

        $statement = $this->schemaConnection()->query(
            'SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()'
        );

        $objects = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $objects[$row['TABLE_NAME']] = $row;
        }

        return $this->schemaObjects = $objects;
    }

    /**
     * @return array<string, array{COLUMN_NAME: string, IS_NULLABLE: string, COLUMN_DEFAULT: mixed, EXTRA: string}>
     */
    private function schemaColumns(string $table): array
    {
        if (isset($this->schemaColumns[$table])) {
            return $this->schemaColumns[$table];
        }

        $statement = $this->schemaConnection()->prepare(
            'SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table
             ORDER BY ORDINAL_POSITION'
        );
        $statement->execute(['table' => $table]);

        $columns = [];

        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $columns[$row['COLUMN_NAME']] = $row;
        }

        return $this->schemaColumns[$table] = $columns;
    }

    private function schemaConnection(): PDO
    {
        return $this->schemaConnection ??= getDbHandler()->getConnection();
    }

    /**
     * @return string[]
     */
    private static function modelClasses(): array
    {
        $models = [];

        foreach (glob(REAL_APP_ROOT . '/lib/SP/Domain/*/Models/*.php') ?: [] as $file) {
            $domain = basename(dirname($file, 2));
            $class = basename($file, '.php');
            $models[] = sprintf('SP\\Domain\\%s\\Models\\%s', $domain, $class);
        }

        sort($models);

        return $models;
    }

    /**
     * @return array<string, ReflectionProperty>
     */
    private static function modelProperties(string $model): array
    {
        $properties = [];

        foreach ((new ReflectionClass($model))->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            if ($property->getName() === 'properties'
                && $property->getDeclaringClass()->getName() === Model::class
            ) {
                continue;
            }

            $properties[$property->getName()] = $property;
        }

        ksort($properties);

        return $properties;
    }

    private static function propertyAllowsNull(ReflectionProperty $property): bool
    {
        $type = $property->getType();

        return $type === null || $type->allowsNull();
    }

    private static function shortClassName(string $class): string
    {
        return (new ReflectionClass($class))->getShortName();
    }
}
