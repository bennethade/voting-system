<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ContactReplyRequest;
use App\Mail\ContactReplyMail;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactMessageController extends AdminController
{
    public function index(Request $request): View
    {
        $query = ContactMessage::query()
            ->select(['id', 'name', 'email', 'subject', 'status', 'replied_at', 'created_at'])
            ->when($this->like($request->query('q')), fn ($q, $term) => $q->where(fn ($w) => $w
                ->where('name', 'like', $term)
                ->orWhere('email', 'like', $term)
                ->orWhere('subject', 'like', $term)))
            ->when(in_array($request->query('status'), ContactMessage::STATUSES, true), fn ($q) => $q->where('status', $request->query('status')));

        $this->applyDateRange($query, $request);

        $messages = $this->applySort($query, $request, ['name' => 'name', 'subject' => 'subject', 'created_at' => 'created_at'])
            ->paginate(25)
            ->withQueryString();

        $unread = ContactMessage::query()->where('status', 'new')->count();

        return view('admin.messages.index', compact('messages', 'unread'));
    }

    public function show(ContactMessage $contactMessage): View
    {
        if ($contactMessage->status === 'new') {
            $contactMessage->forceFill(['status' => 'read'])->save();
        }

        $contactMessage->load('replier:id,name');

        return view('admin.messages.show', ['msg' => $contactMessage]);
    }

    public function reply(ContactReplyRequest $request, ContactMessage $contactMessage): RedirectResponse
    {
        $old = $contactMessage->only(['status', 'reply', 'replied_by', 'replied_at']);

        $contactMessage->forceFill([
            'reply' => $request->validated('reply'),
            'replied_by' => $request->user()->id,
            'replied_at' => now(),
            'status' => 'replied',
        ])->save();

        Mail::to($contactMessage->email)->send(new ContactReplyMail($contactMessage));

        $this->audit->log(
            'contact_message.replied',
            "Replied to contact message from {$contactMessage->email}",
            $contactMessage,
            $old,
            $contactMessage->only(['status', 'reply', 'replied_by', 'replied_at']),
        );

        return redirect()->route('admin.messages.show', $contactMessage)->with('success', 'Reply sent to '.$contactMessage->email.'.');
    }

    public function updateStatus(Request $request, ContactMessage $contactMessage): RedirectResponse
    {
        $request->validate(['status' => ['required', Rule::in(ContactMessage::STATUSES)]]);

        $old = $contactMessage->status;
        $new = $request->input('status');

        if ($old !== $new) {
            $contactMessage->forceFill(['status' => $new])->save();
            $this->audit->log('contact_message.status', "Changed contact message #{$contactMessage->id} status from {$old} to {$new}", $contactMessage, ['status' => $old], ['status' => $new]);
        }

        return back()->with('success', 'Message marked as '.$new.'.');
    }
}
