<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Product;
use App\Services\ConversationService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;

class ConversationController extends Controller
{
    public function __construct(private ConversationService $service) {}

    public function index()
    {
        return Inertia::render('Messages/Index', [
            'conversations' => $this->conversationsFor(auth()->id()),
        ]);
    }

    public function askAboutProduct(Product $product)
    {
        abort_unless($product->status === Product::STATUS_ACTIVE, 404);

        $product->loadMissing('shop');
        abort_if($product->shop->user_id === auth()->id(), 403);

        $conversation = $this->service->getOrCreateForProduct($product, auth()->user());
        $this->service->sendMessage($conversation, auth()->user(), null, null, $product);

        return redirect()->route('messages.show', $conversation);
    }

    public function show(Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $this->service->markAsRead($conversation, auth()->user());

        $conversation->load([
            'buyer:id,name,profile_photo_path',
            'seller:id,name,profile_photo_path',
            'seller.shop:id,user_id,name',
            'order:id,order_number,status,total',
            'messages.sender:id,name,profile_photo_path',
            'messages.product:id,name,slug,price',
            'messages.product.primaryImage',
        ]);

        return Inertia::render('Messages/Show', [
            'conversations' => $this->conversationsFor(auth()->id()),
            'conversation' => [
                'id' => $conversation->id,
                'shop_name' => $conversation->seller->shop->name,
                'order' => $conversation->order ? [
                    'id' => $conversation->order->id,
                    'order_number' => $conversation->order->order_number,
                    'status' => $conversation->order->status,
                    'total' => $conversation->order->total,
                ] : null,
                'other_user' => $conversation->otherParticipant(auth()->user())->only([
                    'id', 'name', 'profile_photo_url',
                ]),
                'messages' => $conversation->messages->map->toChatPayload(),
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

    private function conversationsFor(int $userId): Collection
    {
        return Conversation::query()
            ->where('buyer_id', $userId)
            ->orWhere('seller_user_id', $userId)
            ->with([
                'buyer:id,name,profile_photo_path',
                'seller:id,name,profile_photo_path',
                'seller.shop:id,user_id,name',
                'order:id,order_number,status,total',
                'latestMessage',
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($c) => $this->presentConversation($c));
    }

    private function presentConversation(Conversation $c): array
    {
        $user = auth()->user();
        $other = $c->otherParticipant($user);

        return [
            'id' => $c->id,
            'last_message_at' => $c->last_message_at,
            'shop_name' => $c->seller->shop->name,
            'order' => $c->order ? [
                'order_number' => $c->order->order_number,
                'status' => $c->order->status,
            ] : null,
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
                'product_id' => $c->latestMessage->product_id,
            ] : null,
            'unread_count' => $c->unreadCountFor($user),
        ];
    }
}
