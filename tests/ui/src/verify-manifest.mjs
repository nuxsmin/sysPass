#!/usr/bin/env node
import { readFileSync, readdirSync, statSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import { forbiddenReason, normalizeArtifactPath } from './uploads.mjs';

export const REQUIRED_MANIFEST_ENTRIES = ['run-summary.json', 'junit.xml'];
export const MANIFEST_SELF = 'upload-manifest.json';

function listFiles(root, relative = '') {
  const directory = path.join(root, relative);
  const entries = [];

  for (const name of readdirSync(directory).sort()) {
    const childRelative = relative ? `${relative}/${name}` : name;
    const target = path.join(root, childRelative);
    const stat = statSync(target);

    if (stat.isDirectory()) {
      entries.push(...listFiles(root, childRelative));
    } else if (stat.isFile()) {
      entries.push(childRelative);
    }
  }

  return entries;
}

/**
 * Verify that an upload manifest describes exactly the retained files that are
 * on the explicit CI upload allowlist. Unknown, duplicate, unsafe or missing
 * entries fail closed so the workflow's hardcoded upload paths and the manifest
 * cannot silently diverge.
 *
 * @param {string} manifestPath
 * @param {string} directory
 * @returns {{files: string[]}}
 */
export function verifyUploadManifest(manifestPath, directory) {
  let manifest;

  try {
    manifest = JSON.parse(readFileSync(manifestPath, 'utf8'));
  } catch (error) {
    throw new Error(`upload manifest is not valid JSON: ${error instanceof Error ? error.message : String(error)}`);
  }

  if (!manifest || typeof manifest !== 'object' || !Array.isArray(manifest.files)) {
    throw new Error('upload manifest is invalid: expected a files array');
  }

  const listed = new Set();

  for (const entry of manifest.files) {
    if (!entry || typeof entry.path !== 'string') {
      throw new Error('upload manifest contains an entry without a string path');
    }

    const normalized = normalizeArtifactPath(entry.path);

    if (normalized === null) {
      throw new Error(`upload manifest contains an unsafe path: ${entry.path}`);
    }

    if (normalized === MANIFEST_SELF) {
      throw new Error('upload manifest must not list itself');
    }

    const reason = forbiddenReason(normalized);

    if (reason !== null) {
      throw new Error(`upload manifest contains an unknown entry: ${normalized} (${reason})`);
    }

    if (listed.has(normalized)) {
      throw new Error(`upload manifest lists a duplicate entry: ${normalized}`);
    }

    listed.add(normalized);
  }

  for (const required of REQUIRED_MANIFEST_ENTRIES) {
    if (!listed.has(required)) {
      throw new Error(`upload manifest is missing required entry: ${required}`);
    }
  }

  const actual = listFiles(directory)
    .map((file) => file.split(path.sep).join('/'))
    .filter((file) => file !== MANIFEST_SELF);
  const actualSet = new Set(actual);

  for (const file of actual) {
    const reason = forbiddenReason(file);

    if (reason !== null) {
      throw new Error(`retained file is outside the upload allowlist: ${file} (${reason})`);
    }

    if (!listed.has(file)) {
      throw new Error(`retained file is missing from the upload manifest: ${file}`);
    }
  }

  for (const file of listed) {
    if (!actualSet.has(file)) {
      throw new Error(`upload manifest entry is missing from the retained directory: ${file}`);
    }
  }

  return { files: [...listed].sort() };
}

function parseArguments(argv) {
  const options = {};

  for (let index = 0; index < argv.length; index += 1) {
    switch (argv[index]) {
      case '--manifest':
        options.manifestPath = argv[(index += 1)];
        break;
      case '--directory':
        options.directory = argv[(index += 1)];
        break;
      default:
        throw new Error(`Unknown argument: ${argv[index]}`);
    }
  }

  return options;
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
  try {
    const { manifestPath, directory } = parseArguments(process.argv.slice(2));

    if (!manifestPath || !directory) {
      throw new Error('Usage: verify-manifest.mjs --manifest <file> --directory <dir>');
    }

    const { files } = verifyUploadManifest(manifestPath, directory);

    process.stdout.write(`verified ${files.length} allowlisted upload artifacts\n`);
  } catch (error) {
    process.stderr.write(`ERROR: ${error instanceof Error ? error.message : String(error)}\n`);
    process.exit(1);
  }
}
