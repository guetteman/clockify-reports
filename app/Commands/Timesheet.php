<?php

namespace App\Commands;

use App\Clockify;
use Exception;
use Illuminate\Support\Carbon;
use LaravelZero\Framework\Commands\Command;

class Timesheet extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'report:timesheet
                            {month? : 3 letters month}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Timesheet for provided month';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $workingDays = $this->ask('Working days: ');

        try {
            $client = new Clockify(
                apiKey: env('CLOCKIFY_API_KEY', ''),
                workspaceId: env('CLOCKIFY_WORKSPACE_ID', ''),
            );

            [$from, $to] = $this->getTimeRange();

            $response = $client->detailedReport($from->toISOString(), $to->toISOString());

            $this->render($response, $workingDays);

            $this->newLine();
            $this->info(sprintf('From %s to %s', $from, $to));
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    protected function render(array $response, int $workingDays): void
    {
        $this->newLine();

        if (count(data_get($response, 'timeentries', [])) <= 0) {
            $this->info('There are no tasks for this month');

            return;
        }

        $timesheet = collect(data_get($response, 'timeentries'))
            ->groupBy('projectId')
            ->map(function ($timeEntries) {
                $hours = $timeEntries
                    ->reduce(function (float $result, array $timeEntry) {
                        return $result + data_get($timeEntry, 'timeInterval.duration', 0);
                    }, 0) / 3600;

                return [$timeEntries[0]['clientName'] . ' - ' . $timeEntries[0]['projectName'], round($hours, 2)];
            });

        $total = round(data_get($response, 'totals.0.totalTime', 0) / 3600, 2);

        $timesheet->push(['', '']);

        $timesheet->push(['Working hours', $workingDays * 8]);
        $timesheet->push(['MPB', $total - $workingDays * 8]);
        $timesheet->push(['Total', $total]);

        $this->table(
            ['Project', 'time (h)'],
            $timesheet->values(),
        );
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
}
