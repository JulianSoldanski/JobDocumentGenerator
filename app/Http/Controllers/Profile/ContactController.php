<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContactController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'street' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
        ], [
            'full_name.required' => 'Der Name steht im Kopf jedes Dokuments und ist deshalb Pflicht.',
        ]);

        $request->user()->contact()->fill(array_map(
            fn ($value): string => (string) $value,
            $validated
        ))->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kontaktdaten gespeichert.']);

        return back();
    }
}
