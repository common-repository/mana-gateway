<?php
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Payment Log', $this->plugin_text_domain); ?></h1>
    <hr class="wp-header-end">
    <div id="mana-list-itm">
        <div id="mana-post-body">
            <form id="mana-item-list-form" method="get">
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
                <?php
                $this->log_table->search_box(__('Find', $this->plugin_text_domain), 'mana-log-find');
                ?>
                <div style="position: relative; top: 30px;">
                    <label style="<?php echo((is_rtl()) ? 'margin-left: 20px;margin-right: 10px;' : 'margin-right: 20px; margin-left: 10px;'); ?>">
                        <strong>
                            <?php _e('Display Only', $this->plugin_text_domain); ?>:
                        </strong>
                    </label>
                    <input type="radio" class="display-only" name="status_filter" id="status_all"
                           value="all">
                    <label for="status_all"
                           style="<?php echo((is_rtl()) ? 'margin-left: 20px;' : 'margin-right: 20px;'); ?>"><?php _e('All', $this->plugin_text_domain); ?></label>
                    <input type="radio" class="display-only" name="status_filter" id="status_success"
                           value="success">
                    <label for="status_success"
                           style="<?php echo((is_rtl()) ? 'margin-left: 20px;' : 'margin-right: 20px;'); ?>"><?php _e('Success', $this->plugin_text_domain); ?>
                    </label>
                    <input type="radio" class="display-only" name="status_filter" id="status_error"
                           value="error">
                    <label for="status_error"><?php _e('Failed', $this->plugin_text_domain); ?></label>
                </div>
                <?php
                $this->log_table->display();
                ?>
            </form>
        </div>
    </div>
</div>
