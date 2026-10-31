<?php

declare(strict_types=1);

namespace SP\Tests\Architecture;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * Collects layer dependency violations for the architecture test.
 */
final class LayerRules
{
    public const DOMAIN_ONLY = 'domain-only';
    public const INFRASTRUCTURE_ONLY = 'infrastructure-only';
    public const MODULES_INFRASTRUCTURE = 'modules-infrastructure';
    public const MODULES_CONCRETE_SERVICES = 'modules-concrete-services';
    public const LIB_MODULES = 'lib-modules';
    public const DOMAIN_APPLICATION = 'domain-application';
    public const DOMAIN_PRESENTATION_CALLS = 'domain-presentation-calls';
    public const CORE_INFRASTRUCTURE = 'core-infrastructure';

    public const RULES = [
        self::DOMAIN_ONLY,
        self::INFRASTRUCTURE_ONLY,
        self::MODULES_INFRASTRUCTURE,
        self::MODULES_CONCRETE_SERVICES,
        self::LIB_MODULES,
        self::DOMAIN_APPLICATION,
        self::DOMAIN_PRESENTATION_CALLS,
        self::CORE_INFRASTRUCTURE,
    ];

    private const COMPOSITION_ROOT = 'lib/SP/Core/Definitions/';

    private const OUTER_LAYER_PREFIXES = [
        'SP\\Core\\',
        'SP\\Modules\\',
        'SP\\Util\\',
    ];

    private const OUTER_DOMAIN_PREFIXES = [
        'SP\\Infrastructure\\',
        ...self::OUTER_LAYER_PREFIXES,
    ];

    /**
     * @var array<string, array<string, list<string>>>
     */
    private static array $cache = [];

    /**
     * @return array<string, list<string>>
     */
    public static function collect(string $root): array
    {
        $root = realpath($root) ?: $root;

        if (isset(self::$cache[$root])) {
            return self::$cache[$root];
        }

        $violations = array_fill_keys(self::RULES, []);
        $parser = (new ParserFactory())->createForNewestSupportedVersion();

        foreach (self::phpFiles($root) as $relativePath => $absolutePath) {
            $layer = self::layerFor($relativePath);

            if ($layer === null) {
                continue;
            }

            $code = file_get_contents($absolutePath);

            if ($code === false) {
                continue;
            }

            if ($layer === 'lib'
                && !str_starts_with($relativePath, self::COMPOSITION_ROOT)
                && str_contains($code, 'SP\\Modules')
            ) {
                $violations[self::LIB_MODULES][] = sprintf('%s => SP\\Modules', $relativePath);
            }

            $statements = $parser->parse($code);

            if ($statements === null) {
                continue;
            }

            $imports = self::imports($statements);
            $aliases = self::importAliases($statements);
            $calls = self::functionCalls($statements);
            $staticCalls = self::staticCalls($statements, $aliases);

            foreach ($imports as $import) {
                self::checkImport($layer, $relativePath, $import, $violations);
            }

            if ($layer === 'module') {
                self::checkStaticCalls($relativePath, $staticCalls, $violations);
            }

            if ($layer === 'domain') {
                $presentationCalls = [];

                foreach ($calls as $call) {
                    // __u() is the gettext extraction marker: it returns the untranslated
                    // key and performs no translation, so it is not a presentation call.
                    foreach (['__', 'processException'] as $function) {
                        if (self::isCall($call, $function)) {
                            $presentationCalls[$function] = ($presentationCalls[$function] ?? 0) + 1;
                            break;
                        }
                    }
                }

                foreach ($presentationCalls as $function => $count) {
                    $violations[self::DOMAIN_PRESENTATION_CALLS][] = sprintf(
                        '%s => %s() x%d',
                        $relativePath,
                        $function,
                        $count
                    );
                }
            }
        }

        foreach ($violations as $rule => $ruleViolations) {
            $violations[$rule] = array_values(array_unique($ruleViolations));
            sort($violations[$rule]);
        }

        return self::$cache[$root] = $violations;
    }

    /**
     * @param list<Use_|GroupUse> $statements
     * @return list<string>
     */
    private static function imports(array $statements): array
    {
        $imports = [];
        $finder = new NodeFinder();

        foreach ($finder->findInstanceOf($statements, Use_::class) as $statement) {
            foreach ($statement->uses as $use) {
                $imports[] = $use->name->toString();
            }
        }

        foreach ($finder->findInstanceOf($statements, GroupUse::class) as $statement) {
            foreach ($statement->uses as $use) {
                $imports[] = $statement->prefix->toString() . '\\' . $use->name->toString();
            }
        }

        return $imports;
    }

    /**
     * @param list<Use_|GroupUse> $statements
     * @return array<string, string> Lowercase alias => fully qualified name
     */
    private static function importAliases(array $statements): array
    {
        $aliases = [];
        $finder = new NodeFinder();

        foreach ($finder->findInstanceOf($statements, Use_::class) as $statement) {
            foreach ($statement->uses as $use) {
                $name = $use->name->toString();
                $alias = $use->alias?->toString() ?? self::lastSegment($name);
                $aliases[strtolower($alias)] = $name;
            }
        }

        foreach ($finder->findInstanceOf($statements, GroupUse::class) as $statement) {
            foreach ($statement->uses as $use) {
                $name = $statement->prefix->toString() . '\\' . $use->name->toString();
                $alias = $use->alias?->toString() ?? self::lastSegment($use->name->toString());
                $aliases[strtolower($alias)] = $name;
            }
        }

        return $aliases;
    }

