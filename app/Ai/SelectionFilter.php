<?php

namespace App\Ai;

/**
 * Was die KI an IDs zurückgibt, wird gegen die erlaubte Liste geprüft.
 *
 * Erfundenes wird verworfen. Kommt eine leere oder unbrauchbare Auswahl
 * zurück, greift die vollständige Liste als Rückfallebene — eine schlechte
 * Auswahl kann nie stillschweigend einen ganzen Abschnitt vom Lebenslauf
 * entfernen.
 */
class SelectionFilter
{
    /**
     * Die zulässigen IDs in der Reihenfolge, die das Modell gewählt hat.
     *
     * @param  mixed  $selected
     * @param  array<int, string>  $allowed
     * @return array<int, string>
     */
    public static function filter($selected, array $allowed): array
    {
        if (! is_array($selected)) {
            return [];
        }

        $allowed = array_map(strval(...), $allowed);
        $result = [];

        foreach ($selected as $id) {
            // Manche Modelle antworten mit Objekten statt mit blanken IDs.
            if (is_array($id)) {
                $id = $id['id'] ?? null;
            }

            if (is_int($id)) {
                $id = (string) $id;
            }

            if (is_string($id) && in_array($id, $allowed, true) && ! in_array($id, $result, true)) {
                $result[] = $id;
            }
        }

        return $result;
    }

    /**
     * Wie `filter`, aber mit der vollständigen Liste als Rückfallebene.
     *
     * @param  mixed  $selected
     * @param  array<int, string>  $allowed
     * @return array<int, string>
     */
    public static function filterOrAll($selected, array $allowed): array
    {
        $filtered = self::filter($selected, $allowed);

        return $filtered !== [] ? $filtered : array_values(array_map(strval(...), $allowed));
    }
}
