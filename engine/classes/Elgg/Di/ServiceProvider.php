<?php
namespace Elgg\Di;

use Elgg\Config;
use Elgg\Cache\Pool;
use Elgg\Printer\CliPrinter;
use Elgg\Printer\HtmlPrinter;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;
use Zend\Mail\Transport\TransportInterface as Mailer;

/**
 * Provides common Elgg services.
 *
 * We extend the container because it allows us to document properties in the PhpDoc, which assists
 * IDEs to auto-complete properties and understand the types returned. Extension allows us to keep
 * the container generic.
 *
 * @property-read \Elgg\Database\AccessCollections $accessCollections
 * @property-read \ElggStaticVariableCache         $accessCache
 * @property-read \Elgg\ActionsService             $actions
 * @property-read \Elgg\Database\AdminNotices      $adminNotices
 * @property-read \Elgg\Ajax\Service               $ajax
 * @property-read \Elgg\Amd\Config                 $amdConfig
 * @property-read \Elgg\Database\AnnotationsTable  $annotationsTable
 * @property-read \ElggAutoP                       $autoP
 * @property-read \Elgg\AutoloadManager            $autoloadManager
 * @property-read \Elgg\BatchUpgrader              $batchUpgrader
 * @property-read \Elgg\BootService                $boot
 * @property-read \Elgg\Application\CacheHandler   $cacheHandler
 * @property-read \Elgg\ClassLoader                $classLoader
 * @property-read \Elgg\Cli                        $cli
 * @property-read \ElggCrypto                              $crypto
 * @property-read \Elgg\Config                             $config
 * @property-read \Elgg\Database\ConfigTable               $configTable
 * @property-read \Elgg\Context                            $context
 * @property-read \Elgg\Database                           $db
 * @property-read \Elgg\Database\DbConfig                  $dbConfig
 * @property-read \Elgg\DeprecationService                 $deprecation
 * @property-read \Elgg\EmailService                       $emails
 * @property-read \Elgg\Cache\EntityCache                  $entityCache
 * @property-read \Elgg\EntityPreloader                    $entityPreloader
 * @property-read \Elgg\Database\EntityTable               $entityTable
 * @property-read \Elgg\Assets\ExternalFiles               $externalFiles
 * @property-read \ElggFileCache                           $fileCache
 * @property-read \ElggDiskFilestore                       $filestore
 * @property-read \Elgg\FormsService                       $forms
 * @property-read \Elgg\HandlersService                    $handlers
 * @property-read \Elgg\Security\HmacFactory               $hmac
 * @property-read \Elgg\PluginHooksService                 $hooks
 * @property-read \Elgg\EntityIconService                  $iconService
 * @property-read \Elgg\Http\Input                         $input
 * @property-read \Elgg\ImageService                       $imageService
 * @property-read \Elgg\Logger                             $logger
 * @property-read Mailer                                   $mailer
 * @property-read \Elgg\Menu\Service                       $menus
 * @property-read \Elgg\Cache\MetadataCache                $metadataCache
 * @property-read \Stash\Pool|null                         $memcacheStashPool
 * @property-read \Elgg\Database\MetadataTable             $metadataTable
 * @property-read \Elgg\Database\Mutex                     $mutex
 * @property-read \Elgg\Notifications\NotificationsService $notifications
 * @property-read \Elgg\Cache\NullCache                    $nullCache
 * @property-read \Elgg\PasswordService                    $passwords
 * @property-read \Elgg\PersistentLoginService             $persistentLogin
 * @property-read \Elgg\Database\Plugins                   $plugins
 * @property-read \Elgg\Cache\PluginSettingsCache          $pluginSettingsCache
 * @property-read \Elgg\Printer                            $printer
 * @property-read \Elgg\Database\PrivateSettingsTable      $privateSettings
 * @property-read \Elgg\Application\Database               $publicDb
 * @property-read \Elgg\Database\QueryCounter              $queryCounter
 * @property-read \Elgg\RedirectService                    $redirects
 * @property-read \Elgg\Http\Request                       $request
 * @property-read \Elgg\Http\ResponseFactory               $responseFactory
 * @property-read \Elgg\Database\RelationshipsTable        $relationshipsTable
 * @property-read \Elgg\Router                             $router
 * @property-read \Elgg\Database\Seeder                    $seeder
 * @property-read \Elgg\Application\ServeFileHandler       $serveFileHandler
 * @property-read \ElggSession                             $session
 * @property-read \Elgg\Cache\SimpleCache                  $simpleCache
 * @property-read \Elgg\Database\SiteSecret                $siteSecret
 * @property-read \Elgg\Forms\StickyForms                  $stickyForms
 * @property-read \Elgg\Cache\SystemCache                  $systemCache
 * @property-read \Elgg\SystemMessagesService              $systemMessages
 * @property-read \Elgg\Views\TableColumn\ColumnFactory    $table_columns
 * @property-read \ElggTempDiskFilestore                   $temp_filestore
 * @property-read \Elgg\Timer                              $timer
 * @property-read \Elgg\I18n\Translator                    $translator
 * @property-read \Elgg\Security\UrlSigner                 $urlSigner
 * @property-read \Elgg\UpgradeService                     $upgrades
 * @property-read \Elgg\Upgrade\Locator                    $upgradeLocator
 * @property-read \Elgg\UploadService                      $uploads
 * @property-read \Elgg\UserCapabilities                   $userCapabilities
 * @property-read \Elgg\Database\UsersTable                $usersTable
 * @property-read \Elgg\ViewsService                       $views
 * @property-read \Elgg\Cache\ViewCacher                   $viewCacher
 * @property-read \Elgg\WidgetsService                     $widgets
 *
 * @access private
 */
