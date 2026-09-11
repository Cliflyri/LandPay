<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\FormattedText;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FormattingPreviewController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate(['body' => ['nullable', 'string', 'max:10000']]);

        return response()->json(['html' => FormattedText::admin($data['body'] ?? '')]);
    }
}