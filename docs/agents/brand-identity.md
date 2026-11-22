# sysPass Brand Identity (AI Agent Guide)

Apply these guidelines when updating the sysPass Web interface, static assets, documentation, or public-facing materials in this repository. The approved identity is **Logo A — Primary (version 1.0.1, 21 November 2026)**. Its brand idea is: **Your passwords. Your infrastructure. Your control.**

> **Implementation status:** This document defines the target brand contract derived from the official sysPass brand identity package (v1.0.1). Existing `material-blue` theme files may still reflect legacy Material Design Lite styling until migrated; do not describe target rules or future dark-mode support as already shipped unless verified in code.

## Non-Negotiable Constraints

**MUST**

1. **Preserve application behavior:** Keep all authentication, 2FA, ACLs, encryption, Master Key handling, routes, JSON-RPC API contracts, imports/exports, i18n (`__()`), and subpath/reverse-proxy deployments intact. Treat branding as presentation-layer work.
2. **Preserve Render Contracts:** Keep DOM roots (`#wrap`, `#container`, `#content`) and `data-*` dispatch attributes (`data-onclick`, `data-onsubmit`, `data-route`, `data-action-route`, `data-view`) used by View Binders, `#[BodyChecker]` HTTP integration tests, and Playwright UI tests.
3. **Preserve theme resolution:** Keep the `material-blue` theme identifier working for stored `siteTheme` and per-user preferences.
4. **Use approved Logo A v1.0.1 assets only:** Never redraw, trace, stretch, rotate, add glows/effects, overlay text, or invent colorways. Keep the v1.0.1 concealed shackle seam at the padlock body; never reintroduce visible lower shackle edges inside the body.
5. **Target WCAG 2.2 AA in actual rendered UI:** Verify ≥4.5:1 contrast for normal text, ≥3:1 for meaningful nontext elements/focus indicators, visible keyboard focus, accessible names, and text/icon status (never color alone).

**MUST NOT**

- Fetch external web fonts, remote CDNs, or inline trackers, or ship unapproved third-party font binaries.
- Weakly alter security flows, session handling, password encryption, user-enumeration behavior, or permission inheritance for visual reasons.
- Include real passwords, tokens, keys, private URLs, or sensitive logs in screenshots, previews, or test fixtures.
- Claim that third-party trademark/similarity clearance, formal accessibility audits, or maintainer integration are complete without evidence.

## Positioning and Voice

sysPass is an open-source, self-hosted password manager for teams and individuals. Emphasize infrastructure ownership, granular credential sharing, administrative controls, transparency, and practicality. Use a clear, calm, precise, human technical voice without jargon overload. Never claim sysPass is breach-proof or independently certified without verification.

## Logo and Asset Usage

The identity combines a geometric outline padlock, a custom radial six-arm asterisk, and an upright two-tone wordmark (`sys` in ink, `Pass` in core indigo). Wordmarks are vector outlines and require no installed fonts. Reversed SVGs have transparent backgrounds—always supply a solid dark or brand surface underneath.

| Context | Approved Asset | Rules |
|---|---|---|
| Light header / masthead | `syspass-horizontal-primary.svg` | Recommended ≥160 CSS px wide; validate with localized layouts |
| Dark or indigo header / footer | `syspass-horizontal-reversed.svg` | Transparent SVG; render over solid dark or `#3949AB` surface |
| Centered sign-in / hero | `syspass-stacked-primary.svg` | Use where vertical space allows; use `*-reversed.svg` on dark surfaces |
| Compact sidebar / narrow nav | `syspass-symbol-primary.svg` | Recommended ≥24 CSS px; use `*-reversed.svg` on dark surfaces |
| App icon / PWA / launcher | `syspass-app-icon.svg`, `icon-*.png` | Square tile (`16`–`512` px); visually verify `16px` rasterization and maskable safe areas |
| Browser favicon | `favicon.svg`, `favicon.ico` | Never shrink the horizontal wordmark to favicon size |
| Single-color / print | `*-monochrome-black.svg`, `*-monochrome-white.svg` | Use supplied variant; never simulate with CSS filters |

- **Clear space:** Maintain at least **24% of the symbol width** around all logos.
- **Alternative text:** If the logo links to the application home, give the interactive control an accessible name such as `sysPass home`; hide duplicate decorative icons from assistive technology (`aria-hidden="true"`).
- **Subpath & caching:** Resolve all asset, favicon, `apple-touch-icon`, and `manifest.webmanifest` URLs through sysPass's base URL / `UriContextInterface` so reverse-proxy and subpath deployments work cleanly, and update cache-busting parameters when assets change.

## Color Tokens

The canonical machine-readable palette is `tokens.json` from the brand package. Map semantic roles into CSS custom properties and SCSS variables rather than hardcoding hex literals in templates or stylesheets.

### Brand Primitives

| Token | Hex | Role |
|---|---|---|
| `brand.core` | `#3949AB` | Core indigo — primary identity, light-mode actions and links |
| `brand.heritage` | `#5C6BC0` | Heritage indigo — secondary accents only |
| `brand.ink` | `#263544` | Ink — light-mode text and wordmark |
| `brand.slate` | `#526977` | Slate — muted labels on white |
| `brand.mist` | `#E8EAF6` | Mist — subtle selection / alternate surface |
| `brand.white` | `#FFFFFF` | White — light surfaces and reversed wordmark |

