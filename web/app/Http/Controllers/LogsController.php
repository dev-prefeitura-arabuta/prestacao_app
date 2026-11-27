<?php

namespace App\Http\Controllers;

use App\Services\LogViewerService;
use Illuminate\Http\Request;

/**
 * Logs do Sistema (somente conta de desenvolvedor).
 */
class LogsController extends Controller
{
    public function index(Request $request, LogViewerService $logs)
    {
        $files = $logs->files();
        $file  = (string) $request->query('file', $files[0]['name'] ?? '');
        if ($file !== '' && $logs->path($file) === null) {
            $file = $files[0]['name'] ?? '';
        }

        $level = (string) $request->query('level', 'all');
        if (! in_array($level, ['all', 'error', 'warning', 'info', 'other'], true)) {
            $level = 'all';
        }
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 200);

        return view('logs.index', [
            'userName'  => session('user_name'),
            'username'  => session('username'),
            'userEmail' => session('user_email'),
            'userRole'  => session('user_role') ?? 'admin',
            'files'     => $files,
            'file'      => $file,
            'level'     => $level,
            'search'    => $search,
            'result'    => $file !== '' ? $logs->read($file, $level, $search) : null,
        ]);
    }

    public function download(string $file, LogViewerService $logs)
    {
        $path = $logs->path($file);
        if ($path === null) {
            abort(404);
        }

        return response()->download($path, $file, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
