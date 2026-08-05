# Revision History Log Lifecycle Design

**Date:** 2026-08-04

## Problem

Incoming revision staging correctly keeps the last approved file in `dataDir` and places a checked-in candidate in `incomingDir`, but the revision log no longer maintains one row per file version.

On check-in, the current implementation changes the approved `current` row to `pending` and inserts an `incoming` row. On approval, it changes both rows to the same numeric revision and inserts a third `current` row. This causes:

- the approved Latest row to disappear while a candidate is pending;
- duplicate numeric versions after approval;
- the authorization comment to replace the revision summary as the Latest note;
- `details.php` to report the number of log events instead of the number of file versions;
- repeated rejection and check-in cycles to accumulate incoming log rows.

## Goal

Restore a one-to-one relationship between revision log rows and actual file versions while preserving incoming staging and rejected-file behavior.

## Invariants

1. Every actual file version has exactly one row in the revision log.
2. Exactly one approved version is marked `current`.
3. At most one staged candidate is marked `incoming`.
4. Numeric revision values identify archived approved versions.
5. Pending and rejected candidates are not counted as approved revisions.
6. The revision note entered at check-in remains attached to that file version after approval.
7. Reviewer authorization and rejection activity remains in the existing access log and reviewer-comment fields; it does not create another file-version row.

## Revision Log Lifecycle

### Initial upload

The initial upload creates one `current` row with the note `Initial import`. Initial authorization does not create a new revision row because it approves the existing file rather than creating another file version.

| Revision | Note |
|---|---|
| `current` | Initial import |

### Check-in

Check-in leaves the approved `current` row unchanged and creates one `incoming` row containing the author's revision note. If an `incoming` row already exists from a rejected candidate, check-in replaces that row's author, timestamp, and note instead of inserting another row.

| Revision | Meaning |
|---|---|
| `current` | Last approved file in `dataDir` |
| `incoming` | Candidate file in `incomingDir` |

### Rejection

Rejection leaves both revision rows unchanged and sets `publishable=-1`. The incoming file and its revision summary remain available to the owner. History derives the candidate label from `publishable`:

- `publishable=0`: Pending
- `publishable=-1`: Rejected

A later check-in replaces the single `incoming` row and sets `publishable=0`, changing the displayed state back to Pending.

### Approval

Approval performs two log transitions in the same operation as file promotion:

1. Change the old `current` row to the next numeric revision.
2. Change the single `incoming` row to `current`.

Approval does not insert a third revision row. The new Latest row therefore retains the revision note entered by the author at check-in.

Example after approving the first checked-in revision:

| Display | Stored revision | Note |
|---|---|---|
| Version 1 | `0` | Initial import |
| Latest | `current` | Author's revision summary |

## History Page

`history.php` displays one row for each revision-log row:

- numeric rows as Version `revision + 1`;
- `current` as Latest;
- `incoming` as Pending when `publishable=0`;
- `incoming` as Rejected when `publishable=-1`.

While a candidate awaits review, History displays both the approved Latest row and the candidate Pending row. Rejected candidates remain visible as Rejected but are never assigned a numeric version unless later replaced and approved.

## Details Page

The displayed revision count is derived from actual approved file versions, not the raw number of log rows. It counts numeric rows plus the `current` row and excludes `incoming`.

Examples:

- initial upload: 1 approved revision;
- one pending or rejected candidate: still 1 approved revision;
- first candidate approved: 2 approved revisions;
- second candidate approved: 3 approved revisions.

A numeric revision details request continues to display `revision + 1`. The current details page displays the approved-version count regardless of whether an incoming candidate exists.

## Data and Schema

No schema change is required. The existing `log.revision`, `data.publishable`, access log, and reviewer-comment fields provide the required state.

Existing malformed rows are outside this fix's scope. The change prevents new duplicate revision rows. Test fixtures may include duplicate rows only to verify that normal new workflows do not create more.

## Failure Handling

Log transitions must only occur when the corresponding incoming file exists and the current data file can be archived. If file promotion fails, the candidate must not become `current`. Existing controller error handling remains responsible for reporting the failed authorization.

## Testing

Automated tests will cover:

1. Initial upload and initial authorization retain one `current` row with `Initial import`.
2. Check-in retains the approved `current` row and creates one `incoming` row with the revision note.
3. History shows Latest and Pending during review.
4. Details excludes the incoming candidate from its approved revision count.
5. Rejection displays Rejected while retaining Latest and the incoming note.
6. Re-check-in after rejection replaces the existing incoming row rather than adding another.
7. Approval changes old `current` to the next numeric revision and `incoming` to `current` without inserting another row.
8. History after approval shows unique version numbers and preserves the initial and revision notes.
9. Repeated approval cycles maintain one row per file version and sequential numeric revisions.
10. Existing file staging, checkout, rejection, approval, and access-log behavior remains unchanged.
