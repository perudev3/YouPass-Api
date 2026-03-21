<?php

namespace App\Http\Controllers;

use App\Card;
use Illuminate\Http\Request;

class CardController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'cards' => $request->user()->cards()->latest()->get()
        ]);
    }

    
    public function store(Request $request)
    {

        $user = $request->user();

        if ($request->is_default) {
            Card::where('user_id',$user->id)->update([
                'is_default'=>false
            ]);
        }

        $card = Card::create([
            'user_id'=>$user->id,
            'brand'=>$request->brand,
            'last4'=>$request->last4,
            'expiry'=>$request->expiry,
            'holder'=>$request->holder,
            'is_default'=>$request->is_default
        ]);

        return response()->json($card);
    }

    public function destroy($id)
    {
        Card::where('id',$id)
        ->where('user_id',auth()->id())
        ->delete();

        return response()->json(['ok'=>true]);
    }
}
