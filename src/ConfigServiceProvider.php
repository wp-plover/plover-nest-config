<?php

namespace Plover\Nest\Config;

use Plover\Nest\Support\ServiceProvider;
use Plover\Nest\Config\ConfigRepository;

/**
 * @since 1.0.0
 */
class ConfigServiceProvider extends ServiceProvider {

	/**
	 * Initialize config repository
	 * 
	 * @return void
	 */
	public function register() {
		$this->nest->registered( function () {
			$this->nest->singleton( ConfigRepository::class, function () {
				return new ConfigRepository( $this->nest->get( 'cache' ) );
			} );

			$this->nest->alias( 'config', ConfigRepository::class);
		}, 5 );
	}
}
