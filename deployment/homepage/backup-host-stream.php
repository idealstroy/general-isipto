<?php
// Execute through SSH stdin only. No MODX bootstrap, remote files or database writes.
if (PHP_SAPI !== 'cli') exit(2);
$mode = getenv('ISIPTO_BACKUP_MODE');
$root = '/home/i/idealstroy/isipto.ru/public_html';
if ($mode === 'files') {
    $command = 'GZIP=-1 tar -C '.escapeshellarg($root).' -czf - .';
} elseif ($mode === 'database') {
    require $root.'/core/config/config.inc.php';
    putenv('MYSQL_PWD='.$database_password);
    $dump = 'mysqldump --skip-lock-tables --skip-add-locks --quick --hex-blob --routines --triggers --events --default-character-set=utf8 --host='.escapeshellarg($database_server).' --user='.escapeshellarg($database_user).' '.escapeshellarg($dbase);
    $command = 'bash -o pipefail -c '.escapeshellarg($dump.' | gzip -1');
} else {
    fwrite(STDERR, "Expected files or database\n"); exit(2);
}
// Inherit the SSH stdout/stderr streams; no temporary file on the host.
passthru($command, $code);
if ($mode === 'database') putenv('MYSQL_PWD');
exit($code);
