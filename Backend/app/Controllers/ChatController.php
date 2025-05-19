<?php

namespace App\Controllers;

use App\Models\ChatMessage;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Services\BertSentimentService;
use App\Models\ChatRating;

class ChatController extends Controller
{
    protected FCMService $fcm;
    protected BertSentimentService $bert;
    
    public function __construct(FCMService $fcm, BertSentimentService $bert)
    {
        $this->fcm = $fcm;
        $this->bert = $bert;
    }
    
    /**
     * Send a message in a chat
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
            'message' => 'required|string',
            'receiver_type' => 'required|in:doctor,patient',
            'receiver_id' => 'required|integer',
        ]);
        
        // Determine sender type and ID based on authenticated user
        $user = Auth::user();
        if ($user->hasRole('doctor')) {
            $senderType = 'doctor';
            $senderId = $user->doctor->id; // Assuming relationship exists
        } else {
            $senderType = 'patient';
            $senderId = $user->patient->id; // Assuming relationship exists
        }
        
        // Create the chat message
        $chat = ChatMessage::create([
            'appointment_id' => $data['appointment_id'],
            'sender_type' => $senderType,
            'sender_id' => $senderId,
            'message' => $data['message'],
            'is_read' => false,
        ]);
        
        // Process sentiment analysis if sender is doctor
        $rating = null;
        if ($senderType === 'doctor') {
            $bertResult = $this->bert->analyze($data['message']);
            $appointment = $chat->appointment;
            
            $rating = ChatRating::create([
                'appointment_id' => $data['appointment_id'],
                'patient_id' => $data['receiver_id'], // Assuming receiver is patient when sender is doctor
                'rating' => null, // This might be filled later by patient
                'feedback' => null, // This might be filled later by patient
                // You might want to add sentiment score and label columns to your chat_ratings table
                // 'sentiment_score' => $bertResult['score'],
                // 'sentiment_label' => $bertResult['label'],
            ]);
        }
        
        // Send push notification
        $receiverFcmToken = null;
        $senderName = '';
        
        if ($data['receiver_type'] === 'doctor') {
            $doctor = Doctor::find($data['receiver_id']);
            if ($doctor) {
                $receiverFcmToken = $doctor->fcm_token;
            }
            
            if ($senderType === 'patient') {
                $patient = Patient::find($senderId);
                $senderName = $patient ? $patient->name : 'المريض';
            }
        } else { // receiver is patient
            $patient = Patient::find($data['receiver_id']);
            if ($patient) {
                $receiverFcmToken = $patient->fcm_token;
            }
            
            if ($senderType === 'doctor') {
                $doctor = Doctor::find($senderId);
                $senderName = $doctor ? $doctor->name : 'الدكتور';
            }
        }
        
        if ($receiverFcmToken) {
            $this->fcm->sendToUser(
                $receiverFcmToken,
                'رسالة جديدة',
                $senderName . ': ' . substr($chat->message, 0, 50),
                [
                    'chat_id' => $chat->id,
                    'appointment_id' => $data['appointment_id']
                ]
            );
        }
        
        // Prepare response
        $response = $chat->toArray();
        if ($rating) {
            // You might want to add these fields to the response if you add them to your chat_ratings table
            // $response['sentiment_score'] = $rating->sentiment_score;
            // $response['sentiment_label'] = $rating->sentiment_label;
        }
        
        return response()->json($response, 201);
    }
    
    /**
     * Get messages for a specific appointment
     */
    public function getMessages(int $appointmentId): JsonResponse
    {
        $messages = ChatMessage::where('appointment_id', $appointmentId)
            ->orderBy('created_at')
            ->get();
        
        return response()->json($messages);
    }
    
    /**
     * Mark messages as read
     */
    public function markAsRead(Request $request): JsonResponse
    {
        $data = $request->validate([
            'appointment_id' => 'required|exists:appointments,id',
        ]);
        
        $user = Auth::user();
        
        // Determine receiver type based on authenticated user
        $receiverType = $user->hasRole('doctor') ? 'doctor' : 'patient';
        $receiverId = $user->hasRole('doctor') ? $user->doctor->id : $user->patient->id;
        
        // Mark all unread messages in this appointment where user is NOT the sender
        ChatMessage::where('appointment_id', $data['appointment_id'])
            ->where(function($query) use ($receiverType, $receiverId) {
                $query->where('sender_type', '!=', $receiverType)
                    ->orWhere('sender_id', '!=', $receiverId);
            })
            ->where('is_read', false)
            ->update(['is_read' => true]);
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Get recent chats for the authenticated user
     */
    public function getRecentChats(): JsonResponse
    {
        $user = Auth::user();
        
        // Determine user type and ID
        $userType = $user->hasRole('doctor') ? 'doctor' : 'patient';
        $userId = $user->hasRole('doctor') ? $user->doctor->id : $user->patient->id;
        
        // Get all appointment IDs where the user has sent or received messages
        $appointmentIds = ChatMessage::where(function($query) use ($userType, $userId) {
            $query->where('sender_type', $userType)
                ->where('sender_id', $userId);
        })->orWhere(function($query) use ($userType, $userId) {
            // This assumes the receiver type/id are stored somewhere or can be inferred
            // You might need to adjust this based on your database structure
        })->pluck('appointment_id')->unique();
        
        // Get the latest message for each appointment
        $recentChats = [];
        foreach ($appointmentIds as $appointmentId) {
            $latestMessage = ChatMessage::where('appointment_id', $appointmentId)
                ->latest()
                ->first();
                
            if ($latestMessage) {
                $unreadCount = ChatMessage::where('appointment_id', $appointmentId)
                    ->where('sender_type', '!=', $userType)
                    ->where('sender_id', '!=', $userId)
                    ->where('is_read', false)
                    ->count();
                    
                $recentChats[] = [
                    'appointment_id' => $appointmentId,
                    'last_message' => $latestMessage,
                    'unread_count' => $unreadCount,
                    // You may need to fetch additional data about the appointment/other user here
                ];
            }
        }
        
        // Sort by latest message
        usort($recentChats, function($a, $b) {
            return strtotime($b['last_message']['created_at']) - strtotime($a['last_message']['created_at']);
        });
        
        return response()->json($recentChats);
    }
}
