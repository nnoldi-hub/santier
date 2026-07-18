<?php

namespace App\Http\Controllers;

use App\Models\TaskTemplate;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskTemplateController extends Controller
{
    public function quickCreate(Request $request)
    {
        // Not under /api/* - bootstrap/app.php only renders JSON error responses
        // for that prefix, so validate manually to guarantee a JSON response
        // instead of the redirect $request->validate() would otherwise trigger.
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Date invalide.', 'errors' => $validator->errors()], 422);
        }

        $tenantId = TenantContext::id($request->user());

        $template = TaskTemplate::firstOrCreate([
            'tenant_id' => $tenantId,
            'title' => $validator->validated()['title'],
        ]);

        return response()->json(['id' => $template->id, 'title' => $template->title]);
    }
}
