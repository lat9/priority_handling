<?php
/*
  Priority Handling Module
  ot_priority_handling.php, v1.3.0 20181012
  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2002 osCommerce

  Modified to work with zen cart


  Released under the GNU General Public License
*/
class ot_priority_handling 
{
    var $title, $output;
    
    function __construct()
    {
        $this->code = 'ot_priority_handling';
        $this->title = MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TITLE;
        $this->description = MODULE_ORDER_TOTAL_PRIORITY_HANDLING_DESCRIPTION;
        
        $this->enabled = (defined('MODULE_ORDER_TOTAL_PRIORITY_HANDLING_STATUS') && MODULE_ORDER_TOTAL_PRIORITY_HANDLING_STATUS  == 'true');
        
        if ($this->enabled) {
            $this->sort_order = (int)MODULE_ORDER_TOTAL_PRIORITY_HANDLING_SORT_ORDER;
            $this->handling_per = (float)MODULE_ORDER_TOTAL_PRIORITY_HANDLING_PER;
            $this->handling_over = (float)MODULE_ORDER_TOTAL_PRIORITY_HANDLING_OVER;

            
            $this->increment = (float)MODULE_ORDER_TOTAL_PRIORITY_HANDLING_INCREMENT;
            if ($this->increment <= 0) {
                trigger_error('Handling Charge: Price Tier must be greater than 0 (' . MODULE_ORDER_TOTAL_PRIORITY_HANDLING_INCREMENT . '); using a default of 100.', E_USER_WARNING);
                $this->increment = 100;
            }
            
            $this->fee = (float)MODULE_ORDER_TOTAL_PRIORITY_HANDLING_FEE;
            $this->tax_class = (int)MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TAX_CLASS;
        }
        $this->credit_class = 'true';
        $this->output = array();
    }
    
    function process()
    {
        global $order, $currencies;
        if ($this->enabled) {
            $charge_it = !empty($_SESSION['priority_handling']);
            
            // get country/zone id (copy & paste from functions_taxes.php)
            if (isset($_SESSION['customer_id'])) {
                $cntry_id = $_SESSION['customer_country_id'];
                $zn_id = $_SESSION['customer_zone_id'];
            } else {
                $cntry_id = STORE_COUNTRY;
                $zn_id = STORE_ZONE;
            }
            if ($charge_it) {
                $tax = zen_get_tax_rate($this->tax_class);
                if (MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TYPE =='percent') {
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
                if (MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TAX_INLINE == 'Handling Fee') { 
                    $ph_text = $currencies->format($ph_subtotal + $ph_tax, true, $order->info['currency'], $order->info['currency_value']);
                    $ph_value = $ph_subtotal + $ph_tax; // nr@sebo addition
                } else {
                    $tax_descrip = zen_get_tax_description($this->tax_class, $cntry_id, $zn_id);
                    $order->info['tax_groups'][$tax_descrip] += $ph_tax;
                    $ph_text = $currencies->format($ph_subtotal, true, $order->info['currency'], $order->info['currency_value']);
                    $ph_value = $ph_subtotal; // nr@sebo addition
                }
                $order->info['tax'] += $ph_tax; 
                $order->info['total'] += $ph_subtotal + $ph_tax;
                $this->output[] = array(
                    'title' => $this->title . ':',
                    'text' => $ph_text,
                    'value' => $ph_value
                );
            } 
        }
    }

    function pre_confirmation_check($order_total)
    {
        return 0.0;
    }
              
    function credit_selection()
    {
        if (!$this->enabled) {
            return false;
        }
        $selected = (isset($_SESSION['priority_handling']) && $_SESSION['priority_handling'] == 1);
        $selection = array(
            'id' => $this->code,
            'module' => $this->title,
            'redeem_instructions' => MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TEXT_DESCR . '<br /><br />',
            'fields' => array(
                array(
                    'field' => zen_draw_checkbox_field('opt_priority_handling', '1', $selected),
                    'title' => MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TEXT_ENTER_CODE
                )
            )
        );
        return $selection;
    }
    
    function update_credit_account($i)
    {
    }
    
    function apply_credit()
    {
    }
    
    function clear_posts()
    {
        unset($_SESSION['priority_handling']);
    }
    
    function collect_posts()
    {
        if ($this->enabled) {
            if ($_POST['opt_priority_handling']) {
                $_SESSION['priority_handling'] = $_POST['opt_priority_handling'];
            } else {
                $_SESSION['priority_handling'] = '0';
            }
        }
    }
    
    function check()
    {
        global $db;
        if (!isset($this->check)) {
            $check_query = $db->Execute(
                "SELECT configuration_value 
                   FROM " . TABLE_CONFIGURATION . " 
                  WHERE configuration_key = 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_STATUS'"
            );

            $this->check = $check_query->RecordCount();
        }
        return $this->check;
    }
    
    function keys()
    {
        return array(
            'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_STATUS',
            'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_SORT_ORDER', 
            'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TYPE', 
            'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_PER', 
            'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_FEE', 
            'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_INCREMENT', 
            'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_OVER', 
            'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TAX_CLASS',
            'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TAX_INLINE'
        );
    }
    
    function install()
    {
        global $db;
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Priority Handling Module', 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_STATUS', 'true', 'Do you want to enable this module?', '6', '1','zen_cfg_select_option(array(\'true\', \'false\'), ', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values('Sort Order', 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_SORT_ORDER', '150', 'Sort order of display.', '6', '3', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values('Priority Handling Charge Type', 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TYPE', 'percent', 'Specify whether the handling charge should be a percentage of  cart subtotal, or specified as tiers below', '6', '4', 'zen_cfg_select_option(array(\'percent\', \'tiered\'), ', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, date_added) values('Handling Charge: Percentage', 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_PER', '5', 'Enter the percentage of subtotal to charge as handling fee.', '6', '5', '', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, date_added) values('Handling Charge: Fee Tier', 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_FEE', '.50', 'Enter the fee tier increment.  Handling charge will be: <br> (subtotal/price_tier) * fee_tier', '6', '6', 'currencies->format', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, date_added) values('Handling Charge: Price Tier ', 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_INCREMENT', '100', 'Enter the price tier increment.  To setup a flat-fee structure, enter a large value here and your flat fee in the fee tier above.  For example, if you want to always charge $10 and your orders are typically around $100, enter $5000 here and $10 in the Fee Tier box.', '6', '7', 'currencies->format', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, date_added) values('Handling Charge: Price Tier Ceiling', 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_OVER', '1000', 'Enter the price tier maximum.  For example, the default values setup a 50 cent charge for every $100 assessed up to $1000 of the cart subtotal, or $5 maximum.', '6', '8', 'currencies->format', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values('Tax Class', 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TAX_CLASS', '0', 'If handling fees are taxable, then select the tax class that should apply.', '6', '9', 'zen_get_tax_class_title', 'zen_cfg_pull_down_tax_classes(', now())");

        $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values('Tax Display', 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TAX_INLINE', 'Tax Subtotal', 'Can have tax (see above) be added to the tax subtotal line for the class above or have the it be added to the handling fee line.  Which line should it be added to?', '6', '10', 'zen_cfg_select_option(array(\'Tax Subtotal\', \'Handling Fee\'), ', now())");       
    }
    
    function remove()
    {
        $GLOBALS['db']->Execute(
            "DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key LIKE 'MODULE\_ORDER_TOTAL\_PRIORITY\_HANDLING\_%'"
        );
    }
}
