# sysPass use-case inventory for BDD

Status: maintained application use-case catalog. Source survey: 2026-11-16.

## Purpose and scope

This inventory is the maintained source of truth for the application's user-visible intents, their surveyed entry points, implementation evidence, and outstanding decisions. Use the stable IDs to plan BDD scenarios, implementations, and refactors. It covers the vault, access administration, authentication, configuration, installation, maintenance, and other product operations.

The project glossary's **Use Case** means a Domain class with one `execute()` entry point ([`CONTEXT.md`](../../CONTEXT.md), [`ADR-0003`](../adr/0003-use-case-and-command-result-convention.md)). This catalog also includes intents that bypass such a class. Section A retains one row per existing class for traceability, even where classes represent variants of the same intent. Section B records other intents and unresolved workflows. Group related rows into BDD features where appropriate, but preserve their IDs and boundary-specific examples.

This document catalogs what exists and what needs a decision. Explicit **Target** statements record product decisions for future BDD and refactors; they are not claims of current implementation. Other BDD focus cells are scenario prompts, not approved acceptance criteria. Resolve marked uncertainties against an issue, product decision, and applicable ADRs before asserting a desired outcome. Characterization tests may record existing behavior separately without endorsing a defect. The glossary remains authoritative for terminology and ADRs for architectural decisions; discrepancies with the code are explicitly flagged below.

When a change adds, removes, moves, or changes an intent or boundary, update its row, evidence, status, and scenario prompts in the same change. Preserve IDs for unchanged intents; cross-reference IDs when merging or splitting rows. Recheck the source snapshot when updating counts. An inventory entry is not evidence that a scenario has been implemented or passes.

Included: user-visible product behavior across Web/API/CLI, including operational flows. Excluded: static assets, resource delivery, selector/read-model plumbing, generic page composition, navigation-only and rendering-only behavior. The [UI interaction inventory](ui-interaction-inventory.md) owns JavaScript-to-backend wiring detail; it is not a substitute for this application-level inventory.

### Boundary and actor notation

- **W** — Web module; **API** — JSON-RPC API; **CLI** — console command; **Public W** — Web route reachable without normal login. A listed boundary identifies source-observed entry points, not proof that the full workflow succeeds.
- Actor labels, including those in section headings, are candidates for BDD setup, not a finalized authorization matrix: **Anonymous** (not signed in, including a person requesting or completing password recovery), **User** (authenticated sysPass user), **Admin** (application administrator or appropriately authorized profile), **API client** (user acting through an API Token), **Visitor** (Public Link holder), **Operator** (installer/maintainer using Web or CLI), and **System** (automated action). Actor exceptions are stated on individual rows or next to their tables.
- Section A identifies implemented Domain Use Case classes; section B identifies controller/service/other implementations. **Unresolved** identifies incomplete, conflicting, or unverified behavior and is not an implemented acceptance contract. Actual authorization must be verified at each boundary, including denied access.

## Source snapshot

- **63** concrete Domain use cases exist in eight contexts: Account (public-link management only), CustomField, ItemPreset, Notification, Plugin, Security, Tag, and User. Their class locations are under `lib/SP/Domain/<Context>/UseCases/`.
- All 63 are reachable from Web controllers. Ten are reachable from API controllers: Tag's five single-operation methods and User Group's five methods. No current use case is reached from CLI.
- The API currently exposes 29 JSON-RPC methods across Account, Category, Client, Tag, UserGroup, and Config. Account, Category, Client, and Config methods remain service/controller driven.
- CLI has three commands: `sp:install`, `sp:backup`, and `sp:crypt:update-master-password`.
- The source survey found no `*.feature` Gherkin files or prior BDD inventory. Current testing policy is documented in [`testing.md`](testing.md) and [`ADR-0004`](../adr/0004-test-strategy-and-ci-gating.md); browser UI Workflow Tests are limited to JS-to-backend wiring contracts by [`ADR-0012`](../adr/0012-ui-interaction-coverage.md).

These counts describe the surveyed source snapshot, not scenario coverage or product completeness.

## A. Intents with Domain Use Cases

The rows below enumerate the current classes individually so the inventory can be traced to Commands, Results, unit tests, and adapter coverage. Boundary and actor details are grouped where they apply to multiple intents.

### Public Link management — Admin/User; W

Public-link management classes are in `lib/SP/Domain/Account/UseCases/`. Public retrieval by hash is a separate service/controller flow listed in section B.

**Target:** an Account has at most one Public Link at a time; creating one requires current Account access, Public Link creation permission and Account password-view permission. An expired or exhausted link may be replaced by a newly created link under the same creation permissions; retire the old record only when replacement succeeds. Creation and refresh apply configured expiration time and maximum view count. Refresh requires the creator or a separately authorized administrator, current Account access, Public Link management permission and password-view permission; it generates a new URL and current Account snapshot, invalidates the old URL immediately and resets the view counter as one operation. A failed refresh leaves the previous link and view count unchanged. Link editing only changes notifications. The creator or a separately authorized administrator with Public Link management permission may delete a link even without current Account access. Existing links remain valid after access-control changes, including loss of creator access, until explicitly revoked, expired, exhausted or revoked by Account content changes. This intentional public-access behavior requires maintainer security review.

**Snapshot target:** expose Account name, login, password, URL, notes, Client and Category; do not expose custom fields, file attachments, history or access grants. Creating or refreshing a link for a linked child requires Account access and password-view permission for both child and parent.

| ID | Intent | Domain Use Case | BDD focus |
|---|---|---|---|
| UC-LINK-CREATE | Create a Public Link | `CreatePublicLinkUseCase` | Valid link constraints, invalid/expired inputs, resulting view limit and expiry, event and saved state |
| UC-LINK-CREATE-FOR-ACCOUNT | Create a Public Link for an Account | `CreateAccountPublicLinkUseCase` | Account visibility/authorization, link constraints, and whether the selected Account data is exposed |
| UC-LINK-GET | Get a managed Public Link | `GetPublicLinkUseCase` | Existing vs missing link; ensure management lookup is distinct from anonymous hash retrieval |
| UC-LINK-SEARCH | Search managed Public Links | `SearchPublicLinksUseCase` | Filters, ordering, paging, empty result, and actor-visible links |
| UC-LINK-REFRESH | Refresh a Public Link | `RefreshPublicLinkUseCase` | Expiry/view-count reset semantics and invalid or missing link |
| UC-LINK-DELETE | Delete a Public Link | `DeletePublicLinkUseCase` | Existing vs missing link and whether an already-issued URL stops working |
| UC-LINK-DELETE-BATCH | Delete selected Public Links | `DeletePublicLinksUseCase` | **Target:** reject empty selections, deduplicate IDs and enforce single-link deletion authorization for each link, including after Account access is lost. Delete all or none if any link is missing, unauthorized or otherwise undeletable; return safe per-ID failure reasons and emit success only after the batch succeeds. **Current gap:** the affected count is checked after deletion, with no transaction visible in this path; a mismatch can report failure after deleting matching links. Per-link authorization is not established by the examined controller and use case. |

### Custom Field — Admin; W

Classes are in `lib/SP/Domain/CustomField/UseCases/`.

| ID | Intent | Domain Use Case | BDD focus |
|---|---|---|---|
| UC-CFIELD-CREATE | Create a Custom Field definition | `CreateCustomFieldUseCase` | Required values, duplicate-name rules, encrypted account values, success event |
| UC-CFIELD-UPDATE | Update a Custom Field definition | `UpdateCustomFieldUseCase` | Existing vs missing definition; compatibility of existing Account values |
| UC-CFIELD-GET | Get a Custom Field definition | `GetCustomFieldUseCase` | Existing vs missing definition |
| UC-CFIELD-SEARCH | Search Custom Field definitions | `SearchCustomFieldsUseCase` | Filters, ordering, paging, empty result |
| UC-CFIELD-DELETE | Delete a Custom Field definition | `DeleteCustomFieldUseCase` | Existing vs missing definition and effect on Account values |
| UC-CFIELD-DELETE-BATCH | Delete selected Custom Field definitions | `DeleteCustomFieldsUseCase` | Empty/mixed selection, dependent values, and partial-failure semantics |

