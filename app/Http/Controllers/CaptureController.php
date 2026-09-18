<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\JobPosting\PostingUrl;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Das Ziel des Bookmarklets: Es öffnet diese Seite in einem kleinen Fenster,
 * die Stelle landet in der Queue, das Fenster schließt sich wieder.
 *
 * Angemeldet wird über das Token im Link, nicht über die Sitzung — so klappt
 * es auch, wenn die App gerade nicht offen ist. Ein neues Token (in der Queue)
 * macht alte Links ungültig.
 */
class CaptureController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = User::where('capture_token', (string) $request->query('token'))->first();
        $url = PostingUrl::clean((string) $request->query('url'));

        if ($user === null || $request->query('token') === null) {
            return $this->answer('Dieser Link ist nicht mehr gültig. Hol dir das Bookmarklet neu aus der Queue.', false);
        }

        // Nur echte Webseiten: Die Liste zeigt die Adresse als Link.
        if (! preg_match('#^https?://#i', $url) || filter_var($url, FILTER_VALIDATE_URL) === false || mb_strlen($url) > 500) {
            return $this->answer('Diese Seite lässt sich nicht merken.', false);
        }

        $item = $user->queueItems()->firstOrCreate(
            ['url' => $url],
            ['title' => mb_substr(trim((string) $request->query('title')), 0, 255)],
        );

        return $this->answer(
            $item->wasRecentlyCreated ? 'In der Queue.' : 'Schon in der Queue ('.$item->status->label().').',
            true,
        );
    }

    private function answer(string $message, bool $ok): View
    {
        return view('capture', ['message' => $message, 'ok' => $ok]);
    }
}
