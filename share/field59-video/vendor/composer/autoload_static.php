<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita3b623efd5294615b1817cc12c59024d
{
    public static $prefixLengthsPsr4 = array (
        'I' => 
        array (
            'Inc\\' => 4,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Inc\\' => 
        array (
            0 => __DIR__ . '/../..' . '/inc',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInita3b623efd5294615b1817cc12c59024d::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInita3b623efd5294615b1817cc12c59024d::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}