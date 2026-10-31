# sysPass

A self-hosted password manager. This glossary fixes the vocabulary for how HTTP requests reach application behaviour.

## Language

**Route**:
The mapping between a URI and the Action that handles it. A Route is the pairing, not the string and not the code.
_Avoid_: URL, path, endpoint (when meaning the mapping itself)

**Action**:
A unit of application behaviour that is addressable over HTTP, declared by annotating a controller method.
_Avoid_: endpoint, ajax call, service (an Action is not a domain service)

**Route Parameter**:
A named value extracted from a Route's URI and bound to the Action's argument of the same name.
_Avoid_: positional param, path segment (the segment is syntax; the Parameter is the bound value)

**Entry Point**:
The file through which one application module (web, api, cli) receives HTTP requests and bootstraps. Each module has exactly one Entry Point.
_Avoid_: front controller, index

**Base Path**:
The URL prefix under which the installation is served, empty when installed at a domain root.
_Avoid_: web root, subfolder
