<?php
declare(strict_types=1);

/**
 * OCP Interface Stubs for Standalone Testing
 *
 * These minimal interface definitions allow unit tests to run
 * without a full Nextcloud installation. They should only be used
 * for basic unit testing with mocks.
 */

namespace OCP;

interface IRequest {
    public function getParam(string $key, $default = null);
    public function getParams(): array;
    public function getUploadedFile(string $key);
}

interface IConfig {
    public function getAppValue(string $appId, string $key, string $default = ''): string;
    public function setAppValue(string $appId, string $key, string $value): void;
    public function getUserValue(string $userId, string $appId, string $key, string $default = ''): string;
    public function setUserValue(string $userId, string $appId, string $key, string $value): void;
    public function getSystemValue(string $key, $default = '');
}

interface IUser {
    public function getUID(): string;
    public function getDisplayName(): string;
}

interface IUserSession {
    public function getUser(): ?IUser;
    public function isLoggedIn(): bool;
}

interface IGroupManager {
    public function isAdmin(string $uid): bool;
    public function isInGroup(string $uid, string $gid): bool;
}

interface ITempManager {
    public function getTemporaryFile(string $postFix = ''): string;
    public function getTemporaryFolder(string $postFix = ''): string;
}

interface IDBConnection {
    public function getQueryBuilder();
    public function beginTransaction(): void;
    public function commit(): void;
    public function rollBack(): void;
}

interface IL10N {
    public function t(string $text, $parameters = []): string;
    public function n(string $text_singular, string $text_plural, int $count, array $parameters = []): string;
}

interface IURLGenerator {
    public function imagePath(string $app, string $image): string;
    public function linkToRoute(string $routeName, array $arguments = []): string;
}

namespace OCP\Files;

interface IRootFolder {}
interface File {}
interface Folder {}
interface Node {}

class NotFoundException extends \Exception {}

namespace OCP\AppFramework;

use OCP\IRequest;

abstract class Controller {
    protected string $appName;
    protected IRequest $request;

    public function __construct(string $appName, IRequest $request) {
        $this->appName = $appName;
        $this->request = $request;
    }
}

/**
 * Http class with status code constants
 * Note: This is in namespace OCP\AppFramework, not OCP\AppFramework\Http
 */
class Http {
    public const STATUS_OK = 200;
    public const STATUS_CREATED = 201;
    public const STATUS_NO_CONTENT = 204;
    public const STATUS_MULTI_STATUS = 207;
    public const STATUS_BAD_REQUEST = 400;
    public const STATUS_UNAUTHORIZED = 401;
    public const STATUS_FORBIDDEN = 403;
    public const STATUS_NOT_FOUND = 404;
    public const STATUS_CONFLICT = 409;
    public const STATUS_INTERNAL_SERVER_ERROR = 500;
}

namespace OCP\AppFramework\Http;

class Response {
    protected int $status = 200;
    protected array $headers = [];

    public function getStatus(): int {
        return $this->status;
    }

    public function setStatus(int $status): Response {
        $this->status = $status;
        return $this;
    }

    public function addHeader(string $name, string $value): Response {
        $this->headers[$name] = $value;
        return $this;
    }
}

class DataResponse extends Response {
    private $data;

    public function __construct($data = [], int $status = 200, array $headers = []) {
        $this->data = $data;
        $this->status = $status;
        $this->headers = $headers;
    }

    public function getData() {
        return $this->data;
    }
}

class JSONResponse extends DataResponse {}

class StreamResponse extends Response {
    public function __construct(string $filePath) {
        parent::__construct();
    }
}

namespace OCP\AppFramework\Http\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class NoAdminRequired {}

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class NoCSRFRequired {}

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class PublicPage {}

namespace OCP\Comments;

interface ICommentsManager {}
interface IComment {}

namespace OCP\Activity;

interface IManager {
    public function generateEvent(): IEvent;
    public function publish(IEvent $event): void;
}

interface IEvent {
    public function setApp(string $app): IEvent;
    public function setType(string $type): IEvent;
    public function setSubject(string $subject, array $parameters = []): IEvent;
    public function setObject(string $objectType, int $objectId, string $objectName = ''): IEvent;
    public function setAffectedUser(string $user): IEvent;
    public function setAuthor(string $author): IEvent;
    public function setTimestamp(int $timestamp): IEvent;
    public function getApp(): string;
    public function getType(): string;
    public function getSubject(): string;
    public function getSubjectParameters(): array;
    public function getObjectType(): string;
    public function getObjectId(): int;
    public function getObjectName(): string;
}

interface IProvider {
    public function parse($language, IEvent $event, ?IEvent $previousEvent = null): IEvent;
}

interface ISetting {
    public function getIdentifier(): string;
    public function getName(): string;
    public function getPriority(): int;
    public function canChangeStream(): bool;
    public function isDefaultEnabledStream(): bool;
    public function canChangeMail(): bool;
    public function isDefaultEnabledMail(): bool;
}

namespace OCP\DB;

class Types {
    public const BIGINT = 'bigint';
    public const INTEGER = 'integer';
    public const STRING = 'string';
    public const TEXT = 'text';
    public const DATE = 'date';
    public const DATETIME = 'datetime';
    public const BOOLEAN = 'boolean';
}

interface ISchemaWrapper {
    public function hasTable(string $tableName): bool;
    public function createTable(string $tableName);
    public function getTable(string $tableName);
}

namespace OCP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;

interface IOutput {
    public function info(string $message): void;
    public function warning(string $message): void;
}

abstract class SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        return null;
    }
}

namespace Psr\Log;

interface LoggerInterface {
    public function emergency(string|\Stringable $message, array $context = []): void;
    public function alert(string|\Stringable $message, array $context = []): void;
    public function critical(string|\Stringable $message, array $context = []): void;
    public function error(string|\Stringable $message, array $context = []): void;
    public function warning(string|\Stringable $message, array $context = []): void;
    public function notice(string|\Stringable $message, array $context = []): void;
    public function info(string|\Stringable $message, array $context = []): void;
    public function debug(string|\Stringable $message, array $context = []): void;
    public function log($level, string|\Stringable $message, array $context = []): void;
}
