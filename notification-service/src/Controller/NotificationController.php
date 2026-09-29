<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\Request;
use App\Http\Response;
use App\Http\ResponseFactory;
use App\Resource\NotificationResource;
use App\Service\NotificationQueryService;
use App\Service\NotificationReplayService;

final class NotificationController
{
    private const DEFAULT_PER_PAGE = 20;

    private const MAX_PER_PAGE = 100;

    public function __construct(
        private readonly NotificationQueryService $notifications,
        private readonly NotificationReplayService $replay,
        private readonly ResponseFactory $response,
    ) {
    }

    public function index(Request $request): Response
    {
        $page = max(1, (int) ($request->query('page') ?? 1));
        $perPage = (int) ($request->query('perPage') ?? self::DEFAULT_PER_PAGE);
        $perPage = max(1, min($perPage, self::MAX_PER_PAGE));

        $filters = array_filter([
            'status' => $request->query('status'),
            'channel' => $request->query('channel'),
            'recipient' => $request->query('recipient'),
        ], static fn (mixed $value): bool => $value !== null);

        $list = $this->notifications->list($page, $perPage, $filters);

        return $this->response->ok(NotificationResource::collection($list));
    }

    public function show(Request $request): Response
    {
        $notification = $this->notifications->find((string) $request->routeParam('id'));

        return $this->response->ok(NotificationResource::fromDto($notification));
    }

    public function replay(Request $request): Response
    {
        $notification = $this->replay->replay((string) $request->routeParam('id'));

        return $this->response->ok(NotificationResource::fromDto($notification));
    }
}
