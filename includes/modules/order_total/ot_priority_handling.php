<?php
/*
  Priority Handling Module
  ot_priority_handling.php, v2.0.0
  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2002 osCommerce

  Modified to work with zen cart 2.1.0 and Edit Orders 5.0.0

  Released under the GNU General Public License
*/
class ot_priority_handling
{
    public string $title;
    public array $output;
    public string $code;
    public string $description;
    public bool $enabled;
    public null|int $sort_order;
    public int $tax_class;
    public bool $credit_class;
    public array $eoInfo = [];

    protected int $check;
    protected float $handling_per;
    protected float $handling_over;
    protected float $increment;
    protected float $fee;

    public function __construct()
    {
        $this->code = 'ot_priority_handling';
        $this->title = MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TITLE;
        $this->description = MODULE_ORDER_TOTAL_PRIORITY_HANDLING_DESCRIPTION;
        $this->sort_order = defined('MODULE_ORDER_TOTAL_PRIORITY_HANDLING_SORT_ORDER') ? (int)MODULE_ORDER_TOTAL_PRIORITY_HANDLING_SORT_ORDER : null;
        if ($this->sort_order === null) {
            return;
        }

        $this->output = [];
        $this->credit_class = true;
        $this->enabled = (MODULE_ORDER_TOTAL_PRIORITY_HANDLING_STATUS === 'true');

        $this->eoInfo = [
            'installed' => false,
            'value' => 0,
        ];

        if ($this->enabled === true) {
            $this->handling_per = (float)MODULE_ORDER_TOTAL_PRIORITY_HANDLING_PER;
            $this->handling_over = (float)MODULE_ORDER_TOTAL_PRIORITY_HANDLING_OVER;

            $this->increment = (float)MODULE_ORDER_TOTAL_PRIORITY_HANDLING_INCREMENT;
            if ($this->increment <= 0) {
                trigger_error('Handling Charge: Price Tier must be greater than 0 (' . MODULE_ORDER_TOTAL_PRIORITY_HANDLING_INCREMENT . '); using a default of 100.', E_USER_WARNING);
                $this->increment = 100;
            }

            $this->fee = (float)MODULE_ORDER_TOTAL_PRIORITY_HANDLING_FEE;
            $this->tax_class = (int)MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TAX_CLASS;

            $this->setStatusForEditOrders();
        }
    }

    // -----
    // This method provides integration with EO 5.0.0 and later. That version of EO maintains
    // a list of credit-class order-total modules that are currently used in the order.
    //
    protected function setStatusForEditOrders(): void
    {
        if (IS_ADMIN_FLAG === true) {
            $_SESSION['priority_handling'] = !empty($_POST['opt_priority_handling']) || $this->eoInfo['installed'] === true;
        }
    }

    public function process()
    {
        global $order, $currencies;

        if ($this->enabled === false) {
            return;
        }

        $this->setStatusForEditOrders();
        if (empty($_SESSION['priority_handling'])) {
            return;
        }

        // get country/zone id (copy & paste from functions_taxes.php)
        if (isset($_SESSION['customer_id'])) {
            $cntry_id = $_SESSION['customer_country_id'];
            $zn_id = $_SESSION['customer_zone_id'];
        } else {
            $cntry_id = STORE_COUNTRY;
            $zn_id = STORE_ZONE;
        }

        $tax = zen_get_tax_rate($this->tax_class);
        if (MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TYPE === 'percent') {
            $ph_tax = zen_calculate_tax(($order->info['subtotal'] * $this->handling_per / 100), $tax);
            $ph_subtotal = $order->info['subtotal'] * $this->handling_per / 100;
        } else {
            if ($order->info['subtotal'] > $this->handling_over) {
                $st = $this->handling_over;
            } else {
                $st = $order->info['subtotal'];
            }
            $how_often = ceil($st / $this->increment);
            $ph_tax = zen_calculate_tax($this->fee * $how_often, $tax);
            $ph_subtotal = ($this->fee * $how_often);
        }

        if (MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TAX_INLINE === 'Handling Fee') { 
            $ph_text = $currencies->format($ph_subtotal + $ph_tax, true, $order->info['currency'], $order->info['currency_value']);
            $ph_value = $ph_subtotal + $ph_tax; // nr@sebo addition
        } else {
            $tax_descrip = zen_get_tax_description($this->tax_class, $cntry_id, $zn_id);
            if (!isset($order->info['tax_groups'][$tax_descrip])) {
                $order->info['tax_groups'][$tax_descrip] = 0;
            }
            $order->info['tax_groups'][$tax_descrip] += $ph_tax;
            $ph_text = $currencies->format($ph_subtotal, true, $order->info['currency'], $order->info['currency_value']);
            $ph_value = $ph_subtotal; // nr@sebo addition
        }
        $order->info['tax'] += $ph_tax; 
        $order->info['total'] += $ph_subtotal + $ph_tax;
        $this->output[] = [
            'title' => $this->title . ':',
            'text' => $ph_text,
            'value' => $ph_value
        ];
    }

