<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentGatewayController extends Controller
{
    public function index(): View
    {
        return view('dashboard.pages.payment-gateways.index', [
            'paymentGateways' => PaymentMethod::query()->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $paymentGateway = PaymentMethod::query()->create($validated);

        return back()->with('success', $paymentGateway->title.' was added.');
    }

    public function update(Request $request, PaymentMethod $paymentGateway): RedirectResponse
    {
        $paymentGateway->update($this->validated($request, $paymentGateway));

        return back()->with('success', $paymentGateway->title.' was updated.');
    }

    private function validated(Request $request, ?PaymentMethod $paymentGateway = null): array
    {
        $request->merge([
            'title' => trim((string) $request->input('title')),
            'description' => trim((string) $request->input('description')),
            'class' => trim((string) $request->input('class')),
        ]);

        return $request->validate([
            'title' => [
                'required',
                'string',
                'max:75',
                Rule::unique('payment_method', 'title')->ignore($paymentGateway),
            ],
            'description' => ['nullable', 'string', 'max:250'],
            'class' => ['nullable', 'string', 'max:20', 'regex:/^[a-z0-9_-]+$/i'],
            'active' => ['required', 'boolean'],
        ], [
            'class.regex' => 'The icon name may only contain letters, numbers, underscores, and dashes.',
        ]);
    }
}
