import { readdirSync, realpathSync, statSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const DATABASE_NAME_PATTERN = /^sp_ui_[0-9a-f]{12}$/;
const LOOPBACK_ORIGIN_PATTERN = /^http:\/\/127\.0\.0\.1:(\d{4,5})$/;
const RESERVED_DATABASES = new Set([
  'mysql',
  'information_schema',
  'performance_schema',
  'sys',
  'syspass',
]);
const PROTECTED_REPO_PATHS = ['app/config', 'app/cache', 'app/temp', '.git', 'vendor'];

function isWithin(child, parent) {
  const relative = path.relative(parent, child);

  return relative === '' || (!relative.startsWith('..') && !path.isAbsolute(relative));
}

function realPathOrNull(target) {
  try {
    return realpathSync(target);
  } catch {
    return null;
  }
}

function isDirectory(target) {
  try {
    return statSync(target).isDirectory();
  } catch {
    return false;
  }
}

export function validateDatabaseName(databaseName, errors) {
  if (
    typeof databaseName !== 'string' ||
    !DATABASE_NAME_PATTERN.test(databaseName) ||
    RESERVED_DATABASES.has(databaseName)
  ) {
    errors.push(
      `database name must be a run-owned database name matching ${DATABASE_NAME_PATTERN}`,
    );
  }
}

export function validateOrigin(origin, errors) {
  const match = typeof origin === 'string' ? LOOPBACK_ORIGIN_PATTERN.exec(origin) : null;
  const port = match ? Number.parseInt(match[1], 10) : 0;

  if (!match || port < 1024 || port > 65535) {
    errors.push('origin must be a runner-owned loopback origin http://127.0.0.1:<port>');
  }
}

export function validateRunContext({
  runRoot,
  repoRoot,
  databaseName,
  origin,
  externalBaseUrl = '',
  ownedPaths = [],
}) {
  const errors = [];

  for (const [label, value] of [
    ['run root', runRoot],
    ['repository root', repoRoot],
  ]) {
    if (typeof value !== 'string' || !path.isAbsolute(value)) {
      errors.push(`${label} must be absolute`);
    }
  }

  if (externalBaseUrl) {
    errors.push('E2E_BASE_URL is runner-owned and must not be supplied by the caller');
  }

  validateDatabaseName(databaseName, errors);
  validateOrigin(origin, errors);

  if (typeof runRoot === 'string' && typeof repoRoot === 'string') {
    const canonicalRunRoot = realPathOrNull(runRoot);

    if (canonicalRunRoot === null) {
      errors.push('run root must exist before preflight');
    } else if (canonicalRunRoot !== runRoot) {
      errors.push('run root must not be a symlink or contain symbolic link aliases');
    }

    const canonicalRepoRoot = realPathOrNull(repoRoot);

    if (canonicalRepoRoot === null) {
      errors.push('repository root must exist');
    } else {
      if (isWithin(runRoot, canonicalRepoRoot)) {
        errors.push('run root must be outside the repository');
      }

      for (const protectedPath of PROTECTED_REPO_PATHS) {
        if (isWithin(runRoot, path.join(canonicalRepoRoot, protectedPath))) {
          errors.push(`run root must not be inside the protected repository path ${protectedPath}`);
        }
      }
    }

    for (const ownedPath of ownedPaths) {
      if (typeof ownedPath !== 'string' || !path.isAbsolute(ownedPath)) {
        errors.push(`owned path must be absolute: ${String(ownedPath)}`);
        continue;
      }

      if (!isWithin(ownedPath, runRoot)) {
        errors.push(`owned path must be inside the run root: ${ownedPath}`);
        continue;
      }

      const canonicalOwnedPath = realPathOrNull(ownedPath);

      if (canonicalOwnedPath === null) {
        if (isDirectory(ownedPath)) {
          errors.push(`owned path must be a real directory inside the run root: ${ownedPath}`);
        }
        continue;
      }

      if (canonicalOwnedPath !== ownedPath || !isWithin(canonicalOwnedPath, runRoot)) {
        errors.push(`owned path must be a real directory inside the run root: ${ownedPath}`);
      }
    }

    const configDir = path.join(runRoot, 'config');

    if (isDirectory(configDir)) {
      try {
        if (readdirSync(configDir).length > 0) {
          errors.push('run configuration directory must be empty before installation');
        }
      } catch {
        errors.push('run configuration directory must be readable');
      }
    }
  }

  return { ok: errors.length === 0, errors };
}

function parseArguments(argv) {
  const options = { ownedPaths: [] };

  for (let index = 0; index < argv.length; index += 1) {
    const argument = argv[index];

    switch (argument) {
      case '--run-root':
        options.runRoot = argv[(index += 1)];
        break;
      case '--repo-root':
        options.repoRoot = argv[(index += 1)];
        break;
      case '--database':
        options.databaseName = argv[(index += 1)];
        break;
      case '--origin':
        options.origin = argv[(index += 1)];
        break;
      case '--owned':
        options.ownedPaths.push(argv[(index += 1)]);
        break;
      default:
        throw new Error(`Unknown argument: ${argument}`);
    }
  }

  options.externalBaseUrl = process.env.E2E_BASE_URL ?? '';

  return options;
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
  try {
    const result = validateRunContext(parseArguments(process.argv.slice(2)));

    if (!result.ok) {
      process.stderr.write(`${result.errors.join('\n')}\n`);
      process.exit(1);
    }

    process.stdout.write('preflight ok\n');
  } catch (error) {
    process.stderr.write(`${error.message}\n`);
    process.exit(1);
  }
}
