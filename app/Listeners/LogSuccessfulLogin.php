<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;

class LogSuccessfulLogin
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
    public function handle(Login $event): void
    {
        $user = $event->user;

        // Update last login information
        $user->recordLogin($this->request->ip());

        // Log the activity
        \App\Models\ActivityLog::log(
            "User '{$user->name}' logged in",
            'auth',
            'login',
            $user,
            $user,
            ['ip' => $this->request->ip(), 'user_agent' => $this->request->userAgent()]
        );
    }
}
