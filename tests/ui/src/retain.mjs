import { fileURLToPath } from 'node:url';

import { retainArtifacts } from './uploads.mjs';

export { retainArtifacts };

function parseArguments(argv) {
  const options = {};

  for (let index = 0; index < argv.length; index += 1) {
    const argument = argv[index];

    switch (argument) {
      case '--output':
        options.outputDir = argv[(index += 1)];
        break;
      case '--destination':
        options.destinationDir = argv[(index += 1)];
        break;
      default:
        throw new Error(`Unknown argument: ${argument}`);
    }
  }

  return options;
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
  try {
    const { outputDir, destinationDir } = parseArguments(process.argv.slice(2));

    if (!outputDir || !destinationDir) {
      throw new Error('Usage: retain.mjs --output <dir> --destination <dir>');
    }

    const manifest = retainArtifacts(outputDir, destinationDir);

    process.stdout.write(
      `retained ${manifest.files.length} allowlisted artifacts in ${destinationDir}\n`,
    );
  } catch (error) {
    process.stderr.write(`${error instanceof Error ? error.message : String(error)}\n`);
    process.exit(1);
  }
}
