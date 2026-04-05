<?php

namespace Plover\Nest\Config;

use Plover\Nest\Support\Facade;

/**
 * @since 1.0.1
 */
class Config extends Facade {

	protected static function getFacadeAccessor() {
		return 'config';
	}

}
