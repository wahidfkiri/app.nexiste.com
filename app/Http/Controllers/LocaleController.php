<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Change la langue de l'interface pour l'utilisateur courant et
     * enregistre ce choix en base de données (colonne users.locale),
     * avec une bascule immédiate via la session.
     */
    public function update(Request $request): RedirectResponse
    {
        $supported = config('app.supported_locales', ['fr']);

        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:' . implode(',', $supported)],
        ]);

        $locale = $validated['locale'];

        $request->session()->put('app_locale', $locale);

        $user = $request->user();
        if ($user) {
            $user->locale = $locale;
            $user->save();
        }

        return redirect()->back();
    }
}
