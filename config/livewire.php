<?php

/*
 * Only what differs from Livewire's defaults (the rest comes from the package).
 *
 * The rich text editor keeps its content as a nested document (list > item > paragraph > text),
 * so a description with lists inside a translation goes deeper than the default 10 levels.
 * `payload` replaces the package's whole group, hence the other limits are repeated as they ship.
 */
return [
    'payload' => [
        'max_size' => 1024 * 1024,
        'max_nesting_depth' => 40,
        'max_calls' => 50,
        'max_components' => 200,
    ],
];
