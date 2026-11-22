import type { PageContract } from './contracts';
import { ADMIN_NAVIGATION_CASES } from './admin-navigation.mjs';

export interface AdminNavigationEntry {
  name: string;
  trigger: string;
  route: string;
  content: string[];
  selectors: string[];
}

export const ADMIN_NAVIGATION_ENTRIES: AdminNavigationEntry[] = ADMIN_NAVIGATION_CASES;

export function adminNavigationContract(entry: AdminNavigationEntry): PageContract {
  return {
    name: `admin navigation ${entry.route}`,
    actions: [{ route: entry.route, format: 'html', content: entry.content }],
    conditions: [
      {
        kind: 'count',
        selector: '#content',
        min: 1,
        label: `${entry.route} target content container`,
      },
      {
        kind: 'count',
        selector: '#content #search-rows',
        max: 0,
        label: `${entry.route} must replace stale account search content`,
      },
      ...entry.selectors.map((selector) => ({
        kind: 'count' as const,
        selector: `#content ${selector}`,
        min: 1,
        label: `${entry.route} rendered ${selector}`,
      })),
    ],
  };
}