<!-- Copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org -->

# Domain Docs

Use this guide when exploring domain behavior or changing its documentation.

## Before exploring, read these

- Read **`CONTEXT.md`** at the repository root for shared vocabulary.
- Read relevant decisions in **`docs/adr/`** and verify their implementation status against current source.

If required context is missing, record the gap and use source evidence. Follow [documentation maintenance](documentation.md) for updates.

## File structure

Repository layout:

```
/
├── CONTEXT.md
├── docs/adr/
│   └── numbered architecture decisions
├── lib/SP/Domain/                 # domain contexts
├── lib/SP/Infrastructure/         # driven adapters
├── lib/SP/Core/Definitions/       # composition root
└── app/modules/                  # Web, API and CLI adapters
```

## Use the glossary's vocabulary

When your output names a domain concept (in an issue title, a refactor proposal, a hypothesis, a test name), use the term as defined in `CONTEXT.md`. Don't drift to synonyms it explicitly avoids.

If the concept you need isn't in the glossary yet, that's a signal: either you're inventing language the project doesn't use (reconsider) or there's a real gap (note it for `/domain-modeling`).

## Flag ADR conflicts

If your output contradicts an existing ADR, surface it explicitly rather than silently overriding:

Name the actual decision, the conflicting behavior and the reason to reconsider it.