### Item Preset — Admin; W

Classes are in `lib/SP/Domain/ItemPreset/UseCases/`. Preset types include permission, private, session-timeout, and Account-password defaults; verify the active set against `ItemPresetInterface` before defining examples.

| ID | Intent | Domain Use Case | BDD focus |
|---|---|---|---|
| UC-PRESET-CREATE | Create an Item Preset | `CreateItemPresetUseCase` | Valid values and type-specific constraints; effects on subsequently created/edited Accounts |
| UC-PRESET-UPDATE | Update an Item Preset | `UpdateItemPresetUseCase` | Existing vs missing preset and whether current Accounts remain unchanged |
| UC-PRESET-GET | Get an Item Preset | `GetItemPresetUseCase` | Existing vs missing preset and type |
| UC-PRESET-SEARCH | Search Item Presets | `SearchItemPresetsUseCase` | Type filtering, ordering, paging, empty result |
| UC-PRESET-DELETE | Delete an Item Preset | `DeleteItemPresetUseCase` | Existing vs missing preset and references |
| UC-PRESET-DELETE-BATCH | Delete selected Item Presets | `DeleteItemPresetsUseCase` | Empty/mixed selection and partial-failure semantics |

### Notification — Admin manages; User receives/marks; W

Classes are in `lib/SP/Domain/Notification/UseCases/`. **Target:** Users may view and mark notifications addressed to them or explicitly broadcast to all Users; a broadcast is visible to Users created after it was issued and remains available until an authorized administrator removes it. Cross-user viewing or marking, and access to admin-only notifications, require an appropriate explicit administrative permission, not merely a generic administrative role. Read state for a broadcast is per recipient; an administrator marking it for a recipient changes only that recipient's state. A sticky notification stays visible to its intended audience until an authorized administrator removes it, regardless of individual read state; sticky status does not override recipient or admin-only restrictions. The current model stores one `checked` flag, so per-recipient state is not established by that model. Confirm actor scoping at the driving adapter; a successful ID-based use case call alone does not establish that a User may access that notification.

| ID | Intent | Domain Use Case | BDD focus |
|---|---|---|---|
| UC-NOTIF-CREATE | Create a notification | `CreateNotificationUseCase` | Recipient/visibility, valid schedule/content, delivery event and saved state |
| UC-NOTIF-UPDATE | Update a notification | `UpdateNotificationUseCase` | Existing vs missing notification and delivery/visibility changes |
| UC-NOTIF-GET | Get a notification | `GetNotificationUseCase` | Existing vs missing; actor isolation |
| UC-NOTIF-SEARCH | Search notifications | `SearchNotificationsUseCase` | Recipient scoping, filters, ordering, paging, empty result |
| UC-NOTIF-CHECK | Mark a notification as read | `CheckNotificationUseCase` | **Target:** broadcast read state is independent per User; sticky notices remain visible to their intended audience after being marked read. Cover unread-to-read transition, repeated check, cross-user denial and authorized override. |
| UC-NOTIF-DELETE | Delete a notification | `DeleteNotificationUseCase` | Existing vs missing notification and recipient effects |
| UC-NOTIF-DELETE-BATCH | Delete selected notifications | `DeleteNotificationsUseCase` | Empty/mixed selection, recipient effects, partial-failure semantics |

### Plugin — Admin; W

Classes are in `lib/SP/Domain/Plugin/UseCases/`. Plugin installation, registration, loading, and upgrade are supporting lifecycle plumbing; a user-facing installation action was not established by this survey. Plugin details are covered in section B.

| ID | Intent | Domain Use Case | BDD focus |
|---|---|---|---|
| UC-PLUGIN-SEARCH | Search installed plugins | `SearchPluginsUseCase` | Empty list, filters/order, disabled and incompatible plugin visibility |
| UC-PLUGIN-TOGGLE | Enable or disable a plugin | `TogglePluginUseCase` | Both state transitions, repeated request, incompatible plugin, failed transition |
| UC-PLUGIN-RESET | Reset a plugin | `ResetPluginUseCase` | Define exactly which plugin state is reset and what is preserved |
| UC-PLUGIN-DELETE | Delete a plugin | `DeletePluginUseCase` | Existing vs missing plugin and safe handling of plugin data/files |
| UC-PLUGIN-DELETE-BATCH | Delete selected plugins | `DeletePluginsUseCase` | Empty/mixed selection and partial-failure semantics |

### Security — Admin; W

Classes are in `lib/SP/Domain/Security/UseCases/`.

| ID | Intent | Domain Use Case | BDD focus |
|---|---|---|---|
| UC-SEC-EVENTLOG-SEARCH | Search the event log | `SearchEventlogUseCase` | Filters, ordering, paging, empty result, visibility of security details |
| UC-SEC-EVENTLOG-CLEAR | Clear the event log | `ClearEventlogUseCase` | Confirmation/authorization, empty log, retention and audit consequences |
| UC-SEC-TRACK-SEARCH | Search Tracks | `SearchTracksUseCase` | Filters, locked/unlocked state, ordering, paging, empty result |
| UC-SEC-TRACK-CLEAR | Clear Tracks | `ClearTracksUseCase` | Authorization, empty/mixed state, security/audit consequences |
| UC-SEC-TRACK-UNLOCK | Unlock a Track | `UnlockTrackUseCase` | Locked vs missing/already-unlocked Track and subsequent Account access |

### Tag — Admin; W + API

Classes are in `lib/SP/Domain/Tag/UseCases/`. Tag's single-item create/update/get/search/delete operations are exposed through Web and API; batch deletion is Web-only in the surveyed API surface.

| ID | Intent | Domain Use Case | BDD focus |
|---|---|---|---|
| UC-TAG-CREATE | Create a Tag | `CreateTagUseCase` | Required/duplicate name, trim/normalization rules, success event |
| UC-TAG-UPDATE | Update a Tag | `UpdateTagUseCase` | Existing vs missing Tag and effect on linked Accounts |
| UC-TAG-GET | Get a Tag | `GetTagUseCase` | Existing vs missing Tag |
| UC-TAG-SEARCH | Search Tags | `SearchTagsUseCase` | Search terms, ordering, paging, empty result |
| UC-TAG-DELETE | Delete a Tag | `DeleteTagUseCase` | Existing vs missing Tag and linked Account behavior |
| UC-TAG-DELETE-BATCH | Delete selected Tags | `DeleteTagsUseCase` | Empty/mixed selection, linked Accounts, partial-failure semantics |

### User, User Group, and User Profile — Admin/User; W; User Group also API

Classes are in `lib/SP/Domain/User/UseCases/`. The User Group API exposes create, update, get, search, and single delete; Web exposes the inventory below. User/Profile management is Web-only in the surveyed API surface. Exceptions to the heading's actor label: `UC-USER-PASSWORD-RESET-REQUEST` and `UC-USER-PASSWORD-RESET` are Anonymous; W, without a normal authenticated-session prerequisite.

**Identity deletion targets:** Reject deletion of a User who owns live Accounts until ownership is explicitly resolved in a separate authorized operation, subject to existing Account privacy and transfer rules. A User cannot delete their own identity, including in a batch. Deletion, disablement or demotion must not leave the system without an enabled application administrator, including under concurrency. Block User Group deletion while it is a User's primary group or a live Account's main group; require separate authorized reassignment first. Block User Profile deletion while Users are assigned to it; require authorized reassignment first. Preserve deleted User and User Group identity in a non-login historical representation so Account history retains its original attribution without rewriting history. User, Group and Profile batch deletion rejects empty selections, processes duplicate IDs once and permits partial success with a safe result per distinct item. Each successful deletion and its required cleanup are atomic; failures do not prevent independent items from succeeding. These authorization-sensitive targets require maintainer security review; historical representation and persisted data require compatibility review.

