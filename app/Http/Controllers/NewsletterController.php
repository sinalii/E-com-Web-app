<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email:rfc', 'max:255']]);
        DB::table('newsletter_subscriber')->updateOrInsert(
            ['email' => mb_strtolower($data['email'])],
            ['is_active' => true, 'created_at' => now()]
        );

        return back()->with('success', 'You’re on the list. Watch your inbox for Marketly updates.');
    }
}
