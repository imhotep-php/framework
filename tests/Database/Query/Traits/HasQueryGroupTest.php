<?php declare(strict_types=1);

namespace Imhotep\Tests\Database\Query\Traits;

use Imhotep\Database\Expression;

trait HasQueryGroupTest
{
    public function testGroupByBasic(): void
    {
        $result = $this->posts()
            ->select('user_id')
            ->groupBy('user_id')
            ->orderBy('user_id')
            ->get();

        $this->assertCount(5, $result); // user_id: 1,2,3,4,5
        $this->assertEquals(1, $result[0]->user_id);
        $this->assertEquals(2, $result[1]->user_id);
        $this->assertEquals(3, $result[2]->user_id);
        $this->assertEquals(4, $result[3]->user_id);
        $this->assertEquals(5, $result[4]->user_id);
    }

    public function testGroupByRaw(): void
    {
        $result = $this->posts()
            ->selectRaw('DATE(created_at) AS created_date, COUNT(*) AS cnt')
            ->groupByRaw('DATE(created_at)')
            ->get();

        $this->assertCount(1, $result);
    }

    public function testHavingBasic(): void
    {
        $result = $this->posts()
            ->select('user_id')
            ->selectRaw('COUNT(*) as total_posts')
            ->groupBy('user_id')
            ->having(new Expression('COUNT(*) > 1'))
            ->orderBy('user_id')
            ->get();

        $this->assertCount(2, $result);

        $this->assertEquals(1, $result[0]->user_id);
        $this->assertEquals(2, $result[0]->total_posts);
        $this->assertEquals(5, $result[1]->user_id);
        $this->assertEquals(2, $result[1]->total_posts);
    }

    public function testHavingRaw(): void
    {
        $result = $this->posts()
            ->select('user_id')
            ->selectRaw('COUNT(*) as total_posts')
            ->selectRaw('SUM(views) as total_views')
            ->groupBy('user_id')
            ->havingRaw('SUM(views) > ?', [200])
            ->orderBy('user_id')
            ->get();

        $this->assertCount(1, $result);
        $this->assertEquals(5, $result[0]->user_id);
        $this->assertEquals(550, $result[0]->total_views);
    }

    public function testHavingNested(): void
    {
        $result = $this->posts()
            ->select('user_id')
            ->selectRaw('COUNT(*) as total_posts')
            ->selectRaw('SUM(views) as total_views')
            ->groupBy('user_id')
            ->having(function($query) {
                $query->havingRaw('COUNT(*) > 1')
                    ->orHavingRaw('SUM(views) > ?', [400]);
            })
            ->orderBy('user_id')
            ->get();

        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]->user_id);
        $this->assertEquals(5, $result[1]->user_id);
    }


    protected function addPostsForGroupTest(): void
    {
        $posts = [
            ['user_id' => 1, 'title' => 'Eighth Post', 'content' => 'Content of eighth post', 'views' => 100],
            ['user_id' => 2, 'title' => 'Ninth Post', 'content' => 'Content of ninth post', 'views' => 100],
        ];

        foreach ($posts as $post) {
            $this->posts()->insert($post);
        }
    }
}