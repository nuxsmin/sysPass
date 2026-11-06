import { existsSync, readFileSync } from 'node:fs';

export const REGISTRY_SCHEMA_VERSION = 1;
export const REGISTRY_FILENAME = 'known-failures.json';

export const ADMISSIBLE_FAILURE_KINDS = Object.freeze([
  'http',
  'json-status',
  'request-failed',
  'body-inspection',
  'missing-content',
  'empty-body',
]);

export const NON_DETERMINISTIC_FAILURE_KINDS = Object.freeze([
  'flaky',
  'setup',
  'setup-failure',
  'setup-isolation',
  'setup/isolation',
  'isolation',
  'session-loss',
  'invalid-response',
  'login-navigation',
  'login-page',
  'test-error',
]);

const ENTRY_FIELDS = new Set([
  'spec',
  'route',
  'failureKind',
  'reason',
  'defect',
  'admission',
]);
const ADMISSION_FIELDS = new Set(['who', 'date', 'ref']);
const TOP_LEVEL_FIELDS = new Set(['schemaVersion', 'entries']);
const SPEC_NAME = /^[A-Za-z0-9_.-]+\.spec\.ts$/;
const ROUTE = /^[A-Za-z0-9_./-]+$/;
const KIND = /^[a-z][a-z0-9-]*$/;
const ISO_DATE = /^\d{4}-\d{2}-\d{2}$/;
const ISSUE_LINK = /^(?:#\d+|https?:\/\/github\.com\/[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+\/issues\/\d+)$/;
const FIXED_REGRESSION_REFS = new Set([
  '#2079',
  'https://github.com/nuxsmin/sysPass/issues/2079',
]);
const DETECTOR_PREFIX = /(?:^|[;\s])([a-z][a-z0-9-]*) \[([A-Za-z0-9_./-]+)\]:/;
const DETECTOR_PROBLEM =
  /(?:^|[|;\n\s]+)([a-z][a-z0-9-]*)(?: \[([A-Za-z0-9_./-]+)\])?:(?!\/\/)/g;
const DETECTOR_PROBLEM_KINDS = new Set([
  'body-inspection',
  'console',
  'empty-body',
  'http',
  'invalid-response',
  'json-envelope',
  'json-malformed',
  'json-status',
  'login-navigation',
  'login-page',
  'markup',
  'missing-content',
  'page-error',
  'request-failed',
  'session-loss',
]);

function isPlainObject(value) {
  return value !== null && typeof value === 'object' && !Array.isArray(value);
}

function text(value) {
  return typeof value === 'string' ? value.trim() : '';
}

function invalidDate(value) {
  if (!ISO_DATE.test(value)) {
    return true;
  }

  const date = new Date(`${value}T00:00:00.000Z`);

  return Number.isNaN(date.getTime()) || date.toISOString().slice(0, 10) !== value;
}

function validateExactKey(value, label, pattern, errors) {
  if (typeof value !== 'string' || value.trim() === '') {
    errors.push(`${label} is required as an exact literal`);
    return;
  }

  if (!pattern.test(value) || /[()[\]{}*+?^$|\\]/.test(value)) {
    errors.push(`${label} must be an exact literal, not a regular expression or glob`);
  }
}

function validateIssueLink(value, label, errors) {
  const link = text(value);

  if (link === '') {
    errors.push(`${label} is required`);
    return;
  }

  if (!ISSUE_LINK.test(link)) {
    errors.push(`${label} must be a linked issue (#<number> or GitHub issue URL)`);
    return;
  }

  if (FIXED_REGRESSION_REFS.has(link)) {
    errors.push(`${label} ${link} is a fixed regression and must not be admitted`);
  }
}

function validateAdmission(admission, index, errors) {
  if (!isPlainObject(admission)) {
    errors.push(`entries[${index}].admission must be an object with who, date and ref`);
    return;
  }

  for (const field of Object.keys(admission)) {
    if (!ADMISSION_FIELDS.has(field)) {
      errors.push(`entries[${index}].admission has an unknown field "${field}"`);
    }
  }

  if (text(admission.who) === '') {
    errors.push(`entries[${index}].admission.who is required`);
  }

  if (text(admission.date) === '') {
    errors.push(`entries[${index}].admission.date is required`);
  } else if (invalidDate(text(admission.date))) {
    errors.push(`entries[${index}].admission.date must be a valid YYYY-MM-DD date`);
  }

  validateIssueLink(admission.ref, `entries[${index}].admission.ref`, errors);
}

function normalizeEntry(entry) {
  return {
    spec: entry.spec,
    route: entry.route,
    failureKind: entry.failureKind,
    reason: entry.reason,
    defect: entry.defect,
    admission: {
      who: entry.admission.who,
      date: entry.admission.date,
      ref: entry.admission.ref,
    },
  };
}

export function parseRegistry(value) {
  const errors = [];
  let data = value;

  if (typeof value === 'string') {
    try {
      data = JSON.parse(value);
    } catch {
      return { ok: false, errors: ['known failure registry is not valid JSON'], entries: [] };
    }
  }

  if (!isPlainObject(data)) {
    return { ok: false, errors: ['known failure registry must be a JSON object'], entries: [] };
  }

  for (const field of Object.keys(data)) {
    if (!TOP_LEVEL_FIELDS.has(field)) {
      errors.push(`known failure registry has an unknown field "${field}"`);
    }
  }

  if (data.schemaVersion !== REGISTRY_SCHEMA_VERSION) {
    errors.push(`known failure registry schemaVersion must be ${REGISTRY_SCHEMA_VERSION}`);
  }

  if (!Array.isArray(data.entries)) {
    errors.push('known failure registry entries must be an array');
    return { ok: false, errors, entries: [] };
  }

  const seen = new Set();
  const entries = [];

  data.entries.forEach((entry, index) => {
    const before = errors.length;

    if (!isPlainObject(entry)) {
      errors.push(`entries[${index}] must be an object`);
      return;
    }

    for (const field of Object.keys(entry)) {
      if (!ENTRY_FIELDS.has(field)) {
        errors.push(`entries[${index}] has an unknown field "${field}"`);
      }
    }

    validateExactKey(entry.spec, `entries[${index}].spec`, SPEC_NAME, errors);
    validateExactKey(entry.route, `entries[${index}].route`, ROUTE, errors);

    if (typeof entry.failureKind !== 'string' || !KIND.test(entry.failureKind)) {
      errors.push(`entries[${index}].failureKind must be an exact literal failure kind`);
    } else if (NON_DETERMINISTIC_FAILURE_KINDS.includes(entry.failureKind)) {
      errors.push(
        `entries[${index}].failureKind "${entry.failureKind}" is not an admissible deterministic failure kind`,
      );
    } else if (!ADMISSIBLE_FAILURE_KINDS.includes(entry.failureKind)) {
      errors.push(`entries[${index}].failureKind "${entry.failureKind}" is an unknown failure kind`);
    }

    if (text(entry.reason) === '') {
      errors.push(`entries[${index}].reason is required`);
    }

    validateIssueLink(entry.defect, `entries[${index}].defect`, errors);

    validateAdmission(entry.admission, index, errors);

    if (errors.length > before) {
      return;
    }

    const key = `${entry.spec}\u0000${entry.route}\u0000${entry.failureKind}`;

    if (seen.has(key)) {
      errors.push(
        `entries[${index}] is a duplicate of (${entry.spec}, ${entry.route}, ${entry.failureKind})`,
      );
      return;
    }

    seen.add(key);
    entries.push(normalizeEntry(entry));
  });

  return { ok: errors.length === 0, errors, entries: errors.length === 0 ? entries : [] };
}

export function normalizeSpecName(value) {
  return String(value ?? '')
    .trim()
    .replaceAll('\\', '/')
    .split('/')
    .at(-1);
}

/**
 * Load and validate the known-failure registry from disk. A missing or
 * unreadable file is a fail-closed error, never an empty registry.
 *
 * @param {string} filePath
 * @returns {{ok: boolean, errors: string[], entries: object[]}}
 */
export function loadKnownFailureRegistry(filePath) {
  if (!filePath || !existsSync(filePath)) {
    return {
      ok: false,
      errors: [`known failure registry is missing: ${filePath || '<unset>'}`],
      entries: [],
    };
  }

  try {
    return parseRegistry(readFileSync(filePath, 'utf8'));
  } catch (error) {
    return {
      ok: false,
      errors: [
        `known failure registry could not be read: ${error instanceof Error ? error.message : String(error)}`,
      ],
      entries: [],
    };
  }
}

/**
 * Extract every detector problem from a failure message. A single attempt can
 * carry several `kind [route]: message` problems joined by ` | ` or newlines;
 * each one is an independent admission key.
 *
 * @param {string} message
 * @returns {{route: string | null, failureKind: string}[]}
 */
export function extractFailureProblems(message) {
  const value = String(message ?? '');
  const pattern = new RegExp(DETECTOR_PROBLEM);
  const problems = [];
  const seen = new Set();
  let match;

  while ((match = pattern.exec(value)) !== null) {
    const failureKind = match[1];

    if (!DETECTOR_PROBLEM_KINDS.has(failureKind)) {
      continue;
    }

    const route = match[2] ?? null;
    const key = `${failureKind}\u0000${route ?? ''}`;

    if (seen.has(key)) {
      continue;
    }

    seen.add(key);
    problems.push({ route, failureKind });
  }

  return problems;
}

export function extractFailureRoute(message) {
  const problems = extractFailureProblems(message);

  if (problems.length > 0) {
    return problems[0].route;
  }

  const match = DETECTOR_PREFIX.exec(String(message ?? ''));

  return match?.[2] ?? null;
}

function failureKey(spec, route, failureKind) {
  return `${spec}\u0000${route}\u0000${failureKind}`;
}

export function isFailingAttempt(attempt) {
  return attempt.status !== 'passed' && attempt.status !== 'skipped' && attempt.status !== 'unknown';
}

function canBeAdmittedStatus(attempt) {
  return attempt.status === 'failed' || attempt.status === 'timedOut';
}

export function isFlakyTest(test) {
  return test.attempts.length > 1 && test.firstAttemptStatus !== 'passed' && test.finalStatus === 'passed';
}

function attemptProblemKeys(attempt) {
  if (Array.isArray(attempt.failureProblems) && attempt.failureProblems.length > 0) {
    return attempt.failureProblems.map((problem) => ({
      route: problem.route ?? null,
      failureKind: problem.failureKind ?? null,
    }));
  }

  const extracted = extractFailureProblems(attempt.errorMessage);

  if (extracted.length > 0) {
    return extracted;
  }

  const route = attempt.route ?? extractFailureRoute(attempt.errorMessage);

  return [
    {
      route: route ?? null,
      failureKind: attempt.failureClass ?? 'unknown',
    },
  ];
}

function removalMessage(entry) {
  return [
    `Known failure (${entry.spec}, ${entry.route}, ${entry.failureKind}) no longer occurs`,
    'after its spec executed; remove the stale entry from tests/ui/known-failures.json.',
    'A filtered, skipped or unexecuted spec is never evidence that the defect was fixed.',
  ].join(' ');
}

function annotation(test, attempt, problem, entry) {
  return {
    spec: test.file,
    route: problem.route,
    failureKind: problem.failureKind,
    reason: entry.reason,
    defect: entry.defect,
    admission: { ...entry.admission },
    title: test.title,
    attempt: attempt.attempt,
    firstAttempt: attempt.firstAttempt,
    status: attempt.status,
  };
}

function failureRecord(test, attempt, problem) {
  return {
    spec: test.file,
    route: problem.route ?? null,
    failureKind: problem.failureKind ?? 'unknown',
    title: test.title,
    attempt: attempt.attempt,
    firstAttempt: attempt.firstAttempt,
    status: attempt.status,
  };
}

function knownFailureSummary(entry) {
  return {
    reason: entry.reason,
    defect: entry.defect,
    admission: { ...entry.admission },
  };
}

export function annotateKnownFailures(tests, entries = []) {
  const byKey = new Map(
    entries.map((entry) => [
      failureKey(normalizeSpecName(entry.spec), entry.route, entry.failureKind),
      entry,
    ]),
  );
  const executedSpecs = new Set(
    tests
      .filter(
        (test) =>
          !test.skipped &&
          test.attempts.some((attempt) => attempt.status !== 'skipped' && attempt.status !== 'unknown'),
      )
      .map((test) => normalizeSpecName(test.file)),
  );
  const observedKeys = new Set();
  const matchedKeys = new Set();
  const knownFailures = [];
  const newFailures = [];

  const annotatedTests = tests.map((test) => {
    const flaky = isFlakyTest(test);
    let testKnownFailure = null;

    const attempts = test.attempts.map((attempt) => {
      if (!isFailingAttempt(attempt)) {
        return {
          ...attempt,
          route: attempt.route ?? extractFailureRoute(attempt.errorMessage),
          failureProblems: attempt.failureProblems ?? [],
        };
      }

      const spec = normalizeSpecName(test.file);
      const problems = attemptProblemKeys(attempt).map((problem) => {
        const route = problem.route ?? extractFailureRoute(attempt.errorMessage);
        const key =
          route && problem.failureKind
            ? failureKey(spec, route, problem.failureKind)
            : null;

        if (key) {
          observedKeys.add(key);
        }

        const entry =
          !flaky && key && canBeAdmittedStatus(attempt) ? byKey.get(key) : undefined;

        return { ...problem, route, key, entry };
      });
      const attemptNotes = [];
      const evaluated = problems.map((problem) => {
        if (!problem.entry) {
          newFailures.push(failureRecord(test, attempt, problem));

          return {
            route: problem.route,
            failureKind: problem.failureKind,
            knownFailure: null,
          };
        }

        matchedKeys.add(problem.key);
        const note = annotation(test, attempt, problem, problem.entry);
        knownFailures.push(note);
        attemptNotes.push(note);

        if (!testKnownFailure) {
          testKnownFailure = note;
        }

        return {
          route: problem.route,
          failureKind: problem.failureKind,
          knownFailure: knownFailureSummary(problem.entry),
        };
      });

      return {
        ...attempt,
        route: attempt.route ?? evaluated[0]?.route ?? null,
        failureProblems: evaluated,
        knownFailure: evaluated.find((problem) => problem.knownFailure)?.knownFailure ?? null,
        knownFailures: attemptNotes.map((note) => ({
          reason: note.reason,
          defect: note.defect,
          admission: { ...note.admission },
        })),
      };
    });

    return {
      ...test,
      attempts,
      knownFailure: testKnownFailure,
    };
  });

  const staleEntries = [];
  const unexecutedEntries = [];

  for (const entry of entries) {
    const key = failureKey(normalizeSpecName(entry.spec), entry.route, entry.failureKind);

    if (matchedKeys.has(key) || observedKeys.has(key)) {
      continue;
    }

    if (executedSpecs.has(normalizeSpecName(entry.spec))) {
      staleEntries.push({
        ...entry,
        removal: removalMessage(entry),
      });
    } else {
      unexecutedEntries.push({
        ...entry,
        message:
          'Known failure entry was not exercised by this selection; it is not treated as fixed and cannot be removed on this evidence.',
      });
    }
  }

  return {
    tests: annotatedTests,
    knownFailures,
    newFailures,
    staleEntries,
    unexecutedEntries,
  };
}
