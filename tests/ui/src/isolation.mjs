import { createHash } from 'node:crypto';
import { readdirSync, readFileSync, statSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

function digestFile(target) {
  return createHash('sha256').update(readFileSync(target)).digest('hex');
}

function collect(directory, root, entries) {
  for (const name of readdirSync(directory).sort()) {
    const target = path.join(directory, name);
    const stat = statSync(target);

    if (stat.isDirectory()) {
      collect(target, root, entries);
    } else if (stat.isFile()) {
      entries.push(
        `${path.relative(root, target).replaceAll('\\', '/')}\0${stat.size}\0${digestFile(target)}`,
      );
    }
  }
}

/**
 * Content digest for one file or directory. Timestamps are deliberately
 * excluded: the isolation proof cares about data/configuration changes, not
 * filesystem metadata churn from ordinary reads.
 *
 * @param {string} target
 * @returns {string}
 */
export function digestPath(target) {
  try {
    const stat = statSync(target);

    if (stat.isFile()) {
      return `file:${digestFile(target)}`;
    }

    if (stat.isDirectory()) {
      const entries = [];

      collect(target, target, entries);

      return `dir:${createHash('sha256').update(entries.join('\n')).digest('hex')}`;
    }
  } catch {
    return 'missing';
  }

  return 'missing';
}

/**
 * Capture the seeded instance state that the wizard selection must not touch.
 * The fixed `app/cache/config.cache` binding is part of the app-cache digest.
 *
 * @param {string} runRoot
 * @param {{database?: string}} [options]
 * @returns {{
 *   config: string,
 *   cache: string,
 *   temp: string,
 *   sessions: string,
 *   appCache: string,
 *   appTemp: string,
 *   database: string,
 * }}
 */
export function snapshotSeededState(runRoot, { database = 'unavailable' } = {}) {
  return {
    config: digestPath(path.join(runRoot, 'config', 'config.xml')),
    cache: digestPath(path.join(runRoot, 'cache')),
    temp: digestPath(path.join(runRoot, 'temp')),
    sessions: digestPath(path.join(runRoot, 'sessions')),
    appCache: digestPath(path.join(runRoot, 'app-cache')),
    appTemp: digestPath(path.join(runRoot, 'app-temp')),
    database: String(database || 'unavailable'),
  };
}

const LABELS = {
  config: 'seeded configuration',
  cache: 'seeded DI cache',
  temp: 'seeded upload temp',
  sessions: 'seeded sessions namespace',
  appCache: 'seeded app-cache (the fixed app/cache/config.cache path)',
  appTemp: 'seeded app-temp',
  database: 'seeded database',
};

/**
 * Compare two seeded-instance snapshots. Any difference fails the isolation
 * proof, including a wizard write into the fixed application cache path.
 *
 * @param {ReturnType<typeof snapshotSeededState>} before
 * @param {ReturnType<typeof snapshotSeededState>} after
 * @returns {{ok: boolean, changes: string[]}}
 */
export function compareSnapshots(before, after) {
  const changes = [];

  for (const [key, label] of Object.entries(LABELS)) {
    if ((before?.[key] ?? 'missing') !== (after?.[key] ?? 'missing')) {
      changes.push(`${label} changed during the installer selection`);
    }
  }

  return { ok: changes.length === 0, changes };
}

function parseArguments(argv) {
  const options = {};
  let mode = '';

  for (let index = 0; index < argv.length; index += 1) {
    const argument = argv[index];

    switch (argument) {
      case 'snapshot':
      case 'compare':
        mode = argument;
        break;
      case '--run-root':
        options.runRoot = argv[(index += 1)];
        break;
      case '--database-digest':
        options.database = argv[(index += 1)];
        break;
      case '--before':
        options.before = argv[(index += 1)];
        break;
      case '--after':
        options.after = argv[(index += 1)];
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

    if (options.mode === 'compare' || (options.before && options.after)) {
      const before = JSON.parse(readFileSync(options.before, 'utf8'));
      const after = JSON.parse(readFileSync(options.after, 'utf8'));
      const result = compareSnapshots(before, after);

      process.stdout.write(`${JSON.stringify(result)}\n`);

      if (!result.ok) {
        process.stderr.write(`${result.changes.join('\n')}\n`);
        process.exit(1);
      }
    } else if (options.runRoot) {
      process.stdout.write(
        `${JSON.stringify(snapshotSeededState(options.runRoot, { database: options.database }))}\n`,
      );
    } else {
      throw new Error(
        'Usage: isolation.mjs snapshot --run-root <dir> [--database-digest <digest>] | compare --before <file> --after <file>',
      );
    }
  } catch (error) {
    process.stderr.write(`${error instanceof Error ? error.message : String(error)}\n`);
    process.exit(1);
  }
}
