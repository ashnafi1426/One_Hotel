<?php

namespace App\Http\Controllers\Api\Guests;

use App\Http\Controllers\Controller;
use App\Mail\ContactMessageReceivedMail;
use App\Models\ContactMessage;
use App\Models\Hotel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContactMessageController extends Controller
{
    /**
     * Submit a contact inquiry for the hotel.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hotel_id' => 'nullable|string',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $hotel = Hotel::first();

        // Store inquiry in database
        $contactMessage = ContactMessage::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'status' => 'unread',
        ]);

        // Determine destination hotel email
        $recipientEmail = null;
        if (strtolower($validated['subject']) === 'reservation' && !empty($hotel?->reservation_email)) {
            $recipientEmail = $hotel->reservation_email;
        } elseif (!empty($hotel?->email)) {
            $recipientEmail = $hotel->email;
        } else {
            $recipientEmail = config('mail.from.address');
        }

        $emailSent = false;
        if ($recipientEmail && $hotel) {
            try {
                Mail::to($recipientEmail)->send(new ContactMessageReceivedMail($contactMessage, $hotel));
                $emailSent = true;
            } catch (Throwable $e) {
                Log::error("[ContactMessage] Failed to send email to {$recipientEmail}: " . $e->getMessage(), [
                    'contact_message_id' => $contactMessage->id,
                ]);
            }
        }

        $hotelName = $hotel?->name ?? 'Sheraten Hotel';

        return response()->json([
            'success' => true,
            'message' => "Thank you! Your message has been sent to {$hotelName}. Our team will contact you shortly.",
            'data' => [
                'id' => $contactMessage->id,
                'hotel_name' => $hotelName,
                'email_sent' => $emailSent,
            ],
        ], 201);
    }
}
