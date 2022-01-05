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
        $response = $this->http()
            ->get(
                sprintf(
                    '%s/workspaces/%s/user/%s/time-entries?start=%s&end=%s&hydrated=1',
                    $this->baseUrl(),
                    $this->workspaceId,
                    $this->userId,
                    $from,
                    $to
                )
            );

        return json_decode($response->body());
    }

    protected function getUserId()
    {
        $response = $this->http()->get(sprintf('%s/user', $this->baseUrl()));

        $this->userId = data_get(json_decode($response->body()), 'id');
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
}
