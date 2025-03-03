<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Db\Migration\Service\Database;

use RuntimeException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Db\Migration\Migrator;
use Yiisoft\Yii\Db\Migration\Service\MigrationService;

use function count;
use function implode;
use function preg_match;

final class ListTablesService
{
    private ConnectionInterface $db;
    private MigrationService $migrationService;
    private Migrator $migrator;
    private ?SymfonyStyle $io = null;

    public function __construct(
        ConnectionInterface $db,
        MigrationService $migrationService,
        Migrator $migrator
    ) {
        $this->db = $db;
        $this->migrationService = $migrationService;
        $this->migrator = $migrator;
    }

    public function setIO(?SymfonyStyle $io): void
    {
        $this->io = $io;
        $this->migrationService->setIO($io);
        $this->migrator->setIO($io);
    }

    public function run(): int
    {
        if ($this->io === null) {
            throw new RuntimeException('Need set output decorator via `withIO()`.');
        }

        $tables = $this->getAllTableNames();
        $migrationTable = $this->db->getSchema()->getRawTableName($this->migrator->getHistoryTable());

        if (empty($tables) || implode(',', $tables) === $migrationTable) {
            $this->io->error('Your database does not contain any tables yet.');

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $databaseName = $this->getDatabaseName();
        $this->io->section(
            $databaseName === null
                ? 'List of tables: '
                : ('List of tables for database: ' . $databaseName)
        );

        $count = 0;

        $table = new Table($this->io);
        $table->setHeaders(['#', 'Table']);

        foreach ($tables as $value) {
            if ($value !== $migrationTable) {
                $count++;
                $table->addRow([(string) ($count), (string) ($value)]);
            }
        }

        $table->render();
        $this->migrationService->databaseConnection();

        return ExitCode::OK;
    }

    private function getAllTableNames(): array
    {
        try {
            $schemaNames = $this->db->getSchema()->getSchemaNames(true);
        } catch (NotSupportedException $e) {
            $schemaNames = [];
        }

        if (count($schemaNames) < 2) {
            return $this->db->getSchema()->getTableNames();
        }

        $tableNames = [];
        foreach ($schemaNames as $schemaName) {
            foreach ($this->db->getSchema()->getTableSchemas($schemaName) as $tableName) {
                $tableNames[] = $tableName->getFullName();
            }
        }

        return $tableNames;
    }

    private function getDsnAttribute(string $name, string $dsn): ?string
    {
        $result = null;

        if (preg_match('/' . $name . '=([^;]*)/', $dsn, $match)) {
            $result = $match[1];
        }

        if (preg_match('~([^/]+)\.sq3~', $dsn, $match)) {
            $result = $match[1];
        }

        return $result;
    }

    private function getDatabaseName(): ?string
    {
        if (!$this->db instanceof ConnectionPDOInterface) {
            return null;
        }

        $dsn = $this->db->getDriver()->getDsn();

        return $this->getDsnAttribute('dbname', $dsn);
    }
}
