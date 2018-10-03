<?php

namespace Railken\LaraEye\Tests;

use Railken\LaraEye\Filter;
use Railken\SQ\Exceptions\QuerySyntaxException;

class FilterTest extends \Orchestra\Testbench\TestCase
{
    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        $dotenv = new \Dotenv\Dotenv(__DIR__.'/..', '.env');
        $dotenv->load();
        parent::setUp();
    }

    /**
     * Retrieve a new instance of query.
     *
     * @param string $str_filter
     * @param array  $keys
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function newQuery($str_filter, $keys)
    {
        $filter = new Filter('foo', $keys);
        $query = (new Foo())->newQuery()->getQuery();
        $filter->build($query, $str_filter);

        return $query;
    }

    public function testFilterUndefindKey()
    {
        $this->expectException(QuerySyntaxException::class);
        $this->newQuery('d eq 1', ['x']);
    }

    public function assertQuery(string $sql, string $filter, $keys = ['id', 'x', 'y', 'z', 'created_at']) 
    {
        $this->assertEquals($sql, $this->newQuery($filter, $keys)->toSql());
    }

    public function testFilterAndWrong()
    {
        $this->expectException(QuerySyntaxException::class);
        $this->newQuery('x and 1', ['*']);
    }

    public function testFilterConcatFunction()
    {
        $this->assertQuery('select * from `foo` where `foo`.`x` = CONCAT(`foo`.`x`,?)', 'x eq concat(x,2)');
        $this->assertQuery('select * from `foo` where `foo`.`x` = CONCAT(`foo`.`x`,CONCAT(`foo`.`y`,?))', 'x eq concat(x,concat(y,3))');
    }

    public function testFilterSumFunction()
    {
        $this->assertQuery('select * from `foo` where `foo`.`x` = SUM(`foo`.`x`,?)', 'x eq sum(x,2)');
    }

    public function testFilterAllKeysValid()
    {
        $this->assertQuery('select * from `foo` where `foo`.`d` = `foo`.`f`', 'd eq f', ['*']);
    }

    public function testFilterEqColumns()
    {
        $this->assertQuery('select * from `foo` where `foo`.`x` = `foo`.`x`', 'x eq x');
        $this->assertQuery('select * from `foo` where `foo`.`x` = `foo`.`x`', 'x = x');
    }

    public function testFilterEq()
    {
        $this->assertQuery('select * from `foo` where `foo`.`x` = ?', 'x eq 1');
        $this->assertQuery('select * from `foo` where `foo`.`x` = ?', 'x = 1');
    }

    public function testFilterGt()
    {
        $this->assertQuery('select * from `foo` where `foo`.`x` > ?', 'x gt 1');
        $this->assertQuery('select * from `foo` where `foo`.`x` > ?', 'x > 1');
    }

    public function testFilterGte()
    {
        $this->assertQuery('select * from `foo` where `foo`.`x` >= ?', 'x gte 1');
        $this->assertQuery('select * from `foo` where `foo`.`x` >= ?', 'x >= 1');
    }

    public function testFilterLt()
    {
        $this->assertQuery('select * from `foo` where `foo`.`x` < ?', 'x lt 1');
        $this->assertQuery('select * from `foo` where `foo`.`x` < ?', 'x < 1');
    }

    public function testFilterLte()
    {
        $this->assertQuery('select * from `foo` where `foo`.`x` <= ?', 'x lte 1');
        $this->assertQuery('select * from `foo` where `foo`.`x` <= ?', 'x <= 1');
    }

    public function testFilterCt()
    {
        $this->assertQuery('select * from `foo` where `foo`.`x` like ?', 'x ct 1');
        $this->assertQuery('select * from `foo` where `foo`.`x` like ?', 'x *= 1');
    }

    public function testFilterSw()
    {
        $this->assertQuery('select * from `foo` where `foo`.`x` like ?', 'x sw 1');
        $this->assertQuery('select * from `foo` where `foo`.`x` like ?', 'x ^= 1');
    }

    public function testFilterEw()
    {
        $this->assertQuery('select * from `foo` where `foo`.`x` like ?', 'x ew 1');
        $this->assertQuery('select * from `foo` where `foo`.`x` like ?', 'x $= 1');
    }

    public function testFilterIn()
    {
        $this->assertQuery('select * from `foo` where `foo`.`x` in (?)', 'x in (1)');
        $this->assertQuery('select * from `foo` where `foo`.`x` in (?)', 'x =[] (1)');
    }

    public function testFilterNotIn()
    {
        $this->assertQuery('select * from `foo` where `foo`.`x` not in (?)', 'x not in (1)');
        $this->assertQuery('select * from `foo` where `foo`.`x` not in (?)', 'x !=[] (1)');
    }

    public function testFilterAnd()
    {
        $this->assertQuery('select * from `foo` where (`foo`.`x` = ? and `foo`.`x` = ?)', 'x = 1 and x = 2');
        $this->assertQuery('select * from `foo` where (`foo`.`x` = ? and `foo`.`x` = ?)', 'x = 1 && x = 2');
    }

    public function testFilterOr()
    {
        $this->assertQuery('select * from `foo` where (`foo`.`x` = ? or `foo`.`x` = ?)', 'x = 1 or x = 2');
        $this->assertQuery('select * from `foo` where (`foo`.`x` = ? or `foo`.`x` = ?)', 'x = 1 || x = 2');
    }

    public function testFilterNull()
    {
        $this->assertQuery('select * from `foo` where `foo`.`x` is null', 'x is null');
    }

    public function testFilterNotNull()
    {
        $this->assertQuery('select * from `foo` where `foo`.`x` is not null', 'x is not null');
    }

    public function testGrouping()
    {
        $this->assertQuery('select * from `foo` where (`foo`.`x` = ? or (`foo`.`x` = ? and `foo`.`x` = ?))', 'x = 1 or (x = 2 and x = 3)');
        $this->assertQuery('select * from `foo` where (`foo`.`x` = ? and (`foo`.`x` = ? or `foo`.`x` = ?))', 'x = 1 and (x = 2 or x = 3)');
        $this->assertQuery('select * from `foo` where (`foo`.`x` = ? and (`foo`.`x` = ?))', 'x = 1 and (x = 2)');
    }
}
