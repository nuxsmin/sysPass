import { randomBytes } from 'node:crypto';
import { mkdirSync, readdirSync, rmSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import { INSTANCE_DIRECTORIES } from './install-layout.mjs';

export const INSTANCE_SUBDIRS = INSTANCE_DIRECTORIES;

/**
 * Destroy and recreate the installer workspace. A manual rerun must start from
 * a completely empty instance; only the workspace itself is preserved because
 * the caller owns it.
 *
 * @param {string} root
 * @param {object} [options]
 * @param {(target: string) => void} [options.remove]
 * @param {(target: string, options: {recursive: boolean}) => void} [options.mkdir]
 * @returns {string}
 */
export function resetInstallWorkspace(
  root,
  {
    remove = (target) => rmSync(target, { recursive: true, force: true }),
    mkdir = mkdirSync,
  } = {},
) {
  const resolved = path.resolve(root);

  if (resolved === path.parse(resolved).root) {
    throw new Error('refusing to reset a filesystem root');
  }

  mkdir(resolved, { recursive: true });

  for (const entry of readdirSync(resolved)) {
    remove(path.join(resolved, entry));
  }

  for (const directory of INSTANCE_SUBDIRS) {
    mkdir(path.join(resolved, directory), { recursive: true });
  }

  return resolved;
}

/**
 * Create the identity for a completely new wizard instance. A clean rerun uses
 * a new database, network and container names so it can never reuse an
 * installed instance or its data.
 *
 * @param {object} [options]
 * @param {(size: number) => Buffer} [options.random]
 * @returns {{
 *   hex: string,
 *   databaseName: string,
 *   networkName: string,
 *   dbContainer: string,
 *   webContainer: string,
 *   dbPassword: string,
 * }}
 */
export function createInstallIdentity({ random = randomBytes } = {}) {
  const hex = random(6).toString('hex');

  return {
    hex,
    databaseName: `sp_ui_${hex}`,
    networkName: `sp-ui-install-net-${hex}`,
    dbContainer: `sp-ui-install-db-${hex}`,
    webContainer: `sp-ui-install-web-${hex}`,
    dbPassword: random(24).toString('base64url'),
  };
}

function parseArguments(argv) {
  const options = {};
  let mode = '';

  for (let index = 0; index < argv.length; index += 1) {
    const argument = argv[index];

    switch (argument) {
      case 'reset':
      case 'identity':
        mode = argument;
        break;
      case '--root':
        options.root = argv[(index += 1)];
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

    if (options.mode === 'identity') {
      process.stdout.write(`${JSON.stringify(createInstallIdentity())}\n`);
    } else if (options.mode === 'reset' && options.root) {
      const root = resetInstallWorkspace(options.root);

      process.stdout.write(`${JSON.stringify({ root, directories: [...INSTANCE_SUBDIRS] })}\n`);
    } else {
      throw new Error(
        'Usage: install-lifecycle.mjs reset --root <dir> | identity',
      );
    }
  } catch (error) {
    process.stderr.write(`${error instanceof Error ? error.message : String(error)}\n`);
    process.exit(1);
  }
}
