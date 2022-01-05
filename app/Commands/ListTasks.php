<?php

namespace App\Commands;

use App\Clockify;
use Illuminate\Support\Carbon;
use LaravelZero\Framework\Commands\Command;
use stdClass;

use function Termwind\render;

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
        $client = new Clockify(
            apiKey: 'ZmQzZmI3ZjYtZWEzYS00MTgyLTgxYzMtOGRlYTNmYjY3NGEy',
            workspaceId: '600ef1d1afcebe46f8753cc7'
        );

        [$from, $to] = $this->getTimeRange();

        $response = $client->listTasks($from, $to);

        if ($response instanceof stdClass) {
            $this->error($response->message);
            return;
        }

        $this->render($response);
    }

    protected function getTimeRange(): array
    {
        $month = $this->argument('month');

        $from = Carbon::parse($month)->startOfMonth()->toISOString();
        $to = Carbon::parse($month)->endOfMonth()->toISOString();

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
                $this->info($projectTasks->first()->project->clientName . ' - ' . $projectTasks->first()->project->name);

                $this->newLine();

                $projectTasks
                    ->unique('description')
                    ->each(fn ($task) => $this->line($task->description));

                $this->newLine();
            });
    }
}
