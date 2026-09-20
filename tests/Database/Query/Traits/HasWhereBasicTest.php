<?php declare(strict_types=1);

namespace Imhotep\Tests\Database\Query\Traits;

trait HasWhereBasicTest
{
    public function testWhereBasic(): void
    {
        // Equal
        $result = $this->users()->where('name', 'John Doe')->first();
        $this->assertEquals('John Doe', $result?->name);

        // With operator
        $result = $this->users()->where('age', '>', 30)->get();
        $this->assertCount(2, $result);
        $this->assertEquals('Bob Johnson', $result[0]?->name);
        $this->assertEquals('Charlie Wilson', $result[1]?->name);

        // With list conditions
        $result = $this->users()->where([
            'name' => 'John Doe',
            'active' => true
        ])->first();
        $this->assertEquals('John Doe', $result?->name);

        // With list conditions and operators
        $result = $this->users()->where([
            ['age', '>', 30],
            'active' => true,
        ])->get();
        $this->assertCount(1, $result);
        $this->assertEquals('Charlie Wilson', $result[0]?->name);


        // With grouping
        $result = $this->users()->where(
            fn($q) => $q->where('name', 'John Doe')->orWhere('name', 'Jane Smith')
        )->get();
        $this->assertCount(2, $result);
        $this->assertEquals('John Doe', $result[0]?->name);
        $this->assertEquals('Jane Smith', $result[1]?->name);

        // ...
        $subQuery = $this->posts()->selectRaw('MAX(views)')->whereColumn('posts.user_id', 'users.id');

        $result = $this->users()->where($subQuery, '>', 200)->get();
        $this->assertCount(1, $result);
        $this->assertEquals('Charlie Wilson', $result[0]?->name);

        // ...
        $subQuery = $this->posts()->select('user_id')->where('views', 75);
        $result = $this->users()->where('id', $subQuery)->get();
        $this->assertCount(1, $result);
        $this->assertEquals('Bob Johnson', $result[0]?->name);
    }
}

// whereNot('col', '=', 5) → с отрицанием
// whereAll(['col1','col2'], '>', 10) / whereAny(...)

