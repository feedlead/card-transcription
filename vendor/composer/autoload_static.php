<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita68703dc75b8ff839258f8db9b7429dd
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Symfony\\Component\\Yaml\\' => 23,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Symfony\\Component\\Yaml\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/yaml',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInita68703dc75b8ff839258f8db9b7429dd::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInita68703dc75b8ff839258f8db9b7429dd::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
