<?php
/**
 * Copyright (C) 2023 Svea Payments Oy
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU Lesser General Public License (LGPLv2.1)
 * that is bundled with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/lgpl-2.1.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to info@maksuturva.fi so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    Svea Payments Oy <info@svea.fi>
 * @copyright 2023 Svea Payments Oy
 * @license   https://www.gnu.org/licenses/lgpl-2.1.html GNU Lesser General Public License (LGPLv2.1)
 */

namespace Maksuturva\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentAttemptsController extends FrameworkBundleAdminController
{
    /**
     * List all payment attempts
     */
    public function indexAction(Request $request): Response
    {
        $filters = $this->getFilters($request);
        $page = max(1, (int) $request->query->get('page', 1));
        $pageSize = 100;

        $totalRecords = $this->getTotalPaymentAttempts($filters);
        $totalPages = max(1, (int) ceil($totalRecords / $pageSize));
        $page = min($page, $totalPages);

        $payments = $this->getPaymentAttempts($filters, $page, $pageSize);

        $csrf_token = \Tools::getAdminTokenLite('AdminMaksuturvaPaymentAttempts');

        return $this->render('@Modules/maksuturva/views/templates/admin/payment_attempts_list.html.twig', [
            'csrf_token' => $csrf_token,
            'payments' => $payments,
            'filters' => $filters,
            'statusOptions' => $this->getStatusOptions(),
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_records' => $totalRecords,
                'page_size' => $pageSize,
            ],
        ]);
    }

    /**
     * View single payment attempt
     */
    public function viewAction(int $id): Response
    {
        try {
            require_once _PS_MODULE_DIR_ . 'maksuturva/includes/MaksuturvaPayment.php';
            require_once _PS_MODULE_DIR_ . 'maksuturva/includes/MaksuturvaException.php';

            $payment = new \MaksuturvaPayment($id, 'id_mt_payment');

            return $this->render('@Modules/maksuturva/views/templates/admin/payment_attempt_view.html.twig', [
                'payment' => [
                    'id' => $id,
                    'cart_id' => $payment->getCartId(),
                    'order_id' => $payment->getOrderId(),
                    'attempt' => $payment->getAttempt(),
                    'pmt_id' => $payment->getPmtId(),
                    'status' => $this->getStatusLabel($payment->getStatus()),
                    'date_add' => $payment->getDateAdd(),
                ],
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', $this->trans('Payment attempt not found', 'Modules.Maksuturva.Admin'));
            return $this->redirectToRoute('maksuturva_payment_attempts');
        }
    }

    /**
     * Get filters from request
     */
    protected function getFilters(Request $request): array
    {
        return [
            'cart_id' => $request->query->get('cart_id', ''),
            'order_id' => $request->query->get('order_id', ''),
            'pmt_id' => $request->query->get('pmt_id', ''),
            'status' => $request->query->get('status', ''),
            'date_from' => $request->query->get('date_from', ''),
            'date_to' => $request->query->get('date_to', ''),
        ];
    }

    /**
     * Get payment attempts based on filters
     */
    protected function getPaymentAttempts(array $filters, int $page, int $pageSize): array
    {
        $db = \Db::getInstance();
        $query = new \DbQuery();

        $query->select('*');
        $query->from('mt_payment');
        $this->applyFilters($query, $filters);
        $query->orderBy('date_add DESC, id_mt_payment DESC');
        $query->limit($pageSize, ($page - 1) * $pageSize);

        $results = $db->executeS($query);

        if (!$results) {
            return [];
        }

        // Format results
        $payments = [];
        foreach ($results as $row) {
            $payments[] = [
                'id' => $row['id_mt_payment'],
                'cart_id' => $row['id_cart'],
                'order_id' => $row['id_order'],
                'attempt' => $row['attempt'],
                'pmt_id' => $row['pmt_id'],
                'status' => $this->getStatusLabel((int) $row['status']),
                'date_add' => $row['date_add'],
                'date_upd' => $row['date_upd'],
            ];
        }

        return $payments;
    }

    /**
     * Get total count of payment attempts based on filters
     */
    protected function getTotalPaymentAttempts(array $filters): int
    {
        $db = \Db::getInstance();
        $query = new \DbQuery();

        $query->select('COUNT(*)');
        $query->from('mt_payment');
        $this->applyFilters($query, $filters);

        return (int) $db->getValue($query);
    }

    /**
     * Apply filters to a query
     */
    protected function applyFilters(\DbQuery $query, array $filters): void
    {
        if (!empty($filters['cart_id'])) {
            $query->where('id_cart = ' . (int) $filters['cart_id']);
        }

        if (!empty($filters['order_id'])) {
            $query->where('id_order = ' . (int) $filters['order_id']);
        }

        if (!empty($filters['pmt_id'])) {
            $query->where("pmt_id LIKE '%" . pSQL($filters['pmt_id']) . "%'");
        }

        if (!empty($filters['status'])) {
            $query->where('status = ' . (int) $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->where("date_add >= '" . pSQL($filters['date_from']) . " 00:00:00'");
        }

        if (!empty($filters['date_to'])) {
            $query->where("date_add <= '" . pSQL($filters['date_to']) . " 23:59:59'");
        }
    }

    /**
     * Get status options for filter
     */
    protected function getStatusOptions(): array
    {
        return [
            '' => $this->trans('All', 'Modules.Maksuturva.Admin'),
            \Configuration::get('PS_OS_PAYMENT') => $this->trans('Paid', 'Modules.Maksuturva.Admin'),
            \Configuration::get('PS_OS_CANCELED') => $this->trans('Canceled', 'Modules.Maksuturva.Admin'),
            \Configuration::get('PS_OS_ERROR') => $this->trans('Error', 'Modules.Maksuturva.Admin'),
            \Configuration::get('MAKSUTURVA_OS_AUTHORIZATION') => $this->trans('Pending', 'Modules.Maksuturva.Admin'),
        ];
    }

    /**
     * Get status label for a status code
     */
    protected function getStatusLabel(int $status): string
    {
        switch ($status) {
            case (int) \Configuration::get('PS_OS_PAYMENT'):
                return $this->trans('Paid', 'Modules.Maksuturva.Admin');
            case (int) \Configuration::get('PS_OS_CANCELED'):
                return $this->trans('Canceled', 'Modules.Maksuturva.Admin');
            case (int) \Configuration::get('PS_OS_ERROR'):
                return $this->trans('Error', 'Modules.Maksuturva.Admin');
            case (int) \Configuration::get('MAKSUTURVA_OS_AUTHORIZATION'):
                return $this->trans('Pending', 'Modules.Maksuturva.Admin');
            default:
                return $this->trans('Unknown', 'Modules.Maksuturva.Admin');
        }
    }
}
