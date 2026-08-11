<?php

namespace App\Http\Controllers\Admin\V1;

use App\Enums\ContactMessageStatus;
use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactMessagesWebController extends Controller
{
    public function index(Request $request): View
    {
        $folder = $request->string('folder')->toString() ?: 'inbox';
        if (! in_array($folder, ['inbox', 'unread', 'read', 'replied'], true)) {
            $folder = 'inbox';
        }

        $search = trim((string) $request->input('q', ''));

        $messages = ContactMessage::query()
            ->with('user')
            ->when($folder === 'unread', fn ($query) => $query->where('status', ContactMessageStatus::Pending))
            ->when($folder === 'read', fn ($query) => $query->where('status', ContactMessageStatus::Read))
            ->when($folder === 'replied', fn ($query) => $query->where('status', ContactMessageStatus::Replied))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $like = '%'.$search.'%';
                    $inner->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('subject', 'like', $like)
                        ->orWhere('message', 'like', $like);
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'inbox' => ContactMessage::query()->count(),
            'unread' => ContactMessage::query()->where('status', ContactMessageStatus::Pending)->count(),
            'read' => ContactMessage::query()->where('status', ContactMessageStatus::Read)->count(),
            'replied' => ContactMessage::query()->where('status', ContactMessageStatus::Replied)->count(),
        ];

        return view('pages.admin.contact-messages.index', [
            'title' => 'Inbox',
            'messages' => $messages,
            'folder' => $folder,
            'search' => $search,
            'counts' => $counts,
        ]);
    }

    public function show(ContactMessage $contact_message): View
    {
        if ($contact_message->status === ContactMessageStatus::Pending) {
            $contact_message->update(['status' => ContactMessageStatus::Read]);
        }

        $contact_message->load('user');

        return view('pages.admin.contact-messages.show', [
            'title' => $contact_message->subject,
            'message' => $contact_message,
        ]);
    }

    public function markReplied(ContactMessage $contact_message): RedirectResponse
    {
        $contact_message->update(['status' => ContactMessageStatus::Replied]);

        return redirect()
            ->route('admin.contact-messages.show', $contact_message)
            ->with('success', 'Message marked as replied.');
    }

    public function markUnread(ContactMessage $contact_message): RedirectResponse
    {
        $contact_message->update(['status' => ContactMessageStatus::Pending]);

        return redirect()
            ->route('admin.contact-messages.index')
            ->with('success', 'Message marked as unread.');
    }

    public function destroy(ContactMessage $contact_message): RedirectResponse
    {
        $contact_message->delete();

        return redirect()
            ->route('admin.contact-messages.index')
            ->with('success', 'Message deleted.');
    }
}
