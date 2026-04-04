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
	 * @param mixed $paths
	 */
	public function __construct( $paths = [] ) {
		$this->paths = $paths;
	}

	/**
	 * Add config path
	 * 
	 * @param mixed $path
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

		$this->loaded = true;

		foreach ( $this->paths as $path ) {
			$this->loadFromPath( $path );
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
