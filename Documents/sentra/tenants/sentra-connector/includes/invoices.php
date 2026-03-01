<?php
if (!defined('ABSPATH')) exit;

/**
 * Jobs + Invoices foundation with ValorPay placeholders.
 *
 * This module is intentionally adapter-first:
 * - Works with tenant-scoped Sentra endpoints when available.
 * - Exposes filters/placeholders for ValorPay processing integration.
 */

if (!function_exists('sentrasystems_tenant_path')) {
    function sentrasystems_tenant_path($resource = '') {
        $cfg = sentrasystems_config();
        $tenant = trim((string)($cfg['tenant_id'] ?? ''));
        $resource = ltrim((string)$resource, '/');
        return 'api/tenants/' . rawurlencode($tenant) . ($resource ? '/' . $resource : '');
    }
}

if (!function_exists('sentrasystems_core_request_with_fallback')) {
    function sentrasystems_core_request_with_fallback($path, $method = 'GET', $body = null, $query = []) {
        $cfg = sentrasystems_config();
        $args = [
            'method' => strtoupper((string)$method),
            'signed' => false,
        ];
        if ($body !== null) {
            $args['body'] = $body;
        }

        $resp = sentrasystems_request($cfg['base'], $path, is_array($query) ? $query : [], $args);
        if (is_wp_error($resp) && !empty($cfg['site_id']) && !empty($cfg['site_secret'])) {
            $args['signed'] = true;
            $resp = sentrasystems_request($cfg['base'], $path, is_array($query) ? $query : [], $args);
        }

        return $resp;
    }
}

if (!function_exists('sentrasystems_jobs_list')) {
    function sentrasystems_jobs_list($query = []) {
        $path = apply_filters('sentrasystems_jobs_path', sentrasystems_tenant_path('jobs'));
        $resp = sentrasystems_core_request_with_fallback($path, 'GET', null, is_array($query) ? $query : []);
        if (is_wp_error($resp)) return $resp;

        if (isset($resp['items']) && is_array($resp['items'])) return $resp['items'];
        if (isset($resp['jobs']) && is_array($resp['jobs'])) return $resp['jobs'];
        return [];
    }
}

if (!function_exists('sentrasystems_invoices_list')) {
    function sentrasystems_invoices_list($query = []) {
        $path = apply_filters('sentrasystems_invoices_path', sentrasystems_tenant_path('invoices'));
        $resp = sentrasystems_core_request_with_fallback($path, 'GET', null, is_array($query) ? $query : []);
        if (is_wp_error($resp)) return $resp;

        if (isset($resp['items']) && is_array($resp['items'])) return $resp['items'];
        if (isset($resp['invoices']) && is_array($resp['invoices'])) return $resp['invoices'];
        return [];
    }
}

if (!function_exists('sentrasystems_invoice_create')) {
    function sentrasystems_invoice_create($job_id, $invoice = []) {
        $job_id = trim((string)$job_id);
        if ($job_id === '') {
            return new WP_Error('sentrasystems_invoice_missing_job', 'job_id is required');
        }

        $payload = is_array($invoice) ? $invoice : [];
        $payload['job_id'] = $job_id;

        $path = apply_filters('sentrasystems_invoices_path', sentrasystems_tenant_path('invoices'));
        return sentrasystems_core_request_with_fallback($path, 'POST', $payload, []);
    }
}

if (!function_exists('sentrasystems_invoice_attach_payment')) {
    function sentrasystems_invoice_attach_payment($invoice_id, $payment = []) {
        $invoice_id = trim((string)$invoice_id);
        if ($invoice_id === '') {
            return new WP_Error('sentrasystems_invoice_missing_id', 'invoice_id is required');
        }

        $path = apply_filters(
            'sentrasystems_invoice_payment_path',
            sentrasystems_tenant_path('invoices/' . rawurlencode($invoice_id) . '/payments'),
            $invoice_id
        );

        return sentrasystems_core_request_with_fallback($path, 'POST', is_array($payment) ? $payment : [], []);
    }
}

if (!function_exists('sentrasystems_valorpay_enabled')) {
    function sentrasystems_valorpay_enabled() {
        $cfg = sentrasystems_config();
        return !empty($cfg['valorpay_enabled']) || (bool) apply_filters('sentrasystems_valorpay_enabled', false);
    }
}

if (!function_exists('sentrasystems_valorpay_prepare_invoice_payload')) {
    function sentrasystems_valorpay_prepare_invoice_payload($invoice = [], $customer = []) {
        $payload = [
            'invoice' => is_array($invoice) ? $invoice : [],
            'customer' => is_array($customer) ? $customer : [],
            'meta' => [
                'source' => 'sentra',
                'tenant_id' => (string) (sentrasystems_config()['tenant_id'] ?? ''),
                'created_at' => time(),
            ],
        ];

        return apply_filters('sentrasystems_valorpay_invoice_payload', $payload, $invoice, $customer);
    }
}

if (!function_exists('sentrasystems_valorpay_create_payment_intent')) {
    function sentrasystems_valorpay_create_payment_intent($invoice = [], $customer = []) {
        $payload = sentrasystems_valorpay_prepare_invoice_payload($invoice, $customer);

        if (!sentrasystems_valorpay_enabled()) {
            return new WP_Error(
                'sentrasystems_valorpay_not_enabled',
                'ValorPay integration is not enabled yet.',
                ['payload' => $payload]
            );
        }

        /**
         * Placeholder hook for real ValorPay transport.
         * Return array on success or WP_Error on failure.
         */
        $result = apply_filters('sentrasystems_valorpay_create_intent', null, $payload, $invoice, $customer);
        if (is_array($result)) {
            return $result;
        }
        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_Error(
            'sentrasystems_valorpay_not_implemented',
            'ValorPay intent creation hook is not implemented.',
            ['payload' => $payload]
        );
    }
}
