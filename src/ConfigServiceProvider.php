<?php

namespace Plover\Nest\Config;

use Plover\Nest\Support\ServiceProvider;

/**
 * @since 1.0.0
 */
class ConfigServiceProvider extends ServiceProvider {

	/**
	 * @var array
	 */
	public $singletons = [
		\Plover\Nest\Config\ConfigRepository::class,
	];

	/**
	 * @var array
	 */
	public $aliases = [
		'config' => \Plover\Nest\Config\ConfigRepository::class,
	];
}
