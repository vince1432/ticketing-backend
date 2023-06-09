<?php

namespace App\Services;

use App\Contract\TicketRepositoryInterface;
use App\Contract\TicketServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class TicketService implements TicketServiceInterface
{
    private $ticket_repository;
    // response status
    public $status = 200;

    public function __construct( TicketRepositoryInterface $ticket_repository) {
        $this->ticket_repository = $ticket_repository;
    }

    public function index($count = 10)
    {
        $data = $this->ticket_repository->all($count);
        return $data->toArray();
    }

    public function show($id)
    {
        if(!$this->ticket_repository->exist($id)) {
            $this->status = 404;
            return array();
        }

        $ticket = $this->ticket_repository->get($id);

        return $ticket->toArray();
    }

    public function store($request)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'summary' => 'required|min:1|max:1000',
            'priority_id' => 'required|exists:ticket_prioties,id',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $new_ticket = $this->ticket_repository->insert($validated);

        return $new_ticket->toArray();
    }

    public function update($request, $id)
    {
        $validated = $request->validate([
            'title' => 'nullable|max:255',
            'summary' => 'nullable|min:1|max:1000',
            'priority_id' => 'nullable|exists:ticket_prioties,id',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        if(!$this->ticket_repository->exist($id)) {
            $this->status = 404;
            return array();
        }

        $ticket = $this->ticket_repository->update($validated, $id);

        return $ticket->toArray();
    }

    public function destroy($id) {
        $ticket = $this->ticket_repository->delete($id);

        return $ticket;
    }

    public function close($request, $id) {
        $validated = $request->validate([
            'resolution' => 'required|min:1|max:1000',
        ]);

        if(!$this->ticket_repository->exist($id)) {
            $this->status = 404;
            return array();
        }

        $validated['closed_by'] = auth()->user()->id;
        $validated['closed_at'] = Carbon::now();

        $ticket = $this->ticket_repository->update($validated, $id);

        return $ticket->toArray();
    }

    public function comments($request)
    {
        $query_params = request()->query();
        $item_count = $query_params['item_count'] ?? 0;

        $validated = $request->validate([
            'ticket_id' => 'required|exists:tickets,id'
        ]);

        $data = $this->ticket_repository->comments($validated['ticket_id'], $item_count);

        return $data->toArray();
    }

    public function comment($id)
    {
        if (!$this->ticket_repository->commentExist($id)) {
            $this->status = 404;
            return array();
        }

        $comment = $this->ticket_repository->comment($id);

        return $comment->toArray();
    }

    public function addComment($request) {
        $validated = $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'comment' => 'required|min:1|max:1000'
        ]);

        if(!$this->ticket_repository->exist($validated['ticket_id'])) {
            $this->status = 404;
            return array();
        }

        $validated['commenter_id'] = auth()->user()->id;

        $comment = $this->ticket_repository->addComment($validated);
        $this->status = 201;

        return $comment->toArray();
    }

    public function updateComment($request, $id) {
        $validated = $request->validate([
            'comment' => 'required|min:1|max:1000'
        ]);

        if(!$this->ticket_repository->commentExist($id)) {
            $this->status = 404;
            return array();
        }

        $comment = $this->ticket_repository->updateComment($validated, $id);

        return $comment->toArray();
    }

    public function removeComment($id)
    {
        $comment = $this->ticket_repository->removeComment($id);

        return $comment;
    }
}
