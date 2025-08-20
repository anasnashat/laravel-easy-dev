<?php

namespace AnasNashat\EasyDev\Parsers;

use Illuminate\Database\ConnectionResolverInterface as DB;
use AnasNashat\EasyDev\Contracts\SchemaParser;

class PostgresSchemaParser implements SchemaParser
{
    public function __construct(protected DB $db)
    {
    }

    public function getForeignKeysForTable(string $table): array
    {
        $query = "SELECT kcu.column_name, ccu.table_name AS referenced_table_name, ccu.column_name AS referenced_column_name
                  FROM information_schema.table_constraints AS tc 
                  JOIN information_schema.key_column_usage AS kcu
                    ON tc.constraint_name = kcu.constraint_name
                    AND tc.table_schema = kcu.table_schema
                  JOIN information_schema.constraint_column_usage AS ccu
                    ON ccu.constraint_name = tc.constraint_name
                    AND ccu.table_schema = tc.table_schema
                  WHERE tc.constraint_type = 'FOREIGN KEY' 
                  AND tc.table_name = ?";

        return (array) $this->db->connection()->select($query, [$table]);
    }

    public function findPivotTablesForTable(string $table): array
    {
        $query = "SELECT DISTINCT tc.table_name as pivot_table,
                         kcu.column_name as foreign_key,
                         ccu.table_name as referenced_table
                  FROM information_schema.table_constraints AS tc 
                  JOIN information_schema.key_column_usage AS kcu
                    ON tc.constraint_name = kcu.constraint_name
                  JOIN information_schema.constraint_column_usage AS ccu
                    ON ccu.constraint_name = tc.constraint_name
                  WHERE tc.constraint_type = 'FOREIGN KEY' 
                  AND ccu.table_name = ?
                  AND tc.table_name != ?
                  AND EXISTS (
                      SELECT 1 FROM information_schema.table_constraints AS tc2
                      JOIN information_schema.constraint_column_usage AS ccu2
                        ON ccu2.constraint_name = tc2.constraint_name
                      WHERE tc2.constraint_type = 'FOREIGN KEY'
                      AND tc2.table_name = tc.table_name
                      AND ccu2.table_name != ?
                  )";

        return (array) $this->db->connection()->select($query, [$table, $table, $table]);
    }

    public function getPolymorphicColumnsForTable(string $table): array
    {
        $query = "SELECT column_name
                  FROM information_schema.columns
                  WHERE table_name = ?
                  AND (column_name LIKE '%_type' OR column_name LIKE '%able_type')";

        return (array) $this->db->connection()->select($query, [$table]);
    }

    public function getAllTables(): array
    {
        $query = "SELECT table_name
                  FROM information_schema.tables
                  WHERE table_type = 'BASE TABLE'
                  AND table_schema = 'public'";

        return array_column((array) $this->db->connection()->select($query), 'table_name');
    }

    public function getTableColumns(string $table): array
    {
        $query = "SELECT column_name, data_type, is_nullable, column_default
                  FROM information_schema.columns
                  WHERE table_name = ?
                  ORDER BY ordinal_position";

        return (array) $this->db->connection()->select($query, [$table]);
    }
}