    public function pre_confirmation_check($order_total)
    {
        return 0.0;
    }

    public function credit_selection(): array
    {
        if ($this->enabled === false) {
            return [];
        }

        $this->setStatusForEditOrders();

        $handling_array = [
            [
                'id' => '0',
                'text' => MODULE_ORDER_TOTAL_PRIORITY_HANDLING_NO
            ],
            [
                'id' => '1',
                'text' => MODULE_ORDER_TOTAL_PRIORITY_HANDLING_YES
            ]
        ];
        $selected = (!empty($_SESSION['priority_handling']));
        $selection = [
            'id' => $this->code,
            'module' => $this->title,
            'redeem_instructions' => MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TEXT_DESCR . '<br><br>',
            'fields' => [
                [
                    'tag' => 'sel-' . $this->code,
                    'field' => zen_draw_pull_down_menu('opt_priority_handling', $handling_array, ($selected === true) ? '1' : '0', 'id="sel-' . $this->code . '"'),
                    'title' => MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TEXT_ENTER_CODE,
                ],
            ],
        ];
        return $selection;
    }

    public function update_credit_account($i)
    {
    }

    public function apply_credit()
    {
    }

    public function clear_posts()
    {
        unset($_SESSION['priority_handling']);
    }

    public function collect_posts()
    {
        if ($this->enabled === true) {
            if (IS_ADMIN_FLAG === true && ($_POST['ot_class'] ?? '') === $this->code) {
                $this->setStatusForEditOrders();
            }
        }
    }

    public function check()
    {
        global $db;

        if (!isset($this->check)) {
            $check_query = $db->Execute(
                "SELECT configuration_value
                   FROM " . TABLE_CONFIGURATION . "
                  WHERE configuration_key = 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_STATUS'
                  LIMIT 1"
            );
            $this->check = (int)$check_query->RecordCount();
        }
        return $this->check;
    }

    public function keys()
    {
        return [
            'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_STATUS',
            'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_SORT_ORDER',
            'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TYPE',
            'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_PER',
            'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_FEE',
            'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_INCREMENT',
            'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_OVER',
            'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TAX_CLASS',
            'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TAX_INLINE',
        ];
    }

    public function install()
    {
        global $db;

        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . "
                (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added)
             VALUES
                ('Enable Priority Handling Module', 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_STATUS', 'true', 'Do you want to enable this module?', 6, 1,'zen_cfg_select_option([\'true\', \'false\'], ', NULL, now()),

                ('Sort Order', 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_SORT_ORDER', '150', 'Sort order of display.', 6, 6, NULL, NULL, now()),

                ('Priority Handling Charge Type', 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TYPE', 'percent', 'Specify whether the handling charge should be a percentage of  cart subtotal, or specified as tiers below', 6, 4, 'zen_cfg_select_option([\'percent\', \'tiered\'], ', NULL, now()),

                ('Handling Charge: Percentage', 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_PER', '5', 'Enter the percentage of subtotal to charge as handling fee.', 6, 5, NULL, NULL, now()),

                ('Handling Charge: Fee Tier', 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_FEE', '.50', 'Enter the fee tier increment. Handling charge will be:<br>(subtotal/price_tier) * fee_tier', 6, 6, NULL, 'currencies->format', now()),

                ('Handling Charge: Price Tier ', 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_INCREMENT', '100', 'Enter the price tier increment.  To setup a flat-fee structure, enter a large value here and your flat fee in the fee tier above. For example, if you want to always charge $10 and your orders are typically around $100, enter $5000 here and $10 in the Fee Tier box.', 6, 7, NULL, 'currencies->format', now()),

                ('Handling Charge: Price Tier Ceiling', 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_OVER', '1000', 'Enter the price tier maximum.  For example, the default values setup a 50 cent charge for every $100 assessed up to $1000 of the cart subtotal or $5 maximum.', 6, 8, NULL, 'currencies->format', now()),

                ('Tax Class', 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TAX_CLASS', '0', 'If handling fees are taxable, then select the tax class that should apply.', 6, 9, 'zen_cfg_pull_down_tax_classes(', 'zen_get_tax_class_title', now()),

                ('Tax Display', 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TAX_INLINE', 'Tax Subtotal', 'Can have tax (see above) be added to the tax subtotal line for the class above or have the it be added to the handling fee line.  Which line should it be added to?', 6, 10, 'zen_cfg_select_option([\'Tax Subtotal\', \'Handling Fee\'], ', NULL, now())"
        );
    }

    public function remove()
    {
        global $db;

        $db->Execute(
            "DELETE FROM " . TABLE_CONFIGURATION . "
              WHERE configuration_key IN ('" . implode("', '", $this->keys()) . "')"
        );
    }
}
