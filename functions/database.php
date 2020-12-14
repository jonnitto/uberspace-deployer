<?php

namespace Deployer;

/**
 * Go to the backup folder and create the folder if it doesn't exist'
 *
 * @return string
 */
function goToBackupFolder(): string
{
    if (!test('[ -d {{db_backup_folder}} ]')) {
        run('mkdir -p {{db_backup_folder}}');
    }
    return get('db_backup_folder');
}

/**
 * Create a compressed dump in the current directory
 *
 * @param string|null $database
 * @return string
 */
function createDbDump(?string $database = null): string
{
    if (isset($database)) {
        $filename = "{$database}.sql.tgz";
    } else {
        $database = get('db_name');
        $filename = parse('{{release_name}}.sql.tgz');
    }
    run("mysqldump $database > dump.sql");
    run("tar cfz $filename dump.sql");
    run('rm -f dump.sql');
    return $filename;
}

/**
 * Get all dbs on the server
 *
 * @return array
 */
function getDbs(): array
{
    $items = run('mysql -e "SHOW DATABASES LIKE \'{{user}}%\'"');
    $items = explode("\n", $items);
    array_shift($items);
    return $items;
}

/**
 * Create a backup from the current database on the server
 *
 * @return void
 */
function dbBackup(): void
{
    cd(goToBackupFolder());
    createDbDump();
    writebox('Database backup created', 'blue');

    $keep = get('db_backup_keep_dumps');
    if ($keep === -1) {
        // Keep unlimited dumps.
        return;
    }
    $backups = \explode("\n", run('ls -dt *'));
    $sudo = get('cleanup_use_sudo') ? 'sudo ' : '';
    $runOpts = [];
    if ($sudo) {
        $runOpts['tty'] = get('cleanup_tty', false);
    }
    while ($keep > 0) {
        \array_shift($backups);
        --$keep;
    }

    foreach ($backups as $backup) {
        run("{$sudo}rm -f {{db_backup_folder}}/$backup", $runOpts);
    }
}
