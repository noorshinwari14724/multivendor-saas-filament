<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;

class LogSuccessfulLogout
{
    /**
     * The HTTP request instance.
     */
    protected $request;

    /**
     * Create the event listener.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        $user = $event->user;

        if ($user) {
            // Log the activity
            \App\Models\ActivityLog::log(
                "User '{$user->name}' logged out",
                'auth',
                'logout',
                $user,
                $user,
                ['ip' => $this->request->ip()]
            );
        }
    }
}
