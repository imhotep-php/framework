<?php declare(strict_types=1);

namespace Imhotep\Session\Middleware;

use Closure;
use Imhotep\Contracts\Http\Request;
use Imhotep\Contracts\Http\Response;
use Imhotep\Contracts\Session\ISession;
use Imhotep\Cookie\Cookie;
use Imhotep\Session\SessionManager;

class StartSession
{
    public function __construct(
        protected SessionManager $manager
    ) { }

    public function handle(Request $request, Closure $next): Response
    {
        $session = $this->manager->store();

        $session->setId($request->cookie($session->getName()));

        return $this->handleStatefulRequest($request, $session, $next);
    }

    public function handleStatefulRequest(Request $request, ISession $session, Closure $next): Response
    {
        $request->setSession($session);

        $session->garbageCollect()
                ->setRequestOnHandler($request)
                ->start();

        $response = $next($request);

        $this->addCookieToResponse($response, $session);

        $session->save();

        return $response;
    }

    public function addCookieToResponse(Response $response, ISession $session): void
    {
        if (is_null($session->getId())) {
            return;
        }

        $config = $session->getConfig();

        $expires = $config['expire_on_close'] ? 0 : time() + $config['lifetime'];

        $response->setCookie(new Cookie(
            $session->getName(), $session->getId(), $expires,
            $config['path'], $config['domain'], $config['secure'],
            $config['httpOnly'], $config['sameSite']
        ));
    }
}