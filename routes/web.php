<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Group for Authenticated Users
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', function () {
        return view('selection');
    })->name('selection');

    Route::get('/studio/procreate', function () {
        if (!auth()->user()->hasToolAccess('procreate_studio')) {
            return redirect()->route('selection')->with('error', 'You do not have access to this tool.');
        }
        return view('studio');
    })->name('studio.procreate');

    Route::get('/setting', function () {
        $records = App\Models\PdfRecord::where('user_id', Illuminate\Support\Facades\Auth::id())->latest()->get();
        return view('dashboard', compact('records'));
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Etsy PDF Lab Routes
    Route::middleware(['auth'])->group(function () {
        Route::get('/studio/pdf', [\App\Http\Controllers\PdfGeneratorController::class, 'index'])->name('pdf.index');
        Route::get('/studio/pdf/new', [\App\Http\Controllers\PdfGeneratorController::class, 'create'])->name('pdf.create');
        Route::get('/studio/pdf/edit/{id}', [\App\Http\Controllers\PdfGeneratorController::class, 'edit'])->name('pdf.edit');
        Route::post('/studio/pdf/save', [\App\Http\Controllers\PdfGeneratorController::class, 'save'])->name('pdf.save');
        Route::delete('/studio/pdf/delete/{id}', [\App\Http\Controllers\PdfGeneratorController::class, 'destroy'])->name('pdf.delete');
        Route::get('/studio/pdf/preview/{id}', [\App\Http\Controllers\PdfGeneratorController::class, 'preview'])->name('pdf.preview');
    });

    // Admin Routes
    Route::middleware(['can:admin'])->group(function () {
        Route::get('/admin', [\App\Http\Controllers\AdminController::class, 'index'])->name('admin.index');
        Route::post('/admin/users/{id}/status', [\App\Http\Controllers\AdminController::class, 'updateStatus'])->name('admin.users.status');
        Route::post('/admin/users/{id}/tool-access', [\App\Http\Controllers\AdminController::class, 'updateToolAccess'])->name('admin.users.tools');
        Route::post('/admin/users/{id}/reset-password', [\App\Http\Controllers\AdminController::class, 'resetPassword'])->name('admin.users.password');
        Route::delete('/admin/users/{id}', [\App\Http\Controllers\AdminController::class, 'deleteUser'])->name('admin.users.delete');
    });

    // Procreate Studio Engine Routes (Ported from PHP)
    Route::prefix('studio-engine')->group(function () {
        Route::post('/upload', [\App\Http\Controllers\StudioController::class, 'upload']);
        Route::post('/upload-chunk', [\App\Http\Controllers\StudioController::class, 'uploadChunk']);
        Route::post('/finalize-upload', [\App\Http\Controllers\StudioController::class, 'finalizeUpload']);
        Route::post('/upload-url', [\App\Http\Controllers\StudioController::class, 'uploadUrl']);
        Route::post('/jobs/{jobId}/scan', [\App\Http\Controllers\StudioController::class, 'scan']);
        Route::post('/jobs/{jobId}/save-config', [\App\Http\Controllers\StudioController::class, 'saveConfig']);
        Route::post('/jobs/{jobId}/rebrand', [\App\Http\Controllers\StudioController::class, 'rebrand']);
        Route::post('/jobs/{jobId}/rename-output', [\App\Http\Controllers\StudioController::class, 'renameOutput']);
        Route::get('/jobs/{jobId}/download', [\App\Http\Controllers\StudioController::class, 'downloadJob']);
        Route::get('/jobs/{jobId}/outputs/{index}/download', [\App\Http\Controllers\StudioController::class, 'downloadOutput'])->whereNumber('index');
        Route::get('/jobs/{jobId}', [\App\Http\Controllers\StudioController::class, 'getStatus']);
        Route::post('/jobs/{jobId}/cleanup', [\App\Http\Controllers\StudioController::class, 'cleanup']);
        Route::post('/maintenance/cleanup-storage', [\App\Http\Controllers\StudioController::class, 'maintenanceCleanup']);
        Route::get('/jobs/{jobId}/assets/preview', [\App\Http\Controllers\StudioController::class, 'previewAsset']);
        Route::get('/jobs/{jobId}/assets/{path}', [\App\Http\Controllers\StudioController::class, 'downloadAsset'])->where('path', '.*');
        Route::get('/storage/stats', [\App\Http\Controllers\StudioController::class, 'getStorageStats']);
    });
});

require __DIR__.'/auth.php';
