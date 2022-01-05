<?php

namespace App;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class Clockify
{
    protected string $apiKey;

    protected string $workspaceId;

    protected string $userId;

    public function __construct(string $apiKey, string $workspaceId)
    {
        $this->apiKey = $apiKey;
        $this->workspaceId = $workspaceId;

        $this->getUserId();
    }

    public function listTasks(string $from, string $to)
    {
        return $this->http()
            ->get(
                sprintf(
                    '%s/workspaces/%s/user/%s/time-entries?start=%s&end=%s&hydrated=1&page-size=5000',
                    $this->baseUrl(),
                    $this->workspaceId,
                    $this->userId,
                    $from,
                    $to
                )
            )->throw()->json();
    }

    public function detailedReport(string $from, string $to)
    {
        return $this->http()
            ->post(
                sprintf(
                    '%s/workspaces/%s/reports/detailed',
                    $this->reportsBaseUrl(),
                    $this->workspaceId,
                ),
                [
                    'dateRangeStart' => $from,
                    'dateRangeEnd' => $to,
                    'detailedFilter' => [
                        'page' => 1,
                        'pageSize' => 1000,
                    ],
                    'amountShown' => 'HIDE_AMOUNT',
                ]
            )->throw()->json();
    }

    protected function getUserId()
    {
        $response = $this->http()
            ->get(sprintf('%s/user', $this->baseUrl()))
            ->throw()
            ->json();

        $this->userId = data_get($response, 'id');
    }

    protected function http(): PendingRequest
    {
        return Http::withHeaders([
            'Accept' => 'application/json',
            'X-Api-Key' => $this->apiKey,
        ]);
    }

    protected function baseUrl(): string
    {
        return 'https://api.clockify.me/api/v1';
    }

    protected function reportsBaseUrl(): string
    {
        return 'https://reports.api.clockify.me/v1';
    }
}
