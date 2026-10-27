<?php

namespace App\Http\Controllers\Admin;

use App\City;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CityController extends Controller
{
    public function index(): View
    {
        return view('dashboard.pages.cities.index', [
            'cities' => City::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('cities', 'name')],
        ]);

        City::query()->create([
            'name' => $validated['name'],
            'active' => true,
        ]);

        return back()->with('success', $validated['name'].' is now active.');
    }

    public function update(Request $request, City $city): RedirectResponse
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('cities', 'name')->ignore($city),
            ],
            'active' => ['required', 'boolean'],
        ]);

        $city->update($validated);

        return back()->with('success', $city->name.' was updated.');
    }

    public function destroy(City $city): RedirectResponse
    {
        $name = $city->name;
        $city->delete();

        return back()->with('success', $name.' was removed.');
    }
}
