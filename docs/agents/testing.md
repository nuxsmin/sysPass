# Testing Workflow

Read this guide before changing behavior, fixing a bug or verifying a refactor. Follow [ADR-0004](../adr/0004-test-strategy-and-ci-gating.md) when choosing unit fakes, database-backed repository tests or module integration tests. For CLI permissions, Docker runtime setup and command examples, read [the command kit guide](../../.ai-cli/README.md) before running commands.

## Red-Green Development

Use test-driven development for behavior changes and bug fixes. Work in small vertical slices rather than writing all tests or all implementation at once:

1. Define the expected behavior and the public boundary under test, using the issue's acceptance criteria and the affected layer's test conventions.
2. **Red:** Write one focused test before changing production code. Run it and confirm that it fails because the behavior is missing or incorrect. Setup errors, unavailable services and unrelated failures do not establish red.
3. **Green:** Make the smallest production change that satisfies the test, then rerun it successfully using the Docker PHP wrapper.
4. Repeat for the next behavior. During review, simplify the implementation where useful while keeping tests green, then run the relevant regression and architecture checks.

Test observable behavior through public interfaces rather than private methods or implementation details. For bug fixes, the initial test should reproduce the reported defect.

## Regression Checks

Select tests for the affected layer. Validate dependency changes with `tests/SP/Architecture/` and container wiring changes with `tests/SP/Core/Definitions/ContainerWiringTest.php`. For persisted-class changes, follow [ADR-0005](../adr/0005-preserve-serialized-payload-compatibility.md) and run the serialized-payload compatibility tests.

Documentation-only changes do not require new application tests; behavior-preserving refactors should retain passing regression coverage.

## Verification Evidence

Include the red and green commands and outcomes, plus regression and static-analysis results, in the handoff. Report exactly which checks ran and any failures or unavailable prerequisites. If the runtime or required services prevent execution, report the blocker rather than claiming a verified cycle.
