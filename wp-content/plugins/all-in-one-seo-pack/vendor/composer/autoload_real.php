<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInit10daf29ec0806365c9f7e4d00da8b0ef
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        require __DIR__ . '/platform_check.php';

        spl_autoload_register(array('ComposerAutoloaderInit10daf29ec0806365c9f7e4d00da8b0ef', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInit10daf29ec0806365c9f7e4d00da8b0ef', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInit10daf29ec0806365c9f7e4d00da8b0ef::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}