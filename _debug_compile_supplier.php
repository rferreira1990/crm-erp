<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$compiler = app('blade.compiler');
$source = file_get_contents(resource_path('views/suppliers/show.blade.php'));
$compiled = $compiler->compileString($source);
file_put_contents(storage_path('framework/views/_debug_supplier_compiled.php'), $compiled);
echo "ok\n";