### Semantic Theme Roles

| CSS Variable | Light (Phase 1 target) | Dark (Future phase) |
|---|---|---|
| `--sp-background` | `#FFFFFF` | `#131B2B` |
| `--sp-surface` | `#F5F7FC` | `#1C2940` |
| `--sp-surface-alt` | `#E8EAF6` | `#283453` |
| `--sp-text` | `#263544` | `#F5F7FC` |
| `--sp-muted` | `#526977` | `#C0CCDE` |
| `--sp-border` | `#D7DEEC` | `#475875` |
| `--sp-accent` | `#3949AB` | `#AEBBFF` |
| `--sp-on-accent` | `#FFFFFF` | `#131B2B` |
| `--sp-focus` | `#3949AB` | `#B8C3FF` |
| `--sp-success` | `#2E7D32` | `#81C995` |
| `--sp-error` | `#C62828` | `#FF9494` |
| `--sp-warning` | `#8A5800` | `#FFD27A` |
| `--sp-info` | `#0369A1` | `#84CFFF` |

> **CSS Integration Caveat:** The reference `syspass-theme.css` sets `background: var(--sp-bg)` on `.sp-app` while defining `--sp-background`. Do **not** copy `var(--sp-bg)` verbatim; always reference `var(--sp-background)`. Keep theme rules scoped (e.g., `.sp-app` with `data-theme="light"` / `"dark"` / `"auto"` or mapped theme tokens) and keep semantic status colors distinct from brand indigo.

## Typography, Rhythm, and Iconography

- **Font stack:** `Inter, Roboto, system-ui, sans-serif` for UI (locally bundled `Roboto` WOFF2 and system fallbacks are compliant without adding remote font requests); `ui-monospace, SFMono-Regular, Consolas, monospace` for code, API examples, CLI, and log snippets.
- **Scale:** Body default `16px` (`line-height: 1.5`); dense data tables `≥14px` where practicable; caption minimum `12px` (never use tiny text for essential instructions). Avoid uppercase microtaglines near logos.
- **Tabular numerals:** Apply `font-variant-numeric: tabular-nums` to timestamps, dates, and counters.
- **Geometry & motion:** `4px` spacing base (`4, 8, 12, 16, 24, 32`), radii `6px` (fields), `10px` (buttons/small surfaces), `16px` (main panels); motion `120ms` (quick) / `180ms` (standard) with `@media (prefers-reduced-motion: reduce)` disabling nonessential transitions.
- **Icons:** Prefer consistent `24px` rounded-stroke icons (~`2px` stroke); reserve the proprietary six-arm asterisk strictly for the sysPass brand mark.

## Application Surface Rules

- **Header & drawer (`fixed-header.inc`):** Use a **Core Indigo (`#3949AB`)** header with `syspass-horizontal-reversed.svg` on wide viewports and `syspass-symbol-reversed.svg` at compact breakpoints with an accessible `sysPass home` label. Style active nav items with indigo accent **plus** weight/background/border, keeping keyboard focus visually distinct.
- **Sign-in, password reset & 2FA (`login/index.inc`, `userPassReset/`):** Place the approved logo above the sign-in form; provide visible labels (not placeholder-only inputs); implement password reveal controls as accessible `<button>` elements with dynamic `Show password` / `Hide password` labels rather than hover-only icons; preserve non-enumerating error semantics and keyboard-friendly 2FA flows.
- **Credentials, tables, tags & public links:** Keep surfaces neutral, using indigo for primary actions/links/active states. Communicate `Shared`, `Private`, `Expired`, and public-link states via text (plus optional icon), never color alone. Ensure long names/URLs remain accessible via keyboard without losing security-relevant context.
- **Users, groups, profiles & permissions:** Label `granted`, `denied`, and `inherited` states explicitly in text/shape as well as color; preserve destructive confirmations styled with semantic error/warning tokens.
- **Audit history, notifications & API:** Display full timestamps (tabular numerals, consistent timezone) and text severity labels alongside status colors.

## Validation and Reporting Contract

Before concluding a branding change, verify:
- PHP unit/integration/architecture suites via `"$PHP_BIN"`, JS tests (`npm run test:js`), and asset rebuilds (`scripts/build-assets.sh`).
- Browser rendering (Playwright UI smoke/workflow tests, light/dark where enabled, ~320 CSS px viewport, 200–400% zoom, `16/24/32px` icon rendering, `prefers-reduced-motion`, subpath deployment, and WCAG 2.2 AA contrast).
- Factual completion summary covering: `BRAND_IDENTITY` (`sysPass Logo A / Primary v1.0.1`), `SCOPE`, `FILES_CHANGED`, `ASSETS_IMPORTED`, `TOKENS_MAPPED`, `TESTS_RUN`, `VISUAL_QA`, `ACCESSIBILITY_QA`, `FUNCTIONAL_REGRESSION`, and `UNRESOLVED`.
