<?php
/**
 * Audit trail for selected users (Reels / Livestream support).
 * Edit USER_ACTIVITY_AUDIT_UIDS to add/remove tracked user_ids (CSV).
 */

if (!defined('USER_ACTIVITY_AUDIT_ENABLED')) {
    define('USER_ACTIVITY_AUDIT_ENABLED', true);
}

/** CSV of user_ids to audit. Default: Aldo Intriago (158). */
if (!defined('USER_ACTIVITY_AUDIT_UIDS')) {
    define('USER_ACTIVITY_AUDIT_UIDS', '158');
}

/** Days to keep rows before purge. */
if (!defined('USER_ACTIVITY_AUDIT_RETENTION_DAYS')) {
    define('USER_ACTIVITY_AUDIT_RETENTION_DAYS', 90);
}

/** Seconds to dedupe reels.view for the same post in one session. */
if (!defined('USER_ACTIVITY_AUDIT_VIEW_DEDUPE_SEC')) {
    define('USER_ACTIVITY_AUDIT_VIEW_DEDUPE_SEC', 60);
}
