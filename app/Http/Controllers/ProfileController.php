<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     *
     * Only the email is editable in the zero-knowledge design (there is
     * no `name` column and no `email_verified_at` column, so the Breeze
     * defaults are deliberately omitted).
     *
     * @param  ProfileUpdateRequest  $request  The validated request.
     * @return RedirectResponse Redirect back to the profile page.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());
        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     *
     * The email on the account must be typed as confirmation (the Breeze
     * `current_password` rule would always fail against the missing
     * `password` column in the zero-knowledge design).
     *
     * @param  Request  $request  The current request.
     * @return RedirectResponse Redirect to the root.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'email' => ['required', 'string', 'lowercase', 'email'],
        ]);

        if ($request->user()->email !== $request->email) {
            return back()->withErrors([
                'email' => __('The email does not match your account.'),
            ], 'userDeletion');
        }

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
