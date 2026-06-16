<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginAuthController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoutingSlipController;
use App\Http\Controllers\PagesController;
use App\Http\Controllers\DoctrackController;
use App\Http\Controllers\PrintController;

Route::group(['middleware'=>['guest']],function(){
    Route::get('/', function () {
        return view('.login.login');
    });

    Route::get('/login', [LoginAuthController::class, 'getLogin'])->name('getLogin');
    Route::post('/login', [LoginAuthController::class, 'postLogin'])->name('postLogin');
});

Route::group(['middleware'=>['login_auth']],function(){

    // Main page
    Route::get('/', [DocumentController::class, 'dashboard'])->name('dashboard');

    // Dashboard DataTables AJAX endpoint
    Route::get('/dashboard/data', [DocumentController::class, 'getDashboardData'])->name('dashboard.data');
    
    //pages
    Route::get('/outgoing-document', [PagesController::class, 'outgoingDocs'])->name('outgoingDocs');
    Route::get('/pending', [PagesController::class, 'pending'])->name('pending');
    Route::get('/served', [PagesController::class, 'served'])->name('served');
    Route::get('/viewLogs', [PagesController::class, 'viewLogs'])->name('viewLogs');
    Route::get('/viewLogs-Tracking', [PagesController::class, 'viewLogsTracking'])->name('viewLogsTracking');
    Route::get('/viewPdfRoute', [PagesController::class, 'viewPdfRoute'])->name('viewPdfRoute');
    Route::get('/doctrackslip-list', [PagesController::class, 'doctrackSlip'])->name('doctrackSlip');
    Route::get('/served/data', [PagesController::class, 'getServedData'])->name('served.data');
    

    // update ctrl no in doctrack slip
    Route::put('/doctrack-slip/{doctrack}', [DoctrackController::class, 'update'])->name('doctrack.update');


    // Routed Distribution list
    Route::get('/distributionList', [PagesController::class, 'distributionList'])->name('distributionList');
    Route::get('/distribution-List/view/{id}', [PagesController::class, 'viewDistribution'])->name('viewDistribution');
    Route::get('/distributionList/pdf/{id}', [PagesController::class, 'viewDistributionPdf'])->name('viewDistributionPdf');

    // Tracking Distribution list
    Route::get('/trackingDistributionList', [PagesController::class, 'trackingDistributionList'])->name('trackingDistributionList');
    Route::get('/trackingDistributionList/pdf/{id}', [PagesController::class, 'viewTrackingDistributionPdf'])->name('viewTrackingDistributionPdf');

    // offices
    Route::get('/offices', [PagesController::class, 'offices'])->name('offices');
    Route::put('/offices/{office}', [PagesController::class, 'update'])->name('offices.update');
    Route::delete('/offices/{office}', [PagesController::class, 'destroy'])->name('offices.destroy');
    Route::post('/offices', [PagesController::class, 'store'])->name('offices.store');

    // user groups
    Route::get('/userGroups', [PagesController::class, 'userGroups'])->name('userGroups');
    Route::put('/groups/{group}', [PagesController::class, 'updateGroup'])->name('groups.update');
    Route::delete('/groups/{group}', [PagesController::class, 'destroyGroup'])->name('groups.destroy');
    Route::post('/groups', [PagesController::class, 'storeGroup'])->name('groups.store');


    //tracking page
    Route::post('/tracking', [DocumentController::class, 'tracking'])->name('tracking');
    Route::get('/tracking', [DocumentController::class, 'tracking'])->name('documents.tracking');

    //insert,update documents
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::post('/documents/{id}', [DocumentController::class, 'update'])->name('documents.update');

    Route::post('/documents', [DocumentController::class, 'storeDoc'])->name('documents.storeDoc');

    //download pdf documents
    Route::get('/documents/download/{id}', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('/documents/{id}', [DocumentController::class, 'viewPdf'])->name('documents.viewPdf');

    //logout
    Route::get('/logout', [MasterController::class,'logout'])->name('logout');


    //users
    Route::get('/users', [UserController::class, 'userView'])->name('userView');
    Route::post('/users', [UserController::class, 'addUser'])->name('users.addUser');
    Route::post('/users/Group', [UserController::class, 'addGroup'])->name('users.addGroup');
    Route::get('/users/edit/{id}', [UserController::class, 'userEdit'])->name('userEdit');
    Route::put('/users/update/{id}', [UserController::class, 'userUpdate'])->name('userUpdate');
    Route::post('/update-dpa', [UserController::class, 'updateDpa'])->name('update.dpa');

    Route::get('/users/ChangePass/{id}', [PagesController::class, 'userPassword'])->name('userPassword');
    Route::put('/users/passChange/{id}', [PagesController::class, 'passChange'])->name('passChange');


    Route::delete('/users/{id}', [UserController::class, 'deleteUser'])->name('users.deleteUser');


    //routing slip
    Route::post('/routingSlip', [RoutingSlipController::class, 'storeSlip'])->name('storeSlip');
    Route::get('/routingSlip/edit/{id}', [RoutingSlipController::class, 'editSlip'])->name('editSlip');
    Route::put('/routingSlip/update/{id}', [RoutingSlipController::class, 'updateSlip'])->name('updateSlip');
    Route::get('/routingSlip', [RoutingSlipController::class, 'viewSlip'])->name('viewSlip');
    Route::put('/route-back/{id}', [RoutingSlipController::class, 'routeBackToPresident'])->name('routeBackToPresident');
    Route::get('/routingSlip/view/{id}', [RoutingSlipController::class, 'viewPdfslip'])->name('viewPdfslip');
    // Route::delete('/routingSlip/{id}', [RoutingSlipController::class, 'deletePdf'])->name('deletePdf');
    Route::delete('/routingSlip/{id}', [RoutingSlipController::class, 'destroy'])->name('routingSlip.destroy');
    Route::get('/slipForm/view/{id}', [RoutingSlipController::class, 'slipForm'])->name('slipForm');
    Route::get('/pdfSlip/view/{id}', [RoutingSlipController::class, 'pdfSlip'])->name('pdfSlip');

    Route::get('/routingSlip/editSubject/{id}', [RoutingSlipController::class, 'editSubject'])->name('editSubject');
    Route::put('/routingSlip/updateSubject/{id}', [RoutingSlipController::class, 'updateSubject'])->name('updateSubject');


    //re-assign
    Route::post('/update-assign/{routeId}', [RoutingSlipController::class, 'updateAssign'])->name('updateAssign');

    //destination route
    Route::get('/routingSlip/editDest/{id}', [RoutingSlipController::class, 'editDest'])->name('editDest');
    Route::post('/routingSlip/storeRouteDoc', [RoutingSlipController::class, 'storeRouteDoc'])->name('storeRouteDoc');
    Route::get('/routingSlip/recall/{id}', [RoutingSlipController::class, 'recallSlip'])->name('recallSlip');
    Route::put('/routingSlip/updateRouteDoc/{id}', [RoutingSlipController::class, 'updateRouteDoc'])->name('updateRouteDoc');
    Route::put('/routingSlip/updateRouteDocRecall/{id}', [RoutingSlipController::class, 'updateRouteDocRecall'])->name('updateRouteDocRecall');




    Route::get('/routingSlip/editAssign/{id}', [RoutingSlipController::class, 'editAssign'])->name('editAssign');
    Route::post('/routingSlip/updateReroute/{rslip_id}', [RoutingSlipController::class, 'updateReroute'])->name('updateReroute');

    //doctrack_slip
    Route::post('/store-doctrack', [DoctrackController::class, 'storeDoctrack'])->name('storeDoctrack');
    Route::post('/store-doctrackUpdate', [DoctrackController::class, 'storeDoctrackUpdate'])->name('storeDoctrackUpdate');
    Route::get('/docslip-form/view/{id}', [DoctrackController::class, 'docslipForm'])->name('docslipForm');
    Route::get('/pdf-slip/{id}', [DoctrackController::class, 'pdfDocSlip'])->name('pdfDocSlip');
    Route::get('/slip-monitoring/{docslip_id}', [DoctrackController::class, 'slipMonitoring'])->name('slipMonitoring');
    Route::get('/search', [DoctrackController::class, 'search'])->name('search');
    Route::delete('/doc-slip/{docslip_id}', [PagesController::class, 'deleteSlip'])->name('deleteSlip');
    Route::put('/update-slip-status/{id}', [DoctrackController::class, 'updateSlipStatus'])->name('updateSlipStatus');

    Route::post('/upload-doc-file', [DoctrackController::class, 'uploadFile'])->name('uploadDoctrackFile');


    Route::get('/print-logbook', [PrintController::class, 'printLogbook'])->name('printLogbook');
    Route::get('/print-logbook/pdf', [PrintController::class, 'logbookPdf'])->name('logbookPdf');



});
