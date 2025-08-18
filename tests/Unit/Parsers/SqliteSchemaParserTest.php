<?php

namespace AnasNashat\EasyDev\Tests\Unit\Parsers;

use AnasNashat\EasyDev\Parsers\SqliteSchemaParser;
use AnasNashat\EasyDev\Tests\TestCase;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Connection;
use Mockery;

class SqliteSchemaParserTest extends TestCase
{
    private SqliteSchemaParser $parser;
    private ConnectionResolverInterface $db;
    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->connection = Mockery::mock(Connection::class);
        $this->db = Mockery::mock(ConnectionResolverInterface::class);
        
        $this->db
            ->shouldReceive('connection')
            ->andReturn($this->connection);
            
        $this->parser = new SqliteSchemaParser($this->db);
    }

    public function test_get_foreign_keys_for_table(): void
    {
        $tableName = 'posts';
        $expectedQuery = "PRAGMA foreign_key_list({$tableName})";
        
        $mockForeignKeys = [
            (object) [
                'id' => 0,
                'seq' => 0,
                'table' => 'users',
                'from' => 'user_id',
                'to' => 'id',
                'on_update' => 'NO ACTION',
                'on_delete' => 'CASCADE',
                'match' => 'NONE'
            ],
            (object) [
                'id' => 1,
                'seq' => 0,
                'table' => 'categories',
                'from' => 'category_id',
                'to' => 'id',
                'on_update' => 'NO ACTION',
                'on_delete' => 'SET NULL',
                'match' => 'NONE'
            ]
        ];

        $this->connection
            ->shouldReceive('select')
            ->with($expectedQuery)
            ->andReturn($mockForeignKeys);

        $result = $this->parser->getForeignKeysForTable($tableName);

        $this->assertCount(2, $result);
        $this->assertEquals('user_id', $result[0]->column_name);
        $this->assertEquals('users', $result[0]->referenced_table_name);
        $this->assertEquals('id', $result[0]->referenced_column_name);
        
        $this->assertEquals('category_id', $result[1]->column_name);
        $this->assertEquals('categories', $result[1]->referenced_table_name);
        $this->assertEquals('id', $result[1]->referenced_column_name);
    }

    public function test_get_all_tables(): void
    {
        $expectedQuery = "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'";
        
        $mockTables = [
            (object) ['name' => 'users'],
            (object) ['name' => 'posts'],
            (object) ['name' => 'categories'],
            (object) ['name' => 'post_tag'],
        ];

        $this->connection
            ->shouldReceive('select')
            ->with($expectedQuery)
            ->andReturn($mockTables);

        $result = $this->parser->getAllTables();

        $this->assertEquals(['users', 'posts', 'categories', 'post_tag'], $result);
    }

    public function test_get_table_columns(): void
    {
        $tableName = 'users';
        $expectedQuery = "PRAGMA table_info({$tableName})";
        
        $mockColumns = [
            (object) [
                'cid' => 0,
                'name' => 'id',
                'type' => 'INTEGER',
                'notnull' => 1,
                'dflt_value' => null,
                'pk' => 1
            ],
            (object) [
                'cid' => 1,
                'name' => 'name',
                'type' => 'VARCHAR(255)',
                'notnull' => 1,
                'dflt_value' => null,
                'pk' => 0
            ],
            (object) [
                'cid' => 2,
                'name' => 'email',
                'type' => 'VARCHAR(255)',
                'notnull' => 0,
                'dflt_value' => null,
                'pk' => 0
            ]
        ];

        $this->connection
            ->shouldReceive('select')
            ->with($expectedQuery)
            ->andReturn($mockColumns);

        $result = $this->parser->getTableColumns($tableName);

        $this->assertCount(3, $result);
        $this->assertEquals('id', $result[0]->column_name);
        $this->assertEquals('INTEGER', $result[0]->data_type);
        $this->assertEquals('NO', $result[0]->is_nullable);
        
        $this->assertEquals('email', $result[2]->column_name);
        $this->assertEquals('YES', $result[2]->is_nullable);
    }

    public function test_get_polymorphic_columns_for_table(): void
    {
        $tableName = 'comments';
        $expectedQuery = "PRAGMA table_info({$tableName})";
        
        $mockColumns = [
            (object) ['name' => 'id', 'type' => 'INTEGER'],
            (object) ['name' => 'content', 'type' => 'TEXT'],
            (object) ['name' => 'commentable_id', 'type' => 'INTEGER'],
            (object) ['name' => 'commentable_type', 'type' => 'VARCHAR'],
            (object) ['name' => 'user_id', 'type' => 'INTEGER'],
        ];

        $this->connection
            ->shouldReceive('select')
            ->with($expectedQuery)
            ->andReturn($mockColumns);

        $result = $this->parser->getPolymorphicColumnsForTable($tableName);

        $this->assertCount(1, $result);
        $this->assertEquals('commentable_type', $result[0]->column_name);
    }

    public function test_find_pivot_tables_for_table(): void
    {
        $tableName = 'posts';
        
        // Mock getAllTables call
        $this->connection
            ->shouldReceive('select')
            ->with("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")
            ->andReturn([
                (object) ['name' => 'users'],
                (object) ['name' => 'posts'],
                (object) ['name' => 'tags'],
                (object) ['name' => 'post_tag'],
                (object) ['name' => 'categories'],
            ]);

        // Mock foreign key calls for each table
        $this->connection
            ->shouldReceive('select')
            ->with('PRAGMA foreign_key_list(users)')
            ->andReturn([]);

        $this->connection
            ->shouldReceive('select')
            ->with('PRAGMA foreign_key_list(tags)')
            ->andReturn([]);

        $this->connection
            ->shouldReceive('select')
            ->with('PRAGMA foreign_key_list(post_tag)')
            ->andReturn([
                (object) ['table' => 'posts', 'from' => 'post_id', 'to' => 'id'],
                (object) ['table' => 'tags', 'from' => 'tag_id', 'to' => 'id'],
            ]);

        $this->connection
            ->shouldReceive('select')
            ->with('PRAGMA foreign_key_list(categories)')
            ->andReturn([]);

        $result = $this->parser->findPivotTablesForTable($tableName);

        $this->assertCount(2, $result);
        $this->assertEquals('post_tag', $result[0]->pivot_table);
        $this->assertEquals('post_id', $result[0]->foreign_key);
        $this->assertEquals('posts', $result[0]->referenced_table);
        
        $this->assertEquals('post_tag', $result[1]->pivot_table);
        $this->assertEquals('tag_id', $result[1]->foreign_key);
        $this->assertEquals('tags', $result[1]->referenced_table);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
