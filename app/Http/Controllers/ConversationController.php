<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\ConversationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConversationController extends Controller
{
    public function __construct(private ConversationService $service) {}

    public function index()
    {
        $userId = auth()->id();

        $conversations = Conversation::query()
            ->where('buyer_id', $userId)
            ->orWhere('seller_user_id', $userId)
            ->with([
                'buyer:id,name,profile_photo_path',
                'seller:id,name,profile_photo_path',
                'order:id,order_number,status,total,shop_id',
                'order.shop:id,name',
                'latestMessage',
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($c) => $this->presentConversation($c));

        return Inertia::render('Messages/Index', [
            'conversations' => $conversations,
        ]);
    }

    public function show(Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $this->service->markAsRead($conversation, auth()->user());

        $conversation->load([
            'buyer:id,name,profile_photo_path',
            'seller:id,name,profile_photo_path',
            'order:id,order_number,status,total,shop_id,user_id',
            'order.shop:id,name',
            'messages.sender:id,name,profile_photo_path',
        ]);

        $userId = auth()->id();

        $allConversations = Conversation::query()
            ->where('buyer_id', $userId)
            ->orWhere('seller_user_id', $userId)
            ->with([
                'buyer:id,name,profile_photo_path',
                'seller:id,name,profile_photo_path',
                'order:id,order_number,status,total,shop_id',
                'order.shop:id,name',
                'latestMessage',
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($c) => $this->presentConversation($c));

        return Inertia::render('Messages/Show', [
            'conversations' => $allConversations,
            'conversation' => [
                'id' => $conversation->id,
                'order' => [
                    'id' => $conversation->order->id,
                    'order_number' => $conversation->order->order_number,
                    'status' => $conversation->order->status,
                    'total' => $conversation->order->total,
                    'shop_name' => $conversation->order->shop->name,
                ],
                'other_user' => $conversation->otherParticipant(auth()->user())->only([
                    'id', 'name', 'profile_photo_url',
                ]),
                'messages' => $conversation->messages->map(fn ($m) => [
                    'id' => $m->id,
                    'sender_id' => $m->sender_id,
                    'body' => $m->body,
                    'image_path' => $m->image_path,
                    'read_at' => $m->read_at,
                    'created_at' => $m->created_at,
                    'sender' => [
                        'id' => $m->sender->id,
                        'name' => $m->sender->name,
                        'profile_photo_url' => $m->sender->profile_photo_url,
                    ],
                ]),
            ],
        ]);
    }

    public function storeMessage(Request $request, Conversation $conversation)
    {
        $this->authorize('sendMessage', $conversation);

        $validated = $request->validate([
            'body' => 'nullable|string|max:5000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if (blank($validated['body'] ?? null) && ! $request->hasFile('image')) {
            return back()->withErrors(['body' => 'Message cannot be empty.']);
        }

        $this->service->sendMessage(
            $conversation,
            auth()->user(),
            $validated['body'] ?? null,
            $request->file('image'),
        );

        return back();
    }

    public function markAsRead(Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $this->service->markAsRead($conversation, auth()->user());

        return back();
    }

    private function presentConversation(Conversation $c): array
    {
        $user = auth()->user();
        $other = $c->otherParticipant($user);

        return [
            'id' => $c->id,
            'last_message_at' => $c->last_message_at,
            'order' => [
                'order_number' => $c->order->order_number,
                'status' => $c->order->status,
                'shop_name' => $c->order->shop->name,
            ],
            'other_user' => [
                'id' => $other->id,
                'name' => $other->name,
                'profile_photo_url' => $other->profile_photo_url,
            ],
            'latest_message' => $c->latestMessage ? [
                'body' => $c->latestMessage->body,
                'image_path' => $c->latestMessage->image_path,
                'sender_id' => $c->latestMessage->sender_id,
                'created_at' => $c->latestMessage->created_at,
            ] : null,
            'unread_count' => $c->unreadCountFor($user),
        ];
    }
}
