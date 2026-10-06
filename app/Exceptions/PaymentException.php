<?php

namespace App\Exceptions;

use RuntimeException;

/** A payment could not be initialised or confirmed. The message is safe to show to users. */
class PaymentException extends RuntimeException {}
