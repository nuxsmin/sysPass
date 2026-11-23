import { createHash } from 'node:crypto';
import { readdirSync, readFileSync, lstatSync, realpathSync } from 'node:fs';
import path from 'node:path';

function collect(root) {
  const entries = [];

  const walk = (directory) => {
    for (const name of readdirSync(directory).sort()) {
      const target = path.join(directory, name);
      const relative = path.relative(root, target);
      const stat = lstatSync(target);

      if (stat.isSymbolicLink()) {
        entries.push(`${relative}\0symlink\0${readFileSync(target, 'utf8')}`);
      } else if (stat.isDirectory()) {
        walk(target);
      } else if (stat.isFile()) {
        const hash = createHash('sha256').update(readFileSync(target)).digest('hex');
        entries.push(`${relative}\0file\0${stat.size}\0${stat.mtimeMs}\0${hash}`);
      }
    }
  };

  walk(root);

  return entries;
}

const root = process.argv[2];

if (!root) {
  process.stderr.write('Usage: fingerprint.mjs <directory>\n');
  process.exit(2);
}

const canonicalRoot = realpathSync(root);
const entries = collect(canonicalRoot);
const digest = createHash('sha256').update(entries.join('\n')).digest('hex');

process.stdout.write(`${JSON.stringify({ root: canonicalRoot, digest, count: entries.length })}\n`);
