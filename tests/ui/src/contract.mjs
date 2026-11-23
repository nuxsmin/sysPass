/**
 * @typedef {object} Condition
 * @property {'visible'|'hidden'|'count'|'text'|'class'|'attribute'} kind
 * @property {string} selector
 * @property {number} [min]
 * @property {number} [max]
 * @property {string} [contains]
 * @property {string} [name]
 * @property {string} [value]
 * @property {string} [label]
 */

/**
 * @typedef {object} DomProbe
 * @property {(selector: string) => Promise<boolean>} isVisible
 * @property {(selector: string) => Promise<number>} count
 * @property {(selector: string) => Promise<string>} text
 * @property {(selector: string, name: string) => Promise<string | null>} attribute
 */

/**
 * @param {Condition} condition
 * @returns {string}
 */
export function describeCondition(condition) {
  if (condition.label) {
    return condition.label;
  }

  switch (condition.kind) {
    case 'visible':
      return `${condition.selector} must be visible`;
    case 'hidden':
      return `${condition.selector} must be hidden`;
    case 'count':
      return `${condition.selector} must have between ${condition.min ?? 0} and ${condition.max ?? 'any'} matches`;
    case 'text':
      return `${condition.selector} must contain ${JSON.stringify(condition.contains)}`;
    case 'class':
      return `${condition.selector} must have class ${condition.name}`;
    case 'attribute':
      return condition.value === undefined
        ? `${condition.selector} must have attribute ${condition.name}`
        : `${condition.selector} must have attribute ${condition.name}=${JSON.stringify(condition.value)}`;
    default:
      return `${condition.selector} must satisfy ${String(condition.kind)}`;
  }
}

/**
 * @param {Condition} condition
 * @param {DomProbe} probe
 * @returns {Promise<boolean>}
 */
async function isSatisfied(condition, probe) {
  switch (condition.kind) {
    case 'visible':
      return probe.isVisible(condition.selector);
    case 'hidden':
      return !(await probe.isVisible(condition.selector));
    case 'count': {
      const count = await probe.count(condition.selector);

      return count >= (condition.min ?? 0) && (condition.max === undefined || count <= condition.max);
    }
    case 'text':
      return (await probe.text(condition.selector)).includes(condition.contains ?? '');
    case 'class': {
      const value = (await probe.attribute(condition.selector, 'class')) ?? '';

      return value.split(/\s+/).includes(condition.name ?? '');
    }
    case 'attribute': {
      const value = await probe.attribute(condition.selector, condition.name ?? '');

      return condition.value === undefined ? value !== null : value === condition.value;
    }
    default:
      return false;
  }
}

/**
 * Evaluate a page contract's DOM conditions against the current page.
 *
 * @param {Condition[]} conditions
 * @param {DomProbe} probe
 * @returns {Promise<string[]>}
 */
export async function unmetConditions(conditions, probe) {
  const problems = [];

  for (const condition of conditions) {
    let satisfied = false;

    try {
      satisfied = await isSatisfied(condition, probe);
    } catch {
      satisfied = false;
    }

    if (!satisfied) {
      problems.push(describeCondition(condition));
    }
  }

  return problems;
}

/**
 * Poll DOM conditions until they are all met or the deadline expires. Reports
 * exactly which selectors/conditions remain unmet rather than a generic
 * timeout.
 *
 * @param {Condition[]} conditions
 * @param {DomProbe} probe
 * @param {number} deadline
 * @param {object} [options]
 * @param {() => number} [options.now]
 * @param {(ms: number) => Promise<void>} [options.sleep]
 */
export async function waitForConditions(
  conditions,
  probe,
  deadline,
  { now = () => Date.now(), sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms)) } = {},
) {
  let problems = await unmetConditions(conditions, probe);

  while (problems.length > 0 && now() < deadline) {
    await sleep(50);
    problems = await unmetConditions(conditions, probe);
  }

  if (problems.length > 0) {
    throw new Error(`Page readiness DOM/overlay conditions were not met: ${problems.join('; ')}`);
  }
}
