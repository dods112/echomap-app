<?php

namespace App\Http\Controllers;

use App\Models\Pin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PinController extends Controller
{
    public const THEMES = ['Campus Life', 'Friendship', 'History'];
    public const VISIBILITIES = ['Public', 'Friends', 'Private'];

    private function symbols(): array
    {
        return ["\u{2665}", "\u{2726}", "\u{263B}"];
    }

    public function index(Request $request): JsonResponse
    {
        $pins = Pin::with('user:id,name')
            ->visibleTo($request->user())
            ->orderBy('created_at')
            ->get()
            ->map(fn (Pin $p) => $this->summary($p))
            ->values();

        return response()->json($pins);
    }

    public function show(Request $request, Pin $pin): JsonResponse
    {
        $this->ensureVisible($request, $pin);
        $pin->load(['user:id,name', 'comments.user:id,name', 'reactions']);

        $mine = $request->user()
            ? $pin->reactions->where('user_id', $request->user()->id)->pluck('symbol')->values()
            : [];

        return response()->json($this->summary($pin) + [
            'body' => $pin->body,
            'date' => $pin->created_at->format('F j, Y'),
            'reactions' => $pin->reactions->groupBy('symbol')->map->count(),
            'mine' => $mine,
            'comments' => $pin->comments->sortBy('created_at')->values()->map(fn ($c) => [
                'id' => $c->id,
                'author' => $c->user->name,
                'initials' => $c->user->initials,
                'body' => $c->body,
                'when' => $c->created_at->diffForHumans(),
            ]),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:70'],
            'place' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:500'],
            'theme' => ['required', Rule::in(self::THEMES)],
            'visibility' => ['required', Rule::in(self::VISIBILITIES)],
            'lat' => ['required', 'numeric', 'between:'.config('campus.bounds.south').','.config('campus.bounds.north')],
            'lng' => ['required', 'numeric', 'between:'.config('campus.bounds.west').','.config('campus.bounds.east')],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('pins', 'public');
        }
        unset($data['photo']);

        $pin = $request->user()->pins()->create($data);

        return response()->json($this->summary($pin->load('user:id,name')), 201);
    }

    public function comment(Request $request, Pin $pin): JsonResponse
    {
        $this->ensureVisible($request, $pin);
        $data = $request->validate(['body' => ['required', 'string', 'max:240']]);
        $pin->comments()->create(['user_id' => $request->user()->id, 'body' => $data['body']]);

        return response()->json(['ok' => true], 201);
    }

    public function react(Request $request, Pin $pin): JsonResponse
    {
        $this->ensureVisible($request, $pin);
        $data = $request->validate(['symbol' => ['required', Rule::in($this->symbols())]]);

        $existing = $pin->reactions()
            ->where('user_id', $request->user()->id)
            ->where('symbol', $data['symbol'])
            ->first();

        $existing
            ? $existing->delete()
            : $pin->reactions()->create(['user_id' => $request->user()->id, 'symbol' => $data['symbol']]);

        return response()->json(['ok' => true]);
    }

    private function ensureVisible(Request $request, Pin $pin): void
    {
        abort_unless(Pin::visibleTo($request->user())->whereKey($pin->id)->exists(), 404);
    }

    private function summary(Pin $p): array
    {
        return [
            'id' => $p->id,
            'title' => $p->title,
            'place' => $p->place,
            'theme' => $p->theme,
            'visibility' => $p->visibility,
            'lat' => $p->lat,
            'lng' => $p->lng,
            'year' => $p->created_at->year,
            'author' => $p->user->name,
            'initials' => $p->user->initials,
            'photo_url' => $p->photo_url,
        ];
    }
}