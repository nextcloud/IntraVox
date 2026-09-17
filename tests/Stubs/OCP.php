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
    public function getHeader(string $name): string;
    public function getRemoteAddress(): string;
}

interface IConfig {
    public function getAppValue(string $appId, string $key, string $default = ''): string;
    public function setAppValue(string $appId, string $key, string $value): void;
    public function deleteAppValue(string $appId, string $key): void;
    public function getUserValue(string $userId, string $appId, string $key, string $default = ''): string;
    public function setUserValue(string $userId, string $appId, string $key, string $value): void;
    public function getSystemValue(string $key, $default = '');
    public function getSystemValueString(string $key, string $default = ''): string;
}

interface IUser {
    public function getUID(): string;
    public function getDisplayName(): string;
    public function isEnabled(): bool;
    public function getEMailAddress(): ?string;
}

interface IUserSession {
    public function getUser(): ?IUser;
    public function isLoggedIn(): bool;
}

interface IGroupManager {
    public function isAdmin(string $uid): bool;
    public function isInGroup(string $uid, string $gid): bool;
    public function getUserGroupIds(IUser $user): array;
    public function get(string $gid): ?IGroup;
}

interface ITempManager {
    public function getTemporaryFile(string $postFix = ''): string;
    public function getTemporaryFolder(string $postFix = ''): string;
}

interface ISession {
    public function set(string $key, $value): void;
    public function get(string $key);
    public function exists(string $key): bool;
    public function remove(string $key): void;
    public function clear(): void;
}

interface IDBConnection {
    public function getQueryBuilder();
    public function beginTransaction(): void;
    public function commit(): void;
    public function rollBack(): void;
    public function escapeLikeParameter(string $param): string;
}

interface ICache {
    public function get($key);
    public function set($key, $value, $ttl = 0);
    public function hasKey($key);
    public function remove($key);
    public function clear($prefix = '');
    public function add($key, $value, $ttl = 0);
}

interface ICacheFactory {
    public function isAvailable(): bool;
    public function createDistributed(string $prefix = ''): ICache;
    public function createLocal(string $prefix = ''): ICache;
}

interface IL10N {
    public function t(string $text, $parameters = []): string;
    public function n(string $text_singular, string $text_plural, int $count, array $parameters = []): string;
}

interface IURLGenerator {
    public function imagePath(string $app, string $image): string;
    public function linkToRoute(string $routeName, array $arguments = []): string;
    public function linkToRouteAbsolute(string $routeName, array $arguments = []): string;
    public function getAbsoluteURL(string $url): string;
}

interface IUserManager {
    public function get(string $uid): ?IUser;
    public function search(string $pattern, ?int $limit = null, ?int $offset = null): array;
    public function searchDisplayName(string $pattern, ?int $limit = null, ?int $offset = null): array;
    public function callForAllUsers(\Closure $callback, string $search = '', bool $onlySeen = false): void;
    public function countUsers(): array;
}

namespace OCP\Files;

interface IRootFolder {
    public function getUserFolder(string $userId): Folder;
    public function get(string $path);
}

/**
 * Minimal FileInfo stub carrying the node-type constants (real NC values).
 */
interface FileInfo {
    public const TYPE_FILE = 1;
    public const TYPE_FOLDER = 2;
}

/**
 * The Node/File/Folder stubs carry the subset of the real NC signatures that
 * the unit tests drive (e.g. reorderSiblings). Additive: existing tests mock
 * PageService, not these, so populating the interfaces is backward-compatible.
 */
interface Node {
    public function getName(): string;
    public function getPath(): string;
    public function getType(): int;
    public function getId(): int;
    public function getPermissions(): int;
    public function isReadable(): bool;
    public function isUpdateable(): bool;
    public function isCreatable(): bool;
    public function isDeletable(): bool;
    public function getMTime(): int;
    public function getMimeType(): string;
    public function getSize();
    public function move(string $targetPath);
    public function getParent();
    public function getOwner();
    public function getStorage();
    public function delete(): void;
    // getInternalPath() is untyped on the real OCP\Files\Node; the groupfolder
    // detection and metadata paths in PageService both call it.
    public function getInternalPath();
}

/**
 * Nextcloud spells the MIME getter both ways across its interfaces
 * (getMimetype() on FileInfo, getMimeType() on Node) and the media paths call
 * each spelling. PHP method names are case-insensitive, so the single
 * declaration below satisfies both call sites — declaring the second spelling
 * as well is a redeclaration fatal, not an overload.
 */
interface File extends Node {
    public function getContent(): string;
    public function putContent($data): void;
    public function getMimetype(): string;
    public function fopen(string $mode);
    // getCreationTime(): int on the real OCP\Files\FileInfo; getPageMetadata()
    // reads it (with a 0 fallback) for the page's created-at.
    public function getCreationTime(): int;
}

interface Folder extends Node {
    public function getDirectoryListing(): array;
    public function nodeExists(string $path): bool;
    public function get(string $path);
    public function newFolder(string $path);
    public function newFile(string $path, $content = null);
}

class NotFoundException extends \Exception {}