**Identity deletion side effects:** Deleting an eligible User removes their direct Account grants, favorites, secondary Group memberships, User-associated Item Presets and User-targeted notifications, without removing broadcasts or other Users' targeted notifications. Public Links created by the User remain valid under their existing lifecycle rules and retain their creator attribution through the historical identity. Deleting an eligible User Group removes its secondary memberships, Account sharing grants and Group-associated Item Presets rather than transferring them. Deleting an unassigned User Profile removes its associated Item Presets rather than transferring them. These effects are part of the respective atomic deletion; current schema cascades and restrictive references do not establish the complete target behavior.

**Shared preset and configuration decisions remain open:** An Item Preset may reference more than one User, Group or Profile association and can apply to multiple Users; the schema cascades deletion of the whole preset when any referenced identity is deleted (`schemas/dbstructure.sql`; `lib/SP/Infrastructure/ItemPreset/Repositories/ItemPreset.php`). Clarify whether the approved removal of associated presets includes these shared presets or means detaching only the deleted identity. LDAP and SSO configuration can also reference default Group and Profile IDs; their deletion behavior remains undecided.

**Current deletion gaps:** The traced Web User delete endpoint does not invoke the form's self-deletion check; last-administrator protection was not found in the traced deletion or update paths. Current schema constraints block various live and historical references and do not establish the target policy. The traced batch services compare affected counts after deletion, without an enclosing transaction established; missing or duplicate IDs can lead to an error after matching rows were deleted. Cleanup and success events follow the count check. Verify deployed schema and runtime behavior rather than assuming these targets are implemented.

| ID | Intent | Domain Use Case | BDD focus |
|---|---|---|---|
| UC-USER-CREATE | Create a User | `CreateUserUseCase` | Required fields, uniqueness, profile/group assignment, initial-password rules, event |
| UC-USER-UPDATE | Update a User | `UpdateUserUseCase` | Existing vs missing User; allowed profile/group/status changes |
| UC-USER-GET | Get a User | `GetUserUseCase` | Existing vs missing User and sensitive-field disclosure |
| UC-USER-SEARCH | Search Users | `SearchUsersUseCase` | Filters, visibility, ordering, paging, empty result |
| UC-USER-DELETE | Delete a User | `DeleteUserUseCase` | Existing vs missing User, owned/shared Accounts, cleanup and audit |
| UC-USER-DELETE-BATCH | Delete selected Users | `DeleteUsersUseCase` | Empty/mixed selection, dependent records, partial-failure semantics |
| UC-USER-PASSWORD-UPDATE | Update a User password | `UpdateUserPassUseCase` | Self/admin permission, current vs new password rules, session/token effects |
| UC-USER-PASSWORD-RESET-REQUEST | Request a password reset | `RequestPasswordResetUseCase` | **Target:** the same generic public response for eligible, unknown, mismatched, disabled, and LDAP identities, including identity-dependent throttling. Retain the current per-User limit of three requests per hour. A throttled request has no token side effects; an eligible request that passes the limit invalidates earlier unused tokens, even if delivery fails. On delivery failure, keep the generic response, invalidate the undelivered token, and separately record the failure for authorized operators, including failure type and eligible User ID but not tokens, passwords, reset links or submitted email addresses; emit the existing success event only after delivery succeeds, consistent with ADR-0003. Current errors differ by identity and an event precedes delivery. Cover throttling, identity privacy, token invalidation, delivery and audit failure. |
| UC-USER-PASSWORD-RESET | Reset a password using a reset token | `ResetPasswordUseCase` | **Target:** tokens expire after one hour. A successful password update consumes the valid token; if the update fails, the password and token remain unchanged and the User may retry until expiry. Concurrent submissions of the same token yield at most one successful update. Invalid, expired, used and superseded tokens receive the same generic invalid-or-expired message. The current use case marks the token used before updating the password; atomicity is not established. Cover anonymous token holder, throttling, expiry boundaries and mismatch/blank password. |
| UC-GROUP-CREATE | Create a User Group | `CreateUserGroupUseCase` | Required/duplicate name, member/Account ownership rules, event |
| UC-GROUP-UPDATE | Update a User Group | `UpdateUserGroupUseCase` | Existing vs missing group, membership and shared Account effects |
| UC-GROUP-GET | Get a User Group | `GetUserGroupUseCase` | Existing vs missing group and member disclosure |
| UC-GROUP-SEARCH | Search User Groups | `SearchUserGroupsUseCase` | Filters, visibility, ordering, paging, empty result |
| UC-GROUP-DELETE | Delete a User Group | `DeleteUserGroupUseCase` | Existing vs missing group and user/Account ownership effects |
| UC-GROUP-DELETE-BATCH | Delete selected User Groups | `DeleteUserGroupsUseCase` | Empty/mixed selection, dependent records, partial-failure semantics |
| UC-PROFILE-CREATE | Create a User Profile | `CreateUserProfileUseCase` | Required fields, permission combinations, default/admin constraints |
| UC-PROFILE-UPDATE | Update a User Profile | `UpdateUserProfileUseCase` | Existing vs missing profile, users assigned to it, effective permissions |
| UC-PROFILE-GET | Get a User Profile | `GetUserProfileUseCase` | Existing vs missing profile and permission disclosure |
| UC-PROFILE-SEARCH | Search User Profiles | `SearchUserProfilesUseCase` | Filters, ordering, paging, empty result |
| UC-PROFILE-DELETE | Delete a User Profile | `DeleteUserProfileUseCase` | Existing vs missing profile and assigned-user behavior |
| UC-PROFILE-DELETE-BATCH | Delete selected User Profiles | `DeleteUserProfilesUseCase` | Empty/mixed selection, assigned users, partial-failure semantics |

## B. Other user intents and unresolved workflows

These rows are application use cases for BDD purposes even though current code uses controller/service or client-side paths. Rows marked unresolved identify intended controls or incomplete workflows, not successful implementations. Source references identify discovery anchors, not a desired architecture. Where exact outcomes, validation, or authorization are not settled, treat the BDD focus as a question.

### Authentication, sessions, API Tokens, and public access

