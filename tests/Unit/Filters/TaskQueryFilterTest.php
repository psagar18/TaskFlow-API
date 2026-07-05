<?php

declare(strict_types=1);

namespace Tests\Unit\Filters;

use App\Enums\TaskPriority;
use App\Filters\Task\PriorityFilter;
use App\Filters\Task\SearchFilter;
use App\Filters\Task\SortFilter;
use App\Filters\Task\StatusFilter;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class TaskQueryFilterTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function status_filter_narrows_the_query_by_status(): void
    {
        Task::factory()->create(['status' => 'pending']);
        Task::factory()->create(['status' => 'completed']);

        $filter = new StatusFilter('completed');
        $result = $filter->handle(Task::query(), fn ($query) => $query)->get();

        $this->assertCount(1, $result);
        $this->assertSame('completed', $result->first()->status->value);
    }

    #[Test]
    public function status_filter_is_a_no_op_when_null(): void
    {
        Task::factory()->count(3)->create();

        $filter = new StatusFilter(null);
        $result = $filter->handle(Task::query(), fn ($query) => $query)->get();

        $this->assertCount(3, $result);
    }

    #[Test]
    public function priority_filter_narrows_the_query_by_priority(): void
    {
        Task::factory()->create(['priority' => TaskPriority::Low]);
        Task::factory()->create(['priority' => TaskPriority::Urgent]);

        $filter = new PriorityFilter('urgent');
        $result = $filter->handle(Task::query(), fn ($query) => $query)->get();

        $this->assertCount(1, $result);
    }

    #[Test]
    public function search_filter_matches_title_or_description(): void
    {
        Task::factory()->create(['title' => 'Deploy to production', 'description' => null]);
        Task::factory()->create(['title' => 'Something else', 'description' => 'mentions deploy here']);
        Task::factory()->create(['title' => 'Unrelated', 'description' => 'nothing']);

        $filter = new SearchFilter('deploy');
        $result = $filter->handle(Task::query(), fn ($query) => $query)->get();

        $this->assertCount(2, $result);
    }

    #[Test]
    public function sort_filter_falls_back_to_created_at_for_unknown_columns(): void
    {
        $filter = new SortFilter('unsafe_injection_attempt', 'asc');

        $query = $filter->handle(Task::query(), fn ($query) => $query);

        $this->assertStringContainsString('order by "created_at" asc', strtolower($query->toSql()));
    }
}
