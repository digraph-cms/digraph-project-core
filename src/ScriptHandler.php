<?php
/* digraph-project-core | https://gitlab.com/byjoby/digraph-project-core | MIT License */
namespace DigraphProject;

use Composer\Script\Event;
use Symfony\Component\Filesystem\Filesystem;

class ScriptHandler
{
    protected static $event = null;

    public static function updateHandler(Event $event)
    {
        static::$event = $event;
        static::setupFilesystem();
    }

    public static function installHandler(Event $event)
    {
        static::$event = $event;
        static::setupFilesystem();
    }

    protected static function setupFilesystem()
    {
        // src directory is this package's root
        $src = realpath(__DIR__.'/../files/');
        // dest directory assumes this package is in the project's
        // digraph/core directory, which makes it four levels above
        // this file
        $dest = realpath(__DIR__.'/../../../../');

        /* start creating directories */

        static::mkdir("$dest/digraph/storage", true);
        static::mkdir("$dest/digraph/cache", true);
        static::mkdir("$dest/web");

        /* copy files */

        static::copyFile("$src/digraph.yaml", "$dest/digraph/core/digraph.yaml");
        static::copyFile("$src/digraph.php", "$dest/digraph/core/digraph.php");
        static::copyFile("$src/index.php", "$dest/web/index.php");
        static::copyFile("$src/.htaccess", "$dest/web/.htaccess", true);
    }

    protected static function copyFile($src, $dest, $keep=false)
    {
        if ($keep && file_exists($dest)) {
            $dsrc = file_get_contents($dest);
            $ssrc = file_get_contents($src);
            if ($ssrc == $dsrc) {
                //do nothing, files match
                return;
            }
            static::$event->getIO()->write("New version of $dest may be available, delete it and run `composer install` again to update.");
            if (strpos($dsrc, $ssrc) !== false) {
                static::$event->getIO()->write("Your copy of $dest may not require updating, it contains the target code.");
            }
            return;
        }
        copy($src, $dest);
        static::$event->getIO()->write("Copied $dest");
    }

    protected static function mkdir($path, $writeable=false)
    {
        $fs = new Filesystem();
        if (!$fs->exists($path)) {
            if ($writeable) {
                $oldmask = umask(0);
                $fs->mkdir($path, 0777);
                static::$event->getIO()->write("Created $path with chmod 0777");
                umask($oldmask);
            } else {
                $fs->mkdir($path);
                static::$event->getIO()->write("Created $path with default permissions");
            }
        }
    }
}