| ID | Intent | Actor / boundary | Current implementation evidence | BDD focus |
|---|---|---|---|---|
| APP-AUTH-LOGIN | Sign in to sysPass | Anonymous; W | `app/modules/web/Controllers/Login/LoginController.php`; `lib/SP/Domain/Auth/Services/Login.php`; `lib/SP/Domain/Auth/Providers/Browser/BrowserAuth.php` | Local, LDAP and browser/Basic authentication; enabled/disabled auto-login and domain mapping, valid/invalid/disabled user, tracking, Master Key unlock, events and session state |
| APP-AUTH-MASTER-UNLOCK | Unlock the Master Key with the Master Password | User; W | `lib/SP/Domain/Auth/Services/LoginMasterPass.php`; `lib/SP/Domain/Crypt/Ports/MasterPassService.php` | Correct/wrong Master Password, secure session behavior, re-login/session timeout, vault availability |
| APP-AUTH-LOGOUT | Sign out | User; W | `app/modules/web/Controllers/Login/LogoutController.php` | Session/cookie/key clearing, post-logout access, repeated logout |
| APP-AUTH-SESSION | Expire or regenerate an authenticated session | User/system; W | `app/modules/web/Init.php`; `lib/SP/Core/Context/SessionLifecycleHandler.php` | Expiry, regeneration, stale session and Master Key cleanup |
| APP-TOKEN-CREATE | Create an API Token | User/Admin; W | `app/modules/web/Controllers/AuthToken/`; `AuthTokenService` | Non-admin own-user/allowed-action restriction, secret display/retention, invalid action |
| APP-TOKEN-UPDATE | Edit or refresh an API Token | User/Admin; W | `app/modules/web/Controllers/AuthToken/`; `AuthTokenService` | Ownership/admin rule, scope changes, token rotation/reuse and revocation semantics |
| APP-TOKEN-GET | View an API Token | User/Admin; W | `app/modules/web/Controllers/AuthToken/` | Own vs other-user visibility, secret redaction |
| APP-TOKEN-SEARCH | Search/list API Tokens | User/Admin; W | `app/modules/web/Controllers/AuthToken/` | Own vs other-user visibility, secret redaction, empty result |
| APP-TOKEN-DELETE | Delete an API Token | User/Admin; W | `app/modules/web/Controllers/AuthToken/` | Ownership/admin rule, immediate revocation, repeated delete |
| APP-TOKEN-DELETE-BATCH | Delete selected API Tokens | User/Admin; W | `app/modules/web/Controllers/AuthToken/DeleteController.php` | Selection, ownership/authorization, token revocation, mixed-validity and partial failure |
| APP-API-AUTH | Authenticate and authorize a JSON-RPC call | API client; API | `lib/SP/Domain/Api/Services/Api.php`; `lib/SP/Domain/Auth/Models/AuthToken.php` | Invalid token, token action mismatch, user ACL, rate limit, no secret leakage; for secured actions verify `tokenPass` and vault recovery separately from Web Master Password handling. **Open decision:** token expiry policy. The surveyed model and schema have a start date but no expiry field, and no expiry check was found in the inspected authentication path. |
| APP-API-HELP | Obtain input help for a JSON-RPC method with missing required parameters | API client; API | `lib/SP/Domain/Api/Services/Api.php`; `app/modules/api/Controllers/Help/` | Invalid-parameter error, help hint, required/optional parameter accuracy and no secret leakage; this is not a standalone discovery endpoint |
| APP-LINK-VIEW | Open a Public Link by hash | Visitor; Public W | `app/modules/web/Controllers/Account/ViewLinkController.php`; `lib/SP/Domain/Account/Services/PublicLink.php` | **Target:** enforce the view limit atomically; with one view remaining, concurrent requests reveal Account data to at most one visitor. Denied requests reveal no Account data. Any effective content, credential or custom-field change, including history restoration, revokes and removes the link; a no-op update leaves it intact. Privacy, owner, main-group and sharing-grant changes alone do not revoke it. The resulting continued public exposure after access-control changes requires maintainer security review. Cover valid/invalid/expired/exhausted links and view counts; concurrency guarantees are not established in the current implementation. |

### Account vault, access, history, credentials, and files

Core Account create/edit/delete/search/view APIs and Web controllers remain service-driven; `lib/SP/Domain/Account/UseCases/` currently contains only Public Link use cases.

**Account authorization target:** Every Web and API operation on an Account requires both the relevant User Profile action permission and the appropriate Account-level access. Account edit, password change, and deletion require edit access, not visibility alone. A private Account is accessible only to its owner, including against administrators and other explicit or group grants. A group-private Account is accessible only to Users whose primary group is the Account's main group, without administrator or secondary-membership override; if both privacy flags apply, both restrictions must hold. Subject to privacy and profile permissions, the owner and main-group members retain edit access ahead of conflicting direct read-only grants. Otherwise an explicit User grant takes precedence over secondary-group grants; if there is no direct User grant, any matching secondary-group edit grant permits editing. Retain the full-group-access configuration for evaluating secondary memberships in explicitly shared secondary groups; membership in the Account's main group remains recognized, subject to privacy. Global search requires both global configuration and profile permission and may reveal only name, Client and Category for otherwise inaccessible non-private Accounts, without login, URL, notes, custom fields, password, file names or history; discovery does not confer Account access. Enforce these rules server-side, including alternate entry points; current page, search, and mutation paths do not consistently use the same resource checks. These targets require maintainer security review before behavior changes merge.

**Account create and update target:** Web and API both require an Account name, an existing Client and Category, and a password unless an accessible parent Account supplies it. A linked Account may point only to an existing, accessible non-linked parent; reject self-links, chains and cycles. When a creator lacks permission-management authority, use that User as owner and their primary group as the Account's main group, ignoring attempted ownership or sharing overrides. Across Web and API updates, omitted optional fields and ACL collections remain unchanged; an explicitly supplied empty collection clears it. Fixed Item Presets still apply to controlled fields even when omitted, and win over conflicting explicit values or empty collections; the response reflects the effective saved value. Only the owner may set or clear privacy flags. Ownership transfer and User/group grant changes require permission-management authority and may be performed by the owner or a separately authorized Account administrator who already has Account access; edit access alone is insufficient to change grants. A User whose primary group is the current main group and who has permission-management authority and Account access may also change the Account's main group. On private Account ownership transfer, the former owner immediately loses access and the new owner gains access subject to their profile permissions. Reject transfer of an Account that is both private and group-private to a User whose primary group differs from the Account's main group unless the group-private restriction is also resolved. Additional field-specific validation remains to be decided. Current collection and adapter semantics differ, so do not assume the target is implemented.

**Linked credential target:** A User linking a child requires access and password-view permission for the parent. Revealing an inherited child password requires Account access and password-view permission for both the child and parent. Changing the child's parent requires access and password-view permission for both the current and new parents. Unlinking requires Account edit and password-change permission on the child and access and password-view permission for the current parent. Linking, unlinking or changing the parent atomically revokes the child's Public Link. A parent password change automatically changes its children's effective password and atomically revokes affected children's Public Links. If the parent is missing or inaccessible, password reveal and Public Link creation/refresh fail closed without a password fallback. An already issued child Public Link remains usable after its creator loses parent access alone, subject to the Public Link lifecycle above. Account names are trimmed and must contain at least one non-whitespace character on Web and API.

**Account validation and update atomicity target:** Duplicate trimmed Account names are permitted, including within the same Client and Category. An explicitly blank optional text field clears it; omission preserves it. Account passwords follow the configured Account password Item Preset or policy. Account creation, initial custom fields, grants and fixed preset effects are all-or-nothing on Web and API. For each updated Account, the update, history snapshot, custom-field changes and any required Public Link revocation succeed or fail together without partial state. An update with no effective change after presets succeeds without creating history or revoking a Public Link. Bulk edits apply the same single-Account rules but allow partial success; report individual failures. The current bulk writer uses a transaction, so do not assume partial success is implemented.

**Account field-length target:** Web and API enforce maximum lengths of 100 characters for name, 50 for login, 255 for URL/IP and 5,000 for notes, rejecting over-limit input rather than truncating it. URL/IP is optional text, not restricted to URL or IP syntax. Existing stored values remain readable and are not silently truncated during unrelated updates. These limits appear in the Web form; equivalent server-side enforcement across boundaries is not established.

**Account file target:** Each operation requires its profile action and Account access: view access for list, search, preview and download, and edit access for upload and deletion. Uploads enforce the configured size limit in KiB (1,024 bytes) against actual received bytes and reject upload errors and empty files. Preserve existing numeric settings when changing units, so their effective byte limits increase. The server-detected MIME allowlist is the authoritative type restriction; no separate extension allowlist or match against the client-declared MIME type is required. Application-layer encryption of file contents and thumbnails is not required by this target. Batch deletion deduplicates IDs and deletes all or none if a file is missing or unauthorized, without disclosing inaccessible file details. **Current gap:** the upload path uses 1,000 bytes per configured unit, accepts either declared or detected MIME and stores file bytes without application-layer encryption; end-to-end authorization is not established by the controller alone.

