import { realpathSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

export const INSTANCE_DIRECTORIES = Object.freeze([
  'config',
  'cache',
  'temp',
  'sessions',
  'output',
  'env',
  'app-cache',
  'app-temp',
]);

const LABELS = Object.freeze({
  config: 'configuration',
  cache: 'DI cache',
  temp: 'upload temp',
  sessions: 'session',
  output: 'output',
  env: 'environment',
  'app-cache': 'app-cache',
  'app-temp': 'app-temp',
});

function realPathOrResolved(target) {
  try {
    return realpathSync(target);
  } catch {
    return path.resolve(target);
  }
}

function isWithin(child, parent) {
  const relative = path.relative(parent, child);

  return relative === '' || (!relative.startsWith('..') && !path.isAbsolute(relative));
}

/**
 * The exact per-instance directories the runner creates. Keeping this list in
 * one resolver lets the isolation proof use the same paths as the runtime
 * instead of a synthetic layout.
 *
 * @param {string} root
 * @returns {Record<string, string>}
 */
export function instanceLayout(root) {
  const resolved = realPathOrResolved(root);
  const layout = { root: resolved };

  for (const directory of INSTANCE_DIRECTORIES) {
    layout[directory] = path.join(resolved, directory);
  }

  return layout;
}

/**
 * Resolve the seeded and installer instance layouts for one run root. The
 * installer lives in the documented `<run root>/install` subtree, so a plain
 * "installer root must not be inside the seeded root" rule would be wrong;
 * the proof must instead compare every managed namespace.
 *
 * @param {string} seededRoot
 * @param {{installDirName?: string}} [options]
 * @returns {{seeded: Record<string, string>, install: Record<string, string>}}
 */
export function resolveInstallLayout(seededRoot, { installDirName = 'install' } = {}) {
  const seeded = instanceLayout(seededRoot);
  const install = instanceLayout(path.join(seeded.root, installDirName));

  return { seeded, install };
}

/**
 * Compare the real seeded and installer layouts. Equal or overlapping managed
 * paths and a reused database name fail the isolation proof.
 *
 * @param {{seeded: Record<string, string>, install: Record<string, string>}} layout
 * @param {{seededDatabase?: string, installDatabase?: string}} [options]
 * @returns {string[]}
 */
export function namespaceErrors(layout, { seededDatabase = '', installDatabase = '' } = {}) {
  const errors = [];

  if (!layout?.seeded?.root || !layout?.install?.root) {
    return ['both the seeded and installer layouts are required'];
  }

  if (layout.seeded.root === layout.install.root) {
    errors.push('installer run root must not be the seeded run root');
  }

  for (const directory of INSTANCE_DIRECTORIES) {
    const seededPath = layout.seeded[directory];
    const installPath = layout.install[directory];

    if (
      seededPath === installPath ||
      isWithin(installPath, seededPath) ||
      isWithin(seededPath, installPath)
    ) {
      errors.push(
        `installer ${LABELS[directory]} overlaps the seeded ${LABELS[directory]}: ${installPath}`,
      );
    }
  }

  if (seededDatabase && installDatabase && seededDatabase === installDatabase) {
    errors.push('installer database must not be the seeded database');
  }

  return errors;
}

function parseArguments(argv) {
  const options = {};
  let mode = '';

  for (let index = 0; index < argv.length; index += 1) {
    const argument = argv[index];

    switch (argument) {
      case 'install-root':
      case 'verify':
        mode = argument;
        break;
      case '--seeded-root':
        options.seededRoot = argv[(index += 1)];
        break;
      case '--install-root':
        options.installRoot = argv[(index += 1)];
        break;
      case '--seeded-database':
        options.seededDatabase = argv[(index += 1)];
        break;
      case '--install-database':
        options.installDatabase = argv[(index += 1)];
        break;
      default:
        throw new Error(`Unknown argument: ${argument}`);
    }
  }

  return { mode, ...options };
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
  try {
    const options = parseArguments(process.argv.slice(2));

    if (!options.seededRoot) {
      throw new Error(
        'Usage: install-layout.mjs install-root --seeded-root <dir> | verify --seeded-root <dir> [--install-root <dir>] [--seeded-database <name>] [--install-database <name>]',
      );
    }

    const resolved = resolveInstallLayout(options.seededRoot);

    if (options.mode === 'install-root') {
      process.stdout.write(`${resolved.install.root}\n`);
    } else {
      const layout = options.installRoot
        ? { ...resolved, install: instanceLayout(options.installRoot) }
        : resolved;
      const errors = namespaceErrors(layout, options);

      process.stdout.write(
        `${JSON.stringify({
          ok: errors.length === 0,
          seededRoot: layout.seeded.root,
          installRoot: layout.install.root,
          errors,
        })}\n`,
      );

      if (errors.length > 0) {
        process.stderr.write(`${errors.join('\n')}\n`);
        process.exit(1);
      }
    }
  } catch (error) {
    process.stderr.write(`${error instanceof Error ? error.message : String(error)}\n`);
    process.exit(1);
  }
}
