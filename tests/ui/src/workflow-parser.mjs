import assert from 'node:assert/strict';

function indentOf(line) {
  return /^ */.exec(line)[0].length;
}

function isIgnorable(line) {
  const trimmed = line.trim();

  return trimmed === '' || trimmed.startsWith('#');
}

function unquote(value) {
  const trimmed = value.trim();

  if (
    (trimmed.startsWith("'") && trimmed.endsWith("'")) ||
    (trimmed.startsWith('"') && trimmed.endsWith('"'))
  ) {
    return trimmed.slice(1, -1);
  }

  return trimmed;
}

function flowList(value) {
  return value
    .trim()
    .replace(/^\[/, '')
    .replace(/\]$/, '')
    .split(',')
    .map(unquote)
    .filter(Boolean);
}

function dedentLines(content) {
  const indents = content.filter((line) => !isIgnorable(line)).map(indentOf);
  const base = indents.length > 0 ? Math.min(...indents) : 0;

  return content.map((line) => (isIgnorable(line) ? '' : line.slice(base)));
}

function parseNestedMapping(content, baseIndent) {
  const result = {};
  const keyPattern = new RegExp(`^ {${baseIndent}}([A-Za-z0-9_-]+):(?:\\s*(.*))?$`);

  for (let index = 0; index < content.length; index += 1) {
    const line = content[index];

    if (isIgnorable(line)) {
      continue;
    }

    const match = keyPattern.exec(line);

    if (!match) {
      throw new Error(`unparsable mapping entry at indent ${baseIndent}: ${line}`);
    }

    const key = match[1];
    let value = (match[2] ?? '').trim();

    if (value === '|' || value === '>') {
      const block = [];
      let cursor = index + 1;

      for (; cursor < content.length; cursor += 1) {
        if (!isIgnorable(content[cursor]) && indentOf(content[cursor]) <= baseIndent) {
          break;
        }

        block.push(content[cursor]);
      }

      index = cursor - 1;
      value = dedentLines(block).join('\n');
    }

    result[key] = value === '' ? undefined : unquote(value);
  }

  return result;
}

function childBlock(lines, startIndex, parentIndent) {
  const content = [];

  for (let index = startIndex + 1; index < lines.length; index += 1) {
    if (!isIgnorable(lines[index]) && indentOf(lines[index]) <= parentIndent) {
      break;
    }

    content.push(lines[index]);
  }

  return content;
}

function parseStepBlock(blockLines) {
  const step = {};
  const keyPattern = /^ {8}([A-Za-z0-9_-]+):(?:\s*(.*))?$/;

  for (let index = 0; index < blockLines.length; index += 1) {
    const line = blockLines[index];

    if (isIgnorable(line)) {
      continue;
    }

    const match = keyPattern.exec(line);

    if (!match) {
      throw new Error(`unparsable step entry: ${line}`);
    }

    const key = match[1];
    let value = (match[2] ?? '').trim();

    if (value === '|' || value === '>') {
      const block = [];
      let cursor = index + 1;

      for (; cursor < blockLines.length; cursor += 1) {
        if (!isIgnorable(blockLines[cursor]) && indentOf(blockLines[cursor]) <= 8) {
          break;
        }

        block.push(blockLines[cursor]);
      }

      index = cursor - 1;
      value = dedentLines(block).join('\n');
    } else if (value === '' && (key === 'with' || key === 'env')) {
      const nested = childBlock(blockLines, index, 8);

      index += nested.length;
      value = parseNestedMapping(nested, 10);
    }

    step[key] = value === '' ? undefined : typeof value === 'string' ? unquote(value) : value;
  }

  return step;
}

function parseSteps(lines, stepsIndex) {
  const blocks = [];
  let current = null;

  for (let index = stepsIndex + 1; index < lines.length; index += 1) {
    const line = lines[index];

    if (!isIgnorable(line) && indentOf(line) <= 4) {
      break;
    }

    if (/^ {6}- /.test(line)) {
      if (current) {
        blocks.push(current);
      }

      current = [`        ${line.replace(/^ {6}- /, '')}`];
    } else if (current) {
      current.push(line);
    } else {
      throw new Error(`unparsable steps section: expected a step at indent 6, got: ${line}`);
    }
  }

  if (current) {
    blocks.push(current);
  }

  return blocks.map(parseStepBlock);
}

