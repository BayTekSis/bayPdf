<?php

// Development only. All paths are rooted in this package's workbench.
$root = __DIR__;
foreach (['database', 'storage/private', 'storage/sessions', 'storage/fonts'] as $directory) {
    if (! is_dir($root.'/'.$directory)) {
        mkdir($root.'/'.$directory, 0770, true);
    }
}
if (! file_exists($root.'/database/database.sqlite')) {
    touch($root.'/database/database.sqlite');
}
if (! file_exists($root.'/storage/app-key')) {
    file_put_contents($root.'/storage/app-key', 'base64:'.base64_encode(random_bytes(32)));
}