**Account history access and deletion target:** History operations require the relevant history action and applicable Account access and privacy restrictions. For an existing Account, apply its current access and privacy rules rather than the snapshot's recorded owner and privacy flags. For viewing or deleting a deleted Account's history, use the deletion snapshot's recorded ownership, main group and privacy restrictions for all its history. If that snapshot is missing, corrupt or ambiguously attributed, fail closed rather than falling back to older snapshots or administrator access until an authorized recovery process resolves the ambiguity. Historical password reveal also requires password-view permission. Explicitly authorized Users may delete history, including recovery snapshots; batch deletion is all-or-nothing. Warn when deletion removes the last usable recovery snapshot. When bulk edit requests a history purge, each Account's purge and edit succeed or fail together; an effective edit still creates its required pre-update snapshot. If the edit has no effective change after presets, the authorized purge still occurs without a new snapshot. **Current gap:** bulk Account edit can purge history before its update transaction, so a later failure can leave the purge applied.

For a deleted Account, reject deletion of its deletion snapshot unless all remaining history for that Account is selected and deleted in the same authorized, atomic operation. This avoids leaving history inaccessible solely because its authorization snapshot was removed.

**Account deletion and restoration target:** Single and bulk deletion create a recoverable deletion snapshot containing encrypted core fields and custom-field values for each Account. Deletion is rejected while linked child Accounts remain; they must first be reassigned or unlinked, and unlinking requires an independently encrypted password for the child. A successful deletion atomically revokes and removes its Public Link and stored snapshot, and removes Account files, favorites, Tag associations, sharing grants and live custom-field data while leaving shared Tag definitions intact. Snapshot or cleanup failure rolls back the entire deletion. A bulk deletion is all-or-nothing if any selected Account is missing, unauthorized or cannot be deleted. Restoring a deleted Account normally preserves its original ID and retained history and restores its encrypted core fields, custom fields, recorded owner, main group and privacy flags, but does not restore files, favorites, secondary sharing grants, Tag associations or Public Links. If the original ID is occupied, restore under a new ID and re-associate only the deleted Account's verified history, without moving or exposing the occupying Account's history; if attribution cannot be verified, fail closed without modifying data. Reject restoration if the recorded owner, main group, Client or Category no longer exists until an authorized User explicitly resolves those references. Restoration requires separately authorized history-restore permission and the deleted Account's privacy and ownership restrictions. Restoring a still-existing Account requires Account edit access, history-restore permission and a history entry belonging to that Account. First snapshot its current state, then restore historical core and custom-field values, including the historical password subject to password-change permission, while retaining its current owner, main group, privacy flags, sharing grants, files, favorites and Tags. An effective content change through restoration revokes and removes an existing Public Link, per `APP-LINK-VIEW`. Apply the same deletion cleanup semantics across Web, API and bulk paths. Current single and bulk paths differ in snapshot behavior, cleanup differs by boundary, and removed restoration currently inserts a new ID; do not assume the target is implemented. Persisted-data and ciphertext changes require compatibility review under ADR-0005.

| ID | Intent | Actor / boundary | Current implementation evidence | BDD focus |
|---|---|---|---|---|
| APP-ACCOUNT-LIST | Browse the Account index | User; W | `app/modules/web/Controllers/Account/IndexController.php` | Default ordering/paging, visibility, empty state, and distinction from filtered search |
| APP-ACCOUNT-SEARCH | Search and filter Accounts | User/API client; W + API (`account/search`) | `app/modules/web/Controllers/Account/SearchController.php`; `app/modules/api/Controllers/Account/SearchController.php` | Filters, global search, favorites, sorting/paging, empty results, visibility/ACL filtering |
| APP-ACCOUNT-MANAGER-SEARCH | Search Accounts for management | Admin; W | `app/modules/web/Controllers/AccountManager/SearchController.php` | Manager-specific ACL, filters, sorting/paging, visibility, selection for bulk operations and empty results |
| APP-ACCOUNT-VIEW | View an Account | User/API client; W + API (`account/view`) | Web/API `Account` controllers | Allowed vs denied visibility, encrypted/custom fields, history and file render data |
| APP-ACCOUNT-CREATE | Create an Account | User/API client; W + API (`account/create`) | `Account/SaveCreateController.php`; API `Account/CreateController.php`; `AccountWriter` | Required values, preset/private rules, ACL/group assignment, linked Account parent and password inheritance, encrypted values, saved read-back and audit; verify which variants each boundary supports |
| APP-ACCOUNT-UPDATE | Edit an Account | User/API client; W + API (`account/edit`) | `Account/SaveEditController.php`; API `Account/EditController.php`; `AccountWriter` | Require action permission and Account edit access; preserve omitted optional fields and ACL collections, clear explicitly empty collections. Cover linked Account parent, fixed presets, custom fields, history, audit and boundary-specific inputs. |
| APP-ACCOUNT-DELETE | Delete an Account | User/API client; W + API (`account/delete`; Web confirmation then `account/saveDelete`) | `Account/DeleteController.php`, `SaveDeleteController.php`; API `Account/DeleteController.php` | Require action permission and Account edit access; an owner may delete their private Account subject to the same linked-child and cleanup rules. Cover confirmation, atomic snapshot and cleanup, denied access, read-back absence and failure outcome. |
| APP-ACCOUNT-COPY | Copy an Account into a new Account | User; W | `Account/CopyController.php`, `SaveCopyController.php` | **Target:** require source Account view access, copy action and create permission. Prefill editable ordinary fields, Client, Category, Tags and Custom Fields, but not files, history, favorites or Public Link. Default owner and main group to the creator and their primary group, with no copied sharing grants; authorized changes and fixed presets follow creation rules. An independent copy requires a new password; a retained linked parent supplies it only under linked-credential authorization rules. **Current:** copy opens a prefilled creation form, not a server-side clone; persistence of selected Tags and grants is not established by form state alone. |
| APP-ACCOUNT-BULK-EDIT | Edit multiple Accounts | Admin; W | `app/modules/web/Controllers/AccountManager/` | **Target:** apply the same single-Account authorization, fixed-preset, omission, history and Public Link rules per Account. Reject malformed or request-wide unsupported fields before changes, and process repeated IDs only once. Allow partial success with a result for each submitted Account ID and safe failure reasons without disclosing inaccessible Account details; each Account's own update is atomic. Cover missing/unauthorized IDs and independent read-back. |
| APP-ACCOUNT-BULK-DELETE | Delete selected Accounts | Admin; W | `app/modules/web/Controllers/AccountManager/` | **Target:** process repeated IDs once, snapshot each Account and perform deletion and cleanup atomically; reject the whole batch for a missing, unauthorized or otherwise undeletable Account. Return safe per-ID reasons without disclosing inaccessible Account details. Cover linked children, rollback, read-back and audit. |
| APP-ACCOUNT-PASSWORD-UPDATE | Change an Account password | User/API client; W + API (`account/editPass`) | `Account/SaveEditPassController.php`; API `Account/EditPassController.php` | Account permission, Master Key, encryption, history entry, no plaintext leakage |
| APP-ACCOUNT-PASSWORD-VIEW | Reveal an Account password | User/API client; W + API (`account/viewPass`) | `Account/ViewPassController.php`; API `Account/ViewPassController.php`; `AccountPasswordHelper` | Permission and Master Key, current credential and response confidentiality; historical reveal is the separate Web intent below |
| APP-ACCOUNT-PASSWORD-COPY | Copy an Account password to clipboard | User; W | `Account/CopyPassController.php`; `public/js/app-main.js` | Authorization, clipboard success/failure, no secret in logs or unrelated DOM |
| APP-ACCOUNT-LOGIN-COPY | Copy an Account login to clipboard | User; W | `app/modules/web/themes/material-blue/views/account/viewpass.inc`; `public/js/app-main.js` | Authorized credential reveal, text vs image rendering, clipboard success/failure, no unintended disclosure |
| APP-ACCOUNT-PASSWORD-GENERATE | Generate a suggested password in an Account form | User; W | `public/js/app-util.js` | Character set/length/options, cryptographic randomness requirements, copy/population behavior; current browser generator uses `Math.random()` and needs security review before its behavior is accepted |
| APP-ACCOUNT-REQUEST | Request access or modification to an Account | User; W | `Account/RequestAccessController.php`, `SaveRequestController.php` | **Target:** a User with global-search permission may request access to a discovered non-private Account without additional data disclosure; a read-only User may request edit access. Users lacking access cannot request a private or group-private Account by discovery or guessed ID. Require a non-empty reason and notify only the Account owner without credentials or hidden Account data; permissions change only through a separate authorized grant update. Allow at most three requests per User and Account in any rolling 24-hour period; reject further attempts with a generic limit message without additional data disclosure or permission changes. |
| APP-ACCOUNT-FAVORITE | Add or remove an Account favorite | User; W | `AccountFavorite/MarkController.php`, `UnmarkController.php` | Idempotence, per-user isolation, search-filter effect |
| APP-ACCOUNT-HISTORY-VIEW | View Account history | User; W | `Account/ViewHistoryController.php` | History ordering, visibility, empty history, historical secrets/data disclosure |
| APP-ACCOUNT-HISTORY-SEARCH | Search managed Account history | Admin; W | `app/modules/web/Controllers/AccountHistoryManager/SearchController.php`; `SearchGridControllerBase.php` | Manager ACL, filters, ordering/paging, empty result and visibility |
| APP-ACCOUNT-HISTORY-PASSWORD-VIEW | Reveal a historical Account password | User; W | `Account/ViewPassHistoryController.php` | History visibility, Master Key, selected version, secret disclosure |
| APP-ACCOUNT-HISTORY-PASSWORD-COPY | Copy a historical Account password | User; W | `Account/CopyPassHistoryController.php` | Authorization, selected version, clipboard behavior and secret handling |
| APP-ACCOUNT-HISTORY-RESTORE | Restore an Account from history | User/Admin; W | `Account/SaveEditRestoreController.php`; `AccountHistoryManager/RestoreController.php` | **Target for deleted Accounts:** normally preserve the original Account ID and retained history, using a new ID when the original is occupied; restore encrypted core and custom fields but not deleted files, favorites, grants, Tag associations or Public Links. For an existing Account, snapshot current state and restore historical content while retaining current access settings and associations. Require history-restore permission and privacy/ownership restrictions. Cover ID conflict, version selection, encrypted read-back, failure rollback and persisted-data compatibility. |
| APP-ACCOUNT-HISTORY-DELETE | Delete Account history entries | Admin; W | `app/modules/web/Controllers/AccountHistoryManager/` | Single/batch selection, retention consequences, audit and failure outcome |
| APP-ACCOUNT-FILE-LIST | List Account files | User; W | `app/modules/web/Controllers/AccountFile/ListController.php` | Account visibility, empty list, metadata only |
| APP-ACCOUNT-FILE-SEARCH | Search Account files | User; W | `app/modules/web/Controllers/AccountFile/SearchController.php` | Search criteria, account visibility, empty result and metadata-only disclosure |
| APP-ACCOUNT-FILE-UPLOAD | Upload an Account file | User; W | `AccountFile/UploadController.php` | Size/type limits, encryption, invalid upload, authorization and persistence |
| APP-ACCOUNT-FILE-VIEW | Preview an Account file | User; W | `AccountFile/ViewController.php` | Authorization, content type/disposition, non-disclosure on failure |
| APP-ACCOUNT-FILE-DOWNLOAD | Download an Account file | User; W | `AccountFile/DownloadController.php` | Authorization, exact file bytes, encryption/decryption and safe filename |
| APP-ACCOUNT-FILE-DELETE | Delete an Account file | User; W | `AccountFile/DeleteController.php` | Authorization, physical and metadata cleanup, repeated delete |
| APP-ACCOUNT-FILE-DELETE-BATCH | Delete selected Account files | Admin/User; W | `app/modules/web/Controllers/AccountFile/DeleteController.php` | Selection, permissions and ownership, missing/mixed files, cleanup and partial failure |
| APP-LINK-CREDENTIAL-COPY | Copy a credential from a Public Link | Visitor; Public W | `app/modules/web/themes/material-blue/views/account/account-link.inc`; `public/js/app-main.js` | Valid link and allowed displayed content, clipboard success/failure and no unrelated secret disclosure |
| APP-ACCOUNT-WIKI-LINK | Open a related external wiki page | User; W | `app/modules/web/themes/material-blue/views/account/search-rows.inc` | Configured wiki/filter, Account-name URL encoding, destination and visibility |

