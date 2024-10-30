<?php
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Gateway Settings', $this->plugin_text_domain); ?></h1>
    <hr class="wp-header-end">
    <div class="mgw-div-col-10">
        <div class="mgw-set mgw-tutorial">
            <a href="#">
                <?php _e('Tutorial', $this->plugin_text_domain); ?>
                <span class="dashicons dashicons-info"
                      style="<?php echo((is_rtl()) ? 'float: left;' : 'float: right;') ?>"></span>
            </a>
            <div class="mgw-content">
                <p>
                    <strong><?php _e('Instructions', $this->plugin_text_domain); ?>:</strong>
                    <br>
                    <?php _e('1- Manage and activate desired gateways.', $this->plugin_text_domain); ?>
                    <br>
                    <?php _e("2- Put this shortcode in your post or page: ", $this->plugin_text_domain); ?>
                    <br>
                    <span style="<?php echo((is_rtl()) ? 'direction: ltr; float: left;' : ''); ?>">
                        <strong><?php _e("[payment_form price='PaymentPrice' currency='CurrencyCode']", $this->plugin_text_domain); ?></strong>
                    </span>
                    <br>
                    <sub>
                        <?php _e("* PaymentPrice is digit and CurrencyCode is Standard Currency Code (stated in gateway settings)", $this->plugin_text_domain); ?>
                    </sub>
                    <br>
                </p>
            </div>
        </div>
        <table class="mgw-table">
            <thead class="mgw-thead" style="text-align: <?php echo((is_rtl()) ? 'right' : 'left'); ?>;">
            <tr>
                <th class="mgw-sort"></th>
                <th class="mgw-name"><?php _e('Method', $this->plugin_text_domain); ?></th>
                <th class="mgw-status"><?php _e('Status', $this->plugin_text_domain); ?></th>
                <th class="mgw-description"><?php _e('Description', $this->plugin_text_domain); ?></th>
                <th class="mgw-action"></th>
            </tr>
            </thead>
            <tbody id="mgw-gateway-list" class="mgw-tbody"
                   style="text-align: <?php echo((is_rtl()) ? 'right' : 'left'); ?>;">
            <?php
            $i = 0;
            foreach ($gateway_order as $gateway_id):
                ?>
                <tr class="<?php echo(($i++ % 2 == 0) ? 'mgw-tr-even' : 'mgw-tr-odd'); ?>">
                    <td class="mgw-sort">
                        <a class="mgw-arrow-button-up dashicons dashicons-arrow-up-alt"
                           style="font-family: dashicons, serif !important;">
                            <a class="mgw-arrow-button-down dashicons dashicons-arrow-down-alt"
                               style="font-family: dashicons, serif !important;">
                    </td>
                    <td class="mgw-name"><?php echo $gateways[$gateway_id]->get_title(); ?></td>
                    <td class="mgw-status">
                        <input class="mgw-tgl <?php echo (is_rtl()) ? 'mgw-tgl-rtl' : 'mgw-tgl-ltr' ?>"
                               id="cb<?php echo $gateway_id; ?>"
                               type="checkbox" <?php echo ($gateways[$gateway_id]->get_setting('status') === true) ? 'checked="checked"' : ''; ?>/>
                        <label class="mgw-tgl-btn" for="cb<?php echo $gateway_id; ?>"
                               data-id="<?php echo $gateway_id; ?>" data-nonce="<?php echo wp_create_nonce($this->plugin_text_domain . '_' . $gateway_id); ?>" <?php echo ($gateways[$gateway_id]->get_setting('status') === true) ? 'title="' . __('Enabled', $this->plugin_text_domain) . '" toggled-title="' . __('Disabled', $this->plugin_text_domain) . '"' : 'title="' . __('Disabled', $this->plugin_text_domain) . '" toggled-title="' . __('Enabled', $this->plugin_text_domain) . '"'; ?> ></label>
                    </td>
                    <td class="mgw-description"><?php echo $gateways[$gateway_id]->get_description(); ?></td>
                    <td class="mgw-action"><a class="button"
                                              href="<?php echo add_query_arg(array('page' => $this->plugin_name, 'view' => $gateway_id, 'nonce' => wp_create_nonce($this->plugin_text_domain . '_' . $gateway_id)), admin_url('admin.php')); ?>"><?php _e('Manage', $this->plugin_text_domain); ?></a>
                    </td>
                </tr>
            <?php
            endforeach;
            ?>
            </tbody>
        </table>
    </div>
    <div class="mgw-div-col-2"></div>
</div>