<?php
declare(strict_types=1);
// SPDX-License-Identifier: AGPL-3.0-or-later
return [
	'ocs' => [
		['name' => 'NoteApi#index', 'url' => '/api/v1/notes', 'verb' => 'GET'],
		['name' => 'NoteApi#create', 'url' => '/api/v1/notes', 'verb' => 'POST'],
		['name' => 'NoteApi#update', 'url' => '/api/v1/notes/{id}', 'verb' => 'PUT', 'requirements' => ['id' => '\d+']],
		['name' => 'NoteApi#show', 'url' => '/api/v1/notes/{id}', 'verb' => 'GET', 'requirements' => ['id' => '\d+']],
		['name' => 'NoteApi#shares', 'url' => '/api/v1/notes/{id}/shares', 'verb' => 'GET', 'requirements' => ['id' => '\d+']],
		['name' => 'NoteApi#share', 'url' => '/api/v1/notes/{id}/shares', 'verb' => 'POST', 'requirements' => ['id' => '\d+']],
		['name' => 'NoteApi#unshare', 'url' => '/api/v1/notes/{id}/shares/{userId}', 'verb' => 'DELETE', 'requirements' => ['id' => '\d+', 'userId' => '.+']],
		['name' => 'NoteApi#users', 'url' => '/api/v1/users', 'verb' => 'GET'],
		['name' => 'NoteApi#destroy', 'url' => '/api/v1/notes/{id}', 'verb' => 'DELETE', 'requirements' => ['id' => '\d+']],
		['name' => 'NoteApi#upload', 'url' => '/api/v1/images', 'verb' => 'POST'],
		['name' => 'NoteApi#prefs', 'url' => '/api/v1/prefs', 'verb' => 'PUT'],
	],
	'routes' => [
		['name' => 'Image#show', 'url' => '/img/{name}', 'verb' => 'GET', 'requirements' => ['name' => '[a-f0-9]{32}\.(png|jpg|gif|webp)']],
		['name' => 'Settings#setGroups', 'url' => '/admin/groups', 'verb' => 'POST'],
	],
];