### Public Link and Plugin management beyond Domain Use Cases

| ID | Intent | Actor / boundary | Current implementation evidence | BDD focus |
|---|---|---|---|---|
| APP-LINK-UPDATE | Edit a managed Public Link | Admin/User; W | `app/modules/web/Controllers/PublicLink/EditController.php` | **Target:** only the notification setting can change; linked Account, expiry, view limit, URL and counters remain unchanged. The creator or a separately authorized administrator may edit only with current Account access and Public Link management permission. Expired and view-exhausted links may be edited without renewal or becoming usable. Editing is distinct from refresh. **Current gap:** the edit form targets `publicLink/saveEdit/{id}` but no matching save controller was found. |
| APP-PLUGIN-GET | View plugin details | Admin; W | `app/modules/web/Controllers/Plugin/ViewController.php` | Plugin-view permission, existing/missing plugin, settings and sensitive-data disclosure |

### Category and Client

Both contexts provide CRUD/search through Web and API. Current implementation is service/controller-based: `lib/SP/Domain/Category/Services/Category.php`, `lib/SP/Domain/Client/Services/Client.php`, and their Web/API controller directories.

**Target:** Categories are flat, without parent/child relationships. A Category referenced by an Account cannot be deleted until its Accounts are reassigned. Account history references also block deletion; they must not be changed automatically, although an authorized user may explicitly delete the history first. A batch containing any blocked Category is rejected in full. The current model and schema are flat; verify deletion behavior at the relevant boundaries.

| ID | Intent | Actor / boundary | BDD focus |
|---|---|---|---|
| APP-CATEGORY-CREATE | Create a Category | Admin; W + API (`category/create`) | Required/duplicate name and associated Account behavior |
| APP-CATEGORY-UPDATE | Update a Category | Admin; W + API (`category/edit`) | Existing vs missing Category and Account references |
| APP-CATEGORY-GET | View a Category | Admin; W + API (`category/view`) | Existing vs missing Category and displayed fields |
| APP-CATEGORY-SEARCH | Search Categories | Admin; W + API (`category/search`) | Filters, ordering/paging, empty result |
| APP-CATEGORY-DELETE | Delete a Category | Admin; W + API (`category/delete`) | **Target:** reject deletion while an Account or Account history entry references the Category; permit it only once Accounts are reassigned and referencing history is explicitly deleted, without automatically changing history. Cover missing Category and denied access. |
| APP-CATEGORY-DELETE-BATCH | Delete selected Categories | Admin; W | **Target:** if any selected Category is missing, unauthorized, or referenced by an Account or its history, reject the entire batch and leave every selected Category unchanged. Evidence: `Category/DeleteController.php` |
| APP-CLIENT-CREATE | Create a Client | Admin; W + API (`client/create`) | Required/duplicate name and Account association |
| APP-CLIENT-UPDATE | Update a Client | Admin; W + API (`client/edit`) | Existing vs missing Client and associated Accounts |
| APP-CLIENT-GET | View a Client | Admin; W + API (`client/view`) | Existing vs missing Client and associated Accounts |
| APP-CLIENT-SEARCH | Search Clients | Admin; W + API (`client/search`) | Filters, ordering/paging, empty result |
| APP-CLIENT-DELETE | Delete a Client | Admin; W + API (`client/delete`) | Account references, reject/reassign/cascade policy, missing Client |
| APP-CLIENT-DELETE-BATCH | Delete selected Clients | Admin; W | Selection, Account references, missing/mixed Clients and partial failure; `Client/DeleteController.php` |

### User preferences

