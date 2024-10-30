<?php
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo $gateway->get_title(); ?></h1><a class="page-title-action"
                                                                              href="<?php echo admin_url('admin.php?page=' . $this->plugin_name); ?>"><?php _e('Back', $this->plugin_text_domain); ?></a>
    <hr class="wp-header-end">
    <div class="mgw-div-col-10">
        <form id="mgw-gateway-settings" method="post" action="admin-post.php">
            <table>
                <tbody>
                <?php
                $i = 0;
                foreach ($fields as $field_id => $field):
                    $i++;
                    switch ($field['type']):
                        case 'p':
                            echo '<tr>';
                            echo '<td colspan="2" style="padding: 6px 0;">';
                            echo '<p id="elem-' . $i . '" class="mgw-p-fields-' . $field['size'] . '">' . $field['title'] . '</p>';
                            echo '</td>';
                            echo '</tr>';
                            break;
                        case 'text':
                            echo '<tr>';
                            echo '<td style="padding: 6px 0;">';
                            echo '<label for="elem-' . $i . '"><strong>' . $field['title'] . '</strong></label>';
                            if ($field['desc_tip']):
                                echo '<span class="mgw-tooltip" style="' . ((is_rtl()) ? 'float: left' : 'float: right') . '" data-tooltip="' . $field['description'] . '"><img src="' . plugins_url($this->plugin_name) . '/assets/images/info.png"></span>';
                            endif;
                            echo '</td>';
                            echo '<td style="padding: 6px 0;">';
                            echo '<input type="text" id="elem-' . $i . '" name="' . $field_id . '" value="' . $gateway->get_setting($field_id) . '" ' . ((isset($field['valid_value']))?'pattern="' . $field['valid_value'] . '" ':'') . ((isset($field['example_value']))?'title="' . $field['example_value'] . '" ':'') . '>';
                            echo '</td>';
                            echo '</tr>';
                            break;
                        case 'textarea':
                            echo '<tr>';
                            echo '<td style="padding: 6px 0;">';
                            echo '<label for="elem-' . $i . '"><strong>' . $field['title'] . '</strong></label>';
                            if ($field['desc_tip']):
                                echo '<span class="mgw-tooltip" style="' . ((is_rtl()) ? 'float: left' : 'float: right') . '" data-tooltip="' . $field['description'] . '"><img src="' . plugins_url($this->plugin_name) . '/assets/images/info.png"></span>';
                            endif;
                            echo '</td>';
                            echo '<td style="padding: 6px 0;">';
                            echo '<textarea id="elem-' . $i . '" name="' . $field_id . '" rows="4" cols="50">' . $gateway->get_setting($field_id) . '</textarea>';
                            echo '</td>';
                            echo '</tr>';
                            break;
                        case 'checkbox':
                            echo '<tr>';
                            echo '<td style="padding: 6px 0;">';
                            echo '<label for="elem-' . $i . '"><strong>' . $field['title'] . '</strong></label>';
                            if ($field['desc_tip']):
                                echo '<span class="mgw-tooltip" style="' . ((is_rtl()) ? 'float: left' : 'float: right') . '" data-tooltip="' . $field['description'] . '"><img src="' . plugins_url($this->plugin_name) . '/assets/images/info.png"></span>';
                            endif;
                            echo '</td>';
                            echo '<td style="padding: 6px 0;">';
                            echo '<input type="hidden" name="' . $field_id . '" value="0"/>';
                            echo '<input class="mgw-tgl ' . ((is_rtl()) ? 'mgw-tgl-rtl' : 'mgw-tgl-ltr') . '" id="elem-' . $i . '" type="checkbox" name="' . $field_id . '" ' . (true === ($gateway->get_setting($field_id)) ? 'checked="checked"' : '') . '>';
                            echo '<label class="mgw-tgl-btn" for="elem-' . $i . '" ' . (($gateway->get_setting($field_id) === true) ? ('title="' . __('Enabled', $this->plugin_text_domain) . '" toggled-title="' . __('Disabled', $this->plugin_text_domain) . '"') : ('title="' . __('Disabled', $this->plugin_text_domain) . '" toggled-title="' . __('Enabled', $this->plugin_text_domain) . '"')) . '></label>';
                            echo '</td>';
                            echo '</tr>';
                            break;
                    endswitch;
                    ?>
                <?php
                endforeach;
                ?>
                <tr>
                    <td style="padding-top: 20px"></td>
                    <td style="padding-top: 20px">
                        <input type="submit" class="button-primary"
                               value="<?php _e('Save Settings', $this->plugin_text_domain); ?>">
                        <a class="button-primary" href="<?php echo admin_url('admin.php?page=' . $this->plugin_name); ?>" style="margin: auto 5px">
                            <?php _e('Cancel', $this->plugin_text_domain); ?>
                        </a>
                        <input type="hidden" name="action"
                               value="<?php echo $this->plugin_text_domain; ?>_save_settings">
                        <input type="hidden" name="gateway_id" value="<?php echo $gateway->id; ?>">
                    </td>
                </tr>
                </tbody>
            </table>
        </form>
    </div>
</div>