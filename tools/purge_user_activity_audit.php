<?php
/**
 * CLI / cron: purge Wo_User_Activity_Log rows older than retention days.
 * Usage: php tools/purge_user_activity_audit.php
 */
$root = dirname(__DIR__);
chdir($root);
require_once $root . '/assets/init.php';

$deleted = function_exists('Wo_PurgeUserActivityAuditLog') ? Wo_PurgeUserActivityAuditLog() : 0;
$days = defined('USER_ACTIVITY_AUDIT_RETENTION_DAYS') ? (int) USER_ACTIVITY_AUDIT_RETENTION_DAYS : 90;
echo date('c') . " purged={$deleted} retention_days={$days}\n";
