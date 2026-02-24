<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;   // 🔥
use App\Invitation;
use App\Ticket;   

class InvitationController extends Controller
{
    // Ver los 10 códigos de una mesa
    public function index($ticketId)
    {
        $ticket = Ticket::where('id', $ticketId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $invitations = Invitation::where('ticket_id', $ticketId)->get();

        return response()->json([
            'seat_id'     => $ticket->seat_id,   // 🔥
            'invitations' => $invitations
        ]);
    }

    // GET /auth/my-invitations
    public function myInvitations()
    {
        $user = auth()->user();

        // 🔥 Normalizar: quitar el + del teléfono del usuario
        $userPhone = ltrim($user->phone, '+');

        $invitations = Invitation::with(['event', 'ticket.user'])
            ->whereRaw("REPLACE(guest_phone, '+', '') = ?", [$userPhone])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($inv) {
                return [
                    'id'        => $inv->id,
                    'code'      => $inv->code,
                    'status'    => $inv->status,
                    'seat_id'   => $inv->ticket->seat_id,
                    'host_name' => $inv->ticket->user->name,
                    'event'     => [
                        'name'     => $inv->event->name,
                        'date'     => $inv->event->date,
                        'location' => $inv->event->location,
                        'image'    => $inv->event->image,
                    ]
                ];
            });

        return response()->json($invitations);
    }


    public function assign(Request $request, $id)
    {
        $request->validate([
            'phone' => 'required|string'
        ]);

        $invitation = Invitation::findOrFail($id);

        // Verificar que el ticket le pertenece al usuario
        $ticket = Ticket::where('id', $invitation->ticket_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $token = Str::random(32);

        $invitation->update([
            'guest_phone' => $request->phone,
            'token'       => $token,
            'status'      => 'sent'
        ]);

        // 🔥 Link que se enviará al invitado
        $link = config('app.frontend_url') . "/invite/{$token}";

        // 🔥 Enviar por WhatsApp (usando Twilio, ultramsg, etc)
        // O simplemente retornar el link para que el frontend lo comparta
        return response()->json([
            'success' => true,
            'link'    => $link,
            'phone'   => $request->phone
        ]);
    }

    public function claim($token)
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if ($invitation->status === 'used') {
            return response()->json(['message' => 'Invitación ya usada'], 422);
        }

        return response()->json([
            'invitation_id' => $invitation->id,
            'event_id'      => $invitation->event_id,
            'code'          => $invitation->code,
            'status'        => $invitation->status
        ]);
    }

    // POST /auth/invitations/{id}/respond
    public function respond(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:accepted,declined'
        ]);

        $user = auth()->user();

        $userPhone = ltrim($user->phone, '+');

        $invitation = Invitation::where('id', $id)
            ->whereRaw("REPLACE(guest_phone, '+', '') = ?", [$userPhone])
            ->firstOrFail();

        $invitation->update(['status' => $request->status]);

        return response()->json(['success' => true]);
    }
}
