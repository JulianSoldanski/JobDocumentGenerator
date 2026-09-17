<?php

namespace Tests\Unit\Ai;

use App\Ai\SelectionFilter;
use Tests\TestCase;

/**
 * Prinzip 1: Was die KI an IDs zurückgibt, wird gegen die erlaubte Liste
 * geprüft. Erfundenes wird verworfen, eine leere Auswahl fällt auf die
 * vollständige Liste zurück.
 */
class SelectionFilterTest extends TestCase
{
    public function test_invented_ids_are_dropped_and_order_is_kept(): void
    {
        $filtered = SelectionFilter::filter(['3', 'erfunden', '1'], ['1', '2', '3']);

        $this->assertSame(['3', '1'], $filtered);
    }

    public function test_duplicates_are_dropped(): void
    {
        $this->assertSame(['1'], SelectionFilter::filter(['1', '1'], ['1', '2']));
    }

    public function test_objects_instead_of_plain_ids_are_understood(): void
    {
        $this->assertSame(['2'], SelectionFilter::filter([['id' => '2']], ['1', '2']));
    }

    public function test_numeric_ids_are_accepted(): void
    {
        $this->assertSame(['2'], SelectionFilter::filter([2], ['1', '2']));
    }

    public function test_nonsense_yields_nothing(): void
    {
        $this->assertSame([], SelectionFilter::filter('keine Liste', ['1']));
        $this->assertSame([], SelectionFilter::filter([null, false, 7], ['1']));
    }

    public function test_an_unusable_selection_falls_back_to_the_full_list(): void
    {
        $this->assertSame(['1', '2'], SelectionFilter::filterOrAll(['erfunden'], ['1', '2']));
        $this->assertSame(['1', '2'], SelectionFilter::filterOrAll([], ['1', '2']));
        $this->assertSame(['1', '2'], SelectionFilter::filterOrAll(null, ['1', '2']));
    }

    public function test_a_valid_selection_wins_over_the_fallback(): void
    {
        $this->assertSame(['2'], SelectionFilter::filterOrAll(['2'], ['1', '2']));
    }
}
