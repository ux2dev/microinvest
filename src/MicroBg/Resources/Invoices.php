<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\MicroBg\Resources;

use Ux2Dev\Microinvest\Dto\Input\Documents\InvoiceInput;
use Ux2Dev\Microinvest\Dto\Result\Documents\InvoiceResult;
use Ux2Dev\Microinvest\Exception\ConfigurationException;

/**
 * Printable documents issued against an operation. micro.bg only.
 */
final class Invoices extends Resource
{
    /**
     * Issue an invoice, credit note or debit note. Pass $byExtAppDocId when the
     * operation is identified by the external application's own id rather than
     * by its micro.bg id.
     */
    public function create(InvoiceInput $input, bool $byExtAppDocId = false): InvoiceResult
    {
        if ($input->operationId === null && $input->extAppDocId === null) {
            throw new ConfigurationException('issuing a document needs either an operationId or an extAppDocId');
        }

        return $this->transport->callOne(
            'createInvoice',
            $byExtAppDocId ? ['ByExtAppDocId' => 1] : [],
            $input->toMicroBgArray(),
            InvoiceResult::class,
        );
    }
}
