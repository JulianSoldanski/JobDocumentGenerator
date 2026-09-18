<?php

namespace App\Support\JobPosting;

use RuntimeException;

/**
 * Eine Seite ließ sich nicht laden. Die Meldung ist für den Nutzer gedacht: Er
 * kann die Anzeige stattdessen als Text einfügen.
 */
class PostingFetchException extends RuntimeException {}
