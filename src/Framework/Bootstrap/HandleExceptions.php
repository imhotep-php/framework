<?php declare(strict_types=1);

namespace Imhotep\Framework\Bootstrap;

use ErrorException;
use Imhotep\Console\Output\ConsoleOutput;
use Imhotep\Contracts\Debug\ExceptionHandler;
use Imhotep\Contracts\Http\Request;
use Imhotep\Framework\Application;
use Imhotep\Framework\Exceptions\FatalError;
use Psr\Log\LoggerInterface;
use Throwable;

class HandleExceptions
{
    protected ?string $reservedMemory = null;

    public function __construct(
        protected Application $app
    ) { }

    public function bootstrap(): void
    {
        $this->reservedMemory = str_repeat('x', 32768);

        error_reporting(-1);

        ini_set('ignore_repeated_errors', true);
        ini_set('display_errors', 'Off');
        ini_set('display_startup_errors', false);

        set_error_handler([$this, 'handleError']);

        set_exception_handler([$this, 'handleException']);

        register_shutdown_function([$this, 'handleShutdown']);
    }

    public function handleError($level, $message, $file = '', $line = 0, $context = []): void
    {
        if ($this->isDeprecation($level)) {
            $this->handleDeprecationError($message, $file, $line, $level);
            return;
        }

        if (error_reporting() & $level) {
            throw new ErrorException($message, 0, $level, $file, $line);
        }
    }

    public function handleException(Throwable $e): void
    {
        $this->reservedMemory = null;

        $handler = $this->app->make(ExceptionHandler::class);

        try{
            $handler->report($e);
        } catch (Throwable $e) { }

        if ($this->app->runningInConsole()) {
            $handler->renderForConsole($e);
        }
        else {
            $handler->render($e, $this->app[Request::class])->send();
        }
    }

    public function handleShutdown(): void
    {
        $this->reservedMemory = null;

        if (!is_null($error = error_get_last()) && $this->isFatal($error['type'])) {
            $this->handleException(new FatalError($error['message'], 0, $error));
        }
    }

    public function handleDeprecationError($message, $file, $line, $level = E_DEPRECATED): void
    {
        try {
            $logger = $this->app->make(LoggerInterface::class);
        } catch (Throwable $e) {
            return;
        }

        $config = $this->app['config']->get('logging.deprecations', []);

        if (empty($config['channel'])) $config['channel'] = 'null';
        if (empty($config['trace'])) $config['trace'] = false;

        if ($this->app['config']->has('logging.channels.'.$config['channel'])) {
            $logger->channel($config['channel'])->warning(sprintf('%s in %s on line %s',
                $message, $file, $line
            ));
        }
    }

    protected function isDeprecation($level): bool
    {
        return in_array($level, [E_DEPRECATED, E_USER_DEPRECATED]);
    }

    protected function isFatal($type): bool
    {
        return in_array($type, [E_COMPILE_ERROR, E_CORE_ERROR, E_ERROR, E_PARSE]);
    }
}