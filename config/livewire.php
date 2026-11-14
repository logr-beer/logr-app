<?php

// Only the keys overridden here; everything else falls back to Livewire's own
// config. Livewire merges this file shallowly, so the temporary_file_upload
// block is spelled out in full.

return [

    /*
    |---------------------------------------------------------------------------
    | Temporary File Uploads
    |---------------------------------------------------------------------------
    |
    | Every file sent through a Livewire file input is checked against these
    | rules before it is stored in the temporary upload directory. Logr only
    | accepts photos and CSV/JSON imports, so nothing else gets that far.
    | Components still validate their own fields on submit.
    |
    */

    'temporary_file_upload' => [
        'disk' => null,
        'rules' => ['required', 'file', 'mimes:jpg,jpeg,png,gif,webp,csv,txt,json', 'max:12288'],
        'directory' => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5,
        'cleanup' => true,
    ],

];
