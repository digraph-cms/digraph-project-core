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
        static::placeCodeInFile("$src/htaccess", "$dest/web/.htaccess", "HTACCESS", true);
        static::placeCodeInFile("$src/gitignore", "$dest/.gitignore", "GITIGNORE", true);
    }

    protected static function placeCodeInFile($src, $dest, $name, $append=false, $lp='# ')
    {
        //generate prefix/suffix so code can be found later and replaced
        $prefix = $lp.'BEGIN DIGRAPH-MANAGED: '.$name;
        $suffix = $lp.'END DIGRAPH-MANAGED: '.$name;
        //generate code to be inserted, including prefix/suffix
        $code = implode(PHP_EOL, [
            $prefix,
            $lp.'Do not edit this code, it will be replaced whenever composer update/install runs',
            PHP_EOL,
            file_get_contents($src),
            $suffix,
        ]);
        $code = str_replace('$', '\$', $code);
        //if destination doesn't exist, simply place code into destination
        if (!file_exists($dest)) {
            static::$event->getIO()->write("Placing new code $name in $dest");
            file_put_contents($dest, $code);
            return;
        }
        //destination file exists, so load its content
        $destContent = file_get_contents($dest);
        //build regex to find existing code in file
        $regex = implode('',[
            '/',
            preg_quote($prefix),
            '.+',
            preg_quote($suffix),
            '/s'
        ]);
        //append/prepend code if it isn't already in the file
        if (!preg_match($regex,$destContent)) {
            if ($append) {
                static::$event->getIO()->write("Appending code $name to $dest");
                $destContent .= PHP_EOL.PHP_EOL.$code;
            }else {
                static::$event->getIO()->write("Prepending code $name to $dest");
                $destContent = $code.PHP_EOL.PHP_EOL.$destContent;
            }
            $destContent = trim($destContent).PHP_EOL;
            file_put_contents($dest, $destContent);
            return;
        }
        //code does exist in the file, replace it
        static::$event->getIO()->write("Updating code $name in $dest");
        $destContent = preg_replace($regex, $code, $destContent);
        file_put_contents($dest, $destContent);
    }

    protected static function copyFile($src, $dest)
    {
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
