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
