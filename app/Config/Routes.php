<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

// Rotas de Autenticação
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attemptLogin');
$routes->get('logout', 'Auth::logout');

// Rotas Protegidas pelo filtro 'auth'
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->get('accountability', 'Accountability::index');
    $routes->get('accountability/create', 'Accountability::create');
    $routes->post('accountability', 'Accountability::store');
    $routes->get('accountability/(:num)', 'Accountability::show/$1');
    $routes->get('accountability/(:num)/report', 'Accountability::report/$1');
    $routes->post('accountability/(:num)/analyze-all', 'Accountability::analyzeAll/$1');
    $routes->post('accountability/(:num)/submit', 'Accountability::submitAccountability/$1');
    $routes->post('accountability/(:num)/delete', 'Accountability::delete/$1');
    $routes->post('accountability/(:num)/upload/(:num)', 'Accountability::upload/$1/$2');
    $routes->post('accountability/(:num)/item/(:num)/submit', 'Accountability::submitItem/$1/$2');
    $routes->post('accountability/(:num)/item/(:num)/status', 'Accountability::updateItemStatus/$1/$2');
    $routes->get('accountability/attachment/(:num)/preview', 'Accountability::previewAttachment/$1');
    $routes->get('accountability/attachment/(:num)/download', 'Accountability::downloadAttachment/$1');
    $routes->post('accountability/attachment/(:num)/delete', 'Accountability::deleteAttachment/$1');
    $routes->post('accountability/attachment/(:num)/analyze', 'Accountability::analyzeAttachment/$1');

    // Histórico de Auditoria
    $routes->get('history', 'AuditHistory::index');

    // CRUD de Usuários e Permissões
    $routes->get('users', 'Users::index');
    $routes->get('users/create', 'Users::create');
    $routes->post('users', 'Users::store');
    $routes->get('users/(:num)/edit', 'Users::edit/$1');
    $routes->post('users/(:num)/update', 'Users::update/$1');
    $routes->post('users/(:num)/delete', 'Users::delete/$1');
    $routes->post('users/(:num)/role', 'Users::updateRole/$1');

    // Configurações
    $routes->get('settings', 'Settings::index');
    $routes->post('settings/smtp', 'Settings::saveSmtp');
    $routes->post('settings/ollama', 'Settings::saveOllama');
});
