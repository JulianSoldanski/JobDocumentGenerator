<?php

namespace Tests\Unit\JobPosting;

use App\Support\JobPosting\PostingUrl;
use PHPUnit\Framework\TestCase;

/**
 * Dieselbe Stelle, über LinkedIn, Google und einen Newsletter gefunden, ist
 * ohne die Anhängsel dieselbe Adresse.
 */
class PostingUrlTest extends TestCase
{
    public function test_tracking_parameters_fall_away(): void
    {
        $this->assertSame(
            'https://www.linkedin.com/jobs/view/4012345678/',
            PostingUrl::clean('https://www.linkedin.com/jobs/view/4012345678/?trackingId=abc%3D%3D&refId=xyz&trk=public_jobs&utm_source=newsletter'),
        );
    }

    public function test_parameters_that_name_the_job_stay(): void
    {
        $this->assertSame(
            'https://boards.greenhouse.io/firma/jobs?gh_jid=123&a.b=1',
            PostingUrl::clean('https://boards.greenhouse.io/firma/jobs?gh_src=abc&gh_jid=123&a.b=1'),
        );
    }

    public function test_host_case_and_anchors_do_not_make_a_new_job(): void
    {
        $this->assertSame(
            'https://karriere.example.de/stelle/42',
            PostingUrl::clean('  HTTPS://Karriere.Example.DE/stelle/42?utm_medium=cpc#bewerben '),
        );
    }

    public function test_something_that_is_no_web_address_is_left_alone(): void
    {
        $this->assertSame('javascript:alert(1)', PostingUrl::clean('javascript:alert(1)'));
    }
}
