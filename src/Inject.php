<?php

namespace App;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\BrowserConsoleHandler;
use Monolog\Handler\BufferHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\SocketHandler;
use Monolog\Handler\TelegramBotHandler;
use Monolog\LogRecord;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Processor\WebProcessor;
use Psr\Log\LoggerInterface;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Inject
{
    static function getLogger(): LoggerInterface
    {
        // https://github.com/Seldaek/monolog/blob/main/doc/message-structure.md
        // параметр 'bubble' можно ставить в 'false' чтобы следующие хендлеры не выполнялись
        // formatter для подсветки логов
        // https://github.com/bramus/monolog-colored-line-formatter

        // TODO: create builder

        $log = new Logger('app');

        $myFormat = new LineFormatter(
            format: "[%datetime%] %level_name%: %message% %context% %extra%\n",
            dateFormat: 'h:m:s',
        );
        // TODO:
        // HtmlFormatter
        // NormalizerFormatter ScalarFormatter JsonFormatter

        $mainHandler = (new RotatingFileHandler('logs/app.log', 10, level: Level::Debug))->setFormatter($myFormat);

        $errorHandler = new StreamHandler('logs/app-errors.log', level: Level::Warning);
        $errorHandler->setFormatter($myFormat);

        // дополнительный контекст в extra
        $log->pushProcessor(function (LogRecord $record) {
            $record->extra['user_id'] = rand(42, 100);
            return $record;
        });
        $log->pushProcessor(new IntrospectionProcessor(Level::Error));
        $log->pushProcessor(new WebProcessor());
        $log->pushProcessor(new MemoryUsageProcessor());
        // PsrLogMessageProcessor - чтобы работали плейсхолдеры
        $log->pushProcessor(new PsrLogMessageProcessor());

        // native php error log
        $log->pushHandler(new ErrorLogHandler(level: Level::Error));

        // debug (send for browser in shutdown function)
        $log->pushHandler(new BrowserConsoleHandler(level: Level::Error));

        // RedisHandler / RedisPubSubHandler - в Redis

        // persistent socket - fast variant
//        self::addSocketHandler($log);

        // telegram
//        self::addTelegramHandler($log);

        // другой логгер, который можно настроить отдельно
//        $securityLog = $log->withName('security');

        // скидывает буферы - полезно для долгоживущих процессов
//        $logger->reset();

        $isErrorWrap = false;
        // если не произойдет Error, то и Warning не запишется
        if ($isErrorWrap) {
            $log->pushHandler(new FingersCrossedHandler($errorHandler, Level::Error));
        } else {
            $log->pushHandler($errorHandler);
        }

        // запись будет произведена один раз, накопительно
        $isBufferWrap = true;
        if ($isBufferWrap) {
            $log->pushHandler(new BufferHandler($mainHandler));
        } else {
            $log->pushHandler($mainHandler);
        }

        // пропускать 1/5 часть записей случайным образом
//        $log->pushHandler(new SamplingHandler($mainHandler, 5));

        // проксирование в другой PSR-3 логгер
//        $log->pushHandler(new PsrHandler($otherLogger));

        // TestHandler - для тестирования
        // HandlerWrapper - заготовка для создания своих хендлеров

        return $log;
    }

    static function addSocketHandler(Logger $log, $formatter = null)
    {
        $log->pushHandler(
            (new SocketHandler('unix:///var/log/tmp.socket', level: Level::Error))
            ->setPersistent(true)
            ->setFormatter($formatter)
        );
    }

    static function addTelegramHandler(Logger $log, $formatter = null)
    {
        $log->pushHandler(
            (new TelegramBotHandler(
            apiKey:   '***',
            channel: '859029886',
            level:    Level::Error))
            ->setFormatter($formatter)
        );
    }

    static function clearLogDir(): void
    {
        array_map(unlink(...), array_filter(glob("logs/*")));
    }
}