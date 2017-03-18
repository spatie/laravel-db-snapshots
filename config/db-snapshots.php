<?php

return [

    /*
     * The disk on which the snapshots are stored
     */
    'disk' => 'snapshots',

    /*
     * The directory where temporary files will be stored
     */
    'temporary_directory' => storage_path('app/laravel-db-snapshots/temp');
];