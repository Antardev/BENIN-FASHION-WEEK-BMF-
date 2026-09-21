<?php

namespace App\Http\Controllers;

use App\Models\Event;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::with('ticketTypes')->where('is_active', true)->orderBy('position')->get();

        return view('home', compact('events'));
    }
}
