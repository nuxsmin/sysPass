## Problem Statement

When a Trigger will not fire, the Owner currently has no way of knowing. Reconciliation computes every anomaly the Owner cares about — Triggers that find no Hit within the 48 h horizon, Crossings suppressed by a Weather gate, forecast fetches that fail and skip a Location's planning — but those outcomes only exist as CloudWatch log lines. The Owner discovers the problem late or never: the sun passes, the Routine never runs, and the reason is buried in logs nobody reads. The system's only user-visible signal (a sensor edge) is reserved for Hits, so a Trigger that silently stops firing is indistinguishable from a quiet sun.

## Solution

After each nightly Reconciliation run that has anomalies, the Owner receives one **Notice**: a Telegram push message naming each affected Trigger and why — no Hit found, Crossing suppressed by its Weather gate (with the failing variable, value and threshold), or planning skipped because the forecast fetch failed. Runs with nothing to report send nothing, so silence means "everything is planned". If Telegram cannot deliver, the same report arrives as a fallback email (Amazon SES) with full HTML formatting. Volume stays inside 1–5 messages/day because Reconciliation batches each run's anomalies into one Notice. The design is recorded in ADR 0005; this issue implements it.

## User Stories

1. As an Owner, I want a Notice after every Reconciliation run that has anomalies, so that I learn the same night that my Triggers will not fire as expected.
2. As an Owner, I want the Notice to name each Trigger that found no Hit within the horizon and say why, so that I can fix its altitude, azimuth window, or direction.
3. As an Owner, I want the Notice to list every Crossing suppressed by a Weather gate with the Trigger, the suppressed instant, and the failing variable, value, and threshold, so that I can judge whether my gate thresholds are set correctly.
4. As an Owner, I want the Notice to tell me when a Location's planning was skipped because the forecast fetch failed, so that I know my gated Triggers were not evaluated that night.
5. As an Owner, I want Reconciliation runs without anomalies to send nothing, so that silence reliably means everything is planned.
6. As an Owner, I want all anomalies of one run batched into a single Notice, so that a multi-Trigger config still produces at most one message per night.
7. As an Owner, I want the Notice to arrive as a phone push, so that I see it without opening CloudWatch or an inbox.
8. As an Owner, I want each Location's run to send its own Notice, so that the message tells me which Location the anomalies belong to.
9. As an Owner, I want the Notice written in Spanish, so that it reads naturally alongside my sensors' Spanish names in the Alexa app.
10. As an Owner, I want the Notice to use each Trigger's displayName alongside its config name, so that I recognize the Triggers as they appear in the Alexa app.
11. As an Owner, I want a failed Telegram delivery to fall back automatically to an SES email carrying the same report, so that a chat outage never hides a Reconciliation problem.
12. As an Owner, I want the fallback email to include full HTML (a compact table of anomalies plus a plain-text alternative), so that I can read complex multi-Trigger runs comfortably.
13. As an Owner, I want a Notice too long for Telegram's message limit truncated safely with a pointer to the logs, so that the send never fails and I know where the full detail lives.
14. As an Owner, I want a double delivery failure (Telegram and SES both failing) logged, so that I can audit missed Notices even though the next daily run will re-report live anomalies.
15. As an Owner, I want my Telegram bot token and chat id stored as SSM SecureStrings, so that they never enter Terraform state, environment variables, or code.
16. As an Owner, I want the Notice sent inline during the nightly Reconciliation run, so that I learn about anomalies the same night without new schedules or extra AWS services.
17. As an Owner, I want Notice delivery failure to never fail the Reconciliation run, so that planning and schedules are unaffected by a messaging outage.
18. As an Operator, I want the reconcile simulator to print the composed Notice instead of sending it, so that I can preview message content while tuning gates and thresholds.
19. As an Operator, I want Reconciliation schedule semantics untouched by Notice emission, so that a notification problem can never corrupt, skip, or duplicate schedule planning.
20. As an Operator, I want the Notice sender behind a single adapter seam, so that I can add or swap channels without touching the planner, the runner, or the Lambda event routing.
21. As an Operator, I want the Lambda IAM scoped to sending email only from the verified sender identity, so that the stack cannot email anyone else and stays inside the SES sandbox.
22. As an Operator, I want the README's cost promise restated as "no third-party fees", so that the project's stated constraints remain honest about the Telegram dependency.

## Implementation Decisions

