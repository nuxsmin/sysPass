import { createHash } from 'node:crypto';
import { copyFileSync, mkdirSync, readdirSync, readFileSync, statSync, writeFileSync } from 'node:fs';
import path from 'node:path';

export const ALLOWED_ROOT_ARTIFACTS = new Set([
  'run-summary.json',
  'junit.xml',
  'upload-manifest.json',
]);

const ALLOWED_FAILURE_FILES = new Set(['screenshot.png', 'console.log', 'failure.json']);
const FORBIDDEN_EXTENSIONS = new Set([
  '.zip',
  '.webm',
  '.mp4',
  '.har',
  '.trace',
  '.trace.zip',
  '.pem',
  '.key',
  '.crt',
]);
const FORBIDDEN_NAME =
  /(?:^|[-_.])(auth|storage|cookie|session|config|fixture|secret|private|key|trace|video|har|request|response|body|dom|snapshot)(?:[-_.]|$)/i;

function toPosix(relativePath) {
  return String(relativePath).replaceAll('\\', '/');
}

/**
 * @param {string} relativePath
 * @returns {string | null} normalized path or null when the path escapes
 */
export function normalizeArtifactPath(relativePath) {
  const value = toPosix(relativePath).replace(/^\.\//, '');

  if (value === '' || value.startsWith('/') || /^[A-Za-z]:\//.test(value)) {
    return null;
  }

  const segments = value.split('/');

  if (segments.some((segment) => segment === '' || segment === '.' || segment === '..')) {
    return null;
  }

  return value;
}

/**
 * @param {string} relativePath
 * @returns {boolean}
 */
export function isAllowedArtifact(relativePath) {
  return forbiddenReason(relativePath) === null;
}

/**
 * Return a human-readable reason when a path is not uploadable, or null when
 * it is on the explicit allowlist.
 *
 * @param {string} relativePath
 * @returns {string | null}
 */
export function forbiddenReason(relativePath) {
  const normalized = normalizeArtifactPath(relativePath);

  if (normalized === null) {
    return `not on the upload allowlist: unsafe path ${String(relativePath)}`;
  }

  if (ALLOWED_ROOT_ARTIFACTS.has(normalized)) {
    return null;
  }

  const segments = normalized.split('/');
  const basename = segments.at(-1) ?? '';
  const extension = path.posix.extname(basename).toLowerCase();

  if (FORBIDDEN_EXTENSIONS.has(extension) || basename.toLowerCase().endsWith('.trace.zip')) {
    return `forbidden artifact type: ${normalized}`;
  }

  if (FORBIDDEN_NAME.test(normalized)) {
    return `forbidden artifact name: ${normalized}`;
  }

  if (segments[0] === 'failures' && segments.length >= 3 && ALLOWED_FAILURE_FILES.has(basename)) {
    return null;
  }

  return `not on the upload allowlist: ${normalized}`;
}

/**
 * @param {string[]} relativePaths
 * @returns {string[]}
 */
export function allowedArtifacts(relativePaths) {
  return relativePaths
    .map((relativePath) => normalizeArtifactPath(relativePath))
    .filter((relativePath) => relativePath !== null && isAllowedArtifact(relativePath))
    .sort();
}

/**
 * @param {{path: string, contents: string | Buffer}[]} files
 * @param {{now?: Date}} [options]
 * @returns {{schemaVersion: number, generatedAt: string, files: {path: string, bytes: number, sha256: string}[]}}
 */
export function buildUploadManifest(files, { now = new Date() } = {}) {
  const entries = [];

  for (const file of files) {
    const normalized = normalizeArtifactPath(file.path);

    if (normalized === null || !isAllowedArtifact(normalized)) {
      continue;
    }

    const contents = Buffer.isBuffer(file.contents) ? file.contents : Buffer.from(String(file.contents));

    entries.push({
      path: normalized,
      bytes: contents.length,
      sha256: createHash('sha256').update(contents).digest('hex'),
    });
  }

  entries.sort((left, right) => left.path.localeCompare(right.path));

  return {
    schemaVersion: 1,
    generatedAt: now.toISOString(),
    files: entries,
  };
}

/**
 * @param {string} root
 * @param {string} [relative]
 * @returns {string[]}
 */
export function listArtifacts(root, relative = '') {
  const directory = path.join(root, relative);
  const entries = [];

  for (const name of readdirSync(directory).sort()) {
    const childRelative = relative ? `${relative}/${name}` : name;
    const target = path.join(root, childRelative);
    const stat = statSync(target);

    if (stat.isDirectory()) {
      entries.push(...listArtifacts(root, childRelative));
    } else if (stat.isFile()) {
      entries.push(toPosix(childRelative));
    }
  }

  return entries;
}

/**
 * Copy only allowlisted artifacts from an output directory into a durable
 * retention directory and write the upload manifest for CI.
 *
 * @param {string} outputDir
 * @param {string} destinationDir
 * @param {{now?: Date}} [options]
 * @returns {{schemaVersion: number, generatedAt: string, files: {path: string, bytes: number, sha256: string}[]}}
 */
export function retainArtifacts(outputDir, destinationDir, { now = new Date() } = {}) {
  const allowed = allowedArtifacts(listArtifacts(outputDir)).filter(
    (relativePath) => relativePath !== 'upload-manifest.json',
  );
  const files = [];

  mkdirSync(destinationDir, { recursive: true });

  for (const relativePath of allowed) {
    const source = path.join(outputDir, relativePath);
    const destination = path.join(destinationDir, relativePath);

    mkdirSync(path.dirname(destination), { recursive: true });
    copyFileSync(source, destination);
    files.push({ path: relativePath, contents: readFileSync(source) });
  }

  const manifest = buildUploadManifest(files, { now });

  writeFileSync(
    path.join(destinationDir, 'upload-manifest.json'),
    `${JSON.stringify(manifest, null, 2)}\n`,
  );

  return manifest;
}
