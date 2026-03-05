<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;   // 🔥
use App\Invitation;
use App\Ticket;   
use Illuminate\Support\Facades\Log;

class InvitationController extends Controller
{
    private function sendWhatsApp($phone, $message)
    {
        $token = config('services.factiliza.token');
        $instance = config('services.factiliza.instance');

        $url = "https://apiwsp.factiliza.com/v1/message/sendtext/{$instance}";

        $data = [
            'number' => $phone,
            'text' => $message
        ];

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$token}",
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            Log::error("Error enviando WhatsApp a {$phone}: {$err}");
            return false;
        }

        Log::info("WhatsApp enviado a {$phone}: {$response}");
        return true;
    }
    
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

        $event    = $invitation->event;
        $hostName = auth()->user()->name;
        $seatId   = $ticket->seat_id;
        $code     = $invitation->code;

        $message = "🎉 *¡Tienes una invitación!*\n\n"
            . "*{$hostName}* te invita al evento:\n\n"
            . "📅 *{$event->name}*\n"
            . "🗓 {$event->date}\n"
            . "📍 {$event->location}\n"
            . "🪑 Mesa: *{$seatId}*\n\n"
            . "Tu código de acceso:\n"
            . "🔑 *{$code}*\n\n"
            . "👉 Descarga la app, ingresa con tu número y ve a *Mis Invitaciones* para aceptar y ver tu QR de entrada.\n\n"
            . "¡Te esperamos! 🥳";

        $sent = $this->sendWhatsApp($request->phone, $message);

        return response()->json([
            'success' => true,
            'phone'   => $request->phone,
            'whatsapp' => true,
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
