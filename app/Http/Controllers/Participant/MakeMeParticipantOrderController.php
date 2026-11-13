<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\MakeMeOrder;
use Illuminate\Http\Request;

class MakeMeParticipantOrderController extends Controller
{
    public function index()
    {
        $participant = auth('makeme')->user();
        abort_unless($participant, 403);
        $orders = MakeMeOrder::where('participant_id', $participant->getKey())
            ->with('items')->latest('id')->paginate(15);
        return view('makeme.participant.orders.index', compact('orders'));
    }

    public function show(MakeMeOrder $order)
    {
        $participant = auth('makeme')->user();
        abort_unless($participant && (int) $order->participant_id === (int) $participant->getKey(), 404);
        $order->load('items');
        return view('makeme.participant.orders.show', compact('order'));
    }
}
