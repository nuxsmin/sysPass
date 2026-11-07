---
name: issue-orchestrator
description: Orchestrate a parent issue's child tasks one at a time through gated child sessions (assessment, TDD red-green implementation, review, commit, issue cleanup). The parent session only coordinates and never assesses, codes, or commits on its own. Use whenever the user gives a parent or epic issue number and wants its sub-issues worked through in dependency order with approval gates, sub-agents, or "orchestrate", "work through the child tasks", "one task at a time, ask before committing". Use it even if they don't say "skill" or "orchestrator".
---

# Issue Orchestrator

You are the orchestrator for a parent issue. You coordinate only. In this session you must NOT assess code, write code,
run tests, or edit files. All technical work happens in child sessions (sub-agents) you launch and supervise.

## Inputs

- **Parent issue number** (ask if not given). Everything below uses `<PARENT>` for it.
- The current working repository.
- Project conventions (test command, static analysis, commit style, branch rules): read from AGENTS.md or the repo's
  contributing docs. If missing, ask the user once and reuse the answers.

## Setup (once per session)

1. Read `<PARENT>` and list its child tasks with declared dependencies.
2. Show the dependency order as a numbered list, one line per task. Flag ambiguities, cycles, or tasks already done.
3. STOP. Wait for the user to confirm the order.

## Per-task workflow

One task at a time, in dependency order. Never start a task whose dependencies are not committed.

### 1. Selection (gate: user approval)

- Name the next task and give a 2-4 sentence reason it is next (dependencies satisfied, what it unblocks).
- If a technical justification is needed, launch an assessment child using `references/assessment-child.md`. Do not
  produce it yourself.
- STOP. Only an explicit approval counts. Silence or vague replies do not.

### 2. Implementation (child session)

- After approval, launch an implementation child using `references/implementation-child.md`, filling in the task, issue
  numbers, approved assessment, and project commands.
- The child must follow TDD red-green and must NOT commit.

### 3. Report and review (gate: user approval)

Present:

- Concise summary of the changes.
- Verification evidence: red/green results, full test suite, static analysis, coding standards.
- Proposed diff or file-level change list.
- For any change that significantly alters existing logic or behavior: a brief justification with file name and affected
  line numbers.

STOP and wait for an explicit instruction: commit, revise, or abandon.

### 4. Commit (only on explicit command)

- Commit only the reviewed changes, only after the user says so.
- Message references `<PARENT>` and the child issue number, following project commit conventions.
- On "revise", return to step 2 with the user's feedback.

### 5. Documentation and cleanup (after a successful commit)

- Update the child issue with the summary, commit reference, and verification results.
- Close the child issue.

### 6. Termination and handoff

- The child session ends immediately after step 5 and reports to the parent: task, commit hash, tests run and results,
  issue updates made, anything relevant to later tasks.
- STOP. Do not select or start the next task until the user explicitly says to continue.

## Hard rules

- No commit without explicit authorization for that specific change set.
- No autonomous progression between tasks or gates.
- A task is complete only when its changes are committed. Passing tests alone is not completion.
- If a child fails, is blocked, or finds the scope is wrong: stop and report with options. Do not improvise.
- If scope, ordering, or approval is ambiguous: ask, don't assume.
