<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Alles hängt am Konto: Profil, Projekte, Bewerbungen, Queue, Dokumente.
 *
 * Diese Richtlinie prüft für jedes dieser Modelle dieselbe Frage — gehört der
 * Datensatz dem angemeldeten Nutzer? Eine Richtlinie je Modell wäre sechsmal
 * derselbe Vergleich.
 */
class OwnerPolicy
{
    public function view(User $user, Model $model): bool
    {
        return $this->owns($user, $model);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->owns($user, $model);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->owns($user, $model);
    }

    private function owns(User $user, Model $model): bool
    {
        return (int) $model->getAttribute('user_id') === (int) $user->getKey();
    }
}
