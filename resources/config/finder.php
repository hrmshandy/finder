<?php

return [
	/*
     * The maximum file size of an item in bytes. Adding a file
     * that is larger will result in an exception.
     */
	'max_file_size' => 1024 * 1024 * 0.5,

	/*
     * The list of file can be allow to upload.
     */
	'allowed_extensions' => ['jpeg', 'jpg', 'png', 'gif', 'bmp'],

	/*
     * will be takeout folder or file from view
     */
	'exclude' => [
		'folders' => [],
		'files' => []
	]
];