User, User Group, and User Profile CRUD/password reset are listed in section A; the composite access-management page is a supporting render operation, not a separate business intent.

| ID | Intent | Actor / boundary | Current implementation evidence | BDD focus |
|---|---|---|---|---|
| APP-USER-PREFERENCES | Save personal preferences | User; W | `UserSettingsGeneral/SaveController.php` → `UserService::updatePreferencesById()` | Only own preferences, valid locale/theme/settings, persisted values affect next request |

### Configuration, import/export, backup, and cryptographic administration

The Web configuration controllers use `ConfigFileService`, configuration services, and domain services directly. These operations are security-sensitive; follow [`security.md`](security.md) and obtain maintainer security review before turning them into behavior changes.

| ID | Intent | Actor / boundary | Current implementation evidence | BDD focus |
|---|---|---|---|---|
| APP-CONFIG-GENERAL | Save general configuration | Admin; W | `ConfigGeneral/SaveController.php`, `ConfigSaveControllerBase.php` | Valid/invalid values, persistence/reload, config permissions and error rollback |
| APP-CONFIG-ACCOUNT | Save Account defaults/configuration | Admin; W | `ConfigAccount/SaveController.php`, `ConfigSaveControllerBase.php` | Valid/invalid values and effect on new vs existing Accounts |
| APP-CONFIG-WIKI | Save generic wiki link configuration | Admin; W | `ConfigWiki/SaveController.php` | Valid/invalid values, saved generic wiki settings and generated external links; DokuWiki API configuration is retired |
| APP-CONFIG-LDAP | Configure LDAP authentication | Admin; W | `ConfigLdap` controllers | Invalid connection/config, local-login fallback, secret storage and feature disabled/enabled behavior |
| APP-CONFIG-LDAP-CHECK | Test LDAP configuration | Admin; W | `ConfigLdap/CheckController.php` | Success, connection/bind failure, timeout and secret redaction |
| APP-CONFIG-LDAP-IMPORT-CHECK | Preview or check an LDAP import | Admin; W | `ConfigLdap/CheckImportController.php`; `ConfigLdap/CheckController.php` | Connection/bind failure, import settings and candidate results, no persistent import, response on external-service failure |
| APP-CONFIG-LDAP-IMPORT | Import Users and optionally User Groups from LDAP | Admin; W | `ConfigLdap/ImportController.php`; `LdapImportService` | User/group selection, duplicate handling, group/profile mapping, partial import and external-service failure |
| APP-CONFIG-MAIL | Configure mail delivery | Admin; W | `ConfigMail` controllers | Invalid settings, persistence, mail disabled/enabled behavior |
| APP-CONFIG-MAIL-CHECK | Send a test message | Admin; W | `ConfigMail/CheckController.php` | Success/failure/timeout, recipient, no credential leakage |
| APP-CONFIG-MASTER-PASSWORD | Change the system Master Password | Admin/Operator; W + CLI (`sp:crypt:update-master-password`) | `ConfigEncryption` controllers; `Crypt/UpdateMasterPasswordCommand.php` | Web vs CLI inputs/prompts, correct old/new values, complete data re-encryption, interruption/recovery, sessions, exit code and no plaintext exposure |
| APP-CONFIG-MASTER-HASH | Refresh the stored Master Password hash | Admin; W | `ConfigEncryption/RefreshController.php` | Correctly derived hash, invalid state, existing encrypted data unchanged |
| APP-CONFIG-TEMP-MASTER-PASSWORD | Generate and deliver a Temporary Master Password | Admin; W | `ConfigEncryption/SaveTempController.php`; `TemporaryMasterPass` services | Generation, delivery channel, expiration/use policy, invalidation and secret redaction |
| APP-BACKUP-RUN | Create a system/database backup | Operator/Admin; W + API (`config/backup`) + CLI (`sp:backup`) | `ConfigBackup/FileBackupController.php`; API `Config/BackupController.php`; `BackupCommand.php` | Boundary-specific options/output, success and restore/readability, insufficient space/DB failure, exit code, sensitive content and cleanup |
| APP-EXPORT-XML | Export Accounts to XML | Admin; W + API (`config/export`) | `ConfigBackup/XmlExportController.php`; API `Config/ExportController.php` | Data included, encryption/password policy, malformed output, permission and secret exposure; Web verifies and archives the export, while API returns an XML file reference |
| APP-BACKUP-DOWNLOAD-APP | Download the application backup | Admin; W | `ConfigBackup/DownloadBackupAppController.php` | Authorization and demo mode, existing/missing archive, response headers, contents and audit |
| APP-BACKUP-DOWNLOAD-DB | Download the database backup | Admin; W | `ConfigBackup/DownloadBackupDbController.php` | Authorization and demo mode, existing/missing archive, response headers, contents and audit |
| APP-EXPORT-DOWNLOAD | Download the exported archive | Admin; W | `ConfigBackup/DownloadExportController.php` | Authorization, existing/missing archive, response headers, contents and audit |
| APP-CONFIG-DOWNLOAD | Download a configuration backup | Admin; W | `ConfigGeneral/DownloadConfigBackupController.php` | Included settings/secrets, permission, content integrity and redaction policy |
| APP-CONFIG-DOWNLOAD-LOG | Download the application log | Admin; W | `ConfigGeneral/DownloadLogController.php` | Permission, date/range selection if applicable, sensitive-data redaction and download integrity |
| APP-IMPORT-ACCOUNTS | Import Account data from a file | Admin; W | `ConfigImport/ImportController.php`; `lib/SP/Domain/Import/Services/` | Confirm supported CSV/XML/KeePass/sysPass formats; invalid/malicious file, duplicate handling, preview/rollback, partial success |

### Installation, upgrade, system status, and CLI

| ID | Intent | Actor / boundary | Current implementation evidence | BDD focus |
|---|---|---|---|---|
| APP-INSTALL | Install sysPass and initialize its database/admin/Master Password | Operator; W + CLI (`sp:install`) | Web `Install/InstallController.php`; `app/modules/cli/Commands/InstallCommand.php`; `InstallerService` | Fresh vs already-installed, required values, confirmation/force mode, DB failure, secret-safe prompts and resulting login |
| APP-UPGRADE | Upgrade sysPass and its persisted data | Operator; W | `Upgrade/UpgradeController.php`; `UpgradeService` | Supported source version, upgrade key/authorization, backup/rollback, interrupted upgrade and compatibility |
| APP-STATUS-INFO | Inspect system and environment information | Admin; W | `app/modules/web/Presenters/ConfigManagerPresenter.php`; `app/modules/web/themes/material-blue/views/configManager/info.inc` | Authorized access, database/application versions, extension and locale status, backup status, secured session and safe disclosure of configuration details |
| APP-STATUS-NOTICES | Check and show system notices | User/system; W | `Status/CheckNoticesController.php` | Enabled/disabled check, remote failure, dismissal/display policy; configuration-dependent |
| APP-STATUS-RELEASE | Check for a new release | User/System; W | `app/modules/web/Controllers/Status/StatusController.php`; `public/js/app-actions.js` | **Target:** retain the Web release check. Service failures or malformed data are non-blocking and reported as unavailable, not up to date. Backend `status/status` returns metadata when newer; the client requests missing `status/checkRelease`. Resolve the route and response-contract mismatch; cover success, no update and failure. |

### Retired intents

These IDs are retained for traceability, but are not supported product capabilities or candidates for new BDD feature scenarios. Legacy source may still expose incomplete controls; their removal is separate work.

| ID | Retired intent | Legacy evidence | Decision |
|---|---|---|---|
| APP-ACCOUNT-WIKI-VIEW | View an Account's DokuWiki page inside sysPass | Retired; embedded-view control and handler removed | No longer supported. Do not implement the missing embedded-view route. Generic external wiki links remain covered by `APP-ACCOUNT-WIKI-LINK`. |
| APP-CONFIG-WIKI-CHECK | Configure or test the DokuWiki API | Retired; unreferenced form and check handler removed | DokuWiki API configuration and connection testing are no longer supported. Do not implement their missing routes. Generic wiki link configuration remains covered by `APP-CONFIG-WIKI`. |

