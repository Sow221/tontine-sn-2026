<?php

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

echo 'user_badges count: '.DB::table('user_badges')->count()."\n";

echo "\n=== user_badges actuels ===\n";
DB::table('user_badges')->get()->each(function ($r) {
    echo "  user_id={$r->user_id}, badge_id={$r->badge_id}, earned_at={$r->earned_at}\n";
});
