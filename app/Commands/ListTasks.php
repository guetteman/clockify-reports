<?php

namespace App\Commands;

use App\Clockify;
use Exception;
use Illuminate\Support\Carbon;
use LaravelZero\Framework\Commands\Command;

class ListTasks extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'report:tasks
                            {month? : 3 letters month}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'List tasks for a provided month';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $client = new Clockify(
                apiKey: env('CLOCKIFY_API_KEY', ''),
                workspaceId: env('CLOCKIFY_WORKSPACE_ID', ''),
            );

            [$from, $to] = $this->getTimeRange();

            $response = $client->listTasks($from->toISOString(), $to->toISOString());

            $this->render($response);

            $this->newLine();
            $this->info(sprintf('From %s to %s', $from, $to));
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    protected function getTimeRange(): array
    {
        $month = $this->argument('month');

        if (empty($month)) {
            $from = now()->subMonth();
            $to = now();
        } elseif (now() < Carbon::parse($month)->startOfMonth()) {
            $from = Carbon::parse($month)->subYear()->startOfMonth();
            $to = Carbon::parse($month)->subYear()->endOfMonth();
        } else {
            $from = Carbon::parse($month)->startOfMonth();
            $to = Carbon::parse($month)->endOfMonth();
        }

        return [$from, $to];
    }

    protected function render(array $tasks): void
    {
        $this->newLine();

        if (count($tasks) <= 0) {
            $this->info('There are no tasks for this month');

            return;
        }

        collect($tasks)
            ->groupBy('projectId')
            ->each(function ($projectTasks) {
                $project = $projectTasks->first()['project'];
                $this->info($project['clientName'] . ' - ' . $project['name']);

                $this->newLine();

                $projectTasks
                    ->unique('description')
                    ->each(fn ($task) => $this->line($task['description']));

                $this->newLine();
            });
    }
}
