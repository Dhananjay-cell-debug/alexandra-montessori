# Alexandra Montessori — Scale-Ready Submissions Architecture

Status: implementation plan approved in principle by the user on 2026-07-17.

This plan extends `RESUME-HERE.md`. It does not authorize a production
deployment or deletion of existing production records. Production remains
read-only until a separately approved, rollback-safe release.

## 1. Outcome

Replace the current small-volume WordPress post/post-meta inbox with a durable
operations layer that remains usable when the database contains hundreds of
thousands of form submissions.

The finished system must:

- accept Contact, Visit, Availability and Career submissions quickly;
- persist a submission before any notification attempt;
- prevent duplicate browser retries from creating duplicate records;
- keep customer-entered data immutable for staff;
- support indexed filtering, searching and cursor pagination;
- support assignment, priority, follow-up, workflow status and bulk operations;
- retain an append-only audit trail;
- keep applicant files private and permission-gated;
- decouple notification email from the public form request;
- support immediate, digest and dashboard-only notification modes;
- expose failed or delayed notification work inside the admin;
- preserve and migrate every existing legacy submission without deleting it;
- keep CSV exports bounded, filtered and safe for spreadsheet use;
- remain operable if mail delivery is unavailable.

## 2. Capacity boundary

The architectural target is a database containing at least 100,000 submissions
with predictable indexed admin queries. That is different from promising
100,000 simultaneous requests.

Sustained high request rates still depend on:

- production CPU, memory, database and disk limits;
- a real server cron runner;
- an edge WAF/rate-limit layer;
- SMTP or transactional-email provider limits;
- traffic shape, attachment volume and retention period.

The Local implementation will include repeatable volume and query-timing tests.
Formal production capacity claims require production-like load testing and host
limits. The implementation must not pretend that shared hosting alone provides
unlimited traffic capacity.

## 3. Ownership boundary

The operations system will live in an Alexandra-specific must-use WordPress
plugin, not in the visual theme.

Reasons:

- submission records survive theme changes;
- REST ingestion, schema upgrades, permissions and queue processing load before
  the theme;
- operational releases can be packaged and rolled back independently;
- the client cannot accidentally disable the core operations plugin;
- the public React theme remains responsible for presentation and form UI.

The plugin source will be kept in the project workspace and synced into Local
WordPress for testing.

## 4. Data model

### 4.1 Main submissions table

`{prefix}am_submissions`

Common indexed fields:

- numeric primary key;
- non-sequential public reference;
- unique idempotency key;
- submission type and source;
- workflow status;
- priority;
- assigned WordPress user;
- branch slug and immutable submitted branch label;
- customer display name, email and E.164 phone;
- subject/summary;
- JSON-encoded type-specific payload;
- internal notes;
- submitted, updated, first-opened, follow-up, resolved and archived timestamps;
- first-opening user;
- spam/quarantine state and score;
- notification state and destination;
- duplicate fingerprint;
- HMAC-hashed client IP and user-agent fingerprint;
- legacy WordPress post ID;
- optimistic-lock version.

Indexes will cover:

- type + status + submitted time;
- owner + status + follow-up time;
- branch + status + submitted time;
- priority + status + submitted time;
- spam state + submitted time;
- notification state + submitted time;
- email + submitted time;
- phone + submitted time;
- duplicate fingerprint + submitted time;
- archive state + primary key;
- legacy post ID and idempotency key uniqueness.

No dashboard filter may require querying JSON payload fields.

### 4.2 Audit events table

`{prefix}am_submission_events`

Append-only records for:

- submission created or migrated;
- first opened;
- owner changed;
- status changed;
- priority changed;
- follow-up changed;
- notes changed;
- reply queued/sent/failed;
- notification queued/sent/digested/failed;
- archived/restored;
- file downloaded;
- bulk operation performed.

Events store actor, event type, safe structured details and timestamp. Staff
cannot edit or delete audit rows.

### 4.3 Messages table

`{prefix}am_submission_messages`

One row per outbound customer reply, rather than one ever-growing serialized
post-meta value.

Each row stores:

- immutable recipient;
- subject and message;
- actor;
- channel/transport;
- queued, sent or failed status;
- provider identifier when available;
- transport error;
- created, attempted and sent timestamps.

### 4.4 Private files table

`{prefix}am_submission_files`

Stores searchable metadata only:

- submission ID;
- opaque storage key;
- original filename;
- MIME type;
- byte size;
- SHA-256;
- validation/scan state;
- created timestamp.

File bytes stay outside the public web root. Downloads require a logged-in user,
the submissions capability and a nonce. Download events are audited.

### 4.5 Durable jobs table

`{prefix}am_ops_jobs`

Used for notification and digest work:

- job type;
- submission/message ID;
- immutable recipient;
- JSON payload;
- pending/processing/sent/failed/suppressed state;
- attempts;
- next-attempt time;
- lock token and lock time;
- last error;
- unique job key;
- created and updated timestamps.

Workers claim jobs atomically. Abandoned locks become retryable. Retry delays
increase between attempts and permanently failed work remains visible.

### 4.6 Rate-limit table

`{prefix}am_rate_limits`

Atomic counters keyed by an HMAC hash, form bucket and time window. Expired rows
are cleaned incrementally.

Proxy headers are trusted only when an explicit production proxy configuration
enables them. Raw visitor IP addresses are not stored in the operations tables.

## 5. Ingestion contract

Each public form request will:

