<?php

namespace App\Exceptions;

use RuntimeException;

/** A voting rule was not satisfied. The message is safe to show to users. */
class VotingException extends RuntimeException {}
