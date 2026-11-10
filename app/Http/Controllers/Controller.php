<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Spec 006, RF4: al abrir login o registro, guarda la última página que se vio en la app
     * para volver a ella tras entrar. Usa la URL anterior que guarda la sesión de Laravel
     * (nunca la cabecera Referer) y solo si es de esta misma web y no es login ni registro.
     * Un destino que ya existe (por ejemplo, el que pone Laravel al pedir Favoritos) no se pisa.
     */
    protected function rememberPageBeforeLogin(Request $request): void
    {
        $session = $request->session();

        if ($session->has('url.intended')) {
            return;
        }

        $previous = $session->previousUrl();

        if (!is_string($previous)) {
            return;
        }

        $root = url('/');
        $isSameSite = $previous === $root
            || str_starts_with($previous, $root . '/')
            || str_starts_with($previous, $root . '?');

        $path = strtok($previous, '?#');

        if (!$isSameSite || in_array($path, [route('login'), route('register')], true)) {
            return;
        }

        $session->put('url.intended', $previous);
    }
}
