# Defer Full Audit Log to v2

v1 will not include a dedicated PHI audit log (view/access tracking, exportable compliance reports). Instead it will rely on Laravel application logs and database timestamps for accountability at launch. Full immutable audit logging (reads, writes, deletions, exports) is deferred to v2.

**Considered options:** full audit log at launch (recommended for clinical compliance); action-only log (writes but not reads).

**Consequences:** Manual screening deletion and supervisor actions will be harder to investigate retroactively until v2. When audit logging is added, it should be designed as append-only from the start and backfill is not possible for v1 activity — accept that gap or prioritize audit log earlier if compliance review requires it before launch.
