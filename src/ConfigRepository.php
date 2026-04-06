<?php

namespace Plover\Nest\Config;

/**
 * Config manager
 * 
 * @since 1.0.0
 */
class ConfigRepository implements \ArrayAccess {

	/**
	 * All config items
	 * 
	 * @var array
	 */
	protected $items = [];

	/**
	 * @var array
	 */
	protected $paths = [];

	/**
	 * @var bool
	 */
	protected $loaded = false;

	/**
	 * @var string|null
	 */
	protected $cache_prefix = null;

	/**
	 * @var mixed
	 */
	protected $cache_version = null;

	/**
	 * Cache instance
	 * 
	 * @var null|\Plover\Nest\Cache\CacheRepository
	 */
	protected $cache = null;

	/**
	 * @param mixed $paths
	 */
	public function __construct( $cache ) {
		$this->cache = $cache;
	}

	/**
	 * Enable config cache
	 * 
	 * @param string $slug
	 * @param mixed $version
	 * @return bool
	 */
	public function enableCache( $slug, $version ) {
		if ( $this->cache !== null ) {
			$this->cache_prefix = $slug . '_nest_config_';
			$this->cache_version = $version;
			return true;
		}

		return false;
	}

	/**
	 * Clear cached config
	 * 
	 * @return bool
	 */
	public function clearCache() {
		if ( $this->cache === null ) {
			return true;
		}

		return $this->cache->delete( $this->cache_prefix . 'version' );
	}

	/**
	 * Add config path
	 * 
	 * @param string $path
	 * @return void
	 */
	public function addPath( $path ) {
		$this->paths[] = $path;
		if ( $this->loaded ) {
			$this->loadFromPath( $path );
		}
	}

	/**
	 * Load config files from dictionary
	 * 
	 * @param string $configPath
	 * @return void
	 */
	protected function load() {
		if ( $this->loaded ) {
			return;
		}

		$this->loaded = true; // Avoid infinite loops

		// Cache is enabled, try to load from cache
		if ( $this->cache !== null && $this->cache_prefix !== null ) {
			$cached_version = $this->cache->get( $this->cache_prefix . 'version' );
			// We have a cached config, and its version matches the current version
			if ( $cached_version !== null && $cached_version === $this->cache_version ) {
				// Load cached items
				$this->items = $this->cache->get( $this->cache_prefix . 'items' );

				if ( is_array( $this->items ) ) { // Prevent 'items' cache entry from expiring
					return;
				} else {
					$this->items = [];
				}
			}
		}

		foreach ( $this->paths as $path ) {
			$this->loadFromPath( $path );
		}

		// Cache is enabled, cache loaded items
		if ( $this->cache !== null && $this->cache_prefix !== null ) {
			$this->cache->forever( $this->cache_prefix . 'version', $this->cache_version );
			$this->cache->forever( $this->cache_prefix . 'items', $this->items );
		}
	}

	/**
	 * Load config files
	 * 
	 * @param string $path
	 * @throws \InvalidArgumentException
	 * @return static
	 */
	protected function loadFromPath( string $path ) {
		if ( ! is_dir( $path ) ) {
			throw new \InvalidArgumentException( $path );
		}

		$files = glob( $path . '/*.php' );
		foreach ( $files as $file ) {
			$key = basename( $file, '.php' );
			$config = require $file;
			if ( is_array( $config ) ) {
				$this->set( $key, $config );
			}
		}

		return $this;
	}

	/**
	 * Get config item, e.g. "database.mysql.host" 
	 * 
	 * @param string $key
	 * @param mixed $default
	 * 
	 * @return mixed
	 */
	public function get( string $key, $default = null ) {
		$this->load();

		$segments = explode( '.', $key );
		$result = $this->items;

		foreach ( $segments as $segment ) {
			if ( ! is_array( $result ) || ! array_key_exists( $segment, $result ) ) {
				return $default;
			}
			$result = $result[ $segment ];
		}

		return $result;
	}

	/**
	 * Set config item.
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public function set( string $key, $value ): void {
		$this->load();

		$segments = explode( '.', $key );
		$target = &$this->items;

		foreach ( $segments as $i => $segment ) {
			if ( $i === count( $segments ) - 1 ) {
				$target[ $segment ] = $value;
				break;
			}
			if ( ! isset( $target[ $segment ] ) || ! is_array( $target[ $segment ] ) ) {
				$target[ $segment ] = [];
			}
			$target = &$target[ $segment ];
		}
	}

	/**
	 * If a config item exists or not
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function has( string $key ): bool {
		return $this->get( $key, $this ) !== $this;
	}

	/**
	 * Get all config items
	 * 
	 * @return array
	 */
	public function all(): array {
		$this->load();

		return $this->items;
	}

	/**
	 * Merge external config items
	 * 
	 * @param array $items
	 * @return void
	 */
	public function merge( array $items ): void {
		$this->load();

		$this->items = array_merge_recursive( $this->items, $items );
	}

	/**
	 * ArrayAccess offsetExists implementation
	 * 
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists( mixed $offset ): bool {
		return $this->has( $offset );
	}

	/**
	 * ArrayAccess offsetGet implementation
	 * 
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet( mixed $offset ): mixed {
		return $this->get( $offset );
	}

	/**
	 * ArrayAccess offsetSet implementation
	 * 
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet( mixed $offset, mixed $value ): void {
		$this->set( $offset, $value );
	}

	/**
	 * ArrayAccess offsetUnset implementation
	 * 
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetUnset( mixed $offset ): void {
		$this->set( $offset, null );
	}
}
