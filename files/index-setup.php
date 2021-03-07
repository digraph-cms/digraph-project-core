# digraph-project-core/files/index-setup.php => web/index.php

# set up new config file to get started
$config = new \Flatrr\Config\Config();

# load digraph-project-core default config
$config->readFile(\DigraphProject\ScriptHandler::configFile());

# set site path
$config['paths.site'] = $SITE_PATH;

# load site config, overwriting anything else set
$config->readFile($SITE_PATH . '/digraph.yaml', null, true);

# load environment config, overwriting anything else set
if (file_exists($SITE_PATH . '/env.yaml')) {
    $config->readFile($SITE_PATH . '/env.yaml', null, true);
}

# override config paths using array from index.php
$config->merge($PATHS, 'paths', true);

# set cache path to system temp as a fallback, because we NEED a cache
if (!$config['paths.cache'] || !is_writeable($config['paths.cache'])) {
    $WARNINGS[] = 'Cache directory is not set or not writeable. Falling back to path in sys_get_temp_dir()';
    $config['paths.cache'] = sys_get_temp_dir() . '/digraph-cache';
    if (!is_writeable($config['paths.cache'])) {
        $ERRORS[] = 'Cache directory is not writeable. Site may not behave correctly.';
    }
}

# set up CMS using Bootstrapper
# everything the bootstrapper does can be done manually, but
# in most cases it's better to use it
$cms = \Digraph\Bootstrapper::bootstrap($config);

# load site config, overwriting anything else set, done twice to override modules
$config->readFile($SITE_PATH . '/digraph.yaml', null, true);

# load environment config, overwriting anything else set, done twice to override modules
if (file_exists($SITE_PATH . '/env.yaml')) {
    $config->readFile($SITE_PATH . '/env.yaml', null, true);
}

# set up new request/response package
# it's advisable to use the Bootstrapper url() method for
# getting your query string
$package = new Digraph\Mungers\Package([
    'request.url' => \Digraph\Bootstrapper::url(),
]);
