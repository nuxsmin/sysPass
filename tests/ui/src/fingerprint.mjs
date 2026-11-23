/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

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
