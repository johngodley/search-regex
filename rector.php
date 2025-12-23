<?php

use Rector\Config\RectorConfig;

return RectorConfig::configure()
	->withPaths( [ __DIR__ . '/includes', __DIR__ . '/tests' ] )
	->withPhpSets();
