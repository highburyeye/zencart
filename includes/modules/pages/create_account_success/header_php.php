<?php
/**
 * create_account_success header_php.php
 *
 * @copyright Copyright 2003-2022 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: DrByte 2020 Jul 10 Modified in v1.5.8-alpha $
 */

// This should be first line of the script:
$zco_notifier->notify('NOTIFY_HEADER_START_CREATE_ACCOUNT_SUCCESS');

require(DIR_WS_MODULES . zen_get_module_directory('require_languages.php'));
$breadcrumb->add(NAVBAR_TITLE_1);
$breadcrumb->add(NAVBAR_TITLE_2);

//-BOF-lat9
// Remove this page from the navigation history and if the customer returns to this page after time-out, redirect them to the time_out page
$_SESSION['navigation']->remove_current_page();
if (!zen_is_logged_in()) {
  zen_redirect(zen_href_link(FILENAME_TIME_OUT, '', 'NONSSL'));
}
//-EOF-lat9

if (sizeof($_SESSION['navigation']->snapshot) > 0) {
  $origin_href = zen_href_link($_SESSION['navigation']->snapshot['page'], zen_array_to_string($_SESSION['navigation']->snapshot['get'], array(zen_session_name())), $_SESSION['navigation']->snapshot['mode']);
  $_SESSION['navigation']->clear_snapshot();
} else {
  $origin_href = zen_href_link(FILENAME_DEFAULT);
}

// redirect customer to where they came from if their cart is not empty and they didn't click on create-account specifically
if ($_SESSION['cart']->count_contents() > 0) {
  if ($origin_href != zen_href_link(FILENAME_DEFAULT)) {
    zen_redirect($origin_href);
  }
}

/*  prepare address list */
$addresses_query = "SELECT address_book_id, entry_firstname as firstname, entry_lastname as lastname,
                           entry_company as company, entry_street_address as street_address,
                           entry_suburb as suburb, entry_city as city, entry_postcode as postcode,
                           entry_state as state, entry_zone_id as zone_id, entry_country_id as country_id
                    FROM   " . TABLE_ADDRESS_BOOK . "
                    WHERE  customers_id = :customersID
                    ORDER BY firstname, lastname";

$addresses_query = $db->bindVars($addresses_query, ':customersID', $_SESSION['customer_id'], 'integer');
$addresses = $db->Execute($addresses_query);

$addressArray = array();
while (!$addresses->EOF) {
  $format_id = zen_get_address_format_id((int)$addresses->fields['country_id']);

  $addressArray[] = array('firstname'=>$addresses->fields['firstname'],
                          'lastname'=>$addresses->fields['lastname'],
                          'address_book_id'=>$addresses->fields['address_book_id'],
                          'format_id'=>$format_id,
                          'address'=>$addresses->fields);
  $addresses->MoveNext();
}

// This should be last line of the script:
$zco_notifier->notify('NOTIFY_HEADER_END_CREATE_ACCOUNT_SUCCESS');
?>