class ServiceProvider extends DiContainer {

	/**
	 * Constructor
	 *
	 * @param Config $config Elgg Config service
	 */
	public function __construct(Config $config) {

		$this->setFactory('autoloadManager', function(ServiceProvider $c) {
			$manager = new \Elgg\AutoloadManager($c->classLoader);
			if (!$c->config->AutoloaderManager_skip_storage) {
				$manager->setStorage($c->fileCache);
				$manager->loadCache();
			}
			return $manager;
		});

		$this->setFactory('accessCache', function(ServiceProvider $c) {
			return new \ElggStaticVariableCache('access');
		});

		$this->setFactory('accessCollections', function(ServiceProvider $c) {
			return new \Elgg\Database\AccessCollections(
					$c->config, $c->db, $c->entityTable, $c->userCapabilities, $c->accessCache, $c->hooks, $c->session, $c->translator);
		});

		$this->setFactory('actions', function(ServiceProvider $c) {
			return new \Elgg\ActionsService($c->config, $c->session, $c->crypto);
		});

		$this->setClassName('adminNotices', \Elgg\Database\AdminNotices::class);

		$this->setFactory('ajax', function(ServiceProvider $c) {
			return new \Elgg\Ajax\Service($c->hooks, $c->systemMessages, $c->input, $c->amdConfig);
		});

		$this->setFactory('amdConfig', function(ServiceProvider $c) {
			$obj = new \Elgg\Amd\Config($c->hooks);
			$obj->setBaseUrl($c->simpleCache->getRoot());
			return $obj;
		});

		$this->setFactory('annotationsTable', function(ServiceProvider $c) {
			return new \Elgg\Database\AnnotationsTable($c->db, $c->hooks->getEvents());
		});

		$this->setClassName('autoP', \ElggAutoP::class);

		$this->setFactory('boot', function(ServiceProvider $c) {
			$boot = new \Elgg\BootService();
			if ($c->config->enable_profiling) {
				$boot->setTimer($c->timer);
			}
			return $boot;
		});

		$this->setFactory('batchUpgrader', function(ServiceProvider $c) {
			return new \Elgg\BatchUpgrader($c->config);
		});

		$this->setFactory('cacheHandler', function(ServiceProvider $c) {
			$simplecache_enabled = $c->config->simplecache_enabled;
			if ($simplecache_enabled === null) {
				$simplecache_enabled = $c->configTable->get('simplecache_enabled');
			}
			return new \Elgg\Application\CacheHandler($c->config, $c->request, $simplecache_enabled);
		});

		$this->setFactory('classLoader', function(ServiceProvider $c) {
			$loader = new \Elgg\ClassLoader(new \Elgg\ClassMap());
			$loader->register();
			return $loader;
		});

		$this->setFactory('cli', function(ServiceProvider $c) {
			$version = elgg_get_version(true);
			$console = new \Symfony\Component\Console\Application('Elgg', $version);
			return new \Elgg\Cli($console, $c->hooks);
		});

		$this->setValue('config', $config);

		$this->setFactory('configTable', function(ServiceProvider $c) {
			return new \Elgg\Database\ConfigTable($c->db, $c->boot, $c->logger);
		});

		$this->setFactory('context', function(ServiceProvider $c) {
			$context = new \Elgg\Context();
			$context->initialize($c->request);
			return $context;
		});

		$this->setClassName('crypto', \ElggCrypto::class);

		$this->setFactory('db', function(ServiceProvider $c) {
			$db = new \Elgg\Database($c->dbConfig);
			$db->setLogger($c->logger);

			if ($c->config->profiling_sql) {
				$db->setTimer($c->timer);
			}

			return $db;
		});

		$this->setFactory('dbConfig', function(ServiceProvider $c) {
			$config = $c->config;
			$db_config = \Elgg\Database\DbConfig::fromElggConfig($config);

			// get this stuff out of config!
			unset($config->db);
			unset($config->dbname);
			unset($config->dbhost);
			unset($config->dbuser);
			unset($config->dbpass);

			return $db_config;
		});

		$this->setFactory('deprecation', function(ServiceProvider $c) {
			return new \Elgg\DeprecationService($c->logger);
		});

		$this->setFactory('emails', function(ServiceProvider $c) {
			return new \Elgg\EmailService($c->config, $c->hooks, $c->mailer, $c->logger);
		});

		$this->setFactory('entityCache', function(ServiceProvider $c) {
			return new \Elgg\Cache\EntityCache($c->session, $c->metadataCache);
		});

		$this->setFactory('entityPreloader', function(ServiceProvider $c) {
			return new \Elgg\EntityPreloader($c->entityCache, $c->entityTable);
		});

		$this->setFactory('entityTable', function(ServiceProvider $c) {
			return new \Elgg\Database\EntityTable(
				$c->config,
				$c->db,
				$c->entityCache,
				$c->metadataCache,
				$c->hooks->getEvents(),
				$c->session,
				$c->translator,
				$c->logger
			);
		});

		$this->setClassName('externalFiles', \Elgg\Assets\ExternalFiles::class);

		$this->setFactory('fileCache', function(ServiceProvider $c) {
			return new \ElggFileCache($c->config->cacheroot . 'system_cache/');
		});

		$this->setFactory('filestore', function(ServiceProvider $c) {
			return new \ElggDiskFilestore($c->config->dataroot);
		});

		$this->setFactory('forms', function(ServiceProvider $c) {
			return new \Elgg\FormsService($c->views, $c->logger);
		});

		$this->setClassName('handlers', \Elgg\HandlersService::class);

		$this->setFactory('hmac', function(ServiceProvider $c) {
			return new \Elgg\Security\HmacFactory($c->siteSecret, $c->crypto);
		});

		$this->setFactory('hooks', function(ServiceProvider $c) {
			$events = new \Elgg\EventsService($c->handlers);
			if ($c->config->enable_profiling) {
				$events->setTimer($c->timer);
			}
			return new \Elgg\PluginHooksService($events);
		});

		$this->setFactory('iconService', function(ServiceProvider $c) {
			return new \Elgg\EntityIconService($c->config, $c->hooks, $c->request, $c->logger, $c->entityTable, $c->uploads);
		});

		$this->setClassName('input', \Elgg\Http\Input::class);

		$this->setFactory('imageService', function(ServiceProvider $c) {
			switch ($c->config->image_processor) {
				case 'imagick':
					if (extension_loaded('imagick')) {
						$imagine = new \Imagine\Imagick\Imagine();
						break;
					}
				default:
					// default use GD
					$imagine = new \Imagine\Gd\Imagine();
					break;
			}

			return new \Elgg\ImageService($imagine, $c->config);
		});

		$this->setFactory('logger', function (ServiceProvider $c) {
			$logger = new \Elgg\Logger($c->hooks, $c->context, $c->config, $c->printer);
			return $logger;
		});

		// TODO(evan): Support configurable transports...
		$this->setClassName('mailer', 'Zend\Mail\Transport\Sendmail');

		$this->setFactory('menus', function(ServiceProvider $c) {
			return new \Elgg\Menu\Service($c->hooks, $c->config);
		});

		$this->setFactory('metadataCache', function (ServiceProvider $c) {
			$cache = _elgg_get_memcache('metadata');
			return new \Elgg\Cache\MetadataCache($cache);
		});

		$this->setFactory('memcacheStashPool', function(ServiceProvider $c) {
			if (!$c->config->memcache) {
				return null;
			}

			$servers = $c->config->memcache_servers;
			if (!$servers) {
				return null;
			}
			$driver = new \Stash\Driver\Memcache([
				'servers' => $servers,
			]);
			return new \Stash\Pool($driver);
		});

		$this->setFactory('metadataTable', function(ServiceProvider $c) {
			// TODO(ewinslow): Use Pool instead of MetadataCache for caching
			return new \Elgg\Database\MetadataTable($c->metadataCache, $c->db, $c->hooks->getEvents());
		});

		$this->setFactory('mutex', function(ServiceProvider $c) {
			return new \Elgg\Database\Mutex(
				$c->db,
				$c->logger
			);
		});

		$this->setFactory('notifications', function(ServiceProvider $c) {
			// @todo move queue in service provider
			$queue_name = \Elgg\Notifications\NotificationsService::QUEUE_NAME;
			$queue = new \Elgg\Queue\DatabaseQueue($queue_name, $c->db);
			$sub = new \Elgg\Notifications\SubscriptionsService($c->db);
			return new \Elgg\Notifications\NotificationsService($sub, $queue, $c->hooks, $c->session, $c->translator, $c->entityTable, $c->logger);
		});

		$this->setClassName('nullCache', \Elgg\Cache\NullCache::class);

		$this->setFactory('persistentLogin', function(ServiceProvider $c) {
			$global_cookies_config = $c->config->getCookieConfig();
			$cookie_config = $global_cookies_config['remember_me'];
			$cookie_name = $cookie_config['name'];
			$cookie_token = $c->request->cookies->get($cookie_name, '');
			return new \Elgg\PersistentLoginService(
				$c->db, $c->session, $c->crypto, $cookie_config, $cookie_token);
		});

		$this->setClassName('passwords', \Elgg\PasswordService::class);

		$this->setFactory('plugins', function(ServiceProvider $c) {
			$pool = new Pool\InMemory();
			$plugins = new \Elgg\Database\Plugins($pool, $c->pluginSettingsCache);
			if ($c->config->enable_profiling) {
				$plugins->setTimer($c->timer);
			}
			return $plugins;
		});

		$this->setClassName('pluginSettingsCache', \Elgg\Cache\PluginSettingsCache::class);

		$this->setFactory('printer', function(ServiceProvider $c) {
			if (php_sapi_name() === 'cli') {
				return new CliPrinter();
			} else {
				return new HtmlPrinter();
			}
		});

		$this->setFactory('privateSettings', function(ServiceProvider $c) {
			return new \Elgg\Database\PrivateSettingsTable($c->db, $c->entityTable, $c->pluginSettingsCache);
		});

		$this->setFactory('publicDb', function(ServiceProvider $c) {
			return new \Elgg\Application\Database($c->db);
		});

		$this->setFactory('queryCounter', function(ServiceProvider $c) {
			return new \Elgg\Database\QueryCounter($c->db);
		}, false);

		$this->setFactory('redirects', function(ServiceProvider $c) {
			$url = current_page_url();
			$is_xhr = $c->request->isXmlHttpRequest();
			return new \Elgg\RedirectService($c->session, $is_xhr, $c->config->wwwroot, $url);
		});

		$this->setFactory('relationshipsTable', function(ServiceProvider $c) {
			return new \Elgg\Database\RelationshipsTable($c->db, $c->entityTable, $c->metadataTable, $c->hooks->getEvents());
		});

		$this->setFactory('request', [\Elgg\Http\Request::class, 'createFromGlobals']);

		$this->setFactory('responseFactory', function(ServiceProvider $c) {
			if (php_sapi_name() === 'cli') {
				$transport = new \Elgg\Http\OutputBufferTransport();
			} else {
				$transport = new \Elgg\Http\HttpProtocolTransport();
			}
			return new \Elgg\Http\ResponseFactory($c->request, $c->hooks, $c->ajax, $transport);
		});

		$this->setFactory('router', function(ServiceProvider $c) {
			// TODO(evan): Init routes from plugins or cache
			$router = new \Elgg\Router($c->hooks);
			if ($c->config->enable_profiling) {
				$router->setTimer($c->timer);
			}
			return $router;
		});

		$this->setFactory('seeder', function(ServiceProvider $c) {
			return new \Elgg\Database\Seeder($c->hooks);
		});

		$this->setFactory('serveFileHandler', function(ServiceProvider $c) {
			return new \Elgg\Application\ServeFileHandler($c->hmac, $c->config);
		});

		$this->setFactory('session', function(ServiceProvider $c) {
			return \ElggSession::fromDatabase($c->config, $c->db);
		});

		$this->setClassName('urlSigner', \Elgg\Security\UrlSigner::class);

		$this->setFactory('simpleCache', function(ServiceProvider $c) {
			return new \Elgg\Cache\SimpleCache($c->config);
		});

		/**
		 * If the key is in the settings file, this is injected early.
		 *
		 * @see \Elgg\Application::initConfig
		 */
		$this->setFactory('siteSecret', function(ServiceProvider $c) {
			return \Elgg\Database\SiteSecret::fromDatabase($c->configTable);
		});

		$this->setClassName('stickyForms', \Elgg\Forms\StickyForms::class);

		$this->setFactory('systemCache', function (ServiceProvider $c) {
			$cache = new \Elgg\Cache\SystemCache($c->fileCache, $c->config);
			if ($c->config->enable_profiling) {
				$cache->setTimer($c->timer);
			}
			return $cache;
		});

		$this->setFactory('systemMessages', function(ServiceProvider $c) {
			return new \Elgg\SystemMessagesService($c->session);
		});

		$this->setClassName('table_columns', \Elgg\Views\TableColumn\ColumnFactory::class);

		$this->setClassName('temp_filestore',  \ElggTempDiskFilestore::class);

		$this->setClassName('timer', \Elgg\Timer::class);

		$this->setFactory('translator', function(ServiceProvider $c) {
			return new \Elgg\I18n\Translator($c->config);
		});

		$this->setFactory('uploads', function(ServiceProvider $c) {
			return new \Elgg\UploadService($c->request, $c->imageService);
		});

		$this->setFactory('upgrades', function(ServiceProvider $c) {
			return new \Elgg\UpgradeService(
				$c->translator,
				$c->hooks,
				$c->config,
				$c->logger,
				$c->mutex
			);
		});

		$this->setFactory('userCapabilities', function(ServiceProvider $c) {
			return new \Elgg\UserCapabilities($c->hooks, $c->entityTable, $c->session);
		});

		$this->setFactory('usersTable', function(ServiceProvider $c) {
			return new \Elgg\Database\UsersTable(
				$c->config,
				$c->db,
				$c->metadataTable,
				$c->entityCache
			);
		});

		$this->setFactory('upgradeLocator', function(ServiceProvider $c) {
			return new \Elgg\Upgrade\Locator(
				$c->plugins,
				$c->logger,
				$c->privateSettings
			);
		});

		$this->setFactory('views', function(ServiceProvider $c) {
			return new \Elgg\ViewsService($c->hooks, $c->logger, $c->input);
		});

		$this->setFactory('viewCacher', function(ServiceProvider $c) {
			return new \Elgg\Cache\ViewCacher($c->views, $c->config);
		});

		$this->setClassName('widgets', \Elgg\WidgetsService::class);
	}

}
