<?php

namespace App\Support;

class DeskSubmenus
{
    /**
     * Submenus for every side menu. Items without `built` are UI shells only.
     *
     * @return list<array{key: string, patterns: list<string>, items: list<array{route: string, label: string, active?: string, path?: string, title?: string, built?: bool, super?: bool}>}>
     */
    public static function groups(): array
    {
        return [
            [
                'key' => 'dashboard',
                'patterns' => ['dashboard', 'dashboard.*'],
                'items' => [
                    ['route' => 'dashboard', 'label' => 'Overview', 'built' => true],
                    ['route' => 'dashboard.summary', 'label' => 'Summary', 'path' => '/dashboard/summary', 'title' => 'Summary'],
                ],
            ],
            [
                'key' => 'approvals',
                'patterns' => ['approvals', 'approvals.*'],
                'items' => [
                    ['route' => 'approvals', 'label' => 'Overview', 'built' => true],
                    ['route' => 'approvals.staff', 'label' => 'Staff', 'path' => '/approvals/staff', 'title' => 'Staff approvals'],
                    ['route' => 'approvals.suppliers', 'label' => 'Suppliers', 'path' => '/approvals/suppliers', 'title' => 'Supplier approvals'],
                ],
            ],
            [
                'key' => 'contacts',
                'patterns' => ['contacts', 'contacts.*'],
                'items' => [
                    ['route' => 'contacts', 'label' => 'Overview', 'built' => true],
                    ['route' => 'contacts.suppliers', 'label' => 'Suppliers', 'built' => true],
                    ['route' => 'contacts.customers', 'label' => 'Customers', 'built' => true],
                    ['route' => 'contacts.online-customers', 'label' => 'Online customers', 'path' => '/contacts/online-customers', 'title' => 'Online customers'],
                    ['route' => 'contacts.carriers', 'label' => 'Carriers', 'built' => true],
                    ['route' => 'contacts.message-templates', 'label' => 'Message templates', 'built' => true],
                ],
            ],
            [
                'key' => 'catalog',
                'patterns' => ['catalog', 'catalog.*'],
                'items' => [
                    ['route' => 'catalog', 'label' => 'Overview', 'built' => true],
                    ['route' => 'catalog.products', 'label' => 'Products', 'path' => '/catalog/products', 'title' => 'Products'],
                    ['route' => 'catalog.categories', 'label' => 'Categories', 'path' => '/catalog/categories', 'title' => 'Categories'],
                    ['route' => 'catalog.brands', 'label' => 'Brands', 'path' => '/catalog/brands', 'title' => 'Brands'],
                    ['route' => 'catalog.attributes', 'label' => 'Attributes', 'path' => '/catalog/attributes', 'title' => 'Attributes'],
                    ['route' => 'catalog.units', 'label' => 'Units', 'path' => '/catalog/units', 'title' => 'Units'],
                    ['route' => 'catalog.packing-units', 'label' => 'Packing units', 'path' => '/catalog/packing-units', 'title' => 'Packing units'],
                    ['route' => 'catalog.packages', 'label' => 'Packages', 'path' => '/catalog/packages', 'title' => 'Packages'],
                    ['route' => 'catalog.labels', 'label' => 'Labels', 'path' => '/catalog/labels', 'title' => 'Labels'],
                    ['route' => 'catalog.controls', 'label' => 'Controls', 'path' => '/catalog/controls', 'title' => 'Controls'],
                    ['route' => 'catalog.reviews', 'label' => 'Product reviews', 'path' => '/catalog/reviews', 'title' => 'Product reviews'],
                ],
            ],
            [
                'key' => 'purchasing',
                'patterns' => ['purchasing', 'purchasing.*'],
                'items' => [
                    ['route' => 'purchasing', 'label' => 'Overview', 'built' => true],
                    ['route' => 'purchasing.purchases', 'label' => 'Purchases', 'path' => '/purchasing/purchases', 'title' => 'Purchases'],
                    ['route' => 'purchasing.quotations', 'label' => 'Quotations', 'path' => '/purchasing/quotations', 'title' => 'Purchase quotations'],
                    ['route' => 'purchasing.planning', 'label' => 'Planning', 'path' => '/purchasing/planning', 'title' => 'Purchase planning'],
                    ['route' => 'purchasing.reorder', 'label' => 'Re-order', 'path' => '/purchasing/reorder', 'title' => 'Re-order center'],
                    ['route' => 'purchasing.rate-lists', 'label' => 'Rate lists', 'path' => '/purchasing/rate-lists', 'title' => 'Rate lists'],
                    ['route' => 'purchasing.price-tags', 'label' => 'Price tags', 'path' => '/purchasing/price-tags', 'title' => 'Price tags'],
                    ['route' => 'purchasing.price-updates', 'label' => 'Price updates', 'path' => '/purchasing/price-updates', 'title' => 'Price updates'],
                    ['route' => 'purchasing.payment-summary', 'label' => 'Payment summary', 'path' => '/purchasing/payment-summary', 'title' => 'Purchase payment summary'],
                ],
            ],
            [
                'key' => 'sales',
                'patterns' => ['sales', 'sales.*'],
                'items' => [
                    ['route' => 'sales', 'label' => 'Overview', 'built' => true],
                    ['route' => 'sales.inbox', 'label' => 'Inbox', 'path' => '/sales/inbox', 'title' => 'Sales inbox'],
                    ['route' => 'sales.tasks', 'label' => 'Tasks', 'path' => '/sales/tasks', 'title' => 'Sales tasks'],
                    ['route' => 'sales.pos', 'label' => 'POS', 'path' => '/sales/pos', 'title' => 'POS sales'],
                    ['route' => 'sales.quotations', 'label' => 'Quotations', 'path' => '/sales/quotations', 'title' => 'Sales quotations'],
                    ['route' => 'sales.online-quotations', 'label' => 'Online quotations', 'path' => '/sales/online-quotations', 'title' => 'Online quotations'],
                    ['route' => 'sales.online-orders', 'label' => 'Online orders', 'path' => '/sales/online-orders', 'title' => 'Online orders'],
                    ['route' => 'sales.forwarded', 'label' => 'Forwarded orders', 'path' => '/sales/forwarded', 'title' => 'Forwarded orders'],
                    ['route' => 'sales.dispatch', 'label' => 'Dispatch', 'path' => '/sales/dispatch', 'title' => 'Order dispatch'],
                    ['route' => 'sales.returns', 'label' => 'Returns', 'path' => '/sales/returns', 'title' => 'Sales returns'],
                    ['route' => 'sales.cancelled-quotations', 'label' => 'Cancelled quotations', 'path' => '/sales/cancelled-quotations', 'title' => 'Cancelled quotations'],
                ],
            ],
            [
                'key' => 'store',
                'patterns' => ['store', 'store.*'],
                'items' => [
                    ['route' => 'store', 'label' => 'Overview', 'built' => true],
                    ['route' => 'store.top-bar', 'label' => 'Top bar', 'path' => '/store/top-bar', 'title' => 'Store top bar'],
                    ['route' => 'store.header', 'label' => 'Header', 'path' => '/store/header', 'title' => 'Store header'],
                    ['route' => 'store.menu', 'label' => 'Menu', 'path' => '/store/menu', 'title' => 'Store menu'],
                    ['route' => 'store.hero', 'label' => 'Hero slider', 'path' => '/store/hero', 'title' => 'Hero slider'],
                    ['route' => 'store.ticker', 'label' => 'Ticker', 'path' => '/store/ticker', 'title' => 'Store ticker'],
                    ['route' => 'store.items', 'label' => 'Items', 'path' => '/store/items', 'title' => 'Store items'],
                    ['route' => 'store.pages', 'label' => 'Pages', 'path' => '/store/pages', 'title' => 'Store pages'],
                    ['route' => 'store.about', 'label' => 'About', 'path' => '/store/about', 'title' => 'About page'],
                    ['route' => 'store.blog', 'label' => 'Blog', 'path' => '/store/blog', 'title' => 'Blog'],
                    ['route' => 'store.reviews', 'label' => 'Reviews', 'path' => '/store/reviews', 'title' => 'Store reviews'],
                    ['route' => 'store.brand-reviews', 'label' => 'Brand reviews', 'path' => '/store/brand-reviews', 'title' => 'Brand reviews'],
                    ['route' => 'store.testimonials', 'label' => 'Testimonials', 'path' => '/store/testimonials', 'title' => 'Testimonials'],
                    ['route' => 'store.messages', 'label' => 'Messages', 'path' => '/store/messages', 'title' => 'Store messages'],
                    ['route' => 'store.registration', 'label' => 'Registration', 'path' => '/store/registration', 'title' => 'Store registration'],
                    ['route' => 'store.seo', 'label' => 'SEO', 'path' => '/store/seo', 'title' => 'Store SEO'],
                    ['route' => 'store.footer', 'label' => 'Footer', 'path' => '/store/footer', 'title' => 'Store footer'],
                    ['route' => 'store.analytics', 'label' => 'Visitor analytics', 'path' => '/store/analytics', 'title' => 'Visitor analytics'],
                ],
            ],
            [
                'key' => 'logistics',
                'patterns' => ['logistics', 'logistics.*'],
                'items' => [
                    ['route' => 'logistics', 'label' => 'Overview', 'built' => true],
                    ['route' => 'logistics.methods', 'label' => 'Delivery methods', 'path' => '/logistics/methods', 'title' => 'Delivery methods'],
                    ['route' => 'logistics.shipments', 'label' => 'Shipments', 'path' => '/logistics/shipments', 'title' => 'Shipments'],
                    ['route' => 'logistics.shipped', 'label' => 'Shipped orders', 'path' => '/logistics/shipped', 'title' => 'Shipped orders'],
                    ['route' => 'logistics.delivered', 'label' => 'Delivered orders', 'path' => '/logistics/delivered', 'title' => 'Delivered orders'],
                ],
            ],
            [
                'key' => 'service',
                'patterns' => ['service', 'service.*'],
                'items' => [
                    ['route' => 'service', 'label' => 'Overview', 'built' => true],
                    ['route' => 'service.requests', 'label' => 'Requests', 'path' => '/service/requests', 'title' => 'Service requests'],
                ],
            ],
            [
                'key' => 'reports',
                'patterns' => ['reports', 'reports.*'],
                'items' => [
                    ['route' => 'reports', 'label' => 'Overview', 'built' => true],
                    ['route' => 'reports.sales-summary', 'label' => 'Sales summary', 'path' => '/reports/sales-summary', 'title' => 'Sales summary'],
                    ['route' => 'reports.stock', 'label' => 'Stock', 'path' => '/reports/stock', 'title' => 'Stock report'],
                    ['route' => 'reports.cash', 'label' => 'Cash', 'path' => '/reports/cash', 'title' => 'Cash report'],
                    ['route' => 'reports.expenses', 'label' => 'Expenses', 'path' => '/reports/expenses', 'title' => 'Expense report'],
                    ['route' => 'reports.profit-loss', 'label' => 'Profit & loss', 'path' => '/reports/profit-loss', 'title' => 'Profit & loss'],
                    ['route' => 'reports.customer-ledger', 'label' => 'Customer ledger', 'path' => '/reports/customer-ledger', 'title' => 'Customer ledger'],
                    ['route' => 'reports.supplier-ledger', 'label' => 'Supplier ledger', 'path' => '/reports/supplier-ledger', 'title' => 'Supplier ledger'],
                    ['route' => 'reports.seasonal-sales', 'label' => 'Seasonal sales', 'path' => '/reports/seasonal-sales', 'title' => 'Seasonal sales'],
                    ['route' => 'reports.seasonal-purchases', 'label' => 'Seasonal purchases', 'path' => '/reports/seasonal-purchases', 'title' => 'Seasonal purchases'],
                ],
            ],
            [
                'key' => 'staff',
                'patterns' => ['staff', 'staff.*'],
                'items' => [
                    ['route' => 'staff', 'label' => 'Overview', 'built' => true],
                    ['route' => 'staff.people', 'label' => 'Staff', 'path' => '/staff/people', 'title' => 'Staff'],
                    ['route' => 'staff.attendance', 'label' => 'Attendance', 'path' => '/staff/attendance', 'title' => 'Attendance'],
                    ['route' => 'staff.leave', 'label' => 'Leave', 'path' => '/staff/leave', 'title' => 'Leave management'],
                    ['route' => 'staff.advances', 'label' => 'Advances', 'path' => '/staff/advances', 'title' => 'Advances'],
                    ['route' => 'staff.pay', 'label' => 'Pay', 'path' => '/staff/pay', 'title' => 'Pay management'],
                    ['route' => 'staff.payroll', 'label' => 'Payroll', 'path' => '/staff/payroll', 'title' => 'Payroll'],
                ],
            ],
            [
                'key' => 'finance',
                'patterns' => ['finance', 'finance.*'],
                'items' => [
                    ['route' => 'finance', 'label' => 'Overview', 'built' => true],
                    ['route' => 'finance.cash-book', 'label' => 'Cash book', 'path' => '/finance/cash-book', 'title' => 'Cash book'],
                    ['route' => 'finance.bank-accounts', 'label' => 'Bank accounts', 'path' => '/finance/bank-accounts', 'title' => 'Bank accounts'],
                    ['route' => 'finance.transfers', 'label' => 'Cash/bank transfers', 'path' => '/finance/transfers', 'title' => 'Cash/bank transfers'],
                    ['route' => 'finance.expenses', 'label' => 'Expenses', 'path' => '/finance/expenses', 'title' => 'Expenses'],
                    ['route' => 'finance.expense-categories', 'label' => 'Expense categories', 'path' => '/finance/expense-categories', 'title' => 'Expense categories'],
                    ['route' => 'finance.expense-payments', 'label' => 'Expense payments', 'path' => '/finance/expense-payments', 'title' => 'Expense payments'],
                    ['route' => 'finance.reports', 'label' => 'Financial reports', 'path' => '/finance/reports', 'title' => 'Financial reports'],
                ],
            ],
            [
                'key' => 'marketplace',
                'patterns' => ['marketplace', 'marketplace.*'],
                'items' => [
                    ['route' => 'marketplace', 'label' => 'Overview', 'built' => true],
                    ['route' => 'marketplace.channels', 'label' => 'Sales channels', 'path' => '/marketplace/channels', 'title' => 'Sales channels'],
                    ['route' => 'marketplace.price-control', 'label' => 'Price control', 'path' => '/marketplace/price-control', 'title' => 'Price control'],
                    ['route' => 'marketplace.quantity-control', 'label' => 'Quantity control', 'path' => '/marketplace/quantity-control', 'title' => 'Quantity control'],
                    ['route' => 'marketplace.secret-coding', 'label' => 'Secret coding', 'path' => '/marketplace/secret-coding', 'title' => 'Secret coding'],
                ],
            ],
            [
                'key' => 'docs',
                'patterns' => ['notes*', 'docs', 'docs.*'],
                'items' => [
                    ['route' => 'docs', 'label' => 'Overview', 'path' => '/docs', 'title' => 'Docs'],
                    ['route' => 'notes', 'label' => 'Notes', 'active' => 'notes*', 'built' => true, 'super' => true],
                    ['route' => 'docs.guide', 'label' => 'Guide', 'path' => '/docs/guide', 'title' => 'Guide', 'super' => true],
                    ['route' => 'docs.app-guide', 'label' => 'App guide', 'path' => '/docs/app-guide', 'title' => 'App guide', 'super' => true],
                ],
            ],
            [
                'key' => 'personal-vault',
                'patterns' => ['personal-vault', 'personal-vault.*'],
                'items' => [
                    ['route' => 'personal-vault', 'label' => 'My box', 'built' => true],
                    ['route' => 'personal-vault.all-boxes', 'label' => 'All boxes', 'path' => '/personal-vault/all-boxes', 'title' => 'All boxes'],
                ],
            ],
            [
                'key' => 'forex',
                'patterns' => ['forex*'],
                'items' => [], // filled in shell with role-aware Forex links
            ],
            [
                'key' => 'external-apps',
                'patterns' => ['external-apps', 'external-apps.*'],
                'items' => [
                    ['route' => 'external-apps', 'label' => 'Overview', 'built' => true],
                    ['route' => 'external-apps.barcode', 'label' => 'Barcode printing', 'path' => '/external-apps/barcode', 'title' => 'Barcode printing'],
                    ['route' => 'external-apps.printing', 'label' => 'Printing', 'path' => '/external-apps/printing', 'title' => 'Printing'],
                    ['route' => 'external-apps.home-expenses', 'label' => 'Home expenses', 'path' => '/external-apps/home-expenses', 'title' => 'Home expenses'],
                    ['route' => 'external-apps.pending-payments', 'label' => 'Pending payments', 'path' => '/external-apps/pending-payments', 'title' => 'Pending payments'],
                    ['route' => 'external-apps.purchase-summary', 'label' => 'Purchase summary', 'path' => '/external-apps/purchase-summary', 'title' => 'Purchase summary'],
                    ['route' => 'external-apps.reorder', 'label' => 'Re-order', 'path' => '/external-apps/reorder', 'title' => 'Re-order'],
                ],
            ],
            [
                'key' => 'settings',
                'patterns' => ['settings', 'settings.*', 'roles*', 'colors*'],
                'items' => [
                    ['route' => 'settings', 'label' => 'Overview', 'built' => true, 'super' => true],
                    ['route' => 'roles', 'label' => 'Roles', 'active' => 'roles*', 'built' => true, 'super' => true],
                    ['route' => 'colors', 'label' => 'Appearance', 'active' => 'colors*', 'built' => true, 'super' => true],
                    ['route' => 'settings.ai', 'label' => 'AI Connection', 'built' => true, 'super' => true],
                    ['route' => 'settings.users', 'label' => 'Users', 'path' => '/settings/users', 'title' => 'Users', 'super' => true],
                    ['route' => 'settings.permissions', 'label' => 'Permissions', 'path' => '/settings/permissions', 'title' => 'Permissions', 'super' => true],
                    ['route' => 'settings.media', 'label' => 'Media library', 'path' => '/settings/media', 'title' => 'Media library', 'super' => true],
                    ['route' => 'settings.store', 'label' => 'Store settings', 'path' => '/settings/store', 'title' => 'Store settings', 'super' => true],
                    ['route' => 'settings.printing', 'label' => 'Printing', 'built' => true, 'super' => true],
                    ['route' => 'settings.tax', 'label' => 'Tax system', 'path' => '/settings/tax', 'title' => 'Tax system', 'super' => true],
                    ['route' => 'settings.backups', 'label' => 'Backups', 'path' => '/settings/backups', 'title' => 'Backups', 'super' => true],
                    ['route' => 'settings.activity', 'label' => 'Activity', 'path' => '/settings/activity', 'title' => 'Activity', 'super' => true],
                    ['route' => 'settings.errors', 'label' => 'System errors', 'path' => '/settings/errors', 'title' => 'System errors', 'super' => true],
                    ['route' => 'settings.watermarks', 'label' => 'Watermarks', 'path' => '/settings/watermarks', 'title' => 'Watermarks', 'super' => true],
                    ['route' => 'settings.brand-messages', 'label' => 'Brand review messages', 'path' => '/settings/brand-messages', 'title' => 'Brand review messages', 'super' => true],
                ],
            ],
        ];
    }

    /**
     * @return list<array{route: string, label: string, active?: string}>
     */
    public static function itemsForRequest(): array
    {
        foreach (self::groups() as $group) {
            if (! request()->routeIs(...$group['patterns'])) {
                continue;
            }

            return array_map(static fn (array $item): array => [
                'route' => $item['route'],
                'label' => $item['label'],
                'active' => $item['active'] ?? $item['route'],
            ], $group['items']);
        }

        return [];
    }

    /**
     * @return list<array{route: string, path: string, title: string, super: bool}>
     */
    public static function stubRoutes(): array
    {
        $stubs = [];

        foreach (self::groups() as $group) {
            foreach ($group['items'] as $item) {
                if (($item['built'] ?? false) === true) {
                    continue;
                }

                if (! isset($item['path'])) {
                    continue;
                }

                $stubs[] = [
                    'route' => $item['route'],
                    'path' => $item['path'],
                    'title' => $item['title'] ?? $item['label'],
                    'super' => (bool) ($item['super'] ?? false),
                ];
            }
        }

        return $stubs;
    }
}