namespace OCP\App;

interface IAppManager {
    public function isInstalled(string $appId): bool;
    public function isEnabledForUser(string $appId, $user = null): bool;
    public function getAppVersion(string $appId, bool $useCache = true): string;
}

namespace OCP;

interface IGroup {
    public function getGID(): string;
    public function getDisplayName(): string;
    public function getUsers(): array;
    public function inGroup(IUser $user): bool;
    public function addUser(IUser $user): void;
}

namespace OCP\Security;

/**
 * Server-side encryption for stored secrets (feed tokens, client secrets).
 * Only the two methods the app calls.
 */
interface ICrypto {
    public function encrypt(string $input, string $password = ''): string;
    public function decrypt(string $input, string $password = ''): string;
}

namespace OCP\EventDispatcher;

abstract class Event {
    public function __construct() {
    }
}

interface IEventListener {
    public function handle(Event $event): void;
}

interface IEventDispatcher {
    public function dispatch(string $eventName, Event $event): void;
    public function dispatchTyped(Event $event): void;
}

namespace OCP\Group\Events;

use OCP\EventDispatcher\Event;
use OCP\IGroup;
use OCP\IUser;

abstract class GroupMembershipEvent extends Event {
    public function __construct(
        private IGroup $group,
        private IUser $user,
    ) {}
    public function getGroup(): IGroup { return $this->group; }
    public function getUser(): IUser { return $this->user; }
}

class UserAddedEvent extends GroupMembershipEvent {}
class UserRemovedEvent extends GroupMembershipEvent {}

namespace OCP\Files\Cache;

use OCP\EventDispatcher\Event;

/**
 * Fired when a file leaves the filecache for good — trashbin emptied, or a
 * delete that bypasses it — and NOT when it is moved to the trashbin.
 */
class CacheEntryRemovedEvent extends Event {
    public function __construct(
        private int $fileId = 0,
    ) {}
    public function getFileId(): int { return $this->fileId; }
}

/**
 * A file-cache entry. PageCacheStatusService only reads getId() and checks
 * `instanceof ICacheEntry` to distinguish a real entry from a `false` miss, so
 * the stub carries just that.
 */
interface ICacheEntry {
    public function getId(): int;
}

namespace OCA\Files_Versions\Versions;

interface IVersion {
    public function getTimestamp(): int;
    public function getSize(): int;
}

interface IVersionBackend {}

interface IVersionManager {
    public function getVersionsForFile($user, $file): array;
    public function createVersion($user, $file);
    public function rollback($version);
    public function read($version);
    public function getBackendForStorage($storage);
}

namespace OCP\AppFramework;

use OCP\IRequest;

