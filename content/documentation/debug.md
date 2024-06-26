+++
title = "Debug"
weight = 18
+++

Phel doesn't come with an integrated debug framework. However, you can debug the values of your functions by dumping their values. And for this, there are some strategies.

## println function

```phel
(println (+ 1 1))
# OUTPUT:
2
```

## Native var_dump()

You can use any php function simply using the `php/` prefix, so you can use:

```phel
# Dumping a definition by its name
(def v (+ 2 2))
(php/var_dump v)
# OUTPUT:
int(4)
```

```phel 
# Directly dumping the result of a function
(php/var_dump (+ 3 3))
# OUTPUT:
int(6)
```

Additionally, you can call `(php/die)` to force the execution of the process so that you can debug a particular value on your own rhythm.

## Symfony dumper: dump() & dd()

Symfony has an awesome [VarDumper Component](https://symfony.com/doc/current/components/var_dumper.html) which you can use in your phel projects as well. You can install it by using composer, under your `require-dev` dependencies.

```json
"require-dev": {
    "symfony/var-dumper": "^5.4"
},
```

And then, the same drill, you can `dump()` a definition by its name or the function result:

```phel
(php/dump (+ 4 4))
# OUTPUT:
8
```

Additionally, you can also use `dd()` to dump and die the execution of the program as soon as it reaches that point:

```phel 
(php/dd (+ 5 5))
# OUTPUT:
10
```

## Check the evaluated PHP

You can keep the generated temporal PHP files for debugging purposes. Useful when you see an error occurring on `/private/var/folders/qq/dvftwj.../T/__phelV2KvGD` but the file does not exist. Read the [docs](/documentation/configuration/#keepgeneratedtempfiles).

```php
<?php # phel-config-local.php

return (require __DIR__ . '/phel-config.php')
    ->setKeepGeneratedTempFiles(true)
;
```

> TIP: Add this file to the `.gitignore` of the project, so you can have control over the configuration while on development without changing the global config.