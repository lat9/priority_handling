<?php
/*
  Priority Handling Module
  ot_priority_handling.php, v 1.0 2003/12/03
  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2002 osCommerce

  Modified to work with zen cart


  Released under the GNU General Public License
*/
class ot_priority_handling {
    var $title, $output;
    
    function ot_priority_handling()
    {
        $this->code = 'ot_priority_handling';
        $this->title = MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TITLE;
        $this->description = MODULE_ORDER_TOTAL_PRIORITY_HANDLING_DESCRIPTION;
        $this->enabled = MODULE_ORDER_TOTAL_PRIORITY_HANDLING_STATUS;
        $this->sort_order = MODULE_ORDER_TOTAL_PRIORITY_HANDLING_SORT_ORDER;
        $this->credit_class = 'true';
        $this->output = array();
    }
    
    function process()
    {
        global $order, $currencies;
        if (MODULE_ORDER_TOTAL_PRIORITY_HANDLING_USE == 'true') {
            if (! $_SESSION['priority_handling']) {
                $charge_it = 'false';
            } else {
	            $charge_it = true;
            }
            // get country/zone id (copy & paste from functions_taxes.php)
            if (isset($_SESSION['customer_id'])) {
              $cntry_id = $_SESSION['customer_country_id'];
              $zn_id = $_SESSION['customer_zone_id'];
            } else {
              $cntry_id = STORE_COUNTRY;
              $zn_id = STORE_ZONE;
            }
            if ($charge_it == 'true') {
                $tax = zen_get_tax_rate(MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TAX_CLASS);
                if (MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TYPE =='percent') {
	                $ph_tax      = zen_calculate_tax(($order->info['subtotal'] * MODULE_ORDER_TOTAL_PRIORITY_HANDLING_PER / 100), $tax);
	                $ph_subtotal = $order->info['subtotal'] * MODULE_ORDER_TOTAL_PRIORITY_HANDLING_PER / 100;
                } else {
                  if ($order->info['subtotal'] > MODULE_ORDER_TOTAL_PRIORITY_HANDLING_OVER) {
                    $st = MODULE_ORDER_TOTAL_PRIORITY_HANDLING_OVER;
                  } else {
                    $st = $order->info['subtotal'];
                  }
	                $how_often   = ceil($st/MODULE_ORDER_TOTAL_PRIORITY_HANDLING_INCREMENT);
                  $ph_tax      = zen_calculate_tax((MODULE_ORDER_TOTAL_PRIORITY_HANDLING_FEE * $how_often), $tax);
                  $ph_subtotal = (MODULE_ORDER_TOTAL_PRIORITY_HANDLING_FEE * $how_often);
                }
                if (MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TAX_INLINE == 'Handling Fee') { 
                    $ph_text     = $currencies->format($ph_subtotal+$ph_tax, true, $order->info['currency'], $order->info['currency_value']);
                    $ph_value    = $ph_subtotal+$ph_tax; // nr@sebo addition
                } else {
                    $tax_descrip = zen_get_tax_description(MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TAX_CLASS, $cntry_id, $zn_id);
                    $order->info['tax_groups'][$tax_descrip]+= $ph_tax;
                    $ph_text     = $currencies->format($ph_subtotal, true, $order->info['currency'], $order->info['currency_value']);
                    $ph_value    = $ph_subtotal; // nr@sebo addition
                }
                $order->info['tax'] += $ph_tax; 
                $order->info['total'] += $ph_subtotal + $ph_tax;
                $this->output[] = array('title' => $this->title . ':','text' => $ph_text,'value' => $ph_value);
            } 
        } else if ($charge_it == 'false') {
            $tax = zen_get_tax_rate(MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TAX_CLASS);
            $priority_handling = 0;
            $order->info['tax'] += zen_calculate_tax($priority_handling, $tax);
            $order->info['total'] += $priority_handling + zen_calculate_tax($priority_handling, $tax);
            $this->output[] = array('title' => $this->title . ':',
            'text' => $currencies->format(zen_add_tax($priority_handling, $tax) + zen_calculate_tax($priority_handling, $tax), true, $order->info['currency'], $order->info['currency_value']),
            'value' => zen_add_tax($priority_handling, $tax));
        }
    }

    function pre_confirmation_check($order_total)
    {
        return 0.0;
    }

    function get_order_total() {
      global  $order;
      $order_total_tax = $order->info['tax'];
      $order_total = $order->info['total'];
      if ($this->include_shipping != 'true') $order_total -= $order->info['shipping_cost'];
      if ($this->include_tax != 'true') $order_total -= $order->info['tax'];
      $orderTotalFull = $order_total;
      $order_total = array('totalFull'=>$orderTotalFull, 'total'=>$order_total, 'tax'=>$order_total_tax);
  
      return $order_total;
    }
              
    function credit_selection()
    {
        $selected = (($_SESSION['priority_handling'] == '1') ? true : false);
        $selection = array(
          'id' => $this->code,
          'module' => $this->title,
          'redeem_instructions' => MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TEXT_DESCR.'<br><br>',
          'fields' => array(array(
            'field' => zen_draw_checkbox_field('opt_priority_handling', '1', $selected),
            'title' => MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TEXT_ENTER_CODE
          ))
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
        global $db, $currencies;
        if ($_POST['opt_priority_handling']) {
            $_SESSION['priority_handling'] = $_POST['opt_priority_handling'];
        } else {
            $_SESSION['priority_handling'] = '0';
        }
    }
    
    function check()
    {
        global $db;
        if (!isset($this->check)) {
        $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_STATUS'");
        $this->check = $check_query->RecordCount();
        }
        
        return $this->check;
    }
    
    function keys()
    {
        return array('MODULE_ORDER_TOTAL_PRIORITY_HANDLING_STATUS','MODULE_ORDER_TOTAL_PRIORITY_HANDLING_USE', 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_SORT_ORDER', 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TYPE', 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_PER', 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_FEE', 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_INCREMENT', 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_OVER', 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TAX_CLASS','MODULE_ORDER_TOTAL_PRIORITY_HANDLING_TAX_INLINE');
    }
    
    function install()
    {
      global $db;
  		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Priority Handling Module', 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_STATUS', 'true', 'Do you want to enable this module? To fully turn this off, both this option and the one below should be set to false.', '6', '1','zen_cfg_select_option(array(\'true\', \'false\'), ', now())");
  		$db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values('Offer Priority Handling?', 'MODULE_ORDER_TOTAL_PRIORITY_HANDLING_USE', 'true', 'Do you want to offer priority handling?', '6', '2', 'zen_cfg_select_option(array(\'true\', \'false\'), ', now())");
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
        global $db;
        $keys = '';
        $keys_array = $this->keys();
        for ($i=0; $i<sizeof($keys_array); $i++) {
            $keys .= "'" . $keys_array[$i] . "',";
        }
        $keys = substr($keys, 0, -1);
        
        $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in (" . $keys . ")");
    }
}
?>
