<?php
/**
 * @copyright Copyright 2003-2023 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Scott C Wilson 2022 Oct 25 Modified in v1.5.8a $
 */

  require('includes/application_top.php');

  $action = (isset($_GET['action']) ? $_GET['action'] : '');
  $current_category_id = (isset($_GET['current_category_id']) ? (int)$_GET['current_category_id'] : (int)$current_category_id);

  if (!empty($action)) {
    switch ($action) {
      case 'edit':
        if (!is_writable(DIR_FS_CATALOG_MEDIA)) $messageStack->add(TEXT_WARNING_FOLDER_UNWRITABLE, 'caution');
      break;
      case 'remove_product':
        if (isset($_POST['mID']) && isset($_POST['product_id']))
        {
          $db->Execute("DELETE FROM " . TABLE_MEDIA_TO_PRODUCTS . "
                        WHERE media_id = '" . (int)$_POST['mID'] . "'
                        AND product_id = '" . (int)$_POST['product_id'] . "'");
        }
       zen_redirect(zen_href_link(FILENAME_MEDIA_MANAGER, 'action=products&current_category_id=' . $current_category_id) . '&mID=' . (int)$_POST['mID'] . '&page=' . $_GET['page']);

      break;
      case 'add_product':
        $product_add_query = $db->Execute("INSERT INTO " . TABLE_MEDIA_TO_PRODUCTS . " (media_id, product_id) values
                                           ('" . (int)$_POST['mID'] . "', '" . (int)$_POST['current_product_id'] . "')");
         zen_redirect(zen_href_link(FILENAME_MEDIA_MANAGER, 'action=products') . '&mID=' . $_POST['mID'] . '&page=' . $_GET['page']);

      break;
      case 'new_cat':
        $current_category_id = (isset($_GET['current_category_id']) ? (int)$_GET['current_category_id'] : (int)$current_category_id);
        zen_redirect(zen_href_link(FILENAME_MEDIA_MANAGER, 'action=products&current_category_id=' . $current_category_id . (isset($_GET['mID']) ? '&mID=' . $_GET['mID'] : '') . '&page=' . $_GET['page']));
      break;
      case 'remove_clip':
        if (isset($_POST['mID']) && isset($_POST['clip_id']))
        {
          $delete_query = "DELETE FROM " . TABLE_MEDIA_CLIPS . " WHERE clip_id  = '" . (int)$_POST['clip_id'] . "'";
          $db->Execute($delete_query);
          zen_redirect(zen_href_link(FILENAME_MEDIA_MANAGER, 'action=edit&page=' . $_GET['page'] . '&mID=' . $_POST['mID']));
        }
      break;
      case 'insert':
      case 'save':
        if (isset($_POST['add_clip'])) {
          $clip_name = $_FILES['clip_filename'];
          $clip_name = zen_db_prepare_input($clip_name['name']);
          if ($clip_name) {
            $media_type = zen_db_prepare_input($_POST['media_type']);
            $ext = $db->Execute("SELECT type_ext FROM " . TABLE_MEDIA_TYPES . " WHERE type_id = '" . (int)$_POST['media_type'] . "'");
            if (preg_match('/'.$ext->fields['type_ext'] . '/', $clip_name)) {

              if ($media_upload = new upload('clip_filename')) {
                $media_upload->set_extensions(array('mp3','mp4','swf','avi','mpg','wma','rm','ra','ram','wmv','epub','flv','ogg','m4v','m4a','webm'));
                $media_upload->set_destination(DIR_FS_CATALOG_MEDIA . $_POST['media_dir']);
                if ($media_upload->parse() && $media_upload->save()) {
                  $media_upload_filename = zen_db_prepare_input($_POST['media_dir'] . $media_upload->filename);
                }
                if ($media_upload->filename != 'none' && $media_upload->filename != '' && is_writable(DIR_FS_CATALOG_MEDIA . $_POST['media_dir'])) {

                  $db->Execute("insert into " . TABLE_MEDIA_CLIPS . "
                                (media_id, clip_type, clip_filename, date_added) values (
                                 '" . (int)$_GET['mID'] . "',
                                 '" . zen_db_input($media_type) . "',
                                 '" . zen_db_input($media_upload_filename) . "', now())");
                }
              }

            }
          }
        }
        if (isset($_GET['mID'])) $media_id = zen_db_prepare_input($_GET['mID']);
        $media_name = zen_db_prepare_input($_POST['media_name']);

        $sql_data_array = array('media_name' => $media_name);

        if ($media_name == '') {
          $messageStack->add_session(ERROR_UNKNOWN_DATA, 'caution');
        } else {
          if ($action == 'insert') {
            $insert_sql_data = array('date_added' => 'now()');

            $sql_data_array = array_merge($sql_data_array, $insert_sql_data);

            zen_db_perform(TABLE_MEDIA_MANAGER, $sql_data_array);
            $media_id = zen_db_insert_id();
          } elseif ($action == 'save') {
            $update_sql_data = array('last_modified' => 'now()');

            $sql_data_array = array_merge($sql_data_array, $update_sql_data);

            zen_db_perform(TABLE_MEDIA_MANAGER, $sql_data_array, 'update', "media_id = '" . (int)$media_id . "'");
          }
        }

        zen_redirect(zen_href_link(FILENAME_MEDIA_MANAGER, (isset($_GET['page']) ? 'page=' . $_GET['page'] . '&' : '') . ($media_id != '' ? 'mID=' . $media_id : '')));
        break;
      case 'deleteconfirm':
        $media_id = zen_db_prepare_input($_POST['mID']);

        $db->Execute("DELETE FROM " . TABLE_MEDIA_MANAGER . "
                      WHERE media_id = '" . (int)$media_id . "'");
        $db->Execute("DELETE FROM " . TABLE_MEDIA_TO_PRODUCTS . "
                      WHERE media_id = '" . (int)$media_id . "'");
        $db->Execute("DELETE FROM " . TABLE_MEDIA_CLIPS . "
                      WHERE media_id = '" . (int)$media_id . "'");

        if (isset($_POST['delete_products']) && ($_POST['delete_products'] == 'on')) {

//          while (!$products->EOF) {
//            zen_remove_product($products->fields['products_id']);
//            $products->MoveNext();
//          }
        }

        zen_redirect(zen_href_link(FILENAME_MEDIA_MANAGER, 'page=' . $_GET['page']));
        break;
    }
  }
?>
<!doctype html>
<html <?php echo HTML_PARAMS; ?>>
<head>
    <?php require DIR_WS_INCLUDES . 'admin_html_head.php'; ?>
</head>
<body>
<!-- header //-->
<?php require(DIR_WS_INCLUDES . 'header.php'); ?>
<!-- header_eof //-->

<!-- body //-->
<table class="col-sm-12" border="0" cellspacing="2" cellpadding="2">
  <tr>
<!-- body_text //-->
    <td class="align-top col-sm-12" ><table class="col-sm-12" border="0" cellspacing="0" cellpadding="2">
      <tr>
        <td class="col-sm-12"><table class="col-sm-12" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td class="pageHeading"><?php echo HEADING_TITLE_MEDIA_MANAGER; ?></td>
            <td class="pageHeading text-right"><?php echo zen_draw_separator('pixel_trans.gif', HEADING_IMAGE_WIDTH, HEADING_IMAGE_HEIGHT); ?></td>
          </tr>
        </table></td>
      </tr>
      <tr>
        <td><table class="col-sm-12" border="0" cellspacing="0" cellpadding="0">
          <tr>
            <td class="align-top"><table class="col-sm-12" border="0" cellspacing="0" cellpadding="2">
              <tr class="dataTableHeadingRow">
                <td class="dataTableHeadingContent"><?php echo TABLE_HEADING_MEDIA; ?></td>
                <td class="dataTableHeadingContent text-right"><?php echo TABLE_HEADING_ACTION; ?>&nbsp;</td>
              </tr>
<?php
  $media_query_raw = "SELECT * FROM " . TABLE_MEDIA_MANAGER . " ORDER BY media_name";
  $media_split = new splitPageResults($_GET['page'], MAX_DISPLAY_SEARCH_RESULTS, $media_query_raw, $media_query_numrows);
  $media = $db->Execute($media_query_raw);
  $mManager_parameter = '';
  while (!$media->EOF) {
    if ((!isset($_GET['mID']) || (isset($_GET['mID']) && ($_GET['mID'] == $media->fields['media_id']))) && !isset($mInfo) && (substr($action, 0, 3) != 'new')) {

      $mInfo = new objectInfo($media->fields);
	  $mManager_parameter = '&mID=' . $mInfo->media_id;
    }

    if (isset($mInfo) && is_object($mInfo) && ($media->fields['media_id'] == $mInfo->media_id)) {
      echo '              <tr id="defaultSelected" class="dataTableRowSelected" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . zen_href_link(FILENAME_MEDIA_MANAGER, 'page=' . $_GET['page'] . '&mID=' . $media->fields['media_id']) . '\'">' . "\n";
    } else {
      echo '              <tr class="dataTableRow" onmouseover="rowOverEffect(this)" onmouseout="rowOutEffect(this)" onclick="document.location.href=\'' . zen_href_link(FILENAME_MEDIA_MANAGER, 'page=' . $_GET['page'] . '&mID=' . $media->fields['media_id']) . '\'">' . "\n";
    }
?>
                <td class="dataTableContent"><?php echo $media->fields['media_name']; ?></td>
                <td class="dataTableContent text-right">
                  <?php echo '<a href="' . zen_href_link(FILENAME_MEDIA_MANAGER, 'page=' . $_GET['page'] . '&mID=' . $media->fields['media_id'] . '&action=edit') . '">' . zen_image(DIR_WS_IMAGES . 'icon_edit.gif', ICON_EDIT) . '</a>'; ?>
                  <?php echo '<a href="' . zen_href_link(FILENAME_MEDIA_MANAGER, 'page=' . $_GET['page'] . '&mID=' . $media->fields['media_id'] . '&action=delete') . '">' . zen_image(DIR_WS_IMAGES . 'icon_delete.gif', ICON_DELETE) . '</a>'; ?>
                  <?php if (isset($mInfo) && is_object($mInfo) && ($media->fields['media_id'] == $mInfo->media_id)) { echo zen_image(DIR_WS_IMAGES . 'icon_arrow_right.gif', ''); } else { echo '<a href="' . zen_href_link(FILENAME_MEDIA_MANAGER, zen_get_all_get_params(array('mID')) . 'mID=' . $media->fields['media_id']) . '">' . zen_image(DIR_WS_IMAGES . 'icon_info.gif', IMAGE_ICON_INFO) . '</a>'; } ?>
                </td>
              </tr>
<?php
    $media->MoveNext();
  }
?>
              <tr>
                <td colspan="2"><table class="col-sm-12" border="0" cellspacing="0" cellpadding="2">
                  <tr>
                    <td class="smallText align-top"><?php echo $media_split->display_count($media_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, $_GET['page'], TEXT_DISPLAY_NUMBER_OF_MEDIA); ?></td>
                    <td class="smallText text-right"><?php echo $media_split->display_links($media_query_numrows, MAX_DISPLAY_SEARCH_RESULTS, MAX_DISPLAY_PAGE_LINKS, $_GET['page']); ?></td>
                  </tr>
                </table></td>
              </tr>
<?php
  if (empty($action)) {
?>
              <tr>
                <td align="right" colspan="2" class="smallText"><?php echo '<a href="' . zen_href_link(FILENAME_MEDIA_MANAGER, 'page=' . $_GET['page'] . $mManager_parameter . '&action=new') . '" class="btn btn-primary" role="button">' . IMAGE_INSERT . '</a>'; ?></td>
              </tr>
<?php
  }
?>
            </table></td>
<?php
  $heading = array();
  $contents = array();

  switch ($action) {
    case 'new':
      $heading[] = array('text' => '<strong>' . TEXT_HEADING_NEW_MEDIA_COLLECTION . '</strong>');

      $contents[] = array('text' => zen_draw_form('collections', FILENAME_MEDIA_MANAGER, 'action=insert&page=' . $_GET['page'], 'post', 'enctype="multipart/form-data"'));
      $contents[] = array('text' => TEXT_NEW_INTRO);
      $contents[] = array('text' => '<br>' . TEXT_MEDIA_COLLECTION_NAME . '<br>' . zen_draw_input_field('media_name', '', zen_set_field_length(TABLE_MEDIA_MANAGER, 'media_name')));

      $contents[] = array('align' => 'center', 'text' => '<br>' . '<button type="submit" class="btn btn-primary">' . IMAGE_SAVE . '</button>' . ' <a href="' . zen_href_link(FILENAME_MEDIA_MANAGER, 'page=' . $_GET['page'] . (isset($_GET['mID']) ? '&mID=' . $_GET['mID'] : '')) . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>');
      break;
    case 'edit':
      $heading[] = array('text' => '<strong>' . TEXT_HEADING_EDIT_MEDIA_COLLECTION . '</strong>');

      $contents[] = array('text' => zen_draw_form('collections', FILENAME_MEDIA_MANAGER, 'page=' . $_GET['page'] . $mManager_parameter . '&action=save', 'post', 'enctype="multipart/form-data"'));
      $contents[] = array('text' => TEXT_INFO_EDIT_INTRO);
      $contents[] = array('text' => '<br>' . TEXT_MEDIA_COLLECTION_NAME . '<br>' . zen_draw_input_field('media_name', htmlspecialchars($mInfo->media_name, ENT_COMPAT, CHARSET, TRUE), zen_set_field_length(TABLE_MEDIA_MANAGER, 'media_name')));
      $contents[] = array('align' => 'center', 'text' => '<br>' . '<button type="submit" class="btn btn-primary">' . IMAGE_SAVE . '</button>' . ' <a href="' . zen_href_link(FILENAME_MEDIA_MANAGER, 'page=' . $_GET['page'] . $mManager_parameter) . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>');

      $contents[] = array('text' => zen_draw_separator('pixel_black.gif'));
      $contents[] = array('text' => TEXT_MEDIA_EDIT_INSTRUCTIONS);
      $contents[] = array('text' => zen_draw_separator('pixel_black.gif'));

      $dir_info = zen_build_subdirectories_array(DIR_FS_CATALOG_MEDIA);
      $contents[] = array('text' => '<br>' . TEXT_ADD_MEDIA_CLIP . zen_draw_file_field('clip_filename'));
      $contents[] = array('text' => TEXT_MEDIA_CLIP_DIR . ' ' . zen_draw_pull_down_menu('media_dir', $dir_info));
      $media_type_query = "SELECT type_id, type_name, type_ext FROM " . TABLE_MEDIA_TYPES;
      $media_types = $db->Execute($media_type_query);
      while (!$media_types->EOF) {
        $media_types_array[] = array('id' => $media_types->fields['type_id'], 'text' => $media_types->fields['type_name'] . ' (' . $media_types->fields['type_ext'] . ')');
        $media_types->MoveNext();
      }
      $contents[] = array('text' => TEXT_MEDIA_CLIP_TYPE . ' ' . zen_draw_pull_down_menu('media_type', $media_types_array));

      $contents[] = array('text' => '<input type="submit" name="add_clip" value="' . TEXT_ADD . '">', 'align' => 'center');
      $contents[] = array('text' => '</form>');
      $clip_query = "SELECT * FROM " . TABLE_MEDIA_CLIPS . " WHERE media_id = '" . $mInfo->media_id . "'";
      $clips = $db->Execute($clip_query);
      if ($clips->RecordCount() > 0) $contents[] = array('text' => '<hr>');
      while (!$clips->EOF) {
        $contents[] = array('text'=>zen_draw_form('delete_clip', FILENAME_MEDIA_MANAGER, 'action=remove_clip') . '<input type="hidden" name="mID" value="' . $mInfo->media_id . '">' . '<input type="hidden" name="clip_id" value="' . $clips->fields['clip_id'] . '">' . '<button type="submit" class="btn btn-danger">' . IMAGE_DELETE . '</button>'. '&nbsp;' . $clips->fields['clip_filename'] . '<br>' . '</form>');
        $clips->MoveNext();
      }
      break;
    case 'delete':
      $heading[] = array('text' => '<strong>' . TEXT_HEADING_DELETE_MEDIA_COLLECTION . '</strong>');

      $contents = array('form' => zen_draw_form('collections', FILENAME_MEDIA_MANAGER, 'page=' . $_GET['page'] . '&action=deleteconfirm') . zen_draw_hidden_field('mID', $mInfo->media_id));
      $contents[] = array('text' => TEXT_DELETE_INTRO);
      $contents[] = array('text' => '<br><strong>' . $mInfo->media_name . '</strong>');

      if ($mInfo->products_count > 0) {
        $contents[] = array('text' => '<br>' . zen_draw_checkbox_field('delete_products') . ' ' . TEXT_DELETE_PRODUCTS);
        $contents[] = array('text' => '<br>' . sprintf(TEXT_DELETE_WARNING_PRODUCTS, $mInfo->products_count));
      }

      $contents[] = array('align' => 'center', 'text' => '<br>' . '<button type="submit" class="btn btn-danger">' . IMAGE_DELETE . '</button>'. ' <a href="' . zen_href_link(FILENAME_MEDIA_MANAGER, 'page=' . $_GET['page'] . $mManager_parameter) . '" class="btn btn-warning" role="button">' . IMAGE_CANCEL . '</a>');
      break;
    case 'products':
      $heading[] = array('text' => '<strong>' . TEXT_HEADING_ASSIGN_MEDIA_COLLECTION . '</strong>');
      $contents[] = array('text' => TEXT_PRODUCTS_INTRO . '<br><br>');
      $contents[] = array('text' => zen_draw_form('new_category', FILENAME_MEDIA_MANAGER, '', 'get') . '&nbsp;&nbsp;' .
                           zen_draw_pull_down_menu('current_category_id', zen_get_category_tree('', '', '0'), (isset($current_category_id)? $current_category_id : ''), 'onChange="this.form.submit();"') . zen_hide_session_id() . zen_draw_hidden_field('products_filter', $_GET['products_filter']) . zen_draw_hidden_field('action', 'new_cat') . zen_draw_hidden_field('mID', $mInfo->media_id) . zen_draw_hidden_field('page', $_GET['page']) . '&nbsp;&nbsp;</form>');
      // product_array should be products in this category that do not already 
      // have a media_to_products entry for this media id. 
     $products_query = "SELECT ptc.*, pd.products_name
                            FROM " . TABLE_PRODUCTS_TO_CATEGORIES . " ptc
                            LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd
                            ON ptc.products_id = pd.products_id
                            AND pd.language_id = '" . (int)$_SESSION['languages_id'] . "'
                            WHERE ptc.categories_id='" . (int)$current_category_id. "'
                            AND NOT EXISTS (SELECT pmp.product_id FROM " . TABLE_MEDIA_TO_PRODUCTS . " pmp WHERE pmp.product_id = pd.products_id) 
                            ORDER BY pd.products_name";
      $product_list = $db->Execute($products_query);
      $products_array = []; 
      foreach ($product_list as $product) {
         $product_array[] = array(
            'id' => $product['products_id'],
            'text' => $product['products_name']
         );
      }
      if ($product_array) {
        $contents[] = array('text' => zen_draw_form('new_product', FILENAME_MEDIA_MANAGER, 'action=add_product&page=' . (isset($GET['page']) ? $_GET['page'] : ''), 'post') . '&nbsp;&nbsp;' .
                           zen_draw_pull_down_menu('current_product_id', $product_array) . '&nbsp;' . '<input type="submit" name="add_product" value="Add">' .
                           zen_draw_hidden_field('current_category_id', $current_category_id) .
                           zen_draw_hidden_field('mID', $mInfo->media_id) . '&nbsp;&nbsp;</form>');
      } else {
        $contents[] = array('text' => '&nbsp;&nbsp;' . TEXT_NO_PRODUCTS);
      }
      $products_linked_query = "SELECT * FROM " . TABLE_MEDIA_TO_PRODUCTS . "
                                WHERE media_id = '" . $mInfo->media_id . "'";
      $products_linked = $db->Execute($products_linked_query);
      if ($products_linked->RecordCount() > 0) $contents[] = array('text' => '<hr>');
      while (!$products_linked->EOF) {
        $contents[] = array('text'=>zen_draw_form('remove_product', FILENAME_MEDIA_MANAGER, 'action=remove_product&page=' . $_GET['page']) . '<input type="hidden" name="mID" value="' . $mInfo->media_id . '">' . '<input type="hidden" name="product_id" value="' . $products_linked->fields['product_id'] . '">' . '<button type="submit" class="btn btn-danger">' . IMAGE_DELETE . '</button>'. '&nbsp;' . $zc_products->products_name($products_linked->fields['product_id']) . '<br>' . '</form>');
        $products_linked->MoveNext();
      }
      $contents[] = array('align' => 'center', 'text' =>  '<br><a href="' . zen_href_link(FILENAME_MEDIA_MANAGER, 'page=' . $_GET['page'] . $mManager_parameter) . '" class="btn btn-default" role="button">' . IMAGE_CANCEL . '</a>');
      break;
    default:
      if (isset($mInfo) && is_object($mInfo)) {
        $heading[] = array('text' => '<strong>' . $mInfo->media_name . '</strong>');

        $contents[] = array('align' => 'center', 'text' => '<a href="' . zen_href_link(FILENAME_MEDIA_MANAGER, 'page=' . $_GET['page'] . $mManager_parameter . '&action=edit') . '" class="btn btn-primary" role="button">' . IMAGE_EDIT . '</a> <a href="' . zen_href_link(FILENAME_MEDIA_MANAGER, 'page=' . $_GET['page'] . $mManager_parameter . '&action=delete') . '" class="btn btn-danger" role="button">' . IMAGE_DELETE . '</a> ' . '<a href="' . zen_href_link(FILENAME_MEDIA_MANAGER, 'page=' . $_GET['page'] . $mManager_parameter . '&action=products') . '" class="btn btn-primary" role="button">' . IMAGE_PRODUCTS . '</a>');
        $contents[] = array('text' => '<br>' . TEXT_INFO_DATE_ADDED . ' ' . zen_date_short($mInfo->date_added));
        if (zen_not_null($mInfo->last_modified)) $contents[] = array('text' => TEXT_INFO_LAST_MODIFIED . ' ' . zen_date_short($mInfo->last_modified));
        $products_linked_query = "SELECT product_id FROM " . TABLE_MEDIA_TO_PRODUCTS . "
                                WHERE media_id = '" . $mInfo->media_id . "'";
        $products_linked = $db->Execute($products_linked_query);
        $contents[] = array('text' => '<br>' . TEXT_LINKED_PRODUCTS . ' ' . $products_linked->RecordCount());
        $clip_query = "SELECT clip_id FROM " . TABLE_MEDIA_CLIPS . " WHERE media_id = '" . $mInfo->media_id . "'";
        $clips = $db->Execute($clip_query);
        $contents[] = array('text' =>  TEXT_CLIPS . ' ' . $clips->RecordCount());
      }
      break;
  }

  if (!empty($heading) && !empty($contents)) {
    echo '            <td class="align-top col-sm-3">' . "\n";

    $box = new box;
    echo $box->infoBox($heading, $contents);

    echo '            </td>' . "\n";
  }
?>
          </tr>
        </table></td>
      </tr>
    </table></td>
  </tr>
</table>
<!-- body_eof //-->

<!-- footer //-->
<?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
<!-- footer_eof //-->
<br>
</body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>