<?php

namespace App\Services;

/**
 * Thrown inside the import transaction to roll it back when rows failed.
 */
class ImportRolledBackException extends \RuntimeException {}
