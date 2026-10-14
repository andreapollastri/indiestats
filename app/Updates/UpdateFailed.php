<?php

namespace App\Updates;

use RuntimeException;

/** A step of an update failed; the message says which, in a sentence for the admin page. */
final class UpdateFailed extends RuntimeException {}
