<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Normalize requests that accidentally include the subdirectory twice
// Example: /ITSO/tw32/ITSO/tw32/users  -> redirect to /ITSO/tw32/users
$routes->addRedirect('ITSO/tw32/ITSO/tw32', 'ITSO/tw32');
$routes->addRedirect('ITSO/tw32/ITSO/tw32(:any)', 'ITSO/tw32$1');

// Support requests coming in with the project subdirectory in the URI
// Map requests that include the project subdirectory prefix to the same controllers
$routes->group('ITSO/tw32', static function($routes) {
	$routes->get('', 'Index::index');
	// also allow explicit 'main' path under the prefixed URI
	$routes->get('main', 'Index::index');

	// Users routes under the prefixed path
	$routes->get('users', 'Users::index');
	$routes->get('users/add', 'Users::add');
	$routes->post('users/insert', 'Users::insert');
	$routes->get('users/view/(:num)', 'Users::view/$1');
	$routes->get('users/edit/(:num)', 'Users::edit/$1');
	$routes->post('users/update/(:num)', 'Users::update/$1');
	$routes->get('users/delete/(:num)', 'Users::delete/$1');

	// Equipment routes under the prefixed path
	$routes->get('equipment', 'Equipment::index');
	$routes->get('equipment/add', 'Equipment::add');
	$routes->post('equipment/insert', 'Equipment::insert');
	$routes->get('equipment/view/(:num)', 'Equipment::view/$1');
	$routes->get('equipment/edit/(:num)', 'Equipment::edit/$1');
	$routes->post('equipment/update/(:num)', 'Equipment::update/$1');
	$routes->get('equipment/delete/(:num)', 'Equipment::delete/$1');

	// Borrowing and Reservations under prefixed path
	$routes->get('borrowing', 'Borrowing::index');
	$routes->get('borrowing/create/(:num)', 'Borrowing::create/$1');
	$routes->post('borrowing/submit', 'Borrowing::submit');
	$routes->get('borrowing/return/(:num)', 'Borrowing::return/$1');
	// explicit route to list returns under the borrowing path (handles /borrowing/returns)
	$routes->get('borrowing/returns', 'Borrowing::returns');
	// dedicated returns shortcut
	$routes->get('returns', 'Borrowing::returns');

	$routes->get('reservations', 'Reservations::index');
	$routes->get('reservations/create/(:num)', 'Reservations::create/$1');
	$routes->post('reservations/submit', 'Reservations::submit');
	$routes->get('reservations/borrow/(:num)', 'Reservations::borrow/$1');
	$routes->get('reservations/cancel/(:num)', 'Reservations::cancel/$1');
	// reschedule routes for reservations (show form and submit)
	$routes->get('reservations/reschedule/(:num)', 'Reservations::reschedule/$1');
	$routes->post('reservations/rescheduleSubmit', 'Reservations::rescheduleSubmit');
});

// Routes for Index controller
$routes->get('/', 'Index::index');
// also support 'main' as an alias for the index controller
$routes->get('main', 'Index::index');

// Routes for Users controller
$routes->get('users', 'Users::index');
$routes->get('users/add', 'Users::add');
$routes->post('users/insert', 'Users::insert');
$routes->get('users/view/(:num)', 'Users::view/$1');
$routes->get('users/edit/(:num)', 'Users::edit/$1');
$routes->post('users/update/(:num)', 'Users::update/$1');
$routes->get('users/delete/(:num)', 'Users::delete/$1');

// Routes for Equipment controller
$routes->get('equipment', 'Equipment::index');
$routes->get('equipment/add', 'Equipment::add');
$routes->post('equipment/insert', 'Equipment::insert');
$routes->get('equipment/view/(:num)', 'Equipment::view/$1');
$routes->get('equipment/edit/(:num)', 'Equipment::edit/$1');
$routes->post('equipment/update/(:num)', 'Equipment::update/$1');
$routes->get('equipment/delete/(:num)', 'Equipment::delete/$1');

// Borrowing and Reservations routes
$routes->get('borrowing', 'Borrowing::index');
$routes->get('borrowing/create/(:num)', 'Borrowing::create/$1');
$routes->post('borrowing/submit', 'Borrowing::submit');
$routes->get('borrowing/return/(:num)', 'Borrowing::return/$1');
// explicit route to list returns under the borrowing path (handles /borrowing/returns)
$routes->get('borrowing/returns', 'Borrowing::returns');
// dedicated returns shortcut
$routes->get('returns', 'Borrowing::returns');

$routes->get('reservations', 'Reservations::index');
$routes->get('reservations/create/(:num)', 'Reservations::create/$1');
$routes->post('reservations/submit', 'Reservations::submit');
$routes->get('reservations/cancel/(:num)', 'Reservations::cancel/$1');
// reschedule routes (non-prefixed)
$routes->get('reservations/reschedule/(:num)', 'Reservations::reschedule/$1');
$routes->post('reservations/rescheduleSubmit', 'Reservations::rescheduleSubmit');

$routes->get('reports', 'Reports::index');
