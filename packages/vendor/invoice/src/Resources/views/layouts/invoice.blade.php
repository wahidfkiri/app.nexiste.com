@extends('layouts.global')

@push('scripts')
<script>
window.InvoiceLang = Object.assign(window.InvoiceLang || {}, {
  successTitle: @json(__('invoice::invoices.js.success_title')),
  errorTitle: @json(__('invoice::invoices.js.error_title')),
  warningTitle: @json(__('invoice::invoices.js.warning_title')),
  validationTitle: @json(__('invoice::invoices.js.validation_title')),
  loadError: @json(__('invoice::invoices.js.load_error')),
  emptyDefaultLabel: @json(__('invoice::invoices.js.empty_default_label')),
  emptyInvoiceLabel: @json(__('invoice::invoices.js.empty_invoice_label')),
  emptyQuoteLabel: @json(__('invoice::invoices.js.empty_quote_label')),
  emptyPaymentLabel: @json(__('invoice::invoices.js.empty_payment_label')),
  emptyTitleTemplate: @json(__('invoice::invoices.js.empty_title_template')),
  emptyHelp: @json(__('invoice::invoices.js.empty_help')),
  paidLockTitle: @json(__('invoice::invoices.js.invoice_paid_action_locked')),
  referencePrefix: @json(__('invoice::invoices.js.reference_prefix', ['reference' => ''])),
  settledLabel: @json(__('invoice::invoices.js.invoice_settled')),
  convertedBadge: @json(__('invoice::invoices.js.quote_converted_badge')),
  viewInvoiceTitle: @json(__('invoice::invoices.js.view_invoice_title')),
  paginationInfo: {!! json_encode(__('invoice::invoices.js.pagination_info', ['from' => ':from', 'to' => ':to', 'total' => ':total']), 15, 512) !!},
  countLabel: @json(__('invoice::invoices.js.count_label', ['total' => ':total'])),
  irreversibleAction: @json(__('invoice::invoices.alerts.irreversible')),
  paymentRecalculation: @json(__('invoice::invoices.alerts.payment_recalculates_invoice')),
  lineDescriptionPlaceholder: @json(__('invoice::invoices.js.line_description_placeholder')),
  optionalReferencePlaceholder: @json(__('invoice::invoices.js.optional_reference_placeholder')),
  unitPlaceholder: @json(__('invoice::invoices.js.unit_placeholder')),
  minOneLine: @json(__('invoice::invoices.alerts.at_least_one_line')),
  deleteConfirmText: @json(__('invoice::invoices.actions.delete')),
  invoiceDeleteTitle: @json(__('invoice::invoices.js.invoice_delete_title', ['number' => ':number'])),
  paymentDeleteTitle: @json(__('invoice::invoices.js.payment_delete_title')),
});
window.INVOICE_STATUS_LABELS = @json(__('invoice::invoices.status'));
window.QUOTE_STATUS_LABELS = @json(__('invoice::invoices.quote_status'));
</script>
@endpush
