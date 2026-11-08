<?php
/**
 * ==============================================================================
 * SISTEM SUPPLY CHAIN MANAGEMENT (SCM) INDUSTRI MANUFAKTUR OTOMOTIF
 * FRONT CONTROLLER - PINTU MASUK UTAMA APLIKASI
 * ==============================================================================
 * Mata Kuliah : Pemrograman Web Dasar (PHP Native MVC & OOP)
 * Kelompok    : 1
 * ==============================================================================
 */

// Muat konfigurasi database & komponen inti MVC
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/core/Session.php';
require_once __DIR__ . '/../app/core/Controller.php';
require_once __DIR__ . '/../app/core/Router.php';

// Awali sesi secara konsisten untuk seluruh aplikasi
Session::start();

// Inisialisasi router dengan base URL /scm
$router = new Router('/scm');

// ==============================================================================
// RUTE AUTENTIKASI PENGGUNA (LOGIN & LOGOUT)
// ==============================================================================
$router->get('/login', ['AuthController', 'login']);
$router->post('/login', ['AuthController', 'login']);
$router->get('/logout', ['AuthController', 'logout']);
$router->post('/logout', ['AuthController', 'logout']);

// Daftarkan rute navigasi dashboard
$router->get('/', ['HomeController', 'index']);
$router->get('/dashboard', ['HomeController', 'index']);

// ==============================================================================
// RUTE MODUL MASTER REKANAN VENDOR LOGAM & MUTU ISO
// ==============================================================================
$router->get('/vendor', ['VendorController', 'index']);
$router->get('/vendor/create', ['VendorController', 'create']);
$router->post('/vendor/store', ['VendorController', 'store']);
$router->get('/vendor/edit/{id}', ['VendorController', 'edit']);
$router->post('/vendor/update/{id}', ['VendorController', 'update']);
$router->get('/vendor/verify/{id}', ['VendorController', 'verify']);
$router->post('/vendor/process-verify/{id}', ['VendorController', 'processVerify']);
$router->post('/vendor/delete/{id}', ['VendorController', 'delete']);

// ==============================================================================
// RUTE MODUL PURCHASE ORDER & JADWAL PENGIRIMAN
// ==============================================================================
$router->get('/purchase-order', ['PurchaseOrderController', 'index']);
$router->get('/purchase-order/create', ['PurchaseOrderController', 'create']);
$router->post('/purchase-order/store', ['PurchaseOrderController', 'store']);
$router->get('/purchase-order/detail/{id}', ['PurchaseOrderController', 'detail']);
$router->post('/purchase-order/receive/{id}', ['PurchaseOrderController', 'receive']);
$router->post('/purchase-order/update-status/{id}', ['PurchaseOrderController', 'updateStatus']);
$router->post('/purchase-order/cancel/{id}', ['PurchaseOrderController', 'cancel']);
$router->get('/purchase-order/materials/{vendorId}', ['PurchaseOrderController', 'getMaterialsByVendor']);

// ==============================================================================
// RUTE MODUL SPK METODE SIMPLE ADDITIVE WEIGHTING (SAW)
// ==============================================================================
$router->get('/saw-criteria', ['SawController', 'criteria']);
$router->post('/saw-criteria/store', ['SawController', 'storeCriteria']);
$router->post('/saw-criteria/update/{id}', ['SawController', 'updateCriteria']);
$router->post('/saw-criteria/delete/{id}', ['SawController', 'deleteCriteria']);
$router->post('/saw-criteria/toggle/{id}', ['SawController', 'toggleCriteria']);

$router->get('/saw-evaluation', ['SawController', 'evaluation']);
$router->post('/saw-evaluation/store', ['SawController', 'storeEvaluation']);

$router->get('/saw-ranking', ['SawController', 'ranking']);

// ==============================================================================
// RUTE MODUL MASTER MATERIAL & BAHAN BAKU LOGAM
// ==============================================================================
$router->get('/material', ['MaterialController', 'index']);
$router->get('/material/create', ['MaterialController', 'create']);
$router->post('/material/store', ['MaterialController', 'store']);
$router->get('/material/edit/{id}', ['MaterialController', 'edit']);
$router->post('/material/update/{id}', ['MaterialController', 'update']);
$router->post('/material/delete/{id}', ['MaterialController', 'delete']);

// Jalankan sistem routing
$router->run();
