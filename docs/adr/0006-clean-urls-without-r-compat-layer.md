# 0006 - Clean URLs as the sole Route form, without a `?r=` compatibility layer

Status: Accepted (2026-10-31)

## Context

sysPass routing addresses Actions through the query parameter `index.php?r=controller/action[/params]` (parsed by `RouteContext`, dispatched by the web `Bootstrap`). An `?r=`-free design was assessed. Backward compatibility for `?r=` URLs (bookmarks, third-party integrations, JS in already-deployed themes) was explicitly weighed and rejected: sysPass routes are internal AJAX plumbing, not a public API, and the `?r=` surface in the client is small (~15 call sites in `app-actions.js` plus a few theme/controller literals).

## Decision

`?r=` is removed outright in the same change that introduces clean URLs. Canonical form is PATH_INFO (`index.php/controller/action/{named-params}`), with rewritten clean paths (`/controller/action/...`) accepted when the operator configures the optional rewrite template; both share one parser. The Base Path comes from an explicit configuration value first, falling back to auto-detection. Route Parameters are named segments declared on the `#[Action]` attribute and bound by name; positional segments are dropped. Actions declare their accepted HTTP verbs; the web module answers `404` for unmatched Routes and `405` for verb mismatch, with plain status bodies for both.

## Considered options

- **Dual-mode (`?r=` as fallback alongside clean URLs).** Rejected: compat was declined, and a fallback layer doubles the URL-generation surface (every builder would need two shapes) and delays deleting the query-route parser.
- **True rewrites only (clean paths mandatory).** Rejected as the baseline: it makes a web-server rewrite config the #1 install failure mode on varied self-hosted setups (Apache mod_php/FPM, nginx, sub-directory deployments). PATH_INFO works with zero server configuration.
- **Hash fragments or a single unified Entry Point for web+api.** Rejected; the API keeps its own Entry Point and JSON-RPC contract untouched.

## Consequences

- Existing bookmarks and any external tooling using `?r=` stop working on upgrade; this is deliberate and needs a release note.
- All URL output must flow through a single server-side URL builder (none exists today — URLs are string-concatenated), including `Init::getUriFor()` redirects and the `ajaxUrl.entrypoint` JS constant.
- Rewrite rules become an optional deployment concern rather than a hard requirement; the repo must ship template snippets (`if not exists → index.php`, excluding `api.php`).
