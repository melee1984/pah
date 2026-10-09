<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Model\Cart;
use App\Model\Orders\Orders;
use App\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function loginPage(Request $request)
    {
        return $request->user() ? redirect()->route('profile.dashboard') : view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $guestSession = $request->session()->getId();
        if (! Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Your email or password is incorrect.']);
        }
        $request->session()->regenerate();
        // Keep the guest basket attached to the same customer after session rotation.
        Cart::where('session_id', $guestSession)
            ->where(fn ($q) => $q->whereNull('user_id')->orWhere('user_id', Auth::id()))
            ->update(['user_id' => Auth::id(), 'session_id' => $request->session()->getId()]);
        $destination = $request->session()->pull('url.intended', route('profile.dashboard'));
        if ($request->expectsJson()) {
            return response()->json(['status' => 1, 'redirectURL' => $destination, 'name' => $request->user()->full_name]);
        }

        return redirect()->to($destination);
    }

    public function dashboard(Request $request)
    {
        $userId = $request->user()->id;

        return view('customer.dashboard', [
            'orderCount' => Orders::where('user_id', $userId)->count(),
            'ticketCount' => SupportTicket::where('user_id', $userId)->count(),
            'activeTickets' => SupportTicket::where('user_id', $userId)->whereNotIn('status', ['Resolved', 'Closed'])->count(),
            'recentOrders' => Orders::with(['cart', 'orderStatus', 'status'])->where('user_id', $userId)->latest()->limit(5)->get(),
        ]);
    }

    public function profile(Request $request)
    {
        return view('customer.profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'firstname' => 'required|string|max:100',
            'lastname' => 'required|string|max:100',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user()->id)],
            'mobile' => ['required', 'string', 'max:30', 'regex:/^[+0-9 ()-]{7,30}$/'],
        ]);
        $user = $request->user();
        $user->fill($data);
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        $user->save();

        return redirect()->route('profile.edit')->with('success', 'Your profile has been updated.');
    }

    public function support()
    {
        return view('customer.support');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
