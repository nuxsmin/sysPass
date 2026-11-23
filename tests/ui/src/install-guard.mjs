import { fileURLToPath } from 'node:url';

/**
 * The browser installer owns creating its database and schema. A second run
 * must never be attempted against an already installed instance: the runner
 * destroys and recreates the entire wizard instance instead.
 *
 * @param {object} state
 * @param {boolean} state.installed
 * @param {boolean} state.configFileExists
 * @param {boolean} state.databaseExists
 * @param {number} state.appCacheEntries
 * @param {number} state.sessionEntries
 * @param {number} state.retries
 * @returns {{ok: boolean, errors: string[]}}
 */
export function evaluateFreshness({
  installed,
  configFileExists,
  databaseExists,
  appCacheEntries,
  sessionEntries,
  retries,
}) {
  const errors = [];

  if (retries !== 0) {
    errors.push(`installer retries must be 0; received ${retries}`);
  }

  if (installed) {
    errors.push('the wizard instance is already installed; recreate the entire instance before rerunning');
  }

  if (configFileExists) {
    errors.push('the run configuration already exists before installation');
  }

  if (databaseExists) {
    errors.push('the run database already exists before installation');
  }

  if (appCacheEntries > 0) {
    errors.push('the application cache must be empty before installation');
  }

  if (sessionEntries > 0) {
    errors.push('the session namespace must be empty before installation');
  }

  return { ok: errors.length === 0, errors };
}

function parseBooleanFlag(value, label) {
  if (value === '1' || value === 'true') {
    return true;
  }

  if (value === '0' || value === 'false' || value === undefined) {
    return false;
  }

  throw new Error(`${label} must be 0 or 1; received '${value}'`);
}

function parseArguments(argv) {
  const options = {};

  for (let index = 0; index < argv.length; index += 1) {
    const argument = argv[index];
    const value = argv[(index += 1)];

    switch (argument) {
      case '--installed':
        options.installed = parseBooleanFlag(value, '--installed');
        break;
      case '--config-file':
        options.configFileExists = parseBooleanFlag(value, '--config-file');
        break;
      case '--database':
        options.databaseExists = parseBooleanFlag(value, '--database');
        break;
      case '--app-cache':
        options.appCacheEntries = Number(value);
        break;
      case '--sessions':
        options.sessionEntries = Number(value);
        break;
      case '--retries':
        options.retries = Number(value);
        break;
      default:
        throw new Error(`Unknown argument: ${argument}`);
    }
  }

  return {
    installed: false,
    configFileExists: false,
    databaseExists: false,
    appCacheEntries: 0,
    sessionEntries: 0,
    retries: 0,
    ...options,
  };
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
  try {
    const result = evaluateFreshness(parseArguments(process.argv.slice(2)));

    process.stdout.write(`${JSON.stringify(result)}\n`);

    if (!result.ok) {
      process.stderr.write(`${result.errors.join('\n')}\n`);
      process.exit(1);
    }
  } catch (error) {
    process.stderr.write(`${error instanceof Error ? error.message : String(error)}\n`);
    process.exit(1);
  }
}