export function parseJob(raw) {
  const lines = raw.split(/\r?\n/);
  const jobIndex = lines.findIndex((line) => /^ {2}ui:\s*$/.test(line));

  assert.notEqual(jobIndex, -1, 'the workflow must declare a ui job');

  let end = lines.length;

  for (let index = jobIndex + 1; index < lines.length; index += 1) {
    if (!isIgnorable(lines[index]) && indentOf(lines[index]) <= 2) {
      end = index;
      break;
    }
  }

  const jobLines = lines.slice(jobIndex + 1, end);
  const job = {};

  for (let index = 0; index < jobLines.length; index += 1) {
    const line = jobLines[index];

    if (isIgnorable(line)) {
      continue;
    }

    const indent = indentOf(line);

    if (indent > 4) {
      throw new Error(`unparsable job entry at indent ${indent}: ${line}`);
    }

    const match = /^ {4}([A-Za-z0-9_-]+):(?:\s*(.*))?$/.exec(line);

    if (!match) {
      throw new Error(`unparsable job key: ${line}`);
    }

    const key = match[1];
    let value = (match[2] ?? '').trim();

    if (value === '' && (key === 'env' || key === 'steps')) {
      const nested = childBlock(jobLines, index, 4);

      index += nested.length;

      if (key === 'env') {
        value = parseNestedMapping(nested, 6);
      }
    }

    job[key] = value === '' ? undefined : typeof value === 'string' ? unquote(value) : value;
  }

  const stepsIndex = jobLines.findIndex((line) => /^ {4}steps:\s*$/.test(line));
  job.steps = stepsIndex === -1 ? [] : parseSteps(jobLines, stepsIndex);

  return job;
}

export function parseTriggers(raw) {
  const lines = raw.split(/\r?\n/);
  const onIndex = lines.findIndex((line) => /^on:\s*$/.test(line));

  assert.notEqual(onIndex, -1, 'the workflow must declare triggers');

  const triggers = {};
  const block = childBlock(lines, onIndex, 0);

  for (let index = 0; index < block.length; index += 1) {
    const line = block[index];

    if (isIgnorable(line)) {
      continue;
    }

    const indent = indentOf(line);

    if (indent !== 2) {
      throw new Error(`unparsable trigger entry at indent ${indent}: ${line}`);
    }

    const match = /^ {2}([A-Za-z0-9_-]+):(?:\s*(.*))?$/.exec(line);

    if (!match) {
      throw new Error(`unparsable trigger key: ${line}`);
    }

    const key = match[1];
    const value = (match[2] ?? '').trim();
    const nested = childBlock(block, index, 2);

    index += nested.length;

    if (value !== '') {
      triggers[key] = unquote(value);
    } else if (key === 'pull_request' || key === 'push') {
      const branchesLine = nested.find((entry) => /^ {4}branches:/.test(entry));

      triggers[key] = {
        branches: branchesLine ? flowList(branchesLine.replace(/^ {4}branches:\s*/, '')) : [],
      };
    } else if (key === 'schedule') {
      triggers[key] = nested
        .filter((entry) => /^ {4}- cron:/.test(entry))
        .map((entry) => ({ cron: unquote(entry.replace(/^ {4}- cron:\s*/, '')) }));
    } else if (key === 'workflow_dispatch') {
      const inputs = {};

      for (let cursor = 0; cursor < nested.length; cursor += 1) {
        const inputMatch = /^ {6}([A-Za-z0-9_-]+):\s*$/.exec(nested[cursor]);

        if (inputMatch) {
          inputs[inputMatch[1]] = {};
        } else {
          const fieldMatch = /^ {8}([A-Za-z0-9_-]+):\s*(.*)$/.exec(nested[cursor]);
          const inputName = Object.keys(inputs).at(-1);

          if (fieldMatch && inputName) {
            inputs[inputName][fieldMatch[1]] = unquote(fieldMatch[2]);
          }
        }
      }

      triggers[key] = { inputs };
    } else {
      triggers[key] = {};
    }
  }

  return triggers;
}

/**
 * Parse the top-level permissions mapping. Tests use this to prove the
 * workflow keeps least privilege without depending on an external YAML
 * library.
 *
 * @param {string} raw
 * @returns {Record<string, string>}
 */
export function parsePermissions(raw) {
  const lines = raw.split(/\r?\n/);
  const permissionsIndex = lines.findIndex((line) => /^permissions:\s*$/.test(line));

  assert.notEqual(permissionsIndex, -1, 'the workflow must declare permissions');

  return parseNestedMapping(childBlock(lines, permissionsIndex, 0), 2);
}

/**
 * Parse the workflow structure used by the UI smoke assertions. This is a
 * deliberately small YAML subset parser: it understands only the indentation,
 * mappings, block scalars and flow lists this workflow uses.
 *
 * @param {string} raw
 * @returns {{triggers: Record<string, unknown>, job: Record<string, unknown>}}
 */
export function parseWorkflow(raw) {
  return {
    triggers: parseTriggers(raw),
    job: parseJob(raw),
  };
}
