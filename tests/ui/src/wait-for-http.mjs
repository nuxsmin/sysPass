const argumentsMap = new Map();

for (let index = 2; index < process.argv.length; index += 2) {
  argumentsMap.set(process.argv[index], process.argv[index + 1]);
}

const url = argumentsMap.get('--url');
const marker = argumentsMap.get('--marker');
const timeoutSeconds = Number.parseInt(argumentsMap.get('--timeout') ?? '120', 10);

if (!url || !marker) {
  process.stderr.write('Usage: wait-for-http.mjs --url <url> --marker <text> [--timeout <seconds>]\n');
  process.exit(2);
}

const deadline = Date.now() + timeoutSeconds * 1000;
let lastError = 'no response';

while (Date.now() < deadline) {
  try {
    const response = await fetch(url, {
      redirect: 'error',
      signal: AbortSignal.timeout(5000),
      headers: {
        'User-Agent': 'sysPass-ui-smoke/1.0 (+https://github.com/nuxsmin/sysPass)',
      },
    });
    const body = await response.text();

    if (response.status === 200 && body.includes(marker)) {
      process.stdout.write(`ready: ${response.status}\n`);
      process.exit(0);
    }

    lastError = `status ${response.status}, marker ${body.includes(marker) ? 'present' : 'missing'}`;
  } catch (error) {
    lastError = error instanceof Error ? error.message : String(error);
  }

  await new Promise((resolve) => setTimeout(resolve, 500));
}

process.stderr.write(`UI server did not become ready: ${lastError}\n`);
process.exit(1);
