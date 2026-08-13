<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Model\SystemAdmin;
use App\System\Service\MessageService;
use Hyperf\WebSocketServer\Context;
use Hyperf\WebSocketServer\Exception\WebSocketHandShakeException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class WebSocketTicketMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly MessageService $messageService)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        parse_str($request->getUri()->getQuery(), $query);
        $adminId = $this->messageService->consumeWebSocketTicket((string) ($query['ticket'] ?? ''));
        $admin = $adminId > 0
            ? (new SystemAdmin())->newQuery()->where('id', $adminId)->where('status', SystemAdmin::STATUS_ENABLED)->first()
            : null;
        if (! $admin instanceof SystemAdmin) {
            throw new WebSocketHandShakeException('WebSocket authentication failed');
        }

        Context::set('admin_id', $adminId);
        return $handler->handle($request->withAttribute('admin_id', $adminId));
    }
}
