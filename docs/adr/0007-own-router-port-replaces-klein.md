# 0007 - Own Router port in the web module replaces Klein

Status: Accepted (2026-10-31)

## Context

The web module dispatches through Klein (`BootstrapBase`/`Bootstrap`), an unmaintained library used almost entirely as a thin dispatcher: its only matching rule is the catch-all `'@(?!/api\.php)'`, while the actual route interpretation already lives in sysPass code (`RouteContext` regex → `RouteContextData` → convention-based controller/method resolution via `#[Action]` reflection). The clean-URL redesign touches every dispatch call site anyway.

## Decision

Routing goes through our own `Router` port in `lib/SP` (Domain/Http ports), implemented with a small in-house matcher that consumes the declared `#[Action]` routes, verbs and named parameters. Klein is removed from the web module through an expand-contract sequence: the web Entry Point switches to the new Router first, then Klein request/response types are replaced behind `RequestService`, `ControllerTrait` and the web helpers, and only then is the Klein code path deleted. The API module keeps its current dispatch (out of scope per the routing assessment), so the Klein composer dependency stays until the API Entry Point is ported in a separate decision.

## Considered options

- **Keep Klein, layer clean URLs on top.** Rejected: an unmaintained dependency in the core request path, matching nothing meaningful, and every new capability (verb enforcement, named params, middleware ordering) would fight its dispatch model.
- **Adopt Symfony Routing or nikic/fast-route.** Rejected for now: route declarations already come from `#[Action]` attributes and resolution from controller-convention; a matcher over that model is small and has zero new dependency surface. Revisit if routing needs grow beyond the convention model.

## Consequences

- Klein types are used beyond routing: `RequestService` wraps `Klein\Request` and exposes `Klein\DataCollection`, `HttpModuleBase` and `BootstrapBase` take `Klein\Klein`, `ControllerTrait` and the web helpers hold the router, `Minify` takes Klein request/response, and eight download controllers import `Klein\Response`. Replacing these is a wide refactor, sequenced expand-contract, not a single edit.
- `BootstrapBase` is shared with the API Entry Point, so `composer.json` keeps `klein/klein` until the API is ported. Removing it from the web module does not remove the dependency.
- Verb enforcement and 404/405 semantics live in our matcher, so they are testable without a web server.
