<?php

return [
    /*
     * Routes that are never exposed to the frontend via @routes.
     */
    'except' => [
        'sanctum.*',
        'storage.*',
    ],
];