class App {
    public function __construct(string $appName, array $urlParams = []) {
    }
}

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
    public const STATUS_NOT_MODIFIED = 304;
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

    /** @return array<string,string> */
    public function getHeaders(): array {
        return $this->headers;
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

class DataDisplayResponse extends Response {
    private string $data;

    public function __construct(string $data = '', int $status = 200, array $headers = []) {
        $this->data = $data;
        $this->status = $status;
        $this->headers = $headers;
    }

    public function render(): string {
        return $this->data;
    }
}

class StreamResponse extends Response {
    /** @param string|resource $filePath the real OCP signature accepts either */
    public function __construct($filePath) {
    }
}

class TemplateResponse extends Response {
    public const RENDER_AS_USER = 'user';
    public const RENDER_AS_PUBLIC = 'public';
    public const RENDER_AS_GUEST = 'guest';

    private string $appName;
    private string $templateName;
    private array $params;
    private string $renderAs;
    private ?ContentSecurityPolicy $csp = null;

    public function __construct(string $appName, string $templateName, array $params = [], string $renderAs = self::RENDER_AS_USER) {
        $this->appName = $appName;
        $this->templateName = $templateName;
        $this->params = $params;
        $this->renderAs = $renderAs;
    }

    public function getTemplateName(): string {
        return $this->templateName;
    }

    public function getParams(): array {
        return $this->params;
    }

    public function getRenderAs(): string {
        return $this->renderAs;
    }

    public function setContentSecurityPolicy(ContentSecurityPolicy $csp): void {
        $this->csp = $csp;
    }

    public function getContentSecurityPolicy(): ?ContentSecurityPolicy {
        return $this->csp;
    }
}

class RedirectResponse extends Response {
    private string $redirectUrl;

    public function __construct(string $redirectUrl) {
        $this->redirectUrl = $redirectUrl;
        $this->status = 303;
    }

    public function getRedirectUrl(): string {
        return $this->redirectUrl;
    }
}

class ContentSecurityPolicy {
    public function addAllowedFrameDomain(string $domain): self {
        return $this;
    }
    public function addAllowedChildSrcDomain(string $domain): self {
        return $this;
    }
    public function addAllowedScriptDomain(string $domain): self {
        return $this;
    }
    public function addAllowedConnectDomain(string $domain): self {
        return $this;
    }
    public function addAllowedImageDomain(string $domain): self {
        return $this;
    }
    public function addAllowedMediaDomain(string $domain): self {
        return $this;
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

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class AnonRateLimit {
    public function __construct(int $limit = 0, int $period = 0) {
    }
}

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class UserRateLimit {
    public function __construct(int $limit = 0, int $period = 0) {
    }
}

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class BruteForceProtection {
    public function __construct(string $action = '') {
    }
}

namespace OCP\AppFramework\Services;

interface IInitialState {
    public function provideInitialState(string $key, $data): void;
    public function provideLazyInitialState(string $key, \Closure $closure): void;
}

namespace OCP\Security\Bruteforce;

interface IThrottler {
    public function registerAttempt(string $action, string $ip, array $metadata = []): void;
    public function sleepDelayOrThrowOnMax(string $ip, string $action = ''): int;
}

namespace OCP;

class Util {
    public static function addScript(string $application, string $file, ?string $afterAppId = null): void {
    }
    public static function addStyle(string $application, string $file): void {
    }
}

namespace OCP\Comments;

interface ICommentsManager {
    public function deleteCommentsAtObject(string $objectType, string $objectId): bool;
}
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

namespace OCP\Share;

interface IManager {}

interface IShare {
    public function getToken(): ?string;
    public function getNode();
    public function getShareType(): int;
    public function getPermissions(): int;
}

namespace OCP\Accounts;

interface IAccountProperty {
    public function getName(): string;
    public function getValue(): string;
    public function getScope(): string;
}

interface IAccount {
    public function getProperty(string $property): IAccountProperty;
    /** @return IAccountProperty[] */
    public function getProperties(): array;
    public function getUser(): \OCP\IUser;
}

interface IAccountManager {
    public const PROPERTY_DISPLAYNAME = 'displayname';
    public const PROPERTY_EMAIL = 'email';
    public const PROPERTY_PHONE = 'phone';
    public const PROPERTY_ADDRESS = 'address';
    public const PROPERTY_WEBSITE = 'website';
    public const PROPERTY_TWITTER = 'twitter';
    public const PROPERTY_BLUESKY = 'bluesky';
    public const PROPERTY_FEDIVERSE = 'fediverse';
    public const PROPERTY_ORGANISATION = 'organisation';
    public const PROPERTY_ROLE = 'role';
    public const PROPERTY_HEADLINE = 'headline';
    public const PROPERTY_BIOGRAPHY = 'biography';
    public const PROPERTY_PRONOUNS = 'pronouns';
    public const PROPERTY_BIRTHDATE = 'birthdate';

    public const SCOPE_PRIVATE = 'v2-private';
    public const SCOPE_LOCAL = 'v2-local';
    public const SCOPE_FEDERATED = 'v2-federated';
    public const SCOPE_PUBLISHED = 'v2-published';

    public function getAccount(\OCP\IUser $user): IAccount;
}

namespace OCP\Search;

use OCP\IUser;

interface ISearchQuery {
    public function getTerm(): string;
    public function getSortOrder(): int;
    public function getLimit(): int;
    public function getCursor();
    public function getRoute(): string;
    public function getRouteParameters(): array;
}

class SearchResultEntry implements \JsonSerializable {
    public function __construct(
        public string $thumbnailUrl,
        public string $title,
        public string $subline,
        public string $resourceUrl = '',
        public string $icon = '',
        public bool $rounded = false,
    ) {
    }

    public function jsonSerialize(): array {
        return [
            'thumbnailUrl' => $this->thumbnailUrl,
            'title' => $this->title,
            'subline' => $this->subline,
            'resourceUrl' => $this->resourceUrl,
            'icon' => $this->icon,
            'rounded' => $this->rounded,
        ];
    }
}

class SearchResult implements \JsonSerializable {
    private function __construct(
        private string $name,
        private bool $isPaginated,
        private array $entries,
        private $cursor = null,
    ) {
    }

    public static function complete(string $name, array $entries): self {
        return new self($name, false, $entries);
    }

    public static function paginated(string $name, array $entries, $cursor): self {
        return new self($name, true, $entries, $cursor);
    }

    /** Test helper: the entries this result carries. */
    public function getEntries(): array {
        return $this->entries;
    }

    public function jsonSerialize(): array {
        return [
            'name' => $this->name,
            'isPaginated' => $this->isPaginated,
            'entries' => $this->entries,
            'cursor' => $this->cursor,
        ];
    }
}

interface IProvider {
    public function getId(): string;
    public function getName(): string;
    public function getOrder(string $route, array $routeParameters): ?int;
    public function search(IUser $user, ISearchQuery $query): SearchResult;
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

namespace OCP\Http\Client;

interface IResponse {
    public function getBody();
    public function getStatusCode(): int;
    public function getHeader(string $key): string;
}

interface IClient {
    public function get(string $uri, array $options = []): IResponse;
    public function post(string $uri, array $options = []): IResponse;
}

interface IClientService {
    public function newClient(): IClient;
}

namespace OCP\AppFramework\Bootstrap;

interface IRegistrationContext {
}

interface IBootContext {
}

interface IBootstrap {
    public function register(IRegistrationContext $context): void;
    public function boot(IBootContext $context): void;
}
