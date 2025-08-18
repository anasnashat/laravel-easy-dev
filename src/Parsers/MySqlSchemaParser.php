<?php

namespace AnasNashat\EasyDev\Parsers;

use Illuminate\Database\ConnectionResolverInterface as DB;
use AnasNashat\EasyDev\Contracts\SchemaParser;

class MySqlSchemaParser implements SchemaParser
{
    public function __construct(protected DB $db)
    {
    }

    public function getForeignKeysForTable(string $table): array
    {
        $query = "SELECT kcu.column_name, kcu.referenced_table_name, kcu.referenced_column_name
                  FROM information_schema.key_column_usage AS kcu
                  WHERE kcu.table_schema = SCHEMA() AND kcu.table_name = ?
                  AND kcu.referenced_table_name IS NOT NULL";

        return (array) $this->db->connection()->select($query, [$table]);
    }

    public function findPivotTablesForTable(string $table): array
    {
        $query = "SELECT DISTINCT kcu.table_name as pivot_table,
                         kcu.column_name as foreign_key,
                         kcu.referenced_table_name as referenced_table
                  FROM information_schema.key_column_usage AS kcu
                  WHERE kcu.table_schema = SCHEMA() 
                  AND kcu.referenced_table_name = ?
                  AND kcu.table_name != ?
                  AND EXISTS (
                      SELECT 1 FROM information_schema.key_column_usage AS kcu2
                      WHERE kcu2.table_schema = SCHEMA()
                      AND kcu2.table_name = kcu.table_name
                      AND kcu2.referenced_table_name != ?
                      AND kcu2.referenced_table_name IS NOT NULL
                  )";

        return (array) $this->db->connection()->select($query, [$table, $table, $table]);
    }

    public function getPolymorphicColumnsForTable(string $table): array
    {
        $query = "SELECT column_name
                  FROM information_schema.columns
                  WHERE table_schema = SCHEMA() AND table_name = ?
                  AND (column_name LIKE '%_type' OR column_name LIKE '%able_type')";

        return (array) $this->db->connection()->select($query, [$table]);
    }

    public function getAllTables(): array
    {
        $query = "SELECT table_name
                  FROM information_schema.tables
                  WHERE table_schema = SCHEMA()
                  AND table_type = 'BASE TABLE'";

        return array_column((array) $this->db->connection()->select($query), 'table_name');
    }

    public function getTableColumns(string $table): array
    {
        $query = "SELECT column_name, data_type, is_nullable, column_default
                  FROM information_schema.columns
                  WHERE table_schema = SCHEMA() AND table_name = ?
                  ORDER BY ordinal_position";

        return (array) $this->db->connection()->select($query, [$table]);
    }
}
