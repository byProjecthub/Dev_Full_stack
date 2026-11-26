<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
requireClient();
// Billing and payment actions are handled by the existing invoice screens.
redirect('invoices.php');
