<?php
if ($argc < 3) {
    exit(2);
}
$target = $argv[1];
$sentinel = $argv[2];
register_shutdown_function(function () use ($sentinel) {
    file_put_contents($sentinel, 'shutdown');
});
include $target;
file_put_contents($sentinel, 'continued', FILE_APPEND);
