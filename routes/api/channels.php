<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given callback will be used to check if an
| authenticated user can listen to the channel.
|
*/

Broadcast::channel('admin.notifications', function ($user) {
    // Only allow authenticated admins to listen
    return $user->role === 'admin';
});