## C. Cross-cutting scenario dimensions

Apply the relevant dimensions to each row above. These are prompts to turn into explicit Given/When/Then examples, not assertions that every operation has identical policy.

| ID | Scenario dimension | Scenarios to define |
|---|---|---|
| BDD-X-AUTH | Identity and authorization | Anonymous vs authenticated; allowed vs denied profile/ACL; account visibility; group ownership; API Token owner/action scope; public-link hash/expiry; CLI/config preconditions |
| BDD-X-INPUT | Input and validation | Required/optional/blank values, malformed values, boundaries, duplicates, unsupported file type, invalid encoding, and form/API/CLI parsing differences |
| BDD-X-STATE | Existing/missing and repeated operations | Existing vs missing record, already-deleted/read/unlocked/enabled state, stale IDs, idempotence, constraints and concurrency |
| BDD-X-QUERY | Search/read models | Empty result, combinations of filters, ordering, paging, visibility, and no leakage across users/groups |
| BDD-X-PERSIST | State-changing outcome | Verify mutation with an independent read; verify related rows/files/history are correct; failure must not leave unintended partial state |
| BDD-X-EVENT | Audit and notifications | Distinguish operation-success, attempt/security and start/end lifecycle events; assert each event's name, source, message keys, receiver data and timing on success and failure. ADR-0003 requires use-case events after success and none on failure. **Target for password-reset requests:** separately record delivery failure for authorized operators and emit the existing success event only after successful delivery. Current code emits before delivery; characterize this discrepancy separately. |
| BDD-X-SECRET | Secret handling | Passwords, Master Keys, API Tokens, LDAP/mail credentials, exported/imported data and account-file contents must not leak in unrelated responses, logs, errors or fixtures |
| BDD-X-EXTERNAL | External dependency behavior | Mail, LDAP, database, filesystem, remote release/notice service success, timeout, refusal, malformed response, and retry/rollback behavior |
| BDD-X-BOUNDARY | Adapter contract | Same underlying intent where exposed through more than one boundary; separately assert Web response/render, JSON-RPC result/error envelope, and CLI output/exit code |
| BDD-X-CRYPTO | Encryption and session state | Correct/wrong Master Password, absent/expired session key, encrypted values round-trip, password rotation interruption and legacy ciphertext compatibility |

## D. Evidence and open questions

### Current test tiers to use when implementing scenarios

- Domain Use Case behavior: Unit Tests with in-memory port fakes.
- Repository behavior affecting SQL/hydration: DB-backed Tests against MariaDB.
- Web routes, authorization and rendered/translated response: HTTP Integration Tests, including denied-access cases.
- JSON-RPC and console contract: API and CLI Adapter Integration Tests.
- Browser tests remain limited to UI Smoke and UI Workflow wiring contracts; they are not a substitute for backend authorization or full BDD workflows.

See [`testing.md`](testing.md), [`ADR-0004`](../adr/0004-test-strategy-and-ci-gating.md), and [`ADR-0012`](../adr/0012-ui-interaction-coverage.md) for the current rules.

### Gaps to resolve during BDD refinement

1. **Account is the largest service-driven area.** Account create/edit/delete/view/search, password operations, history, files, favorites, access requests, and bulk management are not represented by Account Domain Use Cases. The Account UseCases directory covers Public Links only.
2. **Category and Client are service/controller-driven.** Their CRUD/search APIs are not use-case-backed.
3. **Authentication and session lifecycle are service/controller-driven.** Make local, LDAP and browser/Basic login, Master Key unlock, failed login, session expiry, and logout explicit scenarios. Password-reset request and completion are public Web flows, not authenticated-only actions.
4. **Configuration, installation, upgrade, import/export, backup, and cryptographic administration are service/controller/CLI flows.** Their validation and side-effects are split across layers.
5. **API parity is incomplete.** API covers 29 methods in six areas; it does not expose all Web behavior. Do not imply parity by copying Web scenarios wholesale.
6. **Authorization often sits outside the Domain Use Case.** A Use Case unit scenario cannot by itself prove Web/API authorization; assert denial at the relevant adapter boundary.
7. **Public-link management and public-link viewing are different intents.** `GetPublicLinkUseCase` is ID-based management; anonymous retrieval by hash, expiry, max-view enforcement, and serialized payload behavior are in `ViewLinkController`/`PublicLinkService`.
8. **Some outcomes are undefined or source-level defects.** The UI interaction inventory records route/outcome discrepancies, including `status/checkRelease` and handlers that continue after error envelopes. Public Link editing has a target contract but no save route. DokuWiki API configuration/testing and embedded DokuWiki viewing are retired; their missing routes are not implementation requirements.
9. **No dedicated reporting subsystem was found.** Event log, Tracks, and Account history are the product's nearest audit/history surfaces; do not invent separate report use cases without new evidence.
10. **Security-sensitive behavior requires maintainer review.** The decided authentication, Public Link and notification authorization targets, along with other changes to authorization, cryptography, Master Password and credential export/import, need explicit maintainer security review before behavior changes merge; see [`security.md`](security.md). A target decision is not itself that review.
11. **Category deletion target requires verification.** Categories are flat; Account or Account history references block deletion, and a batch with a blocked, missing, or unauthorized Category is rejected in full. Preserve historical information unless an authorized user explicitly deletes the history. Verify current behavior and cover both boundaries without assuming the batch implementation is atomic.
12. **Password-reset behavior differs from the target and ADR-0003.** `RequestPasswordResetUseCase` emits the request event before sending mail; `ResetPasswordUseCase` marks the token used before the password update. The targets specify success-event timing, separate failure recording, a generic response, token invalidation on a new request and retryable atomic completion. Verify transaction and failure behavior rather than assuming it. The failure-recording mechanism must respect ADR-0003 and avoid exposing secrets.
13. **User, group and profile deletion policy is unresolved.** Decide how deletion handles owned Accounts, primary group membership, assigned profiles, history references, self-deletion and the last administrator; decide whether batch operations are atomic. Current schema constraints vary by reference and do not establish the desired product policy.
14. **Credential lifecycle policy is unresolved.** Decide whether API Tokens expire and whether a successful password reset invalidates existing sessions and API Tokens. The surveyed API authentication path has no token-expiry check, and the reset path does not explicitly invalidate sessions or API Tokens. These are source observations, not approved targets.

### Suggested implementation order

1. Start with existing Use Case seams: Tag, Item Preset, Custom Field, User/Group/Profile, Notification, Plugin, Security, and Public Link management.
2. Add the highest-risk/most central Account flows: edit/delete/create, reveal/change password, history restore, access request, files, favorites, and ACL-denial behavior.
3. Cover login, Master Key unlock, password reset, Public Link viewing/expiry, then user/group/profile administration.
4. Cover configuration and external integrations, import/export and backup, installation/upgrade, then each CLI command.
5. Add interface-specific scenarios only where that interface exists; maintain API/CLI/Web contracts separately and do not assert nonexistent operations.

## Discovery sources

- Domain vocabulary and architecture: [`CONTEXT.md`](../../CONTEXT.md), [`ADR-0003`](../adr/0003-use-case-and-command-result-convention.md).
- Test tiers: [`testing.md`](testing.md), [`ADR-0004`](../adr/0004-test-strategy-and-ci-gating.md), [`ADR-0012`](../adr/0012-ui-interaction-coverage.md).
- Detailed current Web JavaScript wiring, known route discrepancies and current UI evidence: [`ui-interaction-inventory.md`](ui-interaction-inventory.md).
- Source roots surveyed: `lib/SP/Domain/*/UseCases/`, `app/modules/web/Controllers/`, `app/modules/api/Controllers/`, `app/modules/cli/Commands/`.
