<?php

namespace App\Domains\Authentication\Listeners;

use App\Domains\Authentication\Events\EmailVerified;
use App\Domains\Authentication\Events\PasswordChanged;
use App\Domains\Authentication\Events\PasswordResetCompleted;
use App\Domains\Authentication\Events\PasswordResetRequested;
use App\Domains\Authentication\Events\UserLoggedIn;
use App\Domains\Authentication\Events\UserLoggedOut;

class LogAuthenticationActivity
{
    public function handleUserLoggedIn(UserLoggedIn $event): void
    {
        activity('auth')
            ->causedBy($event->user)
            ->withProperties([
                'ip' => $event->request->ip(),
                'user_agent' => $event->request->userAgent(),
                'event' => 'login',
            ])
            ->log('User logged in');
    }

    public function handleUserLoggedOut(UserLoggedOut $event): void
    {
        activity('auth')
            ->causedBy($event->user)
            ->withProperties([
                'ip' => $event->request->ip(),
                'user_agent' => $event->request->userAgent(),
                'event' => 'logout',
            ])
            ->log('User logged out');
    }

    public function handlePasswordChanged(PasswordChanged $event): void
    {
        activity('auth')
            ->causedBy($event->user)
            ->withProperties(['event' => 'password_changed'])
            ->log('Password changed');
    }

    public function handlePasswordResetRequested(PasswordResetRequested $event): void
    {
        activity('auth')
            ->withProperties([
                'email' => $event->email,
                'event' => 'password_reset_requested',
            ])
            ->log('Password reset requested');
    }

    public function handlePasswordResetCompleted(PasswordResetCompleted $event): void
    {
        activity('auth')
            ->causedBy($event->user)
            ->withProperties(['event' => 'password_reset_completed'])
            ->log('Password reset completed');
    }

    public function handleEmailVerified(EmailVerified $event): void
    {
        activity('auth')
            ->causedBy($event->user)
            ->withProperties(['event' => 'email_verified'])
            ->log('Email verified');
    }
}