- **Single new seam — `NoticeSender`**: an interface `send(content: NoticeContent): Promise<void>` at the boundary between the Lambda's Reconciliation path and the outside world, mirroring the existing `EventGateway` and `SchedulerAdapter` patterns. Two adapters implement it — Telegram (Bot API `sendMessage`, HTML parse mode, 4096-char budget) and SES (SendEmail, Simple content with HTML + plain-text bodies) — plus a fallback-composing sender: try Telegram; on any failure, send via SES; if both fail, log a structured `notice-delivery-failed` event and continue. Delivery is best-effort by design: it must not throw out of the Lambda invocation, because EventBridge Scheduler retries the reconcile target and a thrown error would duplicate sends.
- **Pure Notice composition**: a pure function from `ReconciliationSummary` to `NoticeContent | null` — null when the run has no anomalies (no unsatisfied Triggers, no suppressed Crossings, no weather-failed Locations). Content rules: one batched message per run; grouped sections per anomaly class; Location name in the header; Trigger entries use config name and displayName; gate-failure reasons reuse the existing suppression descriptions; Telegram text is capped at the API limit with a truncation note pointing at the logs; the email carries the untruncated HTML table plus a plain-text alternative. Message strings are Spanish, consistent with the skill's es-ES locale.
- **Wiring**: the Lambda's Reconciliation branch calls the notifier with the summary returned by the Reconciliation runner, after schedule ops are applied. The Reconciliation runner and planner are not modified — they stay schedule-pure and keep serving the simulator. The simulator additionally renders the composed Notice as a dry-run preview and never sends.
- **Credentials and config**: Telegram bot token and chat id in SSM SecureStrings under the existing parameter prefix (the Owner creates them manually, like the gateway credentials placeholder); SES sender and recipient addresses are non-secret and flow from Terraform variables into Lambda environment variables; the Lambda execution role gains only `ses:SendEmail`, scoped to the verified sender identity. No new AWS services, schedules, or databases.
- **Language of record**: ADR 0005 (already in the repo) records the mechanism decision and every rejected alternative — the virtual Notice sensor, the Proactive Events API, `Alexa.SimpleEventSource`, Echo announcements, ntfy.sh, chat webhooks, SNS SMS/push, Pushover, Pushbullet/IFTTT/Zapier. CONTEXT.md defines **Notice** (a once-per-Reconciliation-run message to the Owner summarizing that run's anomalies) and **Owner** (the single linked Alexa account).
- **README**: the "no third-party services, no API fees" claim is reworded to "no third-party fees" so the free Telegram dependency is stated honestly; the one-time setup section gains the Telegram bot creation steps and the two SSM parameter names.

## Testing Decisions

- **What a good test is**: external behavior only. Composition is tested as data-in/text-out (given a summary, assert the exact message content, grouping, Spanish strings, truncation behavior, and the null-for-all-clear contract) — never by inspecting internal call graphs. Adapters are tested by asserting the exact outgoing request (URL, headers, body shape) against injected fakes, never against live services. The fallback orchestrator is tested by outcomes: Telegram succeeds → SES untouched; Telegram fails → SES called once with identical content; both fail → logged, no throw; no anomalies → nothing called.
- **Modules under test**: the pure composition function (planner-seam style — no mocks, golden-style cases for each anomaly class, multi-anomaly batching, and the 4096-char truncation); the Telegram adapter with an injected fetch double (same prior art as the event gateway client tests); the SES adapter with an injected client double; the fallback orchestrator with fake senders (same prior art as the reconciliation runner tests that fake the scheduler).
- **Framework**: the existing Vitest suite; no new test infrastructure.

## Out of Scope

- All Alexa-native notification mechanisms — the virtual Notice sensor, the Proactive Events API, `Alexa.SimpleEventSource`, `Alexa.ProactiveNotificationSource` announcements, and the Reminders API — rejected in ADR 0005 and not built in any form.
- Notifications about fired Hits or upcoming-Hit previews (fired-Hit push already exists through Alexa app device notifications; previews are a separate future feature).
- Delayed or morning-digest delivery via one-shot `notify-*` schedules; the Notice is sent inline at the midnight run.
- Delivery retry queues, persistence, or per-channel retry policies beyond the single inline attempt plus the SES fallback.
- Multi-Owner or multi-channel routing; one bot chat and one email recipient only.
- Localization infrastructure beyond the hardcoded Spanish strings.
- Any change to Reconciliation cadence, the planner, the fire path, or the Alexa skill itself (no new discovery endpoints, no new permissions).

## Further Notes

- Volume budget: at most one Notice per Reconciliation run (1–5/day across Locations), comfortably inside Telegram's per-chat rate limit and the SES sandbox's 200/day cap. Cost: Telegram free; SES ≈ $0.02/month at this volume (the SES free tier closed to new accounts in July 2026; new-account credits cover the initial window).
- A failed Telegram/SES delivery is intentionally best-effort: the next daily run re-reports live anomalies (daily re-planning guarantees every run re-derives the same anomaly set within the horizon), so the worst case of a lost Notice is a 24 h delay, not a permanent gap.
- The Owner must create the two SSM SecureStrings and verify the SES sender/recipient pair once, mirroring the existing gateway-credentials setup; the stack should fail with a clear error at the first anomalous run if they are missing.