    private static function lastSegment(string $name): string
    {
        $parts = explode('\\', $name);

        return (string)end($parts);
    }

    /**
     * Resolve static calls to a class name.
     *
     * @param list<Use_|GroupUse> $statements
     * @param array<string, string> $aliases
     * @return list<string>
     */
    private static function staticCalls(array $statements, array $aliases): array
    {
        $calls = [];

        foreach ((new NodeFinder())->findInstanceOf($statements, StaticCall::class) as $call) {
            if (!$call->class instanceof Name) {
                continue;
            }

            $name = $call->class->toString();

            if (in_array(strtolower($name), ['self', 'static', 'parent'], true)) {
                continue;
            }

            $resolved = self::resolveName($name, $aliases, $call->class->isFullyQualified());
            $method = $call->name instanceof Identifier ? $call->name->toString() : '?';

            $calls[] = sprintf('%s::%s()', $resolved, $method);
        }

        return $calls;
    }

    /**
     * @param array<string, string> $aliases
     */
    private static function resolveName(string $name, array $aliases, bool $fullyQualified): string
    {
        if ($fullyQualified) {
            return ltrim($name, '\\');
        }

        $parts = explode('\\', $name);
        $alias = strtolower($parts[0]);

        if (isset($aliases[$alias])) {
            $parts[0] = $aliases[$alias];

            return implode('\\', $parts);
        }

        return $name;
    }

    /**
     * @param list<string> $staticCalls
     * @param array<string, list<string>> $violations
     */
    private static function checkStaticCalls(
        string $relativePath,
        array $staticCalls,
        array &$violations
    ): void {
        foreach ($staticCalls as $call) {
            $class = strstr($call, '::', true);

            if ($class === false
                || !str_starts_with($class, 'SP\\Domain\\')
                || !str_contains($class, '\\Services\\')
                || str_ends_with($class, 'Exception')
            ) {
                continue;
            }

            $violations[self::MODULES_CONCRETE_SERVICES][] = sprintf('%s => %s', $relativePath, $call);
        }
    }

    /**
     * @param list<Use_|GroupUse> $statements
     * @return list<string>
     */
    private static function functionCalls(array $statements): array
    {
        $calls = [];

        foreach ((new NodeFinder())->findInstanceOf($statements, FuncCall::class) as $call) {
            if ($call->name instanceof Name) {
                $calls[] = $call->name->toString();
            }
        }

        return $calls;
    }

    private static function isCall(string $call, string $function): bool
    {
        return $call === $function || str_ends_with($call, '\\' . $function);
    }

    /**
     * @param array<string, list<string>> $violations
     */
    private static function checkImport(
        string $layer,
        string $relativePath,
        string $import,
        array &$violations
    ): void {
        if ($layer === 'lib'
            && str_starts_with($relativePath, 'lib/SP/Core/')
            && !str_starts_with($relativePath, self::COMPOSITION_ROOT)
            && str_starts_with($import, 'SP\\Infrastructure\\')
        ) {
            $violations[self::CORE_INFRASTRUCTURE][] = sprintf('%s => %s', $relativePath, $import);
        }

        if ($layer === 'domain') {
            if ($import === 'SP\\Core\\Application') {
                $violations[self::DOMAIN_APPLICATION][] = sprintf('%s => %s', $relativePath, $import);
            }

            foreach (self::OUTER_DOMAIN_PREFIXES as $prefix) {
                if (str_starts_with($import, $prefix)) {
                    $violations[self::DOMAIN_ONLY][] = sprintf('%s => %s', $relativePath, $import);
                    break;
                }
            }
        }

        if ($layer === 'infrastructure') {
            foreach (self::OUTER_LAYER_PREFIXES as $prefix) {
                if (str_starts_with($import, $prefix)) {
                    $violations[self::INFRASTRUCTURE_ONLY][] = sprintf('%s => %s', $relativePath, $import);
                    break;
                }
            }
        }

        if ($layer === 'module') {
            // Module bootstrap files are composition roots: they wire the module's
            // adapters and ports and are allowed to reference infrastructure.
            if (!str_ends_with($relativePath, '/module.php')
                && str_starts_with($import, 'SP\\Infrastructure\\')
            ) {
                $violations[self::MODULES_INFRASTRUCTURE][] = sprintf('%s => %s', $relativePath, $import);
            }

            if (str_starts_with($import, 'SP\\Domain\\')
                && str_contains($import, '\\Services\\')
                && !str_ends_with($import, 'Exception')
            ) {
                $violations[self::MODULES_CONCRETE_SERVICES][] = sprintf('%s => %s', $relativePath, $import);
            }
        }
    }

    private static function layerFor(string $relativePath): ?string
    {
        if (str_starts_with($relativePath, 'app/modules/')) {
            return 'module';
        }

        if (!str_starts_with($relativePath, 'lib/')) {
            return null;
        }

        if (str_starts_with($relativePath, 'lib/SP/Domain/')) {
            return 'domain';
        }

        if (str_starts_with($relativePath, 'lib/SP/Infrastructure/')) {
            return 'infrastructure';
        }

        return 'lib';
    }

    /**
     * @return array<string, string> Relative path => absolute path
     */
    private static function phpFiles(string $root): array
    {
        $files = [];

        foreach (['lib', 'app/modules'] as $directory) {
            $path = $root . '/' . $directory;

            if (!is_dir($path)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            /** @var SplFileInfo $file */
            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $relative = str_replace($root . '/', '', $file->getPathname());
                $files[$relative] = $file->getPathname();
            }
        }

        ksort($files);

        return $files;
    }
}
