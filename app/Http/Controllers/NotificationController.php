<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function open(DatabaseNotification $notification): RedirectResponse
    {
        abort_unless(auth()->check(), 403);
        abort_unless((string) $notification->notifiable_type === auth()->user()::class, 404);
        abort_unless((int) $notification->notifiable_id === (int) auth()->id(), 404);

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        $targetUrl = trim((string) data_get($notification->data, 'action_url'));

        if ($targetUrl === '') {
            return redirect()->route('dashboard');
        }

        if (preg_match('#^https?://#i', $targetUrl) === 1) {
            $targetHost = parse_url($targetUrl, PHP_URL_HOST);
            $currentHost = request()->getHost();

            if ($targetHost && $currentHost && !$this->isAllowedHost((string) $currentHost, (string) $targetHost)) {
                return redirect()->route('dashboard');
            }

            return redirect()->to($targetUrl);
        }

        return redirect()->to($targetUrl);
    }

    protected function isAllowedHost(string $currentHost, string $targetHost): bool
    {
        if (strcasecmp($currentHost, $targetHost) === 0) {
            return true;
        }

        $loopbackHosts = ['127.0.0.1', 'localhost', '::1'];

        return in_array(strtolower($currentHost), $loopbackHosts, true)
            && in_array(strtolower($targetHost), $loopbackHosts, true);
    }
}
