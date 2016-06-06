<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit5c276a9522d483802b28c9e6b66741f2
{
    public static $prefixLengthsPsr4 = array (
        'I' => 
        array (
            'Invoker\\' => 8,
            'Interop\\Container\\' => 18,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Invoker\\' => 
        array (
            0 => __DIR__ . '/..' . '/php-di/invoker/src',
        ),
        'Interop\\Container\\' => 
        array (
            0 => __DIR__ . '/..' . '/container-interop/container-interop/src/Interop/Container',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit5c276a9522d483802b28c9e6b66741f2::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit5c276a9522d483802b28c9e6b66741f2::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
