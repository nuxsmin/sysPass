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

import net from 'node:net';

const server = net.createServer();

server.once('error', (error) => {
  process.stderr.write(`${error.message}\n`);
  process.exit(1);
});

server.listen(0, '127.0.0.1', () => {
  const address = server.address();

  if (typeof address === 'string' || address === null) {
    process.stderr.write('unable to allocate a loopback port\n');
    process.exit(1);
  }

  process.stdout.write(`${address.port}\n`);
  server.close();
});