1. reject oversized or malformed requests;
2. run honeypot and minimum-completion-time checks;
3. run atomic burst and sustained rate limits;
4. validate and normalize every field server-side;
5. validate the requested branch against the ready Nursery collection;
6. validate the applicant file before finalizing an Application;
7. honor a unique browser idempotency key;
8. detect an identical short-window duplicate;
9. insert the submission and creation audit event transactionally;
10. store private file metadata where applicable;
11. enqueue notification work durably;
12. return a success response and public reference without waiting for SMTP.

The public request never relies on a transient or non-blocking loopback call for
delivery reliability.

## 6. Notification behavior

New-submission alerts support:

- immediate: one operational alert per accepted submission;
- digest: one grouped email per recipient and configured interval;
- dashboard only: no new-submission email.

Digest mode is the recommended high-volume setting. Customer replies remain
individual messages and are queued immediately.

Notification messages do not attach CV files. They link authorized staff to the
private dashboard record. This reduces mailbox load and unnecessary copies of
applicant data.

The operations dashboard shows:

- pending jobs;
- oldest pending age;
- sent/digested totals;
- retrying jobs;
- permanently failed jobs;
- last worker run;
- cron health.

A real server cron command will be documented for production. WordPress cron is
only a fallback.

## 7. Admin command center

### 7.1 Overview

The overview will prioritize action rather than decorative totals:

- New;
- Unassigned;
- Needs attention;
- Follow-up due;
- High/Urgent priority;
- Notification failures;
- Quarantined;
- Active versus resolved;
- received and resolved trends;
- source and branch breakdown;
- owner workload.

### 7.2 Unified inbox

One table covers every form type with server-side filters for:

- type/source;
- branch;
- status;
- owner;
- priority;
- new/unassigned/follow-up/failed/quarantined;
- submitted date range;
- exact public reference;
- email/phone/name search.

The table uses keyset/cursor pagination, not deep SQL offsets. Default page size
is 50. No page renders thousands of rows.

### 7.3 Operational actions

Single and bulk operations:

- assign to user or unassigned;
- assign to me;
- change status;
- change priority;
- set/clear follow-up date;
- archive resolved records;
- restore archived records.

Staff cannot permanently delete submissions.

### 7.4 Detail view

The detail screen contains:

- immutable customer-submitted fields;
- current owner, status, priority and follow-up;
- internal notes;
- secure CV information/download;
- recipient-locked reply composer;
- complete audit timeline;
- notification and reply delivery results;
- public reference and legacy reference where applicable.

First-open assignment/status changes use an atomic conditional update so two
staff members cannot both claim an unassigned record.

### 7.5 CSV exports

Exports:

- honor the current filters;
- require a bounded date range;
- stream with primary-key cursors;
- neutralize spreadsheet formulas;
- contain a clear personal-data warning;
- are audited;
- never include private file bytes or storage paths.

## 8. Permissions

Dedicated capabilities:

- `manage_am_submissions`;
- `reply_am_submissions`;
- `export_am_submissions`;
- `view_am_private_files`;
- `manage_am_ops_settings`.

The Alexandra Content Manager role receives operational access but no permanent
delete capability. Every write action performs a server-side capability and
nonce check.

## 9. Legacy migration

Migration is copy-first and idempotent:

1. create and verify empty custom tables;
2. read legacy private Contact, Availability and Application posts in primary
   key batches;
3. map their metadata into normalized rows;
4. preserve the old post ID as a unique legacy reference;
5. copy communication history into message/event rows;
6. register existing private CV metadata without moving or renaming the bytes;
7. compare source and destination counts plus per-record checksums;
8. keep legacy posts untouched and hidden as a rollback archive;
9. redirect an old edit URL to the new detail view;
10. enable new-table ingestion only after verification passes.

Production migration requires a fresh database/private-files backup, host lock,
dry run, expected-count assertion and explicit approval.

## 10. Verification gates

### Gate A — schema

- all tables and indexes exist;
- schema installer is repeatable;
- no existing content changes;
- restricted role capabilities are correct.

### Gate B — migration

- every legacy record appears once;
- source and destination counts/checksums match;
- communication history and CV metadata are preserved;
- rerunning migration creates no duplicates.

### Gate C — ingestion

- all four form flows create exactly one record;
- retrying an idempotency key returns the same record;
- invalid, duplicate and rate-limited requests behave correctly;
- accepted records survive mail failure;
- CVs remain private.

### Gate D — operations

- indexed filters and cursor pagination return correct results;
- race-safe claiming works;
- bulk actions are auditable;
- replies are recipient-locked;
- failures and quarantine are visible;
- restricted users cannot delete or mutate customer fields.

### Gate E — scale

- generate at least 100,000 synthetic Local records without sending mail;
- verify common inbox queries with `EXPLAIN`;
- record query timings for latest, filtered, unassigned, branch, owner,
  follow-up and search views;
- prove cursor navigation does not slow down on deep history;
- clean up only prefix-locked synthetic rows.

### Gate F — production readiness

- production-compatible database version confirmed;
- real cron command verified;
- notification mode and recipients approved;
- retention period documented or automatic deletion remains off;
- backup and rollback rehearsed;
- no production change without explicit deployment approval.

## 11. Execution order

1. snapshot and architecture audit;
2. plugin skeleton, schema and repository;
3. Local legacy migration;
4. form ingestion and files;
5. durable notifications and replies;
6. command center;
7. regression and 100,000-record scale proof;
8. Nursery/Testimonial CMS parity;
9. full Local UAT;
10. production migration/deployment approval gate;
11. deployment, smoke tests and handover.

