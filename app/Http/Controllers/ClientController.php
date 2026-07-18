<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Http\Requests\StoreClientRequest;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Client::class, 'client');
    }

    public function index(Request $request): Response
    {
        $tenantId = TenantContext::id($request->user());

        $clients = Client::where('tenant_id', $tenantId)
            ->withCount('projects')
            ->orderBy('name')
            ->paginate(20);

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Clients/Create');
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $tenantId = TenantContext::id($request->user());

        Client::create([
            ...$request->validated(),
            'tenant_id' => $tenantId,
        ]);

        return redirect()->route('clients.index')->with('success', 'Client adaugat cu succes!');
    }

    public function quickCreate(Request $request)
    {
        $this->authorize('create', Client::class);

        // This route is not under /api/*, and bootstrap/app.php only renders JSON
        // error responses for that prefix - validate manually so a failure still
        // returns JSON instead of a redirect (which is what $request->validate()
        // would trigger here).
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:person,company'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Date invalide.', 'errors' => $validator->errors()], 422);
        }

        $client = Client::create([
            ...$validator->validated(),
            'tenant_id' => TenantContext::id($request->user()),
            'active' => true,
        ]);

        return response()->json(['id' => $client->id, 'name' => $client->name]);
    }

    public function show(Client $client): Response
    {
        $client->load('projects');
        return Inertia::render('Clients/Show', ['client' => $client]);
    }

    public function edit(Client $client): Response
    {
        return Inertia::render('Clients/Edit', ['client' => $client]);
    }

    public function update(StoreClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());
        return redirect()->route('clients.index')->with('success', 'Client actualizat!');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();
        return redirect()->route('clients.index')->with('success', 'Client sters!');
    }
}