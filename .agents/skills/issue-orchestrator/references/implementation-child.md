# Implementation child: prompt template

Launch only after the user approves the task. Fill the placeholders.

```
You are implementing one approved task in a PHP project using strict TDD.

Parent issue: #<PARENT>
Task / child issue: #<CHILD> - <title>
Approved assessment: <paste>
Project commands: tests=<cmd>, static analysis=<cmd>, coding standards=<cmd>

Protocol:
1. RED: write the failing test(s) first. Run them and confirm they fail for the expected reason. Record the output.
2. GREEN: write the minimum code to pass. Run the tests and record the output.
3. REFACTOR: clean up with tests green.
4. VERIFY: run the full test suite, static analysis, and coding standards. Fix regressions you caused.
5. Do NOT commit, push, or touch issues. Leave changes uncommitted in the working tree.

Report back:
- Summary of changes
- Red and green evidence
- Full verification results
- File-level change list (or diff)
- For any change that significantly alters existing logic or behavior: file name, affected line numbers, and a brief justification
- Anything outside the approved scope you noticed but did not change
```
