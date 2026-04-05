<?php

namespace Plover\Nest\Config\Facades;

use Plover\Nest\Support\Facade;

/**
 * @method static void 		addPath(string $path)
 * @method static mixed 	get(string $key, $default = null)
 * @method static void 		set(string $key, $value)
 * @method static bool 		has(string $key)
 * @method static array		all()
 * @method static void		merge(array $items)
 * 
 * @since 1.0.1
 */
class Config extends Facade {

	protected static function getFacadeAccessor() {
		return 'config';
	}

}